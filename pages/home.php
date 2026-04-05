<?php
/**
 * pages/home.php — Homepage
 */

require_once __DIR__ . '/../config/db.php';

$page_title  = '首頁';
$page_active = 'home';

$user = session_get_user();

// Load progress if logged in
$progress = ['sushe' => [], 'hanyu' => []];
if ($user) {
    try {
        $rows = db_query('SELECT author_id, level, stars FROM user_progress WHERE user_id = ?', [$user['id']])->fetchAll();
        foreach ($rows as $r) {
            $progress[$r['author_id']][$r['level']] = (int)$r['stars'];
        }
    } catch (PDOException $e) {}
}

function stars_for_author(array $prog): int {
    return array_sum($prog);
}
function levels_done(array $prog): int {
    return count(array_filter($prog, fn($s) => $s >= 1));
}

include __DIR__ . '/../includes/header.php';
?>
<div class="px-4 md:px-8 py-6 max-w-4xl mx-auto">
  <!-- Hero -->
  <div class="text-center mb-10">
    <h1 class="text-4xl md:text-5xl font-bold text-ink mb-3" style="letter-spacing:0.15em;">文 樞</h1>
    <p class="text-gold text-lg tracking-widest mb-1">Mensyu</p>
    <p class="text-ink opacity-70 text-sm max-w-lg mx-auto">專為 DSE 學生設計的文言文互動學習平台<br>AI 翻譯 · 遊戲學習 · 古人茶館</p>
  </div>

  <!-- Author cards -->
  <h2 class="text-xl font-bold text-ink mb-4">📖 學習關卡</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-10">
    <?php foreach ([
        'sushe' => ['name' => '蘇軾', 'sub' => 'Su Shi · 宋代', 'color' => 'from-amber-800 to-yellow-700', 'emoji' => '🖌️'],
        'hanyu' => ['name' => '韓愈', 'sub' => 'Han Yu · 唐代', 'color' => 'from-stone-700 to-gray-600',   'emoji' => '📜'],
    ] as $aid => $a):
        $prog  = $progress[$aid];
        $done  = levels_done($prog);
        $stars = stars_for_author($prog);
        $pct   = round($done / 4 * 100);
    ?>
    <a href="/learning?author=<?= $aid ?>" class="group block rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-shadow">
      <div class="bg-gradient-to-br <?= $a['color'] ?> text-white p-5">
        <div class="flex justify-between items-start">
          <div>
            <div class="text-3xl mb-1"><?= $a['emoji'] ?></div>
            <h3 class="text-2xl font-bold tracking-widest"><?= $a['name'] ?></h3>
            <p class="text-xs opacity-70"><?= $a['sub'] ?></p>
          </div>
          <!-- Progress circle -->
          <div class="relative w-16 h-16">
            <svg class="w-16 h-16 -rotate-90" viewBox="0 0 36 36">
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="3"/>
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="white" stroke-width="3"
                stroke-dasharray="<?= round($pct) ?> 100" stroke-linecap="round"/>
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-sm font-bold"><?= $done ?>/4</span>
          </div>
        </div>
        <div class="mt-3 text-xs opacity-80">
          ⭐ <?= $stars ?> 星 · 已完成 <?= $done ?>/4 關
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Quick links -->
  <h2 class="text-xl font-bold text-ink mb-4">⚡ 快速入口</h2>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-10">
    <?php foreach ([
        ['🔤', '翻譯', '/translate', 'bg-blue-50  hover:bg-blue-100'],
        ['🎮', '遊戲廳', '/games',    'bg-green-50 hover:bg-green-100'],
        ['🍵', '茶館',   '/teahouse', 'bg-amber-50 hover:bg-amber-100'],
        ['📚', '範文列表','/translate#list','bg-rose-50  hover:bg-rose-100'],
    ] as [$icon, $label, $url, $cls]): ?>
    <a href="<?= $url ?>" class="<?= $cls ?> rounded-xl p-4 flex flex-col items-center gap-2 transition-colors text-ink">
      <span class="text-3xl"><?= $icon ?></span>
      <span class="text-sm font-medium"><?= $label ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- DSE essay list preview -->
  <h2 class="text-xl font-bold text-ink mb-4">📋 DSE 指定範文（16 篇）</h2>
  <div id="essay-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
    <div class="col-span-full text-center text-ink opacity-40 py-4">載入中…</div>
  </div>
</div>

<script>
fetch('/api/essays?action=list')
  .then(r => r.json())
  .then(({data}) => {
    const el = document.getElementById('essay-list');
    el.innerHTML = data.map(e =>
      `<a href="/translate?essay_id=${e.id}" class="flex items-center gap-3 bg-white rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
         <span class="text-2xl">${genreIcon(e.genre)}</span>
         <div>
           <div class="font-semibold text-sm">${e.title}</div>
           <div class="text-xs text-gray-500">${e.author} · ${e.dynasty} · ${e.genre}</div>
         </div>
       </a>`
    ).join('');
  })
  .catch(() => {});

function genreIcon(g) {
  const m = {'詩':'🎵','詞':'🎼','史傳':'📜','記':'🏯','論說文':'⚖️','表':'📋','哲學散文':'🧘','語錄':'💬'};
  return m[g] || '📄';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
