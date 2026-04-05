<?php
/**
 * includes/header.php — Common HTML head + navigation
 * $page_title  (optional) — extra title text
 * $page_active (optional) — active nav key
 */

require_once __DIR__ . '/../includes/session.php';
$_user        = session_get_user();
$_csrf        = csrf_token_generate();
$_page_title  = isset($page_title)  ? ' — ' . $page_title : '';
$_page_active = $page_active ?? '';

$nav_items = [
    'home'      => ['label' => '首頁',     'icon' => '🏠', 'url' => '/'],
    'learning'  => ['label' => '學習',     'icon' => '📖', 'url' => '/learning'],
    'games'     => ['label' => '遊戲廳',   'icon' => '🎮', 'url' => '/games'],
    'teahouse'  => ['label' => '古人茶館', 'icon' => '🍵', 'url' => '/teahouse'],
    'translate' => ['label' => '翻譯',     'icon' => '🔤', 'url' => '/translate'],
    'profile'   => ['label' => '個人',     'icon' => '👤', 'url' => '/profile'],
];

// Admin nav entry (only shown to admin users)
if (!empty($_user['is_admin'])) {
    $nav_items['admin'] = ['label' => '管理', 'icon' => '⚙️', 'url' => '/admin'];
}
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>文樞 Mensyu - DSE文言文學習平台<?= htmlspecialchars($_page_title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="文樞 Mensyu 是專為 DSE 學生設計的文言文互動學習平台，包含 16 篇 DSE 指定範文、AI 翻譯、遊戲學習、古人聊天等功能。">
  <meta name="keywords" content="DSE 文言文, 文言翻譯, 廉頗藺相如, 師說, 岳陽樓記, 蘇軾, 韓愈, 文言遊戲">
  <meta property="og:title" content="文樞 Mensyu - DSE文言文學習平台">
  <meta property="og:description" content="專為 DSE 學生設計的文言文互動學習平台">
  <meta property="og:type" content="website">
  <link rel="canonical" href="https://mensyu.infinityfreeapp.com<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES, 'UTF-8') ?>">
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"EducationalApplication","name":"文樞 Mensyu","description":"DSE 文言文互動學習平台","applicationCategory":"EducationApplication","inLanguage":"zh-HK"}
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            ink:    { DEFAULT: '#1a1208', light: '#3d2b1f' },
            paper:  { DEFAULT: '#f5efe0', dark: '#e8dfc8' },
            gold:   { DEFAULT: '#c9a84c', light: '#e8c97a' },
            brush:  { DEFAULT: '#8b1a1a', light: '#c0392b' },
          },
          fontFamily: {
            serif: ['"Noto Serif TC"', 'serif'],
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Noto Serif TC', serif; background-color: #f5efe0; color: #1a1208; }
    .nav-active { color: #c9a84c; border-color: #c9a84c; }
    /* Mobile bottom nav spacing */
    @media (max-width: 767px) { main { padding-bottom: 5rem; } }
  </style>
</head>
<body class="min-h-screen">

<!-- Desktop sidebar -->
<aside class="hidden md:flex flex-col fixed top-0 left-0 h-full w-52 bg-ink text-paper z-40 py-6 px-4">
  <a href="/" class="text-gold text-2xl font-bold mb-8 tracking-widest">文樞</a>
  <nav class="flex flex-col gap-1">
    <?php foreach ($nav_items as $key => $item): ?>
    <a href="<?= $item['url'] ?>"
       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors hover:bg-ink-light <?= $_page_active === $key ? 'text-gold font-semibold' : 'text-paper opacity-80 hover:opacity-100' ?>">
      <span><?= $item['icon'] ?></span>
      <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="mt-auto text-xs opacity-40 pt-4">
    <?php if ($_user): ?>
      <div class="mb-2 text-paper">👤 <?= db_escape($_user['username']) ?></div>
      <a href="/profile" class="block text-paper hover:text-gold mb-1">個人主頁</a>
      <form method="post" action='/api/auth.php' class="inline">
        <input type="hidden" name="action" value="logout">
        <input type="hidden" name="csrf_token" value="<?= $_csrf ?>">
        <button type="submit" class="text-paper hover:text-brush-light">登出</button>
      </form>
    <?php else: ?>
      <a href="/login" class="block text-paper hover:text-gold mb-1">登入</a>
      <a href="/register" class="text-paper hover:text-gold">注冊</a>
    <?php endif; ?>
  </div>
</aside>

<!-- Main content wrapper -->
<div class="md:ml-52">
<main class="min-h-screen">
<?php // page content follows ?>

<!-- Mobile bottom tab bar -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-ink flex justify-around items-center h-16 z-40 border-t border-gold border-opacity-30">
  <?php foreach ($nav_items as $key => $item): ?>
  <a href="<?= $item['url'] ?>"
     class="flex flex-col items-center gap-0.5 text-xs <?= $_page_active === $key ? 'text-gold' : 'text-paper opacity-60' ?>">
    <span class="text-xl"><?= $item['icon'] ?></span>
    <span><?= $item['label'] ?></span>
  </a>
  <?php endforeach; ?>
</nav>
