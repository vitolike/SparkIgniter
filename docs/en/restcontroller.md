# REST Controller (APIs)

The `RestController` class was designed to perfectly handle microservices-based architectures and strict JSON responses. Unlike the standard controller (`Controller`) focusing on HTML Views, this one focuses purely on clean HTTP Payload.

It inherits everything from the BaseController (also giving you free access to the QueryBuilder `$this->qb`, Input, Session).

## Creation

When creating an api controller inside app/controllers/api, inherit from `RestController`. It forces global JSON headers and permissive generic CORS rules right on the first read.

## Modern Input Aliases (`RESTful`)

Unlike a conventional MVC Controller, you actively invoke the HTTP Verb as a variable catcher (it might natively parse from querystrings, formdata or _application/json body stream_).

```php
// Try parsing the ID index from DELETE HTTP Verb raw-body:
$target = $this->delete('id');

// Tries to capture PATCH Verb raw body
$changes = $this->patch('name');

// GET and POST
$search = $this->get('term');
```

## Response Helpers

Use the built-in Response methods (where you don't need to manually json_encode the layout object error) instead of using raw `echo json_encode()`. It shuts down script execution instantly with precision (exit).

```php
// Send "[]" in final application/json formatting with 200 OK wrapper
$this->response(['status' => 'success'], RestController::HTTP_OK); 
```

You can also shield the routes from accepting unsupported verbs (it will transmit automatic `405` code mapping constraint):

```php
// The user hits the POST endpoint, but here it strictly accepts merely GET and DELETE
$this->require_methods(['GET', 'DELETE']);
```

## JWT and Native Authentication

If your structural API is closed, just rapidly apply:

```php
public function secret() {
    $this->require_auth(); 
    // Secure HTTP_AUTHORIZATION header checking loop scope incoming: Bearer {token}
    // And leaves all verified core-token entirely mapped available at $this->user!
    
    $this->response(['id' => $this->user['id']]);
}
```

## Full Practical Example

Imagine an architecture where the FrontEnd application (React Native) needs to post an order to be charged by the user, demanding the correct REST verbs and active token:

```php
<?php

namespace App\Controllers\Api;

use Core\RestController;

class OrderController extends RestController {

    public function __construct() {
        parent::__construct();
        // None of this API actions will run if Javascript doesn't send JWT!
        $this->require_auth();
    }

    public function create() {
        // Forces to accept only Creation methods
        $this->require_methods(['POST']);
        
        $cart_array = $this->json(); // Reads raw to ignore the non-existent $_POST form
        
        if (empty($cart_array)) {
            $this->response(['error' => 'Request body missing.'], 400);
        }
        
        // Takes the logged user from JWT who requested it and passes it to the model with the req array
        $this->load->model('StoreModel');
        $success = $this->StoreModel->newDispatch($this->user['id'], $cart_array);
        
        if ($success) {
            $this->response([
                'status' => 'Done',
                'receipt' => 'OK-2000'
            ], RestController::HTTP_CREATED); // 201 
        } else {
            $this->response(['error' => 'Failure handling processes'], 500);
        }
    }
}
```
