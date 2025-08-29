<?php
// app/functions.php

// DBと設定を読み込む（A方式でも使えるようにしておく）
require_once __DIR__.'/db.php';
$config = require __DIR__.'/config.php';

/**
 * 必要なディレクトリを作成
 */
function ensure_dirs() {
  global $config;
  foreach (['uploads','thumbs'] as $k) {
    if (!is_dir($config['paths'][$k])) {
      mkdir($config['paths'][$k], 0755, true);
    }
  }
}

/**
 * 画像のMIME/実体チェック（壊れ画像・偽装を弾く）
 */
function is_allowed_image($tmpPath, $mime) {
  global $config;
  if (!in_array($mime, $config['upload']['allowed_mime'], true)) return false;
  $info = @getimagesize($tmpPath);   // 実画像かどうか
  if (!$info) return false;
  return true;
}

/**
 * 原寸保存＆サムネ生成（GD）
 * @return array [$fileName,$thumbName,$w,$h,$size]
 */
function save_image_and_thumb($tmpPath, $origName, $mime) {
  global $config;
  ensure_dirs();

  $safeBase = bin2hex(random_bytes(8));
  $ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    default      => 'bin'
  };
  $fileName  = $safeBase . '.' . $ext;
  $thumbName = $safeBase . '_t.' . $ext;

  // 原寸保存
  $dest = $config['paths']['uploads'].'/'.$fileName;
  if (!move_uploaded_file($tmpPath, $dest)) {
    throw new RuntimeException('Failed to move uploaded file.');
  }

  // 情報取得
  [$w,$h] = getimagesize($dest);
  $size = filesize($dest);

  // サムネ作成
  [$tw,$th] = calc_fit($w,$h,$config['upload']['thumb']['max_w'],$config['upload']['thumb']['max_h']);
  $src = @imagecreatefromstring(file_get_contents($dest));
  $dst = imagecreatetruecolor($tw,$th);
  imagealphablending($dst, false);
  imagesavealpha($dst, true);
  imagecopyresampled($dst, $src, 0,0,0,0, $tw,$th,$w,$h);

  $thumbPath = $config['paths']['thumbs'].'/'.$thumbName;
  switch ($mime) {
    case 'image/jpeg': imagejpeg($dst, $thumbPath, $config['upload']['thumb']['quality']); break;
    case 'image/png':  imagepng($dst,  $thumbPath); break;
    case 'image/gif':  imagegif($dst,  $thumbPath); break;
    case 'image/webp': imagewebp($dst, $thumbPath, $config['upload']['thumb']['quality']); break;
    default:           imagejpeg($dst, $thumbPath, $config['upload']['thumb']['quality']);
  }
  imagedestroy($src); imagedestroy($dst);

  return [$fileName,$thumbName,$w,$h,$size];
}

/**
 * 指定最大サイズに収まる寸法を計算
 */
function calc_fit($w,$h,$maxW,$maxH) {
  $r = min($maxW/$w, $maxH/$h);
  return [max(1,(int)($w*$r)), max(1,(int)($h*$r))];
}

/**
 * タグを存在しなければ追加してID配列で返す
 */
function upsert_tags(PDO $pdo, array $tagNames): array {
  $ids = [];
  $stmtSel = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
  $stmtIns = $pdo->prepare('INSERT INTO tags(name) VALUES (?)');
  foreach ($tagNames as $t) {
    $t = trim(mb_strtolower($t));
    if ($t==='') continue;
    $stmtSel->execute([$t]);
    $id = $stmtSel->fetchColumn();
    if (!$id) {
      $stmtIns->execute([$t]);
      $id = $pdo->lastInsertId();
    }
    $ids[] = (int)$id;
  }
  return array_values(array_unique($ids));
}
?>