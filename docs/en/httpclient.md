# HTTP Client (CUrl Requests)

Forget repetitive `curl_init()` lines. `HttpClient` helps you converse through REST API with scripts from the outside world (Payment Gateway, Webhook integration, GitHub Authenticator, etc.) using modern, performant PHP 8 features without brutal repetition in verbosity.

## How to Use (Instantiation)

In the controller injection you already access it as: `$this->httpClient`.
(Or trigger it with `new HttpClient()`).

By default, it guarantees String return and does not print, 30s brute timeout on the network card, SSL Verify triggered, and automatic Accept in JSON.

## Real Examples: GET and POST

The library always returns an associative Array containing the package: `['body' => string, 'status' => int, 'info' => array]` to give you granular control of the response.

### Pulling from Google (`GET`)
```php
$result = $this->httpClient->get('https://google.com', ['q' => 'Search CUrL']);
echo "The Header returned: " . $result['status']; // 200
```

### Sending Form Data (`POST`)
```php
$send = $this->httpClient->post('https://api.test.com/register', [
   'username' => 'johndoe',
   'password' => 'abc1234'
]);
```

### Automatic JSON Array Payload (`postJson`)
It auto-converts in `json_encode` before throwing it into the network for you, and already sets the `Content-Length` of the stream and `Content-Type: application/json` headers! Super handy!

```php
$gateway = $this->httpClient->postJson('https://api.stripe.com/transaction', [
   'amount' => 500,
   'card' => '123'
]);
```

## Other Utilities
### Insert Manual Curl Options
```php
$this->httpClient->setOption(CURLOPT_HTTPHEADER, ['Authorization: Token123']);
// It will merge this rule with the original ones
```
