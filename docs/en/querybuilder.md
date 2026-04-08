# Using QueryBuilder (SparkIgniter)

The SparkIgniter `DB` class provides a clean, SQL Injection safe (using PDO native Prepared Statements) and highly efficient interface to build and execute database queries.

## Instantiation

Usually, the Query Builder is already injected into your base Controllers and Models directly through the main Database instance, but the class requires the `$pdo` upon instantiation:

```php
$pdo = Database::getInstance();
$db = new DB($pdo);
// If you are in a Controller/Model, you possibly already have access via $this->db
```

---

## 🔍 Selection Queries (SELECT)

### Returning Multiple Results (`get`)

Use `get()` to finish setting up the query and retrieve all records functionally as an Associative Array.

```php
$users = $db->select('id, name, email')
            ->from('users')
            ->get();
```
*If you omit `select()`, it fetches all fields (`*`) by default.*
You can also pass the table straightforward to `get()`:

```php
$users = $db->get('users'); // Does "SELECT * FROM users"
```

### Returning a Single Result (`fetchOne`)

Brings the first row found. The method has a natural pre-integrated search limitation to maximize speed (`LIMIT 1`).

```php
$user = $db->select('name, email')
           ->from('users')
           ->where(['id' => 5])
           ->fetchOne();
```

### The `get_where` Helper Method

A quick way to retrieve data with multiple equality checks without chaining multiple methods.

```php
// SELECT * FROM users WHERE status = 'active' AND block = 0
$actives = $db->get_where('users', ['status' => 'active', 'block' => 0]); 
```

---

## 🎛 WHERE Clauses (Filters)

The query builder offers an enormous range of filters. All are parameterized to protect against SQL Injection.

### Optimized Equality (`where`)
```php
$db->where(['status' => 'active', 'role' => 'admin']);
// Generates: WHERE status = :p0 AND role = :p1
```

### Comparison Operators (`whereOp`)
Ideal for `=`, `>`, `<`, `>=`, `<=`, `<>`, `!=`.

```php
$db->whereOp('age', '>=', 18);
// Generates: WHERE age >= :p0
```

### Where In (`whereIn`)
Search in the provided list.

```php
$db->whereIn('role', ['admin', 'manager', 'editor']);
// Third argument as true inverses to NOT IN
$db->whereIn('role', ['banned'], true);
```

### Pattern Search (`whereLike`)
```php
$db->whereLike('email', '%@gmail.com');
```

### Between Block (`whereBetween`)
```php
$db->whereBetween('created_at', '2023-01-01', '2023-12-31');
```

### Raw/Custom Condition (`whereRaw`)
Useful if you need to use advanced database functions in the clause.
```php
$db->whereRaw('YEAR(created_at) = :year', [':year' => 2023]);
```

---

## 🖇 Joins (Table Merging)

Keep control of prefixes (E.g.: `u.id`) utilizing secure aliases inside the tables.

```php
$users = $db->select('u.name, p.title as post_title')
            ->from('users u') // You can use alias!
            ->join('posts p', 'u.id', '=', 'p.user_id', 'LEFT')
            ->get();
```

If you need very complex joins (e.g.: multiple AND/OR within the ON checks):
```php
$db->joinRaw('LEFT JOIN orders o ON o.user_id = u.id AND o.status = "paid"');
```

---

## 🔢 Sorting and Limits

To limit or sort lines, the following methods are used:

### Ordering (`order_by`)
```php
$db->order_by('created_at', 'DESC');
```

### Limit Results (`limit`)
The first param is the maximum limit results. Optionally, to perform pagination, add the offset in the 2nd argument:
```php
$db->limit(10, 20); // LIMIT 10 OFFSET 20
```

---

## ✍️ Inserting, Updating and Deleting

### Insert (`insert`)
Inserts into the DB reverting boolean logic `true`/`false`. The newly generated id can be acquired in the scope helper method.

```php
$success = $db->insert('users', [
    'name'  => 'John',
    'email' => 'john@email.com',
    'age'   => 25
]);

if ($success) {
    echo "Generated ID: " . $db->lastInsertId();
}
```

### Update (`update`)
Specify the columns/values that go to SET clause and WHERE limitations.

```php
$db->update(
     'users',                      // table
     ['status' => 'deactivated'],  // columns for SET
     ['id' => 5]                   // conditions for WHERE (equality)
);
```

#### Utilizing Functions or Dynamic Operations (`setRaw`)
Sometimes you need something like `views = views + 1` natively, and you can't declare it via standard PDO string injection.
```php
// IMPORTANT: Declare setRaw BEFORE ->update()
$db->setRaw('clicks', 'clicks + 1')
   ->update('posts', ['edited' => 1], ['id' => 10]);
```

### Delete (`delete`)
Deletes registers according safely attached restrictions PDO.

```php
$db->delete('users', ['id' => 5, 'status' => 'banned']);
```

---

## 🛠 Raw Queries and Utilities

If the QueryBuilder doesn't support you in ultra generic specific searches, you safely run your own free complex queries maintaining "prepared statements":

```php
$query = "SELECT * FROM reports WHERE type = :type AND total > :min";

// PDO Statement with custom optimization
$stmt = $db->query($query, [
    ':type' => 'annual',
    ':min' => 5000
]);

$results = $stmt->fetchAll();
```

### Auxiliary Result Functions
Acknowledge amount of impacted lines (great for visual report validation scopes):

```php
// Returns lines affected by deepest DELETE, UPDATE or INSERT
$qty = $db->rowCount(); 

// Pull the direct raw PDO instance to natively trigger global events inside
$pdo_instance = $db->pdo();
```
