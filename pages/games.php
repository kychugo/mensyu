<?php
/**
 * pages/games.php — Games hall (Breakout + Matching)
 */

$page_title  = '遊戲廳';
$page_active = 'games';
$csrf = csrf_token_generate();

$game_type = $_GET['type']   ?? '';
$author    = $_GET['author'] ?? '';
$level     = (int)($_GET['level'] ?? 0);

// Validate author and level to prevent unexpected array access
if (!in_array($author, ['sushe', 'hanyu'], true)) $author = '';
if ($level < 1 || $level > 4) $level = 0;

// Level texts for learning-context matching game (same data as learning.php)
$level_texts = [
    'sushe' => [
        1 => "元豐六年十月十二日夜，解衣欲睡，月色入戶，欣然起行。念無與為樂者，遂至承天寺尋張懷民。懷民亦未寢，相與步於中庭。庭下如積水空明，水中藻荇交橫，蓋竹柏影也。何夜無月？何處無竹柏？但少閒人如吾兩人者耳。",
        2 => "千古江山，英雄無覓，孫仲謀處。舞榭歌臺，風流總被雨打風吹去。斜陽草樹，尋常巷陌，人道寄奴曾住。想當年，金戈鐵馬，氣吞萬里如虎。元嘉草草，封狼居胥，贏得倉皇北顧。四十三年，望中猶記，烽火揚州路。可堪回首，佛狸祠下，一片神鴉社鼓。憑誰問：廉頗老矣，尚能飯否？",
        3 => "余自錢塘移守膠西，釋舟楫之安，而服車馬之勞；去雕牆之美，而庇采椽之居；背湖山之觀，而適桑麻之野。始至之日，歲比不登，盜賊滿野，獄訟充斥；而齋廚索然，日食杞菊，人固疑余之不樂也。處之期年，而貌加豐，發之白者，日以反黑。余既樂其風俗之淳，而其吏民亦安余之拙也。於是治其園圃，潔其庭宇，伐安丘、高密之木，以修補破敗，為苟全之計。",
        4 => "壬戌之秋，七月既望，蘇子與客泛舟游於赤壁之下。清風徐來，水波不興。舉酒屬客，誦明月之詩，歌窈窕之章。少焉，月出於東山之上，徘徊於斗牛之間。白露橫江，水光接天。縱一葦之所如，凌萬頃之茫然。浩浩乎如馮虛御風，而不知其所止；飄飄乎如遺世獨立，羽化而登仙。",
    ],
    'hanyu' => [
        1 => "世有伯樂，然後有千里馬。千里馬常有，而伯樂不常有。故雖有名馬，祇辱於奴隸人之手，駢死於槽櫪之間，不以千里稱也。馬之千里者，一食或盡粟一石。食馬者不知其能千里而食也。是馬也，雖有千里之能，食不飽，力不足，才美不外見，且欲與常馬等不可得，安求其能千里也？策之不以其道，食之不能盡其材，鳴之而不能通其意，執策而臨之，曰：「天下無馬！」嗚呼！其真無馬邪？其真不知馬也！",
        2 => "古之善鳴者，則為天之所善也，豈苟然哉？余將就木，而子之年尚少，不得與汝偕行，有輟其鳴者矣。余不得與子偕行，而道之不行，豈惟余之憾哉！天之意以謂何如也？東野，勉之，無怠于善鳴！天將和其聲而使鳴國家之盛也，可知而待也。",
        3 => "生九年，通《詩》、《書》，十七，與遊人之善，既學為文，中年絕交遊，以文字為事。凡所工者，惟古文與詩耳。我之名，天下不見而知之，及其死，天下不聞而悲之，天下不見不聞而悲之者，誰哉？",
        4 => "嗚呼！吾少孤，及長，不省所怙，惟兄嫂是依。中年，兄歿南方，吾與汝俱幼，從嫂歸葬河陽。既又與汝就食江南，零丁孤苦，未嘗一日相離也。吾上有三兄，皆不幸早世，承先人後者，在孫惟汝，在子惟吾。兩世一身，形單影隻。嗚呼！汝病吾不知時，汝歿吾不知日，生不能相養以共居，歿不能撫汝以盡哀，斂不憑其棺，窆不臨其穴。",
    ],
];

