# SparkIgniter

Bem-vindo ao **SparkIgniter**! 

O SparkIgniter é um framework PHP MVC customizado, construído para ser leve, rápido e fácil de manter. Inspirado na simplicidade do CodeIgniter, ele oferece ferramentas modernas como um QueryBuilder robusto e serviços otimizados para interações eficientes com banco de dados, mantendo a facilidade de desenvolvimento.

## Estrutura do Projeto

O framework adota uma estrutura clássica:
* `app/`: Contém aos controladores (Controllers), modelos (Models), views e serviços nucleares da sua aplicação.
* `public/`: Diretório de entrada da aplicação (Front Controller). As requisições passam pelo `index.php` aqui contido, que inicializa o framework de forma segura (já configurado no Docker).
* `storage/`: Diretório para armazenar logs, cache e arquivos gerados.
* `docs/`: Documentação e referências adicionais.

## Requisitos

- [Docker](https://www.docker.com/) e [Docker Compose](https://docs.docker.com/compose/) instalados na sua máquina.

## Como rodar o projeto com Docker

O projeto já está configurado com um ambiente de desenvolvimento em containers utilizando PHP 8.4, Apache e extensões prontas para PostgreSQL (`pdo_pgsql`, `pgsql`). O diretório raiz será montado no container, permitindo que você altere os arquivos e veja os resultados em tempo real.

Siga os passos abaixo para subir a aplicação:

1. **Abra o terminal** na pasta raiz do projeto.
2. **Suba o container** usando o Docker Compose:
   ```bash
   docker-compose up -d
   ```
   *(Nota: Dependendo da sua versão do Docker, o comando pode ser `docker compose up -d`)*

3. O Docker fará o build da imagem na primeira vez (baixando o PHP e habilitando o `mod_rewrite`).
4. **Acesse no navegador**:
   [http://localhost](http://localhost)

A aplicação agora estará rodando na porta `80`. Todo o tráfego é direcionado para a pasta `/public` que contém o Front Controller.

## Parando o container

Para parar os serviços, execute dentro da pasta do projeto:
```bash
docker-compose down
```

## Logs e Problemas
Caso ocorra algum problema, as configurações e erros do Apache podem ser inspecionados rodando:
```bash
docker-compose logs -f web
```

## Documentação

Para mais detalhes sobre as funcionalidades e de como utilizar o framework, consulte os arquivos de documentação listados abaixo:

- [Controller](docs/controller.md)
- [Database](docs/database.md)
- [Environment (.env)](docs/env.md)
- [HTTP Client](docs/httpclient.md)
- [ID Generator](docs/idgenerator.md)
- [Input](docs/input.md)
- [JWT](docs/jwt.md)
- [Loader](docs/loader.md)
- [Model](docs/model.md)
- [Pagination](docs/pagination.md)
- [Query Builder](docs/querybuilder.md)
- [REST Controller](docs/restcontroller.md)
- [Router](docs/router.md)
- [Services](docs/services.md)
- [Session](docs/session.md)
