<?php
require_once __DIR__.'/../app/functions.php';
require_once __DIR__.'/../app/csrf.php';
require_once __DIR__.'/../app/auth.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
if (!csrf_validate($_POST['csrf'] ?? '')) { http_response_code(403); exit('CSRF'); }
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('UPDATE images SET is_deleted=1 WHERE id=?');
$st->execute([$id]);
header('Location: index.php');