// Pre-load text when coming from the learning page
$learning_text  = ($author && $level > 0 && isset($level_texts[$author][$level]))
    ? $level_texts[$author][$level]
    : '';

include __DIR__ . '/../includes/header.php';
?>
<div class="px-4 md:px-8 py-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-ink mb-6">🎮 遊戲廳</h1>

  <?php if (!$game_type): ?>
  <!-- Game selection -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <a href="/games?type=breakout"
       class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow p-6 text-center group">
      <div class="text-6xl mb-3">🎮</div>
      <h2 class="text-xl font-bold text-ink mb-1">文磚挑戰</h2>
      <p class="text-sm text-gray-500">打磚塊遊戲 · 支援觸控</p>
    </a>
    <a href="/games?type=matching"
       class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow p-6 text-center group">
      <div class="text-6xl mb-3">🃏</div>
      <h2 class="text-xl font-bold text-ink mb-1">文言配對</h2>
      <p class="text-sm text-gray-500">字詞配對遊戲 · 支援觸控</p>
    </a>
  </div>

  <?php elseif ($game_type === 'breakout'): ?>
  <!-- Breakout game -->
  <div class="flex items-center gap-3 mb-4">
    <a href="/games" class="text-gold hover:underline text-sm">← 返回遊戲廳</a>
  </div>
  <div class="flex justify-between items-center mb-2 text-sm text-ink">
    <span>❤️ <span id="lives">3</span></span>
    <span>分數：<span id="score">0</span></span>
    <span>關卡：<span id="level-display">1</span></span>
  </div>
  <div class="relative flex justify-center">
    <canvas id="breakout-canvas" width="480" height="320"
      class="rounded-xl border-2 border-gold shadow-lg bg-ink touch-none w-full max-w-lg"
      style="max-height:60vw;"></canvas>
    <div id="game-msg" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-60 rounded-xl hidden">
      <div class="text-center">
        <p id="game-msg-text" class="text-gold text-xl font-bold mb-4"></p>
        <button onclick="breakoutStart()" class="bg-gold text-ink font-bold px-6 py-2 rounded-lg">開始</button>
      </div>
    </div>
  </div>
  <div class="mt-3 text-center text-xs text-gray-400">桌面：滑鼠左右移動板 | 手機：左右滑動螢幕</div>

  <?php elseif ($game_type === 'matching'): ?>
  <!-- Matching game -->
  <div class="flex items-center gap-3 mb-4">
    <a href="/games" class="text-gold hover:underline text-sm">← 返回遊戲廳</a>
    <select id="essay-pick" class="text-sm border border-gray-300 rounded px-2 py-1 bg-white">
      <option value="">選擇範文…</option>
    </select>
    <button onclick="loadPairs()" class="bg-ink text-gold text-xs font-bold px-3 py-1.5 rounded-lg">載入</button>
  </div>
  <div id="match-status" class="text-sm text-ink mb-3 hidden">
    ⏱ <span id="match-time">0</span>s ·
    ✅ <span id="match-done">0</span>/<span id="match-total">0</span>
  </div>
  <div id="match-board" class="grid grid-cols-4 gap-2"></div>
  <div id="match-result" class="hidden mt-6 text-center">
    <div class="text-4xl mb-2">🎉</div>
    <p class="font-bold text-lg text-ink mb-1">配對完成！</p>
    <p class="text-sm text-gray-500">用時 <span id="result-time"></span> 秒</p>
    <button onclick="loadPairs()" class="mt-3 bg-ink text-gold font-bold px-6 py-2 rounded-lg text-sm">再玩一次</button>
  </div>
  <div id="match-spinner" class="hidden flex justify-center py-8">
    <div class="w-8 h-8 border-4 border-gold border-t-transparent rounded-full animate-spin"></div>
  </div>
  <?php endif; ?>
