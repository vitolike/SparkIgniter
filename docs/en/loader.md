# Files Dynamism (Loader)

The `Loader` class is the Controller's dynamic dependency manager. That is, it is the `$this->load` variable that helps you instantiate files, injecting them directly into the variables and scopes of your live Controller.

## Triggering on Children

It usually comes injected into the BaseController and is active by the primary syntax:

```php
$this->load->{what_to_load}('file_name');
```

## Models Loader (`model`)

Performs a "require" of your class located in `app/models/` and injects it into the controller, instantiating the model using native `$db`:

```php
$this->load->model('UserModel');

// And voilà, it starts existing:
$all = $this->UserModel->all(); 
```

## Libraries Loader (`library`)

Searches and instantiates local classes in `app/libraries` or even in native core files folders like in `app/core`.

```php
$this->load->library('PDFGenerator');
$this->PDFGenerator->export();
```

## Functions/Helpers Loader (`helper`)

Useful for calling files that contain only compilations of normal functions not enclosed in object orientation (`app/helpers/`). They seek out files always ending in `_helper.php`.

```php
$this->load->helper('url');
// Loaded "url_helper.php". The functions from there become global!
redirect('/home'); 
```

## Services Loader (`service`)

Unlike libraries, a file in `app/services/` already wakes up with the native injection of the Controller's `$db`, ideal for massive routines based on abstruse data where Models are insufficient.

```php
$this->load->service('EmailSender');
$this->EmailSender->notify();
```

## Array Autoload

Through simultaneous injected multiple autoload of configs array listed in root directives:

```php
$this->load->autoload([
   'helpers' => ['url', 'text'],
   'models' => ['UserModel']
]);
```

## Full Practical Example

Your framework has the classic MVC structure. But sometimes, your endpoint needs to trigger library or model calls orchestrating them dynamically.

```php
// app/controllers/ReportController.php
public function generate() {
    // 1. Loads a custom library from app/libraries/PdfBuilder.php
    $this->load->library('PdfBuilder');
    
    // 2. Loads a custom BaseModel with live DB injection
    $this->load->model('SalesModel');
    
    // Now the controller can use and pass the data forward
    $sales_data = $this->SalesModel->listToday();
    $this->PdfBuilder->write($sales_data);
    
    // 3. Uses a simple string helper (app/helpers/text_helper.php)
    $this->load->helper('text');
    echo uppercase('report generated successfully!');
}
```
