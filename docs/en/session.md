# Session Controller (Session)

The State Manipulation API (`Session`) encapsulates the default hostile interactivity of PHP 8.4 `$_SESSION` under the shadow of a fully shielded "CodeIgniter" style wrapper.

## Naturally Applied Protections

Merely by opening the class, the Core injects automatic protection (XSS Cookie HttpOnly Flag Set, IP Address check-list, Strict-mode and HTTPS Secure Transfer flags) via interpreter internal init configuration without the need to edit `php.ini`.

It recreates the key via `session_regenerate_id()` to kill _Network sniff fixation_.

## Associations (Adding to the Local Web Session)

To save something for the next tab, redirection or page permanence:

```php
// Single Direct Setter
$this->session->set_userdata('user_plan', 'PREMIUM');

// Multiple Associative Array Setter
$this->session->set_userdata([
    'cart_count' => 15,
    'user_id'    => 44
]);
```

## Retrievals (Secure Getters)

Never directly query the raw bookshelf of the global `$_SESSION['key'];`.
Always opt for `$this->session->userdata('key');`. (So you don't get Warning Errors if it's absent from the server).

Checking logical presence for Views if/returns:

```php
if ($this->session->has_userdata('user_id')) {
    // Welcome back. We display the html top bar
} 
```

Return the whole table excluding the local internal core scan index token `__session_initialized`.

```php
$allMemoryArrayItems = $this->session->all_userdata();
```

## Revoking Data (Deletions)

Delete variables from the temporary site memory board to reset flows and empty bags for example.

```php
$this->session->unset_userdata('user_plan'); // removes from where we injected above
```

Destroying EVERYTHING from the client without exceptions with a complete purge of Linux/Windows Server flags and Cookie Expire Date forced for global retroactivity (Ex: End session Log-off).

```php
$this->session->sess_destroy();
```

## Full Practical Example

A very common use for server-side navigation sessions like ours is the temporary checking of the user, or displaying and clearing instant notification messages known as Flash Data:

```php
// app/controllers/DashboardController.php

public function index() {
    // 1. Checks if someone is actively accessing
    if (!$this->session->has_userdata('user_id')) {
        // Since the user has not logged in, we force them to return to the form and exit
        redirect('/login');
    }

    // 2. If previous rendered view failed and sent flash data error display
    $alert = null;
    if ($this->session->has_userdata('error_msg')) {
        $alert = $this->session->userdata('error_msg');
        // Once copied to the View, we remove it so it won't appear on F5
        $this->session->unset_userdata('error_msg');
    }

    $this->view('dashboard/home', [
        'name' => $this->session->userdata('logged_name'),
        'alert' => $alert
    ]);
}
```