</div>

<?php if ($game_type === 'breakout'): ?>
<script>
// ── Breakout engine ──────────────────────────────────────────────
const CSRF = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';
const canvas = document.getElementById('breakout-canvas');
const ctx    = canvas.getContext('2d');

// Responsive canvas
function resizeCanvas() {
  const cw = canvas.clientWidth;
  canvas.width  = cw;
  canvas.height = Math.round(cw * 2/3);
  initPositions();
}

let W, H, paddleW, paddleH, paddleX, ballR, ballX, ballY, ballDX, ballDY;
let bricks = [], score = 0, lives = 3, gameRunning = false;
const COLS = 8, ROWS = 4, BRICK_H = 18, BRICK_GAP = 4;

function initPositions() {
  W = canvas.width; H = canvas.height;
  paddleW = W * 0.2; paddleH = 12; paddleX = W/2 - paddleW/2;
  ballR = 7; ballX = W/2; ballY = H - 60;
  const speed = W * 0.007;
  ballDX = speed; ballDY = -speed;
  bricks = [];
  const bw = (W - (COLS+1)*BRICK_GAP) / COLS;
  for (let r = 0; r < ROWS; r++) {
    for (let c = 0; c < COLS; c++) {
      bricks.push({
        x: BRICK_GAP + c*(bw+BRICK_GAP),
        y: 40 + r*(BRICK_H+BRICK_GAP),
        w: bw, h: BRICK_H,
        active: true,
        color: ['#c9a84c','#8b1a1a','#3d5a80','#4a7c59'][r],
        word: ['仁','義','禮','智','信','孝','悌','忠'][c],
      });
    }
  }
}

function showMsg(text) {
  document.getElementById('game-msg-text').textContent = text;
  document.getElementById('game-msg').classList.remove('hidden');
}
function hideMsg() {
  document.getElementById('game-msg').classList.add('hidden');
}

function breakoutStart() {
  score = 0; lives = 3;
  resizeCanvas();
  updateHUD();
  hideMsg();
  gameRunning = true;
  requestAnimationFrame(gameLoop);
}

function updateHUD() {
  document.getElementById('score').textContent = score;
  document.getElementById('lives').textContent = lives;
}

let mouseX = canvas.width/2;
canvas.addEventListener('mousemove', e => {
  const r = canvas.getBoundingClientRect();
  mouseX = (e.clientX - r.left) * (canvas.width / r.width);
});
let touchStartX = 0;
canvas.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; e.preventDefault(); }, {passive:false});
canvas.addEventListener('touchmove', e => {
  const dx = e.touches[0].clientX - touchStartX;
  paddleX = Math.max(0, Math.min(W - paddleW, paddleX + dx * 1.5));
  touchStartX = e.touches[0].clientX;
  e.preventDefault();
}, {passive:false});

