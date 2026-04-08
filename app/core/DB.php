<?php

// app/core/DB.php — Query Builder completo, otimizado e seguro
#[\AllowDynamicProperties]
class DB
{
    private PDO $pdo;

    // Partes da query de SELECT
    private string $select = '*';
    private ?string $from = null;
    private array $joins = [];
    private array $wheres = [];
    private ?string $order = null;
    private ?int $limit = null;
    private ?int $offset = null;
    private array $rawSets = [];

    // Controle interno
    private int $paramCounter = 0;
    private ?PDOStatement $lastStmt = null;
    private string $driverName;
    private string $quoteChar;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // Otimização de Cache: Obter o driver apenas uma vez no instanciamento
        $this->driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->quoteChar = $this->driverName === 'mysql' ? '`' : '"';
    }

    /* ----------------------------------------------------------------------
     * Helpers de Identificadores (Segurança contra SQL Injection em schema/coluna)
     * -------------------------------------------------------------------- */

    private function driver(): string
    {
        return $this->driverName;
    }

    private function isValidName(string $name): bool
    {
        // Valida nome simples (ex: users, email, _id1)
        return (bool)preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name);
    }

    private function quoteOne(string $name): string
    {
        if (!$this->isValidName($name)) {
            throw new InvalidArgumentException("Invalid identifier part: $name");
        }
        // Otimização: Usar char armazenado em cache direto em vez de função + ternário
        return $this->quoteChar . $name . $this->quoteChar;
    }

    /**
     * Define um valor SET cru que não será tratado como placeholder.
     * Ex: ->setRaw('balance', 'balance - 1')
     */
    public function setRaw(string $column, string $expression): self
    {
        $colQ = $this->quoteOne($column);
        $this->rawSets[] = "$colQ = $expression";
        return $this;
    }

    // Aceita schema.table (ex: public.users) e aplica quoting em cada parte
    private function quoteQualified(string $qualified): string
    {
        $parts = explode('.', $qualified);
        if (count($parts) > 2) {
            throw new InvalidArgumentException("Too many parts in identifier: $qualified");
        }
        return implode('.', array_map(fn($p) => $this->quoteOne($p), $parts));
    }

    private function quoteIdent(string $ident): string
    {
        // Trata "table alias" ou "table AS alias" para quoting (case-insensitive com /i)
        $ident = trim($ident);
        $parts = preg_split('/\s+AS\s+|\s+/i', $ident, 2);

        $name = $parts[0] ?? '';
        $alias = $parts[1] ?? null;
        
        $q = $this->quoteQualified($name);
        
        if ($alias !== null) {
            if (!$this->isValidName($alias)) {
                throw new InvalidArgumentException("Invalid alias: $alias");
            }
            return "$q AS " . $this->quoteOne($alias);
        }
        return $q;
    }
    
    /* ----------------------------------------------------------------------
     * Helpers de Parâmetros e Limpeza
     * -------------------------------------------------------------------- */

    private function newParam($value): string
    {
        // Cria um novo placeholder único
        $name = ':p' . $this->paramCounter++;
        return $name;
    }

    private function addWhere(string $sql, array $params): void
    {
        // Armazena a cláusula WHERE e seus respectivos parâmetros
        $this->wheres[] = ['sql' => $sql, 'params' => $params];
    }

    private function reset(): void
    {
        // Limpa todas as partes para uma nova query
        $this->select = '*';
        $this->from = null;
        $this->joins = [];
        $this->wheres = [];
        $this->order = null;
        $this->limit = null;
        $this->offset = null;
        $this->rawSets = [];
        $this->paramCounter = 0;
    }

    /* ----------------------------------------------------------------------
     * Construção de SELECT
     * -------------------------------------------------------------------- */

    public function select(string $fields): self
    {
        $items = array_map('trim', explode(',', $fields));
        $quoted = [];

        foreach ($items as $it) {
            if ($it === '*') {
                $quoted[] = '*';
                continue;
            }
            $quoted[] = $this->quoteIdent($it);
        }
        $this->select = implode(', ', $quoted);
        return $this;
    }

    public function from(string $table): self
    {
        $this->from = $this->quoteIdent($table);
        return $this;
    }

    // JOIN seguro por colunas (sem string crua)
    public function join(string $table, string $leftColumn, string $op, string $rightColumn, string $type = 'INNER'): self
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'])) $type = 'INNER';

        $tableQ = $this->quoteIdent($table);
        $leftQ = $this->quoteQualified($leftColumn);
        $rightQ = $this->quoteQualified($rightColumn);

        $allowedOps = ['=', '<>', '!=', '>', '>=', '<', '<='];
        if (!in_array($op, $allowedOps, true)) {
            throw new InvalidArgumentException("Unsupported join operator: $op");
        }

        $this->joins[] = "$type JOIN $tableQ ON $leftQ $op $rightQ";
        return $this;
    }

    // JOIN Raw
    public function joinRaw(string $sql): self
    {
        $this->joins[] = $sql;
        return $this;
    }

    /* ----------------------------------------------------------------------
     * WHERE (seguras) + variações
     * -------------------------------------------------------------------- */
     
    // where(['u.id'=>1, 'u.status'=>'active'])
    public function where(array $conditions): self
    {
        foreach ($conditions as $col => $val) {
            $colQ = $this->quoteQualified($col);
            $p = $this->newParam($val);
            $this->addWhere("$colQ = $p", [$p => $val]);
        }
        return $this;
    }

    // whereOp('u.id','>','10')
    public function whereOp(string $col, string $op, $value): self
    {
        $allowed = ['=', '<>', '!=', '>', '>=', '<', '<='];
        if (!in_array($op, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported operator: $op");
        }
        $colQ = $this->quoteQualified($col);
        $p = $this->newParam($value);
        $this->addWhere("$colQ $op $p", [$p => $value]);
        return $this;
    }

    public function whereIn(string $col, array $values, bool $not = false): self
    {
        if (empty($values)) {
            $this->addWhere($not ? '1=1' : '1=0', []);
            return $this;
        }
        $colQ = $this->quoteQualified($col);
        $placeholders = [];
        $params = [];
        foreach ($values as $v) {
            $p = $this->newParam($v);
            $placeholders[] = $p;
            $params[$p] = $v;
        }
        $notStr = $not ? ' NOT' : '';
        $this->addWhere("$colQ$notStr IN (" . implode(',', $placeholders) . ")", $params);
        return $this;
    }

    // whereLike('u.email', '%@gmail.com')
    public function whereLike(string $col, string $pattern, bool $not = false): self
    {
        $colQ = $this->quoteQualified($col);
        $p = $this->newParam($pattern);
        $this->addWhere($not ? "$colQ NOT LIKE $p" : "$colQ LIKE $p", [$p => $pattern]);
        return $this;
    }

    public function whereBetween(string $col, $start, $end, bool $not = false): self
    {
        $colQ = $this->quoteQualified($col);
        $p1 = $this->newParam($start);
        $p2 = $this->newParam($end);
        $this->addWhere($not ? "$colQ NOT BETWEEN $p1 AND $p2" : "$colQ BETWEEN $p1 AND $p2", [$p1 => $start, $p2 => $end]);
        return $this;
    }

    // WHERE Raw
    public function whereRaw(string $sql, array $params = []): self
    {
        $this->addWhere($sql, $params);
        return $this;
    }

    /* ----------------------------------------------------------------------
     * ORDER/LIMIT/OFFSET
     * -------------------------------------------------------------------- */
     
    public function order_by(string $field, string $dir = 'ASC'): self
    {
        $dir = strtoupper($dir);
        if (!in_array($dir, ['ASC', 'DESC'], true)) $dir = 'ASC';
        $this->order = $this->quoteQualified($field) . " $dir";
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = max(0, $limit);
        if ($offset !== null) $this->offset = max(0, $offset);
        return $this;
    }

    /* ----------------------------------------------------------------------
     * Execução de SELECT (Otimizada e Refatorada)
     * -------------------------------------------------------------------- */

    // Isolamento: Constrói a string SQL principal
    private function buildSelectSql(): string
    {
        if (!$this->from) throw new RuntimeException('No table specified (use from()).');
        
        $sql = "SELECT {$this->select} FROM {$this->from}";
        if ($this->joins) $sql .= ' ' . implode(' ', $this->joins);
        if ($this->wheres) $sql .= ' WHERE ' . implode(' AND ', array_column($this->wheres, 'sql'));
        if ($this->order) $sql .= " ORDER BY {$this->order}";
        if ($this->limit !== null) $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        
        return $sql;
    }

    // Isolamento: Coleta todos os parâmetros necessários para o execute()
    private function getParams(): array
    {
        $params = [];
        foreach ($this->wheres as $w) $params += $w['params'];
        return $params;
    }

    /**
     * Retorna todas as linhas do SELECT.
     */
    public function get(?string $table = null): array
    {
        if ($table !== null) $this->from($table);
        
        $sql = $this->buildSelectSql();
        $params = $this->getParams();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->lastStmt = $stmt;
        $this->reset();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a primeira linha. Otimizado com LIMIT 1 e busca direta (sem fetchAll desnecessário).
     */
    public function fetchOne(?string $table = null): ?array
    {
        if ($table !== null) $this->from($table);
        $this->limit(1); // Otimização de I/O: Para a busca no DB após a primeira linha.
        
        $sql = $this->buildSelectSql();
        $params = $this->getParams();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->lastStmt = $stmt;
        $this->reset();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null; // Retorna nulo se não houver resultado
    }
    
    // Método auxiliar para uso rápido
    public function get_where(string $table, array $where): array
    {
        return $this->from($table)->where($where)->get();
    }
    
    /* ----------------------------------------------------------------------
     * Helpers de DML (Insert/Update/Delete) - DRY
     * -------------------------------------------------------------------- */

    // Isolamento: Prepara colunas e placeholders para INSERT/UPDATE
    private function prepareDmlParams(array $data): array
    {
        $params = [];
        $placeholders = [];
        $sets = [];
        
        foreach ($data as $col => $value) {
            $p = $this->newParam($value);
            $colQ = $this->quoteOne($col);
            
            $placeholders[] = $p;
            $sets[] = "$colQ = $p"; // Usado em UPDATE
            $params[$p] = $value;
        }

        return [
            'cols' => array_map(fn($c) => $this->quoteOne($c), array_keys($data)),
            'ph' => $placeholders,
            'sets' => $sets,
            'params' => $params
        ];
    }

    public function insert(string $table, array $data): bool
    {
        $tableQ = $this->quoteIdent($table);
        $result = $this->prepareDmlParams($data);

        $sql = "INSERT INTO {$tableQ} (" . implode(',', $result['cols']) . ") VALUES (" . implode(',', $result['ph']) . ")";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($result['params']);
    }

   public function update(string $table, array $data, array $where): bool
    {
        $tableQ = $this->quoteIdent($table);
        $result = $this->prepareDmlParams($data); // Data para SET

        // Condições WHERE (não mudam)
        $conds = [];
        $paramsWhere = [];
        foreach ($where as $k => $v) {
            $p = $this->newParam($v);
            $conds[] = $this->quoteQualified($k) . " = $p";
            $paramsWhere[$p] = $v;
        }

        // COMBINAR SETS NORMAIS E RAW
        $allSets = array_merge($result['sets'], $this->rawSets); // <--- CORREÇÃO AQUI
        
        // Verifica se há SETs para evitar SQL inválido
        if (empty($allSets)) {
            throw new InvalidArgumentException("Update data and raw sets cannot be empty.");
        }

        $sql = "UPDATE {$tableQ} SET " . implode(', ', $allSets) // <--- USAR $allSets AQUI
            . " WHERE " . implode(' AND ', $conds);

        // Combina parâmetros SET e WHERE
        $params = $result['params'] + $paramsWhere; 
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute($params);

        $this->reset(); // Não se esqueça de resetar após a execução!
        return $success;
    }

    public function delete(string $table, array $where): bool
    {
        $tableQ = $this->quoteIdent($table);

        $conds = [];
        $params = [];
        foreach ($where as $k => $v) {
            $p = $this->newParam($v);
            $conds[] = $this->quoteQualified($k) . " = $p";
            $params[$p] = $v;
        }

        $sql = "DELETE FROM {$tableQ} WHERE " . implode(' AND ', $conds);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        // Otimização: Se não houver parâmetros passados, usa pdo->query (Bypass Prepare-Execute)
        if (empty($params)) {
             $stmt = $this->pdo->query($sql);
        } else {
             $stmt = $this->pdo->prepare($sql);
             $stmt->execute($params);
        }
        $this->lastStmt = $stmt;
        return $stmt;
    }

    /* ----------------------------------------------------------------------
     * Métodos auxiliares
     * -------------------------------------------------------------------- */

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
    
    public function rowCount(): int
    {
        return $this->lastStmt?->rowCount() ?? 0;
    }
    
    public function pdo(): PDO
    {
        return $this->pdo;
    }
    public function affected_rows(): int
    {
        return $this->lastStmt?->rowCount() ?? 0;
    }
}