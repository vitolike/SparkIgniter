<!-- app/views/welcome.php -->
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars('SparkIgniter') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial;color:#222;background:#f8fafc;margin:0;padding:40px}
      .card{max-width:760px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.05);padding:32px}
      h1{margin:0 0 8px;font-size:28px}
      code{background:#f1f5f9;padding:2px 6px;border-radius:6px}
      .muted{color:#6b7280}
      ul{line-height:1.7}
    </style>
  </head>
  <body>
    <div class="card">
      <h1><?= htmlspecialchars('SparkIgniter') ?></h1>
      <p class="muted">PHP <?= htmlspecialchars(PHP_VERSION) ?> • MVC • PDO PgSQL/MySQL</p>
    </div>
  </body>
</html>