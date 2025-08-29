<?php
require_once __DIR__.'/../app/functions.php';
require_once __DIR__.'/../app/csrf.php';
require_once __DIR__.'/../app/auth.php';
if (!isset($GLOBALS['pdo'])) {
    require_once __DIR__.'/../app/db.php';
}
$pdo = $GLOBALS['pdo'];
require_login();
require_admin();

$err = $ok = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_validate($_POST['csrf'] ?? '')) {
    $err = 'Invalid CSRF token';
  } else {
    try {
      $title = trim($_POST['title'] ?? '');
      $desc  = trim($_POST['description'] ?? '');
      $album_slug = trim($_POST['album'] ?? '');
      $tags_str = trim($_POST['tags'] ?? '');
      $tagNames = array_filter(array_map('trim', preg_split('/[,\\s]+/', $tags_str)));

      $album_id = null;
      if ($album_slug !== '') {
        $st = $pdo->prepare('SELECT id FROM albums WHERE slug=?');
        $st->execute([$album_slug]);
        $album_id = $st->fetchColumn();
        if (!$album_id) {
          $st = $pdo->prepare('INSERT INTO albums(name,slug) VALUES(?,?)');
          $st->execute([$album_slug, $album_slug]);
          $album_id = $pdo->lastInsertId();
        }
      }

      if (!isset($_FILES['images'])) throw new RuntimeException('No files uploaded');

      $pdo->beginTransaction();
      $count=0;
      foreach ($_FILES['images']['error'] as $i=>$errCode) {
        if ($errCode === UPLOAD_ERR_NO_FILE) continue;
        if ($errCode !== UPLOAD_ERR_OK) throw new RuntimeException('Upload error code: '.$errCode);

        $tmp = $_FILES['images']['tmp_name'][$i];
        $mime = mime_content_type($tmp) ?: '';
        if (!is_allowed_image($tmp,$mime)) throw new RuntimeException('Unsupported or invalid image');
        if ($_FILES['images']['size'][$i] > (require __DIR__.'/../app/config.php')['upload']['max_bytes']) throw new RuntimeException('File too large');

        [$file,$thumb,$w,$h,$size] = save_image_and_thumb($tmp, $_FILES['images']['name'][$i], $mime);

        $st = $pdo->prepare('INSERT INTO images(album_id,title,description,file_name,mime,size_bytes,width,height,thumb_name) VALUES (?,?,?,?,?,?,?,?,?)');
        $st->execute([$album_id?:null,$title,$desc,$file,$mime,$size,$w,$h,$thumb]);
        $image_id = (int)$pdo->lastInsertId();

        if ($tagNames) {
          $tagIds = upsert_tags($pdo,$tagNames);
          $st2 = $pdo->prepare('INSERT IGNORE INTO image_tags(image_id,tag_id) VALUES (?,?)');
          foreach ($tagIds as $tid) { $st2->execute([$image_id,$tid]); }
        }
        $count++;
      }
      $pdo->commit();
      $ok = $count.' file(s) uploaded.';
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $err = $e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload</title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="icon" href="assets/favicon.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=DotGothic16&family=Hachi+Maru+Pop&family=Hina+Mincho&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&family=Yomogi&display=swap" rel="stylesheet">
</head>
<body>
<header><h1>Upload</h1><nav><a href="index.php">Back</a></nav></header>

<?php if ($err): ?><div class="alert error"><?=htmlspecialchars($err)?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert ok"><?=htmlspecialchars($ok)?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="card">
  <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">

  <label>Title (任意)
    <input type="text" name="title" maxlength="200">
  </label>

  <label>Description (任意)
    <textarea name="description" rows="3"></textarea>
  </label>

  <label>Album slug（例: travel-2025）
    <input type="text" name="album" maxlength="120" placeholder="optional">
  </label>

  <label>Tags（カンマ or スペース区切り）
    <input type="text" name="tags" placeholder="cat, night, portrait">
  </label>

  <label>Images
    <input type="file" name="images[]" accept="image/*" multiple required>
  </label>

  <button>Upload</button>
</form>
</body>
</html>
