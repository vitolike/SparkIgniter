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
