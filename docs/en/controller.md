# Base Controller

The `Controller` class is the main brain of the MVC relationship within SparkIgniter. Every controller you create that intends to render HTML pages (`views`) must necessarily inherit from it.

By creating a Controller and extending this base class, you gain global access and immediate instantiation to all features in the ecosystem, eliminating the need to initialize `new Input()`, `new Session()`, etc.

## Native Injected Properties

The following variables are born already instantiated within any `$this` of your controller:
- **`$this->input`**: Access to the `Input` sanitizing class (GET, POST, $_SERVER).
- **`$this->load`**: Dynamic injection manager (Models, Library, Helper, Service).
- **`$this->db`**: Raw direct access to the PDO Singleton.
- **`$this->qb`**: The dedicated and clean **Query Builder** associated with the same database connection.
- **`$this->idGen`**: Access to the generation routines of the `IdGenerator` class.
- **`$this->jwt`**: Direct parse/creation access via `JWT`.
- **`$this->httpClient`**: The built-in `HttpClient` API request Wrapper.
- **`$this->session`**: CI3-style Sessions Manager.

## Global Autoload
It is possible to configure components (like helpers or widely useful Models) that always inject globally before any Controller executes via `Controller::setAutoload([...])`.

## Calling Views
To pass processing forward to the corresponding html/php file:

```php
public function index() {
    $data = [
        'title' => 'User Page',
        'logged' => true
    ];
    // Loads app/views/painel.php with the loose variables.
    $this->view('painel', $data); 
}
```

## RequireAuth (Route Protection with Built-in Middleware)

If your web page requires the visitor to send a signed Cookie, or pass an Auth via Bearer Token Header (ideal for views built by mixing JWT with WebHooks), just invoke it in the method or constructor:

```php
public function admin() {
    $this->requireAuth(); // Blocks with HTTP 401 if it doesn't have a bearer token
    // from here on, the user array will be saved globally in the Controller
    var_dump($this->user); 
}
```

## Full Practical Example

Below is a real-world example of a `UserController` managing the registration of a user and rendering a view:

```php
<?php

namespace App\Controllers;

use Core\Controller;

class UserController extends Controller {

    public function __construct() {
        // Loads model and helper that will be used in all methods
        $this->load->model('UserModel');
        $this->load->helper('url');
    }

    public function register() {
        // If the request is POST, try to register
        if ($this->input->method() === 'POST') {
            $name = $this->input->post('name', '', true);
            $email = $this->input->post('email', '', true);

            if (!empty($name) && !empty($email)) {
                $success = $this->UserModel->insert([
                    'name' => $name,
                    'email' => $email
                ]);

                if ($success) {
                    $this->session->set_userdata('msg', 'Registered successfully!');
                    redirect('/users/login');
                }
            }
            $error = "Fill all fields!";
        }

        // Renders the view on the registration screen
        $this->view('users/register', [
            'error' => $error ?? null
        ]);
    }
}
```
