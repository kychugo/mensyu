<?php
/**
 * pages/profile.php — User profile page
 */

require_once __DIR__ . '/../config/db.php';

$page_title  = '個人主頁';
$page_active = 'profile';
$user = session_get_user();

if (!$user) {
    header('Location: /login');
    exit;
}

// Load progress
$progress = ['sushe' => [], 'hanyu' => []];
try {
    $rows = db_query('SELECT author_id, level, stars FROM user_progress WHERE user_id = ?', [$user['id']])->fetchAll();
    foreach ($rows as $r) {
        $progress[$r['author_id']][$r['level']] = (int)$r['stars'];
    }
} catch (PDOException $e) {}

// Total stars
$total_stars = array_sum(array_merge(array_values($progress['sushe']), array_values($progress['hanyu'])));
$total_levels = count(array_filter(array_merge($progress['sushe'], $progress['hanyu']), fn($s) => $s >= 1));

include __DIR__ . '/../includes/header.php';
?>
<div class="px-4 md:px-8 py-6 max-w-2xl mx-auto">
  <!-- Profile header -->
  <div class="bg-gradient-to-br from-ink to-ink-light text-white rounded-2xl p-6 mb-6 flex items-center gap-5">
    <div class="w-16 h-16 rounded-full bg-gold text-ink flex items-center justify-center text-2xl font-bold shrink-0">
      <?= mb_substr(htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?>
    </div>
    <div>
      <h1 class="text-xl font-bold"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h1>
      <div class="text-yellow-300 text-sm mt-1">⭐ <?= $total_stars ?> 星 · 已完成 <?= $total_levels ?>/8 關</div>
    </div>
  </div>

  <!-- Progress per author -->
  <h2 class="text-lg font-bold text-ink mb-3">📖 學習進度</h2>
  <div class="space-y-4 mb-8">
    <?php
    $author_meta = [
        'sushe' => ['name' => '蘇軾', 'emoji' => '🖌️',
            'levels' => [1=>'記承天寺夜遊',2=>'永遇樂 并序',3=>'超然臺記',4=>'前赤壁賦']],
        'hanyu' => ['name' => '韓愈', 'emoji' => '📜',
            'levels' => [1=>'雜說四（馬說）',2=>'送孟東野序',3=>'答李翊書',4=>'祭十二郎文']],
    ];
    foreach ($author_meta as $aid => $meta):
        $prog = $progress[$aid];
        $done = count(array_filter($prog, fn($s) => $s >= 1));
        $pct  = round($done / 4 * 100);
    ?>
    <div class="bg-white rounded-xl shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <span class="text-2xl"><?= $meta['emoji'] ?></span>
          <span class="font-bold text-ink"><?= $meta['name'] ?></span>
        </div>
        <span class="text-sm text-gray-500"><?= $done ?>/4 關</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
        <div class="bg-gold h-2 rounded-full" style="width:<?= $pct ?>%"></div>
      </div>
      <div class="grid grid-cols-4 gap-2">
        <?php foreach ($meta['levels'] as $lvl => $title):
          $stars = $prog[$lvl] ?? 0;
        ?>
        <div class="text-center">
          <div class="text-xs text-gray-400 mb-1">第<?= $lvl ?>關</div>
          <div class="text-yellow-400"><?= str_repeat('⭐', $stars) ?: '☆' ?></div>
          <div class="text-xs text-gray-500 truncate"><?= mb_substr($title, 0, 4) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Achievements -->
  <h2 class="text-lg font-bold text-ink mb-3">🏅 成就徽章</h2>
  <div id="badges" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-8">
    <div class="col-span-full text-center text-gray-400 text-sm py-4 animate-pulse">載入中…</div>
  </div>

  <!-- Logout -->
  <div class="text-center">
    <form method="post" action='/api/auth.php'>
      <input type="hidden" name="action" value="logout">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token_generate(), ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit"
        class="text-sm text-brush-light hover:underline">登出帳戶</button>
    </form>
  </div>
</div>

<script>
fetch('/api/achievements.php?action=list')
  .then(r => r.json())
  .then(({success, data, all}) => {
    if (!success) return;
    const box = document.getElementById('badges');
    box.innerHTML = Object.entries(all).map(([bid, b]) => {
      const earned = data[bid];
      const dim = earned ? '' : 'opacity-40 grayscale';
      const dt  = earned ? new Date(earned).toLocaleDateString('zh-HK') : '未解鎖';
      return `<div class="bg-white rounded-xl p-3 text-center shadow-sm ${dim}">
        <div class="text-3xl mb-1">${b.icon}</div>
        <div class="text-sm font-semibold text-ink">${b.name}</div>
        <div class="text-xs text-gray-400 mt-1">${b.desc}</div>
        <div class="text-xs text-gray-300 mt-1">${dt}</div>
      </div>`;
    }).join('');
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
