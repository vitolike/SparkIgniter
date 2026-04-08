# Entity Generator (IdGenerator)

This library takes care of managing, encrypting, and issuing Unique Identification Strings or secure randomness used by your framework.

```php
// Manual Instantiation
$id = new IdGenerator();

// Or use via scope access
$this->idGen->uuid();
```

## Available Functions

### `guid()`
Pulls GUID for COM-based Windows systems. Or invokes alternatives with V4 in High Hexadecimal format.
*(Example: DB45C1A2-89B7...)*

### `uuid()` (Standard and Recommended)
Issues cryptographically secure Universal Unique Identifier v4 via `openssl_random_pseudo_bytes` or `random`.
*(Example: 550e8400-e29b-41d4-a716-446655440000)*

### `traceid()`
Unique support string (Trace ID). Fixed format: **LLLLLLDDXXXX** (6 Random Letters, 2 Random Digits, and 4 Final Fixed Suffix).
*(Example: AXQZPT458921)* Ideal for receipts.

### `tokenHex(int $length)`
Generates a purely hexadecimal random token of your requested even length. Ideal for Database Sessions and backend-generated passwords.
```php
$token = $this->idGen->tokenHex(40);
```

### `tokenBase64(int $bytes)`
Returns pure BASE64 encoded Strings _URL_SAFE_ (Without `+ / =`).

### `hashShortUrl(int $length = 8)`
Generates small shorts like URL shorteners (Ex: Bitly). Be careful, strict limiters should not be used in passwords due to the small variation.

## Full Practical Example

If you need to generate a unique ID before saving a transaction and also generate a random confirmation token that will be sent via email:

```php
public function createTransaction() {
    // Let's say your Transactions Model uses primary UUID and not Auto-increment Integer...
    $new_uuid = $this->idGen->uuid();
    $email_token = $this->idGen->tokenHex(32); 
    
    // Inserting
    $this->db->insert('transactions', [
        'id'     => $new_uuid,
        'amount' => 500.00,
        'status' => 'pending',
        'token'  => $email_token
    ]);

    // The email_token (ex: a8b4cdef12...) would be sent in the user's URL.
    // The id (ex: 550e8400-e29b...) will be used in interactions with Gateways like Stripe or PayPal.
}
```
