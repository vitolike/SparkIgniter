# JSON Web Tokens Control (JWT)

The `JWT` Class provides the essential stateless Handshake (HS256) locks and validations of your backend. Allows the ecosystem to authenticate requests from a React/Vue client, etc., or protect WebHooks Controllers.

The `.env` credentials act on the free operation of the class:
- `JWT_SECRET`: Master password.
- `JWT_EXPIRE`: Global lifespan in seconds (access standard).
- `JWT_REFRESH_EXPIRE`: Lifespan (refresh standard).

## Issuing a Custom Specific Payload (_encode_)

To encode freely (Header, Payload, Signature) informing its validity manually, pass as the second parameter in int (seconds):

```php
$token = $this->jwt->encode(['role' => 'admin', 'userid' => 123], 3600); // 1hr
```

## Issuing Access Based on Configuration (_issueTokens_)

The most practical tool. Instead of worrying about reload tokens on your Login endpoint, just inject the key variables. The algorithm will return a perfect array.

```php
$result = $this->jwt->issueTokens(['userid' => 505]);

// var_dump of the returned array:
/*
[
  'access_token'  => 'eyJh...',
  'refresh_token' => 'eyXF...',
  'expires_in'    => 3600
]
*/
```

## Validation Decoding (_decode_)

This method emits a heavy error `exception` for fast Try/Catch use or root middleware.
It not only verifies the mathematical integrity of the Hash but also checks if the `exp` listed within the current payload is greater than the local PHP `time()`.

```php
try {
   $client_payload = $this->jwt->decode($your_token_string);
} catch (\Exception $e) {
   // Caught if Forged or Time Exceeded
   echo $e->getMessage();
}
```

## Full Practical Example

If you have a mobile app and it is performing the "Login" process for the first time against your Back-End to grab the fixed navigation key:

```php
public function performLogin() {
    // 1. Did the app validate email and password? (Simulation)
    $email = $this->input->post('email');
    $password = $this->input->post('password');
    
    // Model searches the database...
    $user = $this->db->get_where('users', ['email' => $email])->fetchOne();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // 2. Transforms the successful credentials into the Secure Tokens
        $keys = $this->jwt->issueTokens([
            'id' => $user['id'],
            'role' => $user['account_type']
        ]);
        
        // 3. Returns to the app (The app will save the Strings internally on the phone)
        $this->response(['status' => 'logged', 'auth' => $keys]);
    }
    
    $this->response(['error' => 'Invalid Credentials'], 401);
}
```