function gameLoop() {
  if (!gameRunning) return;
  ctx.clearRect(0, 0, W, H);

  // Background
  ctx.fillStyle = '#1a1208';
  ctx.fillRect(0, 0, W, H);

  // Paddle (follow mouse on desktop)
  paddleX = mouseX - paddleW/2;
  paddleX = Math.max(0, Math.min(W - paddleW, paddleX));

  ctx.fillStyle = '#c9a84c';
  ctx.beginPath();
  ctx.roundRect(paddleX, H - paddleH - 10, paddleW, paddleH, 6);
  ctx.fill();

  // Ball
  ballX += ballDX; ballY += ballDY;
  if (ballX + ballR > W || ballX - ballR < 0) ballDX *= -1;
  if (ballY - ballR < 0) ballDY *= -1;

  // Paddle collision
  if (ballY + ballR >= H - paddleH - 10 && ballY + ballR <= H - 10 &&
      ballX >= paddleX && ballX <= paddleX + paddleW) {
    ballDY = -Math.abs(ballDY);
    const hit = (ballX - paddleX) / paddleW - 0.5;
    ballDX = hit * W * 0.012;
  }

  // Bottom
  if (ballY + ballR > H) {
    lives--;
    updateHUD();
    if (lives <= 0) { gameRunning = false; showMsg('遊戲結束 — 分數：' + score); return; }
    ballX = W/2; ballY = H - 60;
    const sp = W * 0.007;
    ballDX = sp; ballDY = -sp;
  }

  // Bricks
  let any = false;
  for (const b of bricks) {
    if (!b.active) continue;
    any = true;
    if (ballX+ballR>b.x && ballX-ballR<b.x+b.w && ballY+ballR>b.y && ballY-ballR<b.y+b.h) {
      b.active = false; ballDY *= -1; score += 10; updateHUD();
    }
    ctx.fillStyle = b.color;
    ctx.beginPath();
    ctx.roundRect(b.x, b.y, b.w, b.h, 4);
    ctx.fill();
    ctx.fillStyle = '#fff';
    ctx.font = `bold ${b.h * 0.7}px serif`;
    ctx.textAlign = 'center';
    ctx.fillText(b.word, b.x + b.w/2, b.y + b.h*0.75);
  }
  if (!any) { gameRunning = false; showMsg('過關！分數：' + score + ' 🎉'); grantGameAchievement('breakout'); return; }

  // Ball draw
  ctx.fillStyle = '#f5efe0';
  ctx.beginPath();
  ctx.arc(ballX, ballY, ballR, 0, Math.PI*2);
  ctx.fill();

  requestAnimationFrame(gameLoop);
}

// Init
async function grantGameAchievement(game) {
  try {
    const fd = new FormData();
    fd.append('action', 'game_complete');
    fd.append('game', game);
    fd.append('csrf_token', CSRF);
    await fetch('/api/achievements.php', { method: 'POST', body: fd });
  } catch (e) { /* silent — achievement grant is best-effort */ }
}

resizeCanvas();
showMsg('文磚挑戰 — 準備好了嗎？');
window.addEventListener('resize', () => { if (!gameRunning) resizeCanvas(); });
</script>
<?php endif; ?>

