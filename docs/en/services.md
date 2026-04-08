# Services Architecture (Services)

The Core `Service` has an extremely clear and minimalist purpose: to act as a non-routable "Mini Controller" for you to separate massive business logic from your Controller and ensure a cleaner SOA architectural structure in your project.

## What is a Service?

Controllers should only manage requests (call validation, ask partners for data, issue JWT). In case you need to issue invoices interlinking the HttpClient with the IdGenerator triggering massive inserts in the Model, **everything turns into spaghetti.**

In your default folder, create your "App/Services/NFGenerator.php" extended from the core class.

```php
class NFGenerator extends Service {
   // It has everything natively! ($this->input, $this->db, $this->qb, $this->idGen...)

   public function generateForClient($id) {
       $data = $this->qb->get_where('clients', ['id' => $id]);
       // Heavy logic separated keeping the controller intact
       return true;
   }
}
```

## How do we inject it into the Execution Controller?

Just use the loading core (`$this->load`):

```php
$this->load->service('NFGenerator');
$success = $this->NFGenerator->generateForClient($this->user['id']);
```

The `Services.php` loads the **Abstract Native Database Accesses `PDO` / `QB`**, **Multiple Token Generation (`IdGen`)** and the attached functions from the RestController Response Maker (`set_response` / `response`) without depending on the Framework's routes to interact and give Break Point/Exit to the Client!

## Full Practical Example

Let's say we have a subscription renewal rule that messes with the Database and an External API (Stripe). Putting this in the Controller would be chaotic. A `PaymentService` would be ideal:

```php
// app/services/PaymentService.php
namespace App\Services;

use Core\Service;

class PaymentService extends Service {

    public function renewSubscription($user_id) {
        $user = $this->qb->get_where('users', ['id' => $user_id])->fetchOne();
        
        // 1. Uses the Base Service HTTP Client to talk to Stripe
        $this->httpClient->setOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer sk_test_...']);
        $response = $this->httpClient->post('https://api.stripe.com/v1/charges', [
            'amount' => 2990, // $29.90
            'customer' => $user['stripe_id']
        ]);
        
        // 2. If it fails, uses native JSON output methods from Service to kill the request
        if ($response['status'] !== 200) {
            $this->response(['error' => 'Failed to charge the card at operator'], 402);
        }

        // 3. Updates the database using the embedded QueryBuilder
        $this->qb->update('users', ['due_date' => date('Y-m-d', strtotime('+30 days'))], ['id' => $user_id]);
        
        return true;
    }
}
```

And in your Controller, you just call it like this:
```php
public function renew() {
    $this->load->service('PaymentService');
    $this->PaymentService->renewSubscription($this->user['id']);
    
    $this->response(['status' => 'success']);
}
```
