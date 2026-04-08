# Model Base (Model)

O Core `Model` é uma classe abstrata de apoio para dar à sua estrutura as ferramentas necessárias para manipular os dados do banco sem reescrever nada. As suas classes concretas devém herdar desta classe.

Ele te passa automaticamente a sua conexão do banco `PDO` (`$this->db`) e a sua própria instância independente do poderoso QueryBuilder (`$this->qb`).

## Como criar o seu Model

Atribua o Model principal como extensão e defina o nome da sua tabela protegida obrigatória:

```php
class UsuarioModel extends Model {
    protected string $table = 'usuarios';

    // Você não precisa criar um construtor, o Base já lida com injeção com o BD!
}
```

## Métodos Embutidos

Você já herda "de graça" essas rotinas úteis no seu novo objeto:

### Acesso Dinâmico (`find`)
Puxa somente 1 registro da referida tabela com base na primary key.

```php
$model = new UsuarioModel($pdo);

// SELECT * FROM usuarios WHERE id = 5 LIMIT 1
$usuario = $model->find(5);

// Você pode passar a chave customizada se sua PK não for "id"
$usuario = $model->find('AB-123', 'uuid_secreto');
```

### Listagem Automática (`all`)
Permite puxar todos da tabela de forma crua, tendo recursos nativos de limit e offset da própria tabela.
```php
$usuarios = $model->all(50, 0); // Limit 50
```

### Inserções Simples (`insert`)
Insere chaves/valores de array associativo ignorando as amarras do QueryBuilder e devolve o ID numérico criado (inclui adaptações para POSTGRES `RETURNING id` ou MYSQL `lastInsertId`).

```php
$novoId = $model->insert([
    'nome' => 'Maria',
    'status' => 'ativo'
]);
```

### Atualizando (`update`) *(Work in Progress)*
> O update cru ainda tem a estrutura básica definida, para updates elaborados priorize usar seu QueryBuilder interno `$this->qb->update(...)`!
