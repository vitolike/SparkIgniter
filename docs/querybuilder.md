# Usando o QueryBuilder (SparkIgniter)

A classe `DB` do SparkIgniter fornece uma interface limpa, segura contra SQL Injection (usando Prepared Statements nativos do PDO) e altamente eficiente para construir e executar queries no banco de dados.

## Instanciação

Normalmente o Query Builder já está injetado nos seus Controllers e Models base e através da instância principal de Database, mas a classe pede o `$pdo` no momento de instanciação:

```php
$pdo = Database::getInstance();
$db = new DB($pdo);
// Se você estiver em um Controller/Model, possivelmente já tem acesso via $this->db
```

---

## 🔍 Consultas de Seleção (SELECT)

### Retornando Vários Resultados (`get`)

Use `get()` para finalizar a montagem da consulta e recuperar todos os registros em forma de resutado (Array Associativo).

```php
$users = $db->select('id, nome, email')
            ->from('users')
            ->get();
```
*Se você omitir o `select()`, ele busca todos os campos (`*`) por padrão.*
Você também pode passar a tabela direto no `get()`:

```php
$users = $db->get('users'); // Faz "SELECT * FROM users"
```

### Retornando um Único Resultado (`fetchOne`)

Traz a primeira linha encontrada. Ométodo possui limitação de busca natural pre-integrada para maximizar velocidade (`LIMIT 1`).

```php
$user = $db->select('nome, email')
           ->from('users')
           ->where(['id' => 5])
           ->fetchOne();
```

### O Método Auxiliar `get_where`

Uma forma rápida de recuperar dados com múltiplas checagens de igualdade sem encadear múltiplos métodos.

```php
// SELECT * FROM users WHERE status = 'ativo' AND block = 0
$ativos = $db->get_where('users', ['status' => 'ativo', 'block' => 0]); 
```

---

## 🎛 Cláusulas WHERE (Filtros)

O query builder oferece uma gama enorme de filtros. Todos são parametrizados para proteger contra SQL Injection.

### Igualdade Otimizada (`where`)
```php
$db->where(['status' => 'ativo', 'role' => 'admin']);
// Gera: WHERE status = :p0 AND role = :p1
```

### Operadores de Comparação (`whereOp`)
Ideal para `=`, `>`, `<`, `>=`, `<=`, `<>`, `!=`.

```php
$db->whereOp('age', '>=', 18);
// Gera: WHERE age >= :p0
```

### Onde está Contido (`whereIn`)
Busca na lista fornecida.

```php
$db->whereIn('role', ['admin', 'manager', 'editor']);
// Terceiro argumento como true inverte para NOT IN
$db->whereIn('role', ['banned'], true);
```

### Busca em Padrões (`whereLike`)
```php
$db->whereLike('email', '%@gmail.com');
```

### Buscar Entre (`whereBetween`)
```php
$db->whereBetween('criado_em', '2023-01-01', '2023-12-31');
```

### Condição Bruta/Personalizada (`whereRaw`)
Útil se precisar usar funções avançadas do banco na cláusula.
```php
$db->whereRaw('YEAR(criado_em) = :ano', [':ano' => 2023]);
```

---

## 🖇 Joins (Junção de Tabelas)

Mantenha controle de prefixos (Ex: `u.id`) utilizando aliases seguros nas tabelas.

```php
$users = $db->select('u.nome, p.titulo as post_titulo')
            ->from('users u') // Pode usar alias!
            ->join('posts p', 'u.id', '=', 'p.user_id', 'LEFT')
            ->get();
```

Caso precise de joins muito complexos (ex: múltiplos AND/OR dentro das checagens do ON):
```php
$db->joinRaw('LEFT JOIN orders o ON o.user_id = u.id AND o.status = "paid"');
```

---

## 🔢 Ordenação e Limites

Para limitar ou ordenar linhas são usados os métodos a seguir:

### Ordernar (`order_by`)
```php
$db->order_by('data_criacao', 'DESC');
```

### Limitar resultados (`limit`)
O primeiro paramento é o maximo limite de resultados. Opcionalmente para fazer paginamento adicione o offset no 2º argumento:
```php
$db->limit(10, 20); // LIMIT 10 OFFSET 20
```

---

## ✍️ Inserindo, Atualizando e Deletando

### Inserir (`insert`)
Insere no BD devolvendo um booleano `true`/`false`. O id recém gerado pode ser adquirido no método auxiliar de escopo.

```php
$sucesso = $db->insert('users', [
    'nome'  => 'João',
    'email' => 'joao@email.com',
    'age'   => 25
]);

if ($sucesso) {
    echo "ID gerado: " . $db->lastInsertId();
}
```

### Atualizar (`update`)
Especifique as colunas/valores que vão para cláusula SET e as condições do WHERE.

```php
$db->update(
     'users',                      // tabela
     ['status' => 'desativado'],   // colunas para o SET
     ['id' => 5]                   // condições para WHERE (igualdade)
);
```

#### Usando Funções ou Operações Dinâmicas (`setRaw`)
Às vezes você precisa de algo como `views = views + 1` de maneira nativa, e não consegue declarar via string com injeção padrão PDO.
```php
// IMPORTANTE: Declare o setRaw ANTES do ->update()
$db->setRaw('acessos', 'acessos + 1')
   ->update('posts', ['editado' => 1], ['id' => 10]);
```

### Deletar (`delete`)
Deleta registros de acordo com restrições e segurança PDO atrelada.

```php
$db->delete('users', ['id' => 5, 'status' => 'banned']);
```

---

## 🛠 Queries Cruas e Utilitários

Se o QueryBuilder não te suportar nas buscas super específicas, você tem segurança para usar queries complexas livres mantendo os "prepared statements":

```php
$query = "SELECT * FROM relatorios WHERE tipo = :tipo AND total > :minimo";

// PDO Statement com otimização customizada
$stmt = $db->query($query, [
    ':tipo' => 'anual',
    ':minimo' => 5000
]);

$resultados = $stmt->fetchAll();
```

### Funções Auxiliares de Resultados
Saber número de linhas afetadas (ótimo para reports visuais ou assertividades):

```php
// Retorna linhas afetadas pelo DELETE, UPDATE ou INSERT mais recente
$qtd = $db->rowCount(); 

// Puxar a instancia raw direta do PDO caso necessário invocar métodos globais dele
$instancia_pdo = $db->pdo();
```

## Exemplo Prático Completo

Se quisermos listar todas as faturas em atraso para um painel com paginação, join com usuário e condições dinâmicas de buscas opcionais que vieram do formulário GET:

```php
public function pesquisarAtrasos() {
    $busca_email = $this->input->get('pesquisa_email');
    
    // 1. Inicia o molde básico da consulta complexa
    $this->qb->select('f.*, u.email as cliente_email')
             ->from('faturas f')
             ->join('usuarios u', 'f.id_user', '=', 'u.id', 'INNER')
             ->where(['f.status' => 'PENDENTE'])
             ->whereOp('f.data_vencimento', '<', date('Y-m-d'));
             
    // 2. Acopla um filtro opcional sem quebrar a string primária (Checagem condicional!)
    if (!empty($busca_email)) {
        $this->qb->whereLike('u.email', "%$busca_email%");
    }
    
    // 3. Organiza ordenando e buscando no BD o pacote formatado
    $resultados = $this->qb->order_by('f.data_vencimento', 'ASC')
                           ->limit(50)
                           ->get();
                           
    var_dump($resultados);
}
```
