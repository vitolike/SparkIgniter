# Environment Variables (Env)

The `Env` class is responsible for reading, injecting and making available the environment variables written in your `.env` file to the entire application core. 

It manages and unifies variables injected via native PHP _putenv_ and the `$_ENV` array.

## How to Initialize

Usually loaded at global initialization (`index.php` at the root), you point to where the `.env` file is:

```php
Env::load(__DIR__ . '/.env');
```

The method will automatically ignore empty lines and lines that have comments starting with `#`.

## Getting values (Getter)

Anywhere in the code, you can retrieve a directive from .env using `Env::get`. The first parameter is the Key sought and the second (optional) is the Default Value if it is not found.

```php
// Uses 'pgsql' as fallback if DB_DRIVER doesn't exist
$db = Env::get('DB_DRIVER', 'pgsql');

// Checks which environment level
$isDev = Env::get('APP_ENV') === 'dev';
```

The priority order of method checking is:
1. Internal cache of class variables.
2. Internal `$_ENV` of the server.
3. Operating system's `getenv()`.
4. Default Value that you pass last.

## `.env` File Structure 

We were careful to organize the global configuration variables into logical blocks focused on responsibility, divided into **App**, **Database**, and **Security**.

### 1. Application Configuration (APP)

| Variable | Description | Importance |
|---|---|---|
| **APP_ENV** | Defines the security level for displaying critical errors in the browser. `dev` displays the entire error *stack* on the screen; `production` hides flaws to protect your files from curious eyes. | **Required** (`dev` or `production`)
| **LOG_CHANNEL** | Determines the error recording behavior by the application. (Ex: `file`, `syslog`, `both`). | Optional (`both` by default)
| **CORS** | List of URLs allowed to make Cross-Origin requests (Frontend/API). `*` will release to everyone globally. | **Required**
| **DEFAULT_CONTROLLER** | Controller mapped if the base URI is accessed empty (`/`). | **Required**
| **DEFAULT_METHOD** | Method invoked in the base Controller. | **Required**
| **BP_PHP_VERSION** | App engine informative. | Optional

### 2. Database (DB)

Handles the root PDO connection for the entire `QueryBuilder` data manipulation engine and services of your app. If filled out wrong, the whole framework refuses to compile *Models*!

| Variable | Description | Importance |
|---|---|---|
| **DB_DRIVER** | Connection type (`pgsql` for Postgres or `mysql` for MariaDB). | **Required**
| **DB_HOST** | IP of your database server (DNS or IPv4, usually `127.0.0.1` or `localhost`). | **Required**
| **DB_PORT** | Listening port (5432 for PGSQL or 3306 for MySQL). | **Required**
| **DB_NAME** | Schema Name (The database that will be accessed). | **Required**
| **DB_USER** | Permission user. | **Required**
| **DB_PASS** | Database password (Locally it might be empty in some scenarios). | *Recommended*

### 3. Authentication (JWT Security)

Exclusive and necessary if you make use of `app/core/JWT.php` for protection and validation of tokens in JSON Web Tokens format in your Backend API.

| Variable | Description | Importance |
|---|---|---|
| **JWT_SECRET** | Keyword or unique validation MD5 used to encrypt and sign your tokens. Never use a standard and *never send this data to Github*. | **Required** for locked routes
| **JWT_EXPIRE** | Useful life of the main authentication token. | **Required**
| **JWT_REFRESH_EXPIRE** | Extended useful life assigned to reauthentication refresh tokens. | **Required**
