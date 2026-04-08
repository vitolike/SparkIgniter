# SparkIgniter

Welcome to **SparkIgniter**! 

SparkIgniter is a custom PHP MVC framework built to be lightweight, fast, and easy to maintain. Inspired by the simplicity of CodeIgniter, it offers modern tools such as a robust QueryBuilder and optimized services for efficient database interactions, while maintaining ease of development.

## Project Structure

The framework adopts a classic structure:
* `app/`: Contains the controllers, models, views, and core services for your application.
* `public/`: The application's entry directory (Front Controller). Requests go through `index.php` located here, which safely initializes the framework (already configured in Docker).
* `storage/`: Directory for storing logs, cache, and generated files.
* `docs/`: Documentation and additional references.

## Requirements

- [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/) installed on your machine.

## How to run the project with Docker

The project is already set up with a containerized development environment using PHP 8.4, Apache, and PostgreSQL extensions (`pdo_pgsql`, `pgsql`). The root directory will be mounted in the container, allowing you to modify files and see results in real-time.

Follow the steps below to start the application:

1. **Open the terminal** in the root directory of the project.
2. **Start the container** using Docker Compose:
   ```bash
   docker-compose up -d
   ```
   *(Note: Depending on your Docker version, the command might be `docker compose up -d`)*

3. Docker will build the image the first time (downloading PHP and enabling `mod_rewrite`).
4. **Access it in the browser**:
   [http://localhost](http://localhost)

The application will now be running on port `80`. All traffic is directed to the `/public` directory containing the Front Controller.

## Stopping the container

To stop the services, run this inside the project folder:
```bash
docker-compose down
```

## Logs and Troubleshooting
If any issues occur, you can inspect Apache configurations and errors by running:
```bash
docker-compose logs -f web
```

## Documentation

For more details on the features and how to use the framework, please check the documentation files listed below:

- [Controller](docs/en/controller.md)
- [Database](docs/en/database.md)
- [Environment (.env)](docs/en/env.md)
- [HTTP Client](docs/en/httpclient.md)
- [ID Generator](docs/en/idgenerator.md)
- [Input](docs/en/input.md)
- [JWT](docs/en/jwt.md)
- [Loader](docs/en/loader.md)
- [Model](docs/en/model.md)
- [Pagination](docs/en/pagination.md)
- [Query Builder](docs/en/querybuilder.md)
- [REST Controller](docs/en/restcontroller.md)
- [Router](docs/en/router.md)
- [Services](docs/en/services.md)
- [Session](docs/en/session.md)
