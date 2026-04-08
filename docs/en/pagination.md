# Pagination (Pagination)

The `Pagination` class is a native core library of SparkIgniter that allows generating numerical pagination links easily. It maintains the architecture, similarities, and classic facilities found in CodeIgniter 3. And the best part: it already comes standardized for Bootstrap 4 and 5 HTML/CSS classes (`page-item`, `page-link`), making its use completely *Plug and Play*.

## How to Load and Initialize

Because it is a core class, we can invoke it in our controllers through the **Loader**, in the same way we load standard framework utilities.

```php
// Load the class from the Controller
$this->load->library('Pagination');

// Minimum configuration array
$config = [
    'base_url'   => 'http://localhost/your-project/public/products/list',
    'total_rows' => 200, // Inform the global total of records from this table
    'per_page'   => 10,  // How many items you want to display per page
];

// Initialize and fill the configs in the engine
$this->pagination->initialize($config);

// Generate the HTML string ready for the view:
$linksHTML = $this->pagination->create_links();
```

## Routing Options (URL and Parameters)

Pagination supports two main tracking architectures and URLs formation, defined by the variable `$config['page_query_string']`.

### The Standard (Via Query Strings)
By default (`true`), SparkIgniter works natively adding variables to the end of the current URL, merging seamlessly without destroying properties that already existed in your URL (such as GET search filters).

```php
$config['page_query_string'] = true; // (Active by default)
$config['query_string_segment'] = 'page'; // The logical name of your parameter in the URL

$this->pagination->initialize($config);
```

This will generate segmented links respecting previous variables.
> **Example:** `http://localhost/site/list?filter=cars&page=2`

### Via URL Segments (Classic CI3 Standard)
If your route is structured and treats the read path itself as the page that will be captured completely, you can turn off the use of Query String:

```php
$config['page_query_string'] = false;
```

This will create clean routings based on the informed Base URL, cleanly appending the numbering at the end.
> **Example:** `http://localhost/site/list/2`

## Customize Your Way (Styles)

Bootstrap variables are already defined in the base code, but you have broad freedom to modify the HTML visual rendering by rewriting the properties upon initialization.

```php
$config['first_link'] = 'First Page';
$config['last_link'] = 'Last Page';
$config['next_link'] = 'Next ->';
$config['prev_link'] = '<- Previous';

// Changing colors, classes or adding different tags to the current Active Link
$config['cur_tag_open']   = '<li class="my-different-class"><a href="#">';
$config['cur_tag_close']  = '</a></li>';

$this->pagination->initialize($config);
```

## Full Implementation (with QueryBuilder)

In the real-life workflow, we use the numbers calculated by Pagination as our database query clippings reference (also known as the OFFSET and LIMIT blocks of `Query Builder`). Our API exposes the requesting user's page through the internal variable `$cur_page`.

Check the ideal flow:

```php
$this->load->library('Pagination');

// 1. Fetch the REAL total of items from the table in DB
$totalRows = $this->qb->query("SELECT COUNT(*) as qty FROM logs")->fetchOne();

// 2. Full Pagination setup
$config = [
    'base_url'   => 'http://localhost/site/logs',
    'total_rows' => $totalRows['qty'],
    'per_page'   => 15 // Each rendered block will show 15 at a time
];
$this->pagination->initialize($config);

// 3. Assemble the temporal margin / offset
$currentPage = $this->pagination->cur_page;
$limit = $config['per_page'];
$offset = ($currentPage - 1) * $limit;

// 4. Fetch only the database "slice" referring to the requested view
$items = $this->qb->from('logs')
                  ->order_by('id', 'DESC')
                  ->limit($limit, $offset)
                  ->get();

// 5. Insert the bound records and magic HTML links to your View
$this->view('logs_screen', [
    'records'           => $items,
    'pagination_links'  => $this->pagination->create_links()
]);
```