<?php if ($game_type === 'matching'): ?>
<script>
const LEARNING_TEXT = <?= json_encode($learning_text, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const CSRF = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';

// Load essay list into picker
fetch('/api/essays.php?action=list')
  .then(r => r.json())
  .then(({data}) => {
    const sel = document.getElementById('essay-pick');
    data.forEach(e => {
      sel.appendChild(new Option(`${e.title} — ${e.author}`, e.id));
    });
  });

let _pairs = [], _selected = null, _matched = 0, _timer = 0, _timerInt = null;

// When coming from learning page, auto-load pairs from the level text
if (LEARNING_TEXT) {
  document.addEventListener('DOMContentLoaded', () => {
    // Hide the essay picker row since we already have context
    const pickerRow = document.getElementById('essay-pick');
    if (pickerRow) pickerRow.closest('div.flex')?.classList.add('hidden');
    loadPairsFromText(LEARNING_TEXT);
  });
}

async function loadPairs() {
  const esId = document.getElementById('essay-pick').value;
  if (!esId) { alert('請先選擇範文'); return; }

  document.getElementById('match-spinner').classList.remove('hidden');
  document.getElementById('match-board').innerHTML = '';
  document.getElementById('match-result').classList.add('hidden');
  document.getElementById('match-status').classList.add('hidden');

  // Get essay text then generate pairs
  const er = await fetch(`/api/essays.php?action=get&id=${esId}`);
  const {data: essay} = await er.json();
  if (!essay) {
    document.getElementById('match-spinner').classList.add('hidden');
    document.getElementById('match-board').innerHTML = '<p class="col-span-4 text-red-500 text-sm">⚠️ 無法載入範文，請稍後再試。</p>';
    return;
  }
  await loadPairsFromText(essay.content);
}

async function loadPairsFromText(text) {
  document.getElementById('match-spinner').classList.remove('hidden');
  document.getElementById('match-board').innerHTML = '';
  document.getElementById('match-result').classList.add('hidden');
  document.getElementById('match-status').classList.add('hidden');

  let success, data;
  try {
    const r = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'pairs', text, count:6})
    });
    ({success, data} = await r.json());
  } catch (e) {
    document.getElementById('match-spinner').classList.add('hidden');
    document.getElementById('match-board').innerHTML = '<p class="col-span-4 text-red-500 text-sm">⚠️ 無法載入配對，請稍後再試。</p>';
    return;
  }
  document.getElementById('match-spinner').classList.add('hidden');

  if (!success || !Array.isArray(data) || data.length === 0) {
    document.getElementById('match-board').innerHTML = '<p class="col-span-4 text-red-500 text-sm">⚠️ 無法載入配對，請稍後再試。</p>';
    return;
  }

  _pairs = data;
  _matched = 0;
  _timer = 0;
  clearInterval(_timerInt);
  _timerInt = setInterval(() => {
    _timer++;
    document.getElementById('match-time').textContent = _timer;
  }, 1000);

  document.getElementById('match-total').textContent = data.length;
  document.getElementById('match-done').textContent = 0;
  document.getElementById('match-status').classList.remove('hidden');

  // Build cards: classical + modern, shuffled
  const cards = [];
  data.forEach((p, i) => {
    cards.push({id: i, type: 'classical', text: p.classical});
    cards.push({id: i, type: 'modern',    text: p.modern});
  });
  cards.sort(() => Math.random() - 0.5);

  const board = document.getElementById('match-board');
  board.innerHTML = '';
  cards.forEach(card => {
    const el = document.createElement('button');
    el.className = 'aspect-square bg-white rounded-xl shadow text-center flex items-center justify-center text-sm font-serif font-semibold text-ink p-1 cursor-pointer hover:bg-yellow-50 transition-colors border-2 border-transparent';
    el.textContent = card.text;
    el.dataset.id   = card.id;
    el.dataset.type = card.type;
    el.addEventListener('click', () => selectCard(el));
    board.appendChild(el);
  });

  // (achievement tracked on game completion, not on loading pairs)
}

async function grantGameAchievement(game) {
  try {
    const fd = new FormData();
    fd.append('action', 'game_complete');
    fd.append('game', game);
    fd.append('csrf_token', CSRF);
    await fetch('/api/achievements.php', { method: 'POST', body: fd });
  } catch (e) { /* silent — achievement grant is best-effort */ }
}

function selectCard(el) {
  if (el.classList.contains('matched') || el.classList.contains('selected')) return;

  el.classList.add('selected', 'border-gold', 'bg-yellow-50');

  if (!_selected) {
    _selected = el;
    return;
  }

  const a = _selected, b = el;
  if (a.dataset.id === b.dataset.id && a.dataset.type !== b.dataset.type) {
    // Match!
    [a, b].forEach(c => {
      c.classList.remove('selected', 'border-gold');
      c.classList.add('matched', 'bg-green-100', 'border-green-400', 'text-green-800');
      c.disabled = true;
    });
    _matched++;
    document.getElementById('match-done').textContent = _matched;
    if (_matched === _pairs.length) {
      clearInterval(_timerInt);
      document.getElementById('result-time').textContent = _timer;
      document.getElementById('match-result').classList.remove('hidden');
      grantGameAchievement('matching');
    }
  } else {
    // No match
    [a, b].forEach(c => c.classList.add('bg-red-50', 'border-red-300'));
    setTimeout(() => {
      [a, b].forEach(c => {
        c.classList.remove('selected', 'border-gold', 'bg-yellow-50', 'bg-red-50', 'border-red-300');
        c.classList.add('border-transparent', 'bg-white');
      });
    }, 800);
  }
  _selected = null;
}
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
