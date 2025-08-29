<?php
require_once __DIR__.'/../app/functions.php';
require_once __DIR__.'/../app/auth.php';
$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
if (!csrf_validate($_POST['csrf'] ?? '')) { $err='Invalid CSRF'; }
else {
$u = trim($_POST['username'] ?? '');
$p = (string)($_POST['password'] ?? '');
if (login($u,$p)) { header('Location: index.php'); exit; }
else { $err = 'ユーザー名またはパスワードが違います'; }
}
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="icon" href="assets/favicon.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=DotGothic16&family=Hachi+Maru+Pop&family=Hina+Mincho&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&family=Yomogi&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <h1>Login</h1>
    <nav>
        <a href="index.php">Back</a>
    </nav>
</header>
<?php if($err):?><div class="alert error"><?=htmlspecialchars($err)?></div><?php endif; ?>
<form method="post" class="card">
<input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
<label>Username<input type="text" name="username" required></label>
<label>Password<input type="password" name="password" required></label>
<button>Login</button>
</form>
</body>
</html>