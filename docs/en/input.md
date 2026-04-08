# Input Escort (Input)

The wonderful core class inspired by the glorious object design of CodeIgniter 3 with maximum power and match operator from PHP 8.4. Removes the need for the project to expose the dangerous $_POST globally and prevents Null Referencing in scripts that query it.

Usually used via `$this->input` of the `Controller`.

## Universal String/Raw Catchers

Instead of `$_POST['email']`, use `$this->input->post('email')`. The benefit is avoiding null errors in case of keys that the user hasn't filled, the natural fail-forward value is `null`.

```php
// If it doesn't exist, outputs 'None'
$name = $this->input->post('name', 'None'); 

$search = $this->input->get('q'); // Tries the key ?q=...
$age = $this->input->cookie('age'); // In $_COOKIE
$referrer = $this->input->server('REMOTE_ADDR'); // Equivalent to secure $_SERVER
```

Unified Wildcard Method:
```php
// Searches first in the POST array. If not found, searches in global $_GET.
$filter = $this->input->get_post('min-price');
```

## Built-in Native Sanitizations

The script runs a brute native scan to prevent XSS `FILTER_SANITIZE_SPECIAL_CHARS` if triggered.
It can be executed with the final boolean of all getters (`post('arg', default, $sanitize)`);

## Typing (Input Typed Fetchers)

These sub-functions do not return plain text, they cast the type and pass native PHP boolean validations avoiding type-juggling:

```php
$age = $this->input->getInt('numeric_age', 18); // fallback: integer var 18
$price = $this->input->getFloat('product_price', 0.0); // guarantees float type
$isAdmin = $this->input->getBool('admin_auth', false);
```

## JSON Data from Raw REST Requests
With the advent of REST APIs, requests do not come from the conventional form, and many break when assembling the $_POST array because React.JS just threw a continuous raw JSON string at the port.
`Input` decodes, caches the response, and provides you with the easy array:

```php
$payload_array = $this->input->json(); // Decodes 1x from php://input stream
```
If you need to see what the heck the partner API sent you via HTTP Stream, use the raw input: `$this->input->raw()`;

## Current Web Interaction Properties

Magic methods to extract data without sweeping $_SERVER:

- `$this->input->method();` --> Returns: POST, GET, DELETE...
- `$this->input->header('Authorization');` -> Retrieves the Custom Header without caring about Capital Case.
- `$this->input->isAjax();` -> Boolean about XMLHttpRequest request Headers.
- `$this->input->ip();` -> Filters and resolves proxied IPs, valid range and IPV4/IPV6 based on reliable static arrays (`trusted_proxies`).

## Full Practical Example

Imagine an API that receives a JSON Payload containing payment data and user settings:

```php
public function processWebhook() {
    // 1. Check Method and reject aggressively
    if ($this->input->method() !== 'POST') {
        $this->response(['error' => 'Method not allowed'], 405);
    }
    
    // 2. Extracting custom validation Header
    $signature = $this->input->header('X-Stripe-Signature');
    if (empty($signature)) {
        die('Forbidden');
    }

    // 3. Getting the raw body (Raw JSON Payload from Webhook)
    $payload = $this->input->json();

    // 4. Sanitize and Typed Fetching
    $transaction_amount = $this->input->getFloat('amount'); // Protects type-juggling for database insertions
    $customer_email = $this->input->post('customer_email', 'anonymous@site.com', true); // Cleans XSS if it comes dirty
    
    // Log of real isolated IP (Bypassing potential Cloudflare)
    $ip = $this->input->ip();
    $this->myService->writeLog("Webhook received from $ip -> Amount: $transaction_amount");
}
```
