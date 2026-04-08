<?php
/**
 * pages/home.php — Landing page / Homepage
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

<style>
/* ── Home page design variables (v3.HTML palette) ─────────────────── */
:root {
  --hp-primary:   #7fb3d5;
  --hp-secondary: #a2d9ce;
  --hp-highlight: #7dcea0;
  --hp-accent:    #a9cce3;
  --hp-soft:      #76d7c4;
  --hp-light:     #aed6f1;
  --hp-text:      #2c3e50;
  --hp-dark:      #34495e;
  --hp-shadow-l:  0 2px 12px rgba(127,179,213,.15);
  --hp-shadow-m:  0 4px 24px rgba(127,179,213,.2);
  --hp-shadow-h:  0 8px 36px rgba(127,179,213,.25);
  --hp-grad-p:    linear-gradient(135deg, #7fb3d5, #a2d9ce);
  --hp-grad-s:    linear-gradient(135deg, #7dcea0, #76d7c4);
  --hp-grad-a:    linear-gradient(135deg, #a9cce3, #aed6f1);
  --hp-radius:    12px;
  --hp-trans:     all 0.3s cubic-bezier(.4,0,.2,1);
}

/* ── Hero ──────────────────────────────────────────────────────────── */
.hp-hero {
  background: var(--hp-grad-p);
  border-radius: var(--hp-radius);
  padding: 52px 32px;
  text-align: center;
  color: #fff;
  position: relative;
  overflow: hidden;
  margin-bottom: 40px;
  box-shadow: var(--hp-shadow-h);
}
.hp-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='20' cy='20' r='2' fill='rgba(255,255,255,.1)'/%3E%3Ccircle cx='180' cy='40' r='3' fill='rgba(255,255,255,.08)'/%3E%3Ccircle cx='100' cy='100' r='1.5' fill='rgba(255,255,255,.1)'/%3E%3Ccircle cx='160' cy='160' r='2.5' fill='rgba(255,255,255,.07)'/%3E%3Ccircle cx='40' cy='160' r='2' fill='rgba(255,255,255,.09)'/%3E%3C/svg%3E");
  pointer-events: none;
}
.hp-hero h1 {
  font-family: 'Noto Serif TC', serif;
  font-size: clamp(2.5rem, 6vw, 4rem);
  font-weight: 700;
  letter-spacing: 0.2em;
  margin-bottom: 8px;
  position: relative;
}
.hp-hero .subtitle {
  font-size: 1rem;
  opacity: .85;
  letter-spacing: 0.25em;
  margin-bottom: 24px;
}
.hp-hero .tagline {
  font-size: 1.05rem;
  opacity: .9;
  max-width: 560px;
  margin: 0 auto 32px;
  line-height: 1.7;
}
.hp-cta-group { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.hp-btn-primary {
  background: #fff;
  color: var(--hp-primary);
  font-weight: 700;
  font-size: 0.95rem;
  padding: 12px 28px;
  border-radius: 50px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: var(--hp-trans);
  box-shadow: 0 4px 14px rgba(0,0,0,.1);
  display: inline-flex; align-items: center; gap: 6px;
}
.hp-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.15); }
.hp-btn-outline {
  background: transparent;
  color: #fff;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 11px 26px;
  border-radius: 50px;
  border: 2px solid rgba(255,255,255,.7);
  cursor: pointer;
  text-decoration: none;
  transition: var(--hp-trans);
  display: inline-flex; align-items: center; gap: 6px;
}
.hp-btn-outline:hover { background: rgba(255,255,255,.15); border-color: #fff; }

/* ── Stats bar ─────────────────────────────────────────────────────── */
.hp-stats {
  background: #fff;
  border-radius: var(--hp-radius);
  box-shadow: var(--hp-shadow-l);
  padding: 24px 20px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 40px;
  border: 1px solid rgba(127,179,213,.1);
}
@media (max-width: 640px) { .hp-stats { grid-template-columns: 1fr; } }
.hp-stat-item { text-align: center; padding: 12px 8px; }
.hp-stat-num {
  font-family: 'Noto Serif TC', serif;
  font-size: 2rem; font-weight: 700;
  background: var(--hp-grad-p);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  display: block; margin-bottom: 4px;
}
.hp-stat-label { font-size: 0.78rem; color: var(--hp-dark); line-height: 1.4; }

/* ── Feature cards ─────────────────────────────────────────────────── */
.hp-features { margin-bottom: 40px; }
.hp-features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}
@media (max-width: 480px) { .hp-features-grid { grid-template-columns: 1fr; } }
.hp-feat-card {
  background: #fff;
  border-radius: var(--hp-radius);
  padding: 24px 20px;
  box-shadow: var(--hp-shadow-l);
  border: 2px solid transparent;
  transition: var(--hp-trans);
  text-decoration: none;
  display: block;
  position: relative; overflow: hidden;
}
.hp-feat-card::before {
  content:''; position:absolute; top:0; left:0; right:0; height:4px;
  background: var(--hp-grad-p);
}
.hp-feat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hp-shadow-m);
  border-color: rgba(127,179,213,.2);
}
.hp-feat-card.green::before  { background: var(--hp-grad-s); }
.hp-feat-card.accent::before { background: var(--hp-grad-a); }
.hp-feat-card.warm::before   { background: linear-gradient(135deg,#f39c12,#e67e22); }
.hp-feat-icon { font-size: 2.2rem; margin-bottom: 10px; display: block; }
.hp-feat-title { font-size: 1.05rem; font-weight: 700; color: var(--hp-text); margin-bottom: 6px; }
.hp-feat-desc  { font-size: 0.8rem; color: var(--hp-dark); line-height: 1.55; }
.hp-feat-badge {
  display: inline-block; margin-top: 10px;
  font-size: 0.7rem; font-weight: 600;
  padding: 3px 10px; border-radius: 20px;
  background: var(--hp-grad-p); color: #fff;
}
.hp-feat-card.green  .hp-feat-badge { background: var(--hp-grad-s); }
.hp-feat-card.accent .hp-feat-badge { background: var(--hp-grad-a); }
.hp-feat-card.warm   .hp-feat-badge { background: linear-gradient(135deg,#f39c12,#e67e22); }

/* ── Author cards ──────────────────────────────────────────────────── */
.hp-authors { margin-bottom: 40px; }
.hp-author-card {
  border-radius: var(--hp-radius);
  overflow: hidden;
  box-shadow: var(--hp-shadow-m);
  transition: var(--hp-trans);
  text-decoration: none;
  display: block;
}
.hp-author-card:hover { transform: translateY(-6px); box-shadow: var(--hp-shadow-h); }
.hp-author-header {
  padding: 28px 20px 22px;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 18px;
}
.hp-author-header.blue  { background: var(--hp-grad-p); }
.hp-author-header.green { background: var(--hp-grad-s); }
.hp-author-avatar {
  width: 72px; height: 72px;
  border-radius: 50%;
  border: 3px solid rgba(255,255,255,.5);
  background: rgba(255,255,255,.2);
  object-fit: cover;
  flex-shrink: 0;
}
.hp-author-avatar-placeholder {
  width: 72px; height: 72px;
  border-radius: 50%;
  border: 3px solid rgba(255,255,255,.5);
  background: rgba(255,255,255,.2);
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem;
}
.hp-author-name { font-family: 'Noto Serif TC', serif; font-size: 1.7rem; font-weight: 700; letter-spacing: .1em; }
.hp-author-sub  { font-size: 0.8rem; opacity: .8; margin-top: 2px; }
.hp-author-body { background: #fff; padding: 20px; }
.hp-progress-bar-wrap { background: rgba(127,179,213,.15); border-radius: 99px; height: 8px; margin-top: 10px; }
.hp-progress-bar      { height: 8px; border-radius: 99px; background: var(--hp-grad-p); transition: width .5s ease; }
.hp-author-card.green .hp-progress-bar { background: var(--hp-grad-s); }
.hp-levels-row { display: flex; gap: 8px; margin-top: 12px; }
.hp-level-dot {
  flex: 1; height: 6px; border-radius: 99px;
  background: rgba(127,179,213,.2);
  transition: var(--hp-trans);
}
.hp-level-dot.done    { background: var(--hp-grad-p); }
.hp-level-dot.partial { background: rgba(127,179,213,.5); }
.hp-author-card.green .hp-level-dot.done { background: var(--hp-grad-s); }

/* ── Problem section ────────────────────────────────────────────────── */
.hp-problem {
  background: linear-gradient(135deg, rgba(127,179,213,.08), rgba(162,217,206,.06));
  border: 1px solid rgba(127,179,213,.2);
  border-radius: var(--hp-radius);
  padding: 28px 24px;
  margin-bottom: 40px;
}
.hp-problem-header { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
.hp-problem-header h2 { font-size: 1.1rem; font-weight: 700; color: var(--hp-text); }
.hp-problem-ref { font-size: 0.7rem; color: #888; margin-left: auto; text-align: right; line-height: 1.4; }
.hp-stat-list { list-style: none; padding: 0; margin: 0; space-y: 10px; }
.hp-stat-list li {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid rgba(127,179,213,.1);
  font-size: 0.85rem; color: var(--hp-dark); line-height: 1.55;
}
.hp-stat-list li:last-child { border-bottom: none; }
.hp-stat-list .bullet {
  min-width: 24px; height: 24px;
  background: var(--hp-grad-p);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .65rem; color: #fff; font-weight: 700;
  margin-top: 1px;
}

/* ── Essay list ─────────────────────────────────────────────────────── */
.hp-essay-item {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border-radius: 10px;
  padding: 12px 14px;
  box-shadow: var(--hp-shadow-l);
  text-decoration: none; color: var(--hp-text);
  transition: var(--hp-trans);
  border: 1px solid transparent;
}
.hp-essay-item:hover { box-shadow: var(--hp-shadow-m); border-color: rgba(127,179,213,.2); transform: translateY(-2px); }
.hp-essay-icon { font-size: 1.5rem; flex-shrink: 0; }
.hp-essay-title  { font-size: 0.875rem; font-weight: 600; }
.hp-essay-meta   { font-size: 0.72rem; color: #888; margin-top: 2px; }

/* ── Section headings ───────────────────────────────────────────────── */
.hp-section-title {
  font-size: 1.15rem; font-weight: 700; color: var(--hp-text);
  margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
}
.hp-section-title::after {
  content: ''; flex: 1; height: 2px;
  background: linear-gradient(to right, rgba(127,179,213,.3), transparent);
  border-radius: 2px;
}
</style>

<div class="px-4 md:px-8 py-6 max-w-3xl mx-auto">

  <!-- ── Hero ─────────────────────────────────────────────────── -->
  <section class="hp-hero">
    <h1>文 樞</h1>
    <p class="subtitle">Mensyu · 古典文學互動學習平台</p>
    <p class="tagline">
      與古代文豪為友，透過遊戲化學習突破文言文障礙<br>
      <span style="font-size:.85rem;opacity:.75;">專為 DSE 學生設計 · AI 翻譯 · 關卡挑戰 · 古人茶館</span>
    </p>
    <div class="hp-cta-group">
      <a href="/learning" class="hp-btn-primary">📖 開始學習</a>
      <a href="/translate" class="hp-btn-outline">🔤 文言翻譯</a>
      <?php if (!$user): ?>
      <a href="/register" class="hp-btn-outline">✨ 免費註冊</a>
      <?php endif; ?>
    </div>
  </section>

  <!-- ── Stats bar (research-backed) ──────────────────────────── -->
  <section class="hp-stats">
    <div class="hp-stat-item">
      <span class="hp-stat-num">&gt;50%</span>
      <span class="hp-stat-label">中四學生不主動閱讀文言文</span>
    </div>
    <div class="hp-stat-item">
      <span class="hp-stat-num">38%</span>
      <span class="hp-stat-label">字詞認讀平均答對率</span>
    </div>
    <div class="hp-stat-item">
      <span class="hp-stat-num">27%</span>
      <span class="hp-stat-label">句式理解平均答對率</span>
    </div>
  </section>

  <!-- ── Features ──────────────────────────────────────────────── -->
  <section class="hp-features">
    <h2 class="hp-section-title">⚡ 平台功能</h2>
    <div class="hp-features-grid">
      <a href="/learning" class="hp-feat-card">
        <span class="hp-feat-icon">📖</span>
        <div class="hp-feat-title">關卡式學習</div>
        <div class="hp-feat-desc">選擇蘇軾或韓愈，逐關解鎖 DSE 指定範文，AI 逐字解析助你深入理解。</div>
        <span class="hp-feat-badge">學習</span>
      </a>
      <a href="/games" class="hp-feat-card green">
        <span class="hp-feat-icon">🎮</span>
        <div class="hp-feat-title">遊戲化學習</div>
        <div class="hp-feat-desc">文磚挑戰打磚塊，文言配對卡，寓學於樂，提升記憶字詞效率。</div>
        <span class="hp-feat-badge">遊戲</span>
      </a>
      <a href="/translate" class="hp-feat-card accent">
        <span class="hp-feat-icon">🔤</span>
        <div class="hp-feat-title">AI 逐字翻譯</div>
        <div class="hp-feat-desc">AI 逐句語譯 + 逐字解釋，常見文言字詞粗體標示，強化記憶。</div>
        <span class="hp-feat-badge">翻譯</span>
      </a>
      <a href="/teahouse" class="hp-feat-card warm">
        <span class="hp-feat-icon">🍵</span>
        <div class="hp-feat-title">古人茶館</div>
        <div class="hp-feat-desc">與 AI 扮演的古代文豪交流互動，在虛擬社群中暢所欲言。</div>
        <span class="hp-feat-badge">茶館</span>
      </a>
    </div>
  </section>

  <!-- ── Author cards ─────────────────────────────────────────── -->
  <section class="hp-authors">
    <h2 class="hp-section-title">📚 選擇文學導師</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <?php
      $authors = [
          'sushe' => [
              'name'   => '蘇軾',
              'sub'    => 'Su Shi · 北宋文學家',
              'class'  => 'blue',
              'img'    => 'https://i.ibb.co/wrhVfCjJ/image.png', // hosted on ibb.co — also used in v3.HTML
              'levels' => [1=>'記承天寺夜遊',2=>'永遇樂',3=>'超然臺記',4=>'前赤壁賦'],
          ],
          'hanyu' => [
              'name'   => '韓愈',
              'sub'    => 'Han Yu · 唐代文學家',
              'class'  => 'green',
              'img'    => 'https://i.ibb.co/LhqsVb40/image.png', // hosted on ibb.co — also used in v3.HTML
              'levels' => [1=>'馬說',2=>'送孟東野序',3=>'答李翊書',4=>'祭十二郎文'],
          ],
      ];
      foreach ($authors as $aid => $a):
          $prog  = $progress[$aid];
          $done  = levels_done($prog);
          $stars = stars_for_author($prog);
          $pct   = $done > 0 ? round($done / 4 * 100) : 0;
      ?>
      <a href="/learning?author=<?= $aid ?>" class="hp-author-card <?= $a['class'] ?>">
        <div class="hp-author-header <?= $a['class'] ?>">
          <img src="<?= htmlspecialchars($a['img'], ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?>頭像"
               class="hp-author-avatar"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
          <div class="hp-author-avatar-placeholder" style="display:none;">📜</div>
          <div>
            <div class="hp-author-name"><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="hp-author-sub"><?= htmlspecialchars($a['sub'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <?php if ($done > 0): ?>
          <div class="ml-auto text-right text-xs opacity-80">
            <div class="font-bold text-base"><?= $done ?>/4</div>
            <div>關卡</div>
          </div>
          <?php endif; ?>
        </div>
        <div class="hp-author-body">
          <div class="text-xs text-gray-500 mb-2">DSE 指定範文</div>
          <div class="flex flex-wrap gap-1 mb-3">
            <?php foreach ($a['levels'] as $lvl => $title): ?>
            <span class="text-xs px-2 py-0.5 rounded-full <?= isset($prog[$lvl]) && $prog[$lvl] >= 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
              <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php endforeach; ?>
          </div>
          <?php if ($user): ?>
          <div class="text-xs text-gray-400 mb-1">⭐ <?= $stars ?> 星 · <?= $done ?>/4 已完成</div>
          <div class="hp-progress-bar-wrap">
            <div class="hp-progress-bar" style="width:<?= $pct ?>%"></div>
          </div>
          <?php else: ?>
          <div class="text-xs text-gray-400">登入後追蹤學習進度</div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ── Problem / Research context ─────────────────────────── -->
  <section class="hp-problem">
    <div class="hp-problem-header">
      <span class="text-xl">💡</span>
      <h2>為什麼需要文樞？</h2>
      <div class="hp-problem-ref">
        來源：教育學報 2017<br>
        第 45 卷第 2 期，頁 161–181
      </div>
    </div>
    <ul class="hp-stat-list">
      <li>
        <span class="bullet">1</span>
        <span>超過 50% 中四學生<strong>不主動閱讀文言文</strong>，認為「枯燥乏味」、「與現實無關」</span>
      </li>
      <li>
        <span class="bullet">2</span>
        <span>454 名中四學生文言字詞認讀平均答對率僅 <strong>38.33%</strong>，句式理解僅 <strong>26.65%</strong></span>
      </li>
      <li>
        <span class="bullet">3</span>
        <span>學生依賴教師講解，缺乏語譯和閱讀策略，<strong>學習成效低</strong></span>
      </li>
    </ul>
    <div class="mt-4 p-3 rounded-lg text-xs" style="background:rgba(127,179,213,.1);color:var(--hp-dark);line-height:1.6;">
      <strong style="color:var(--hp-primary);">文樞的解決方案：</strong>
      以遊戲化學習（Gamified Learning）突破文言文學習障礙 —— 虛擬文豪對話、關卡挑戰，
      配合 AI 逐字翻譯，幫助學生在趣味互動中建立文言文理解能力。
    </div>
  </section>

  <!-- ── DSE essay list ───────────────────────────────────────── -->
  <section>
    <h2 class="hp-section-title">📋 DSE 指定文言範文</h2>
    <div id="essay-list" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
      <div class="col-span-full text-center text-gray-400 py-4 text-sm">載入中…</div>
    </div>
  </section>

</div>

<script>
fetch('/api/essays.php?action=list')
  .then(r => r.json())
  .then(({data}) => {
    if (!Array.isArray(data)) return;
    const el = document.getElementById('essay-list');
    el.innerHTML = data.map(e =>
      `<a href="/translate?essay_id=${e.id}" class="hp-essay-item">
         <span class="hp-essay-icon">${genreIcon(e.genre)}</span>
         <div>
           <div class="hp-essay-title">${esc(e.title)}</div>
           <div class="hp-essay-meta">${esc(e.author)} · ${esc(e.dynasty)} · ${esc(e.genre)}</div>
         </div>
       </a>`
    ).join('');
  })
  .catch(() => {
    document.getElementById('essay-list').innerHTML =
      '<p class="col-span-full text-center text-gray-400 text-sm py-4">暫時無法載入範文列表</p>';
  });

function genreIcon(g) {
  const m = {
    '詩': '🎵', '詞': '🎼', '史傳': '📜',
    '記': '🏯', '論說文': '⚖️', '表': '📋',
    '哲學散文': '🧘', '語錄': '💬', '賦': '✨',
  };
  return m[g] || '📄';
}

function esc(str) {
  const d = document.createElement('div');
  d.textContent = str == null ? '' : String(str);
  return d.innerHTML;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

