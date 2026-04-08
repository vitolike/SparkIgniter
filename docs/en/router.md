# Smart Routing (Router)

The `Router` is the great interceptor of your URL, which extracts where the user wants to visit and magically triggers the pertinent action of the Controller and Method based on convention, also supporting separate URLs in `/api`.

It uses the static standard style in segmented routes:  
**Base Url / ControllerName / MethodName / (Parameter1) / (Parameter2) ...**

## How to Trigger
The class is instantiated and gives the dispatch voice `dispatch()` right in the root index:

```php
$router = new Router();
$router->dispatch();
```

## The Connection Rule (Common Controllers)

If you click on `mysite.com/users/delete/5`.

1. The `Router` searches for `Users.php` in your `app/controllers/` folder. (The first letter will automatically become Uppercase via StudlyCase).
2. It executes the `Users` class.
3. It runs the `delete($id)` method contained in the file, passing the value `5` to the function.

If you omit it, the "fallback standard" of the URL will search for the `Home` Controller and `index` method.

## Support Patterns for Rest APIS

If the first segment of a URL is in the reserved form **`/api/`**, the provider will automatically change the local search path for controller files from the root of `controllers` to the subfolder `app/controllers/api/`.

```text
Access: /api/products/list
```
In this call, the framework locally opens the code at:  
`app/controllers/api/Products.php` executing the `list()` method.

### Built-in 404 Handler
If it finds out that the route (typed path) has no controller or that such a method was not programmed, it automatically generates `404 Not Found` responses transformed into JSON, emitting the exact logical failure notification and recording it in the local internal log.

## Full Practical Example

Imagine the following interactions executed by the FrontEnd via Axios or even through pure Browser interactions:

```text
GET http://your-site.local/
- Calls App\Controllers\Home::index() (Default mapped in .env)

GET http://your-site.local/products
- Calls App\Controllers\Products::index()

GET http://your-site.local/products/show/50
- Calls App\Controllers\Products::show(50)

POST http://your-site.local/api/settings/update
- Calls App\Controllers\Api\Settings::update()
```

The cool part is that there isn't a massive `routes.php` file mapping everything! The core itself deduces where to hit by real *namespaces* and physical folders, reducing verbosity by 90%.
