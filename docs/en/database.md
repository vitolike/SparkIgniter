# Database Injector (Database)

The `Database` class ensures that the _PDO_ connection of your database happens in a Singleton structural pattern (meaning only ONE single connection will be opened, even if you call the database multiple times).

This class reacts in harmony with the information filled in the environment variables (`.env`).

## Instance Usage

The class has only 1 global static method, `getInstance()`, which returns the PDO instance connected in exceptions (ERRMODE_EXCEPTION), always returning Associative Fetch by default.

```php
$pdo = Database::getInstance();
```

## How to configure .env

It will automatically look for the following tags in the `.env`:

```env
DB_DRIVER=mysql   # or pgsql
DB_HOST=127.0.0.1
DB_PORT=3306      # smart default
DB_NAME=mydb
DB_USER=root
DB_PASS=1234
APP_ENV=dev       # in prod errors will not be shown to the user
```

If the database cannot connect and `APP_ENV=dev`, a log will be generated and the system will die outputting the error HTML. Otherwise, it will just return a 500 HTTP internal error in safe mode.
