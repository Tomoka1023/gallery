<?php
require_once __DIR__.'/../app/functions.php';
require_once __DIR__.'/../app/csrf.php';

// A方式の保険：pdo未設定ならdbを読む
if (!isset($GLOBALS['pdo'])) {
    require_once __DIR__.'/../app/db.php';
}
$pdo = $GLOBALS['pdo']; // ここで必ずPDOが入る

// ===== 検索・ページネーション用の初期化（未定義警告を防ぐ） =====
$q     = isset($_GET['q'])     ? trim($_GET['q'])     : '';
$album = isset($_GET['album']) ? trim($_GET['album']) : '';
$page  = isset($_GET['page'])  ? max(1, (int)$_GET['page']) : 1;

$perPage = 20;
$offset  = ($page - 1) * $perPage;

// ===== クエリ構築 =====
$where  = ['is_deleted = 0'];
$params = [];

if ($q !== '') {
		$where[] = '(title LIKE ? OR description LIKE ?
				OR EXISTS (
						SELECT 1 FROM image_tags it
						JOIN tags t ON it.tag_id = t.id
						WHERE it.image_id = images.id AND t.name LIKE ?
				)
				OR EXISTS (
						SELECT 1 FROM albums a2
						WHERE a2.id = images.album_id AND (a2.name LIKE ? OR a2.slug LIKE ?)
				)
		)';
		$params[] = "%$q%";
		$params[] = "%$q%";
		$params[] = "%$q%";
		$params[] = "%$q%";
		$params[] = "%$q%";
}

if ($album !== '') {
		$where[] = 'album_id = (SELECT id FROM albums WHERE slug = ? OR name = ? LIMIT 1)';
		$params[] = $album;
		$params[] = $album;
}

$sqlCount = 'SELECT COUNT(*) FROM images WHERE '.implode(' AND ', $where);
$st = $pdo->prepare($sqlCount);
$st->execute($params);
$total = (int)$st->fetchColumn();

$sql = 'SELECT images.*, COALESCE(a.name, "No Album") as album_name
        FROM images
        LEFT JOIN albums a ON a.id = images.album_id
        WHERE '.implode(' AND ', $where).'
        ORDER BY created_at DESC
        LIMIT '.$perPage.' OFFSET '.$offset;
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$pages = max(1, (int)ceil($total / $perPage));
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TOMOKA's Gallery</title>
  <link rel="stylesheet" href="assets/style.css">
	<link rel="icon" href="assets/favicon.png" type="image/png">
	<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=DotGothic16&family=Hachi+Maru+Pop&family=Hina+Mincho&family=Kaisei+Decol&family=M+PLUS+Rounded+1c&family=Yomogi&display=swap" rel="stylesheet">
</head>
<body>
<?php require_once __DIR__.'/../app/auth.php'; ?>
<header>
<div class="brand">
  <a href="index.php" class="brand-link">
		<span class="brand-text">TOMOKA's Gallery</span>
	</a>
</div>

  <nav>
		<?php if (is_admin()): ?><a href="upload.php">Upload</a><?php endif; ?>
		<?php if (is_logged_in()): ?>
			<span style="opacity:.7;">😊 <?=htmlspecialchars(current_user()['username'])?></span>
			<a href="logout.php">Logout</a>
		<?php else: ?>
			<a href="login.php">Login</a>
		<?php endif; ?>
	</nav>
  <form class="search" method="get">
    <input type="text" name="q" value="<?=htmlspecialchars($q, ENT_QUOTES)?>" placeholder="タイトル・説明・タグ・アルバム名 で検索">
		<input list="albums" name="album" value="<?=htmlspecialchars($album, ENT_QUOTES)?>" placeholder="アルバム（名前 or slug）">
		<datalist id="albums">
			<?php
				// 追加：アルバム一覧を取得して候補表示
				$alist = $pdo->query('SELECT name, slug FROM albums ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
				foreach ($alist as $a):
			?>
				<option value="<?=htmlspecialchars($a['name'])?>"><?=htmlspecialchars($a['slug'])?></option>
				<option value="<?=htmlspecialchars($a['slug'])?>"><?=htmlspecialchars($a['name'])?></option>
			<?php endforeach; ?>
  	</datalist>
    <button>検索</button>
  </form>
</header>

<main class="grid">
  <?php foreach ($rows as $r): ?>
    <a class="item" href="view.php?id=<?= $r['id'] ?>">
  		<img loading="lazy"
      	src="thumbs/<?=htmlspecialchars($r['thumb_name'])?>"
      	alt="<?=htmlspecialchars($r['title'] ?? '')?>"
      	data-lightbox>
      <div class="meta">
        <span class="title"><?=htmlspecialchars($r['title'] ?? '')?></span>
        <span class="album"><?=htmlspecialchars($r['album_name'])?></span>
      </div>
    </a>
  <?php endforeach; ?>
</main>

<?php if ($pages > 1): ?>
<nav class="pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a class="<?= $i === $page ? 'current' : '' ?>" href="?<?=http_build_query(['q'=>$q,'album'=>$album,'page'=>$i])?>"><?=$i?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>

<script src="assets/app.js"></script>
</body>
</html>
