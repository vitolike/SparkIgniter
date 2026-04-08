# Variáveis de Ambiente (Env)

A classe `Env` é a responsável por ler, injetar e disponibilizar as variáveis de ambiente escritas no seu arquivo `.env` para todo o núcleo da aplicação. 

Ela gerencia e unifica as variáveis injetadas via _putenv_ nativo do PHP e pelo array `$_ENV`.

## Como Inicializar

Geralmente carregado na inicialização global (`index.php` da raiz), você aponta onde o arquivo `.env` está:

```php
Env::load(__DIR__ . '/.env');
```

O método irá ignorar automaticamente linhas vazias e linhas que possuam comentários começando com `#`.

## Pegando valores (Getter)

Em qualquer lugar do código você pode recuperar uma diretiva do .env usando `Env::get`. O primeiro parâmetro é a Chave procurada e o segundo (opcional) é o Valor Padrão caso não seja encontrada.

```php
// Usa 'pgsql' como fallback se DB_DRIVER não existir
$banco = Env::get('DB_DRIVER', 'pgsql');

// Verifica qual o nível de ambiente
$isDev = Env::get('APP_ENV') === 'dev';
```

A ordem de prioridade de checagem do método é:
1. Cache interno de variáveis da classe.
2. `$_ENV` interno do servidor.
3. `getenv()` do sistema operacional.
4. Valor Default que você passar por último.

## Estrutura do Arquivo `.env` 

Fomos cuidadosos ao organizar as variáveis de configuração global em blocos lógicos focados em responsabilidade, divididos em **App**, **Banco de Dados** e **Segurança**.

### 1. Configurações da Aplicação (APP)

| Variável | Descrição | Importância |
|---|---|---|
| **APP_ENV** | Define o nível de segurança para exibição de erros críticos no browser. `dev` exibe todo o *stack* de erro na tela; `production` oculta falhas para proteger seus arquivos aos curiosos. | **Obrigatório** (`dev` ou `production`)
| **LOG_CHANNEL** | Determina o comportamento de gravação de erros pela aplicação. (Ex: `file`, `syslog`, `both`). | Opcional (`both` por padrão)
| **CORS** | Relação de URLs permitidas a realizarem requisições Cross-Origin (Frontend/API). `*` vai liberar para todas globalmente. | **Obrigatório**
| **DEFAULT_CONTROLLER** | Controller mapeado caso a URI base seja acessada vazia (`/`). | **Obrigatório**
| **DEFAULT_METHOD** | Método invocado na Controller base. | **Obrigatório**
| **BP_PHP_VERSION** | Informativo do motor do app. | Opcional

### 2. Banco de Dados (DB)

Trata da conexão raiz do PDO para todo o motor de manipulação de dados `QueryBuilder` e serviços do seu app. Se preenchido errado, o framework inteiro se recusa a compilar *Models*!

| Variável | Descrição | Importância |
|---|---|---|
| **DB_DRIVER** | Tipo de conexão (`pgsql` para Postgres ou `mysql` para MariaDB). | **Obrigatório**
| **DB_HOST** | IP do servidor da sua database (DNS ou IPv4, usualmente `127.0.0.1` ou `localhost`). | **Obrigatório**
| **DB_PORT** | Porta de escuta (5432 para PGSQL ou 3306 para MySQL). | **Obrigatório**
| **DB_NAME** | Nome do Schema (A base que será acessada). | **Obrigatório**
| **DB_USER** | Usuário de permissão. | **Obrigatório**
| **DB_PASS** | Senha do banco (Em local pode ser vazio em alguns cenários). | *Recomendado*

### 3. Autenticação (Segurança JWT)

Exclusivo e necessário se você fizer uso do `app/core/JWT.php` para proteção e validação de tokens em formato JSON Web Tokens na sua API Backend.

| Variável | Descrição | Importância |
|---|---|---|
| **JWT_SECRET** | Palavra-chave ou MD5 de validação única usada para encripitar e assinar seus tokens. Jamais utilize um padrão e *nunca envie este dado para o Github*. | **Obrigatório** para rotas trancadas
| **JWT_EXPIRE** | Duração útil do token de autenticação principal. | **Obrigatório**
| **JWT_REFRESH_EXPIRE** | Duração útil estendida designada a tokens de refresh de reautenticação. | **Obrigatório**
