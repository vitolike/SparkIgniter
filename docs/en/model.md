# Base Model (Model)

The Core `Model` is an abstract support class to give your structure the necessary tools to manipulate database data without rewriting anything. Your concrete classes should inherit from this class.

It automatically passes you your `PDO` database connection (`$this->db`) and its own independent instance of the powerful QueryBuilder (`$this->qb`).

## How to create your Model

Assign the main Model as an extension and define the name of your mandatory protected table:

```php
class UserModel extends Model {
    protected string $table = 'users';

    // You don't need to create a constructor, the Base already handles BD injection!
}
```

## Built-in Methods

You already inherit these useful routines "for free" in your new object:

### Dynamic Access (`find`)
Pulls only 1 record from the said table based on the primary key.

```php
$model = new UserModel($pdo);

// SELECT * FROM users WHERE id = 5 LIMIT 1
$user = $model->find(5);

// You can pass the custom key if your PK is not "id"
$user = $model->find('AB-123', 'secret_uuid');
```

### Automatic Listing (`all`)
Allows pulling everyone from the table in a raw way, having native limit and offset features from the table itself.
```php
$users = $model->all(50, 0); // Limit 50
```

### Simple Insertions (`insert`)
Inserts associative array keys/values ignoring the physical bonds of QueryBuilder and returns the numerical ID created (incorporates adaptations for POSTGRES `RETURNING id` or MYSQL `lastInsertId`).

```php
$newId = $model->insert([
    'name' => 'Maria',
    'status' => 'active'
]);
```

### Updating (`update`) *(Work in Progress)*
> Raw update still has a basic defined structure, for elaborated updates prioritize using your internal QueryBuilder `$this->qb->update(...)`!

## Full Practical Example

Usually, we create customized methods in the Model that group very specific business rules to avoid dirtying the Controller, taking advantage of the active `$this->qb`:

```php
// app/models/InventoryModel.php
namespace App\Models;

use Core\Model;

class InventoryModel extends Model {
    protected string $table = 'inventory_products';

    // A Model-focused function that solves a business pain point using the QueryBuilder
    public function getMissingItems($minimum_qty = 5) {
        return $this->qb
                    ->from($this->table)
                    ->whereOp('qty_available', '<', $minimum_qty)
                    ->where(['active' => 1])
                    ->order_by('id', 'ASC')
                    ->get();
    }
    
    // Method to decrease inventory that uses raw Queries and PDO Transactions via Base Model
    public function sellItem($product_id, $qty_sold) {
        // Using PDO access to avoid locking the wrong table
        $pdo = $this->db;
        
        try {
            $pdo->beginTransaction();
            $this->qb->setRaw('qty_available', "qty_available - $qty_sold")
                     ->update($this->table, ['modified' => 1], ['id' => $product_id]);
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
```
