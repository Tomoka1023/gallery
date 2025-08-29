<?php
// app/auth.php
require_once __DIR__.'/db.php';
require_once __DIR__.'/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();


function current_user() { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return isset($_SESSION['user']); }
function is_admin(): bool { return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin'; }


function login(string $username, string $password): bool {
global $pdo;
$st = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
$st->execute([$username]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) return false;
if (!password_verify($password, $u['password_hash'])) return false;
// 速度対策: hash再計算が推奨なら更新
if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
$new = password_hash($password, PASSWORD_DEFAULT);
$st2 = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
$st2->execute([$new, $u['id']]);
}
$_SESSION['user'] = ['id'=>$u['id'],'username'=>$u['username'],'role'=>$u['role']];
return true;
}


function logout(): void {
$_SESSION = [];
if (ini_get('session.use_cookies')) {
$params = session_get_cookie_params();
setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
}


function require_login(): void {
if (!is_logged_in()) { header('Location: login.php'); exit; }
}


function require_admin(): void {
if (!is_admin()) { http_response_code(403); echo 'Forbidden'; exit; }
}
?>