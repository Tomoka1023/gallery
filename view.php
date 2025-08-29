<?php
require_once __DIR__.'/../app/functions.php';
require_once __DIR__.'/../app/csrf.php';
require_once __DIR__.'/../app/auth.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM images WHERE id=? AND is_deleted=0');
$st->execute([$id]);
$img = $st->fetch(PDO::FETCH_ASSOC);
if (!$img) { http_response_code(404); echo 'Not found'; exit; }
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=htmlspecialchars($img['title'] ?: 'Image #'.$img['id'])?></title>
    <link rel="stylesheet" href="assets/style.css">
    <meta property="og:image" content="<?=htmlspecialchars('uploads/'.$img['file_name'])?>">
    <link rel="icon" href="assets/favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=DotGothic16&family=Hachi+Maru+Pop&family=Hina+Mincho&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&family=Yomogi&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <h1>
            <?=htmlspecialchars($img['title'] ?: 'Image')?>
        </h1>
        <nav>
            <a href="index.php" id="backLink">Back</a>
        </nav>
    </header>
    <main class="viewer">
        <img src="uploads/<?=htmlspecialchars($img['file_name'])?>" alt="">
        <p><?=nl2br(htmlspecialchars($img['description'] ?? ''))?></p>

        <?php if (is_admin()):?>
        <form action="delete.php" method="post" onsubmit="return confirm('削除しますか？');">
            <input type="hidden" name="id" value="<?=$img['id']?>">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
            <button class="danger">Delete</button>
        </form>
        <?php endif; ?>
    </main>
    <script src="assets/app.js"></script>
</body>
</html>