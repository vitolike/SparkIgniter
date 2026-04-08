# Injetor de Banco de Dados (Database)

A classe `Database` garante que a conexão via _PDO_ do seu banco de dados aconteça em um padrão estrutural Singleton (ou seja, apenas UMA única conexão será aberta, mesmo que você chame múltiplas vezes o banco).

Essa classe reage em harmonia com as informações preenchidas nas variáveis de ambiente (`.env`).

## Uso via Instância

A classe tem apenas 1 método estático global, o `getInstance()` que retorna a instância do PDO conectado em exceções (ERRMODE_EXCEPTION), retornando sempre Fetch Associativo por padrão.

```php
$pdo = Database::getInstance();
```

## Como configurar o .env

Ela irá automaticamente procurar pelas seguintes tags no `.env`:

```env
DB_DRIVER=mysql   # ou pgsql
DB_HOST=127.0.0.1
DB_PORT=3306      # default inteligente
DB_NAME=meubanco
DB_USER=root
DB_PASS=1234
APP_ENV=dev       # em prod erros não serão mostrados ao user
```

Se o banco não conseguir conectar e `APP_ENV=dev`, um log será gerado e o sistema morrerá emitindo o HTML do erro. Caso contrário apenas retornará erro interno HTTP 500 em modo de segurança.
