<?php
/**
 * pages/learning.php — Learning levels system (Read → Play → Test)
 */

require_once __DIR__ . '/../config/db.php';

$page_title  = '學習';
$page_active = 'learning';

$user      = session_get_user();
$author_id = $_GET['author'] ?? '';
if (!in_array($author_id, ['sushe', 'hanyu'], true)) $author_id = '';

$authors = [
    'sushe' => [
        'name'  => '蘇軾',
        'color' => 'amber',
        'emoji' => '🖌️',
        'levels' => [
            1 => ['title' => '記承天寺夜遊', 'difficulty' => '⭐'],
            2 => ['title' => '永遇樂 并序',  'difficulty' => '⭐⭐'],
            3 => ['title' => '超然臺記',      'difficulty' => '⭐⭐⭐'],
            4 => ['title' => '前赤壁賦',      'difficulty' => '⭐⭐⭐⭐'],
        ],
    ],
    'hanyu' => [
        'name'  => '韓愈',
        'color' => 'stone',
        'emoji' => '📜',
        'levels' => [
            1 => ['title' => '雜說四（馬說）', 'difficulty' => '⭐'],
            2 => ['title' => '送孟東野序',      'difficulty' => '⭐⭐'],
            3 => ['title' => '答李翊書',         'difficulty' => '⭐⭐⭐'],
            4 => ['title' => '祭十二郎文',       'difficulty' => '⭐⭐⭐⭐'],
        ],
    ],
];

// Progress
$progress = [];
if ($user) {
    try {
        $rows = db_query('SELECT author_id, level, stars FROM user_progress WHERE user_id = ?', [$user['id']])->fetchAll();
        foreach ($rows as $r) {
            $progress[$r['author_id']][$r['level']] = (int)$r['stars'];
        }
    } catch (PDOException $e) {}
}

// Level texts (abbreviated versions for in-app reading)
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

include __DIR__ . '/../includes/header.php';
?>
<div class="px-4 md:px-8 py-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-ink mb-6">📖 學習關卡</h1>

  <?php if (!$author_id): ?>
  <!-- Author selection -->
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <?php foreach ($authors as $aid => $a): ?>
    <a href="/learning?author=<?= $aid ?>" class="group block rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-shadow">
      <div class="bg-gradient-to-br <?= $aid === 'sushe' ? 'from-amber-700 to-yellow-600' : 'from-stone-700 to-gray-600' ?> text-white p-6 text-center">
        <div class="text-5xl mb-3"><?= $a['emoji'] ?></div>
        <h2 class="text-3xl font-bold tracking-widest mb-1"><?= $a['name'] ?></h2>
        <p class="text-xs opacity-70 mb-4"><?= $aid === 'sushe' ? 'Su Shi · 宋代' : 'Han Yu · 唐代' ?></p>
        <div class="text-sm opacity-90">4 大關 · <?= array_sum($progress[$aid] ?? []) ?> ⭐</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php else:
  $a = $authors[$author_id];
  $prog = $progress[$author_id] ?? [];
  ?>

  <!-- Back + header -->
  <div class="flex items-center gap-3 mb-6">
    <a href="/learning" class="text-gold hover:underline text-sm">← 返回選擇</a>
    <span class="text-ink opacity-30">|</span>
    <span class="text-xl"><?= $a['emoji'] ?> <?= $a['name'] ?></span>
  </div>

  <!-- Level cards -->
  <div class="space-y-4" id="level-list">
    <?php foreach ($a['levels'] as $lvl => $info):
      $stars     = $prog[$lvl] ?? 0;
      $prev_done = $lvl === 1 || ($prog[$lvl - 1] ?? 0) >= 1;
      $locked    = !$prev_done;
    ?>
    <div class="bg-white rounded-xl shadow p-4 <?= $locked ? 'opacity-50' : '' ?>">
      <div class="flex items-center justify-between">
        <div>
          <span class="text-xs text-gray-400 font-medium">第 <?= $lvl ?> 關</span>
          <h3 class="text-base font-bold text-ink"><?= htmlspecialchars($info['title'], ENT_QUOTES, 'UTF-8') ?></h3>
          <p class="text-xs text-gray-500"><?= $info['difficulty'] ?></p>
        </div>
        <div class="text-right">
          <div class="text-yellow-400 text-lg"><?= str_repeat('⭐', $stars) . str_repeat('☆', 3 - $stars) ?></div>
          <?php if (!$locked): ?>
          <button onclick="openLevel(<?= $lvl ?>, '<?= $author_id ?>')"
            class="mt-1 px-4 py-1.5 bg-ink text-gold text-xs font-bold rounded-lg hover:bg-ink-light transition-colors">
            <?= $stars > 0 ? '再挑戰' : '開始' ?>
          </button>
          <?php else: ?>
          <span class="text-xs text-gray-400">🔒 尚未解鎖</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Translation CSS (matches translate.php design) -->
<style>
:root {
  --tran-primary:   #7fb3d5;
  --tran-secondary: #a2d9ce;
  --tran-highlight: #7dcea0;
  --tran-light-acc: #aed6f1;
  --tran-border-rs: 6px;
  --tran-shadow-l:  0 2px 12px rgba(127,179,213,.15);
  --tran-trans:     all 0.3s cubic-bezier(.4,0,.2,1);
}
.tran-block {
  margin-bottom: 20px; padding-bottom: 16px;
  border-bottom: 2px dashed var(--tran-light-acc);
}
.tran-block:last-child { border-bottom: none; margin-bottom: 0; }
.tran-block-title {
  font-weight: 600; color: var(--tran-primary);
  margin-bottom: 10px; font-size: 0.9rem;
  font-family: 'Noto Serif TC', serif;
}
.tran-original {
  font-size: 15px; line-height: 1.9;
  padding: 12px 14px; margin-bottom: 12px;
  background: linear-gradient(135deg, var(--tran-light-acc), rgba(173,214,241,.3));
  border-left: 4px solid var(--tran-primary);
  border-radius: var(--tran-border-rs);
  font-family: 'Noto Serif TC', serif;
}
.tran-translation {
  padding: 12px 14px; border-radius: var(--tran-border-rs);
  background: linear-gradient(135deg, rgba(162,217,206,.2), rgba(118,215,196,.1));
  border-left: 4px solid var(--tran-secondary);
  line-height: 1.85; font-size: 13.5px;
}
.tran-breakdown {
  margin-top: 12px; padding: 12px 14px;
  background: linear-gradient(135deg, rgba(169,204,227,.2), rgba(125,206,160,.1));
  border-radius: var(--tran-border-rs);
  border-left: 4px solid var(--tran-highlight);
  font-size: 13px;
}
.tran-char-item {
  margin-bottom: 8px; padding: 5px 0;
  border-bottom: 1px solid rgba(127,179,213,.1);
}
.tran-char-item:last-child { border-bottom: none; }
.tran-char {
  color: var(--tran-primary); font-weight: 600; cursor: pointer;
  border-bottom: 2px dotted var(--tran-primary);
  padding: 2px 3px; border-radius: 3px;
  transition: var(--tran-trans);
}
.tran-char:hover { background: rgba(127,179,213,.1); }
.tran-char-exp {
  display: none; padding: 5px 0 5px 14px; color: #34495e;
  font-style: italic; margin-top: 4px;
  border-left: 3px solid var(--tran-secondary);
}
.tran-char-exp.revealed { display: block; }
.tran-sentence-wrap { margin-bottom: 6px; }
.tran-hidden {
  background: #ecf0f1; color: transparent;
  border-radius: 4px; padding: 3px 6px;
  cursor: pointer; transition: var(--tran-trans);
  position: relative; display: inline;
  user-select: none;
}
.tran-hidden::before {
  content: '點擊顯示';
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%,-50%);
  color: var(--tran-primary); font-size: 11px;
  opacity: 1; pointer-events: none; white-space: nowrap;
}
.tran-hidden:hover { background: rgba(127,179,213,.2); }
.tran-hidden.revealed { background: transparent; color: inherit; }
.tran-hidden.revealed::before { display: none; }
.tran-common {
  background: linear-gradient(135deg,rgba(127,179,213,.2),rgba(162,217,206,.2));
  padding: 1px 5px; border-radius: 3px; font-weight: 600;
}
</style>

<!-- Level modal (Read → Play → Test) -->
<div id="level-modal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
  <div class="bg-paper rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center p-4 border-b border-gold border-opacity-30">
      <h2 id="modal-title" class="font-bold text-ink text-lg"></h2>
      <button onclick="closeLevel()" class="text-gray-400 hover:text-ink text-2xl leading-none">&times;</button>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200">
      <?php foreach (['read' => '📖 閱讀', 'play' => '🎮 練習', 'test' => '✏️ 測驗'] as $tab => $label): ?>
      <button data-tab="<?= $tab ?>" onclick="switchTab('<?= $tab ?>')"
        class="tab-btn flex-1 py-3 text-sm font-medium text-ink opacity-50 hover:opacity-100 border-b-2 border-transparent transition-all">
        <?= $label ?>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Read tab -->
    <div id="tab-read" class="tab-content p-5 hidden">
      <p id="read-author-info" class="text-xs text-gray-500 mb-3 italic"></p>
      <div id="read-text" class="font-serif text-base leading-9 text-ink select-text"></div>
      <p class="text-xs text-gray-400 mt-2">💡 點擊任意字詞查看 AI 注釋</p>
      <!-- Annotation tooltip -->
      <div id="annotation-tip"
        class="hidden fixed z-50 bg-ink text-gold text-xs rounded-lg px-3 py-2 shadow-lg max-w-xs pointer-events-none">
      </div>

      <!-- Translation section -->
      <div class="mt-5 border-t border-gray-100 pt-5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-sm font-bold text-ink">🔤 翻譯解析</span>
          <button id="btn-translate-read" onclick="triggerTranslation()"
            class="px-4 py-1.5 text-sm font-semibold rounded-lg border-2 transition-colors"
            style="border-color:#7fb3d5;color:#7fb3d5;background:#fff;">
            <span id="btn-translate-label">開始翻譯</span>
          </button>
        </div>
        <!-- Loading -->
        <div id="tran-loading-inline" class="hidden text-center py-6">
          <div class="w-7 h-7 rounded-full border-4 animate-spin mx-auto"
               style="border-color:#aed6f1;border-top-color:#7fb3d5;"></div>
          <p class="mt-3 text-xs" style="color:#7fb3d5;">正在翻譯解析中，請稍候…</p>
        </div>
        <!-- Error -->
        <div id="tran-error-inline" class="hidden text-sm rounded-lg px-4 py-3 mt-2"
             style="background:#fadbd8;border:1px solid #f1948a;color:#c0392b;"></div>
        <!-- Results -->
        <div id="tran-result-inline" class="mt-2"></div>
      </div>

      <div class="mt-6 flex justify-end">
        <button onclick="switchTab('play')"
          class="px-6 py-2 bg-ink text-gold font-bold rounded-lg hover:bg-ink-light transition-colors text-sm">
          前往練習 →
        </button>
      </div>
    </div>

    <!-- Play tab -->
    <div id="tab-play" class="tab-content p-5 hidden">
      <p class="text-sm text-ink mb-4">選擇練習模式：</p>
      <div class="grid grid-cols-2 gap-4 mb-6">
        <button onclick="startGame('breakout')"
          class="bg-white rounded-xl p-4 shadow hover:shadow-md transition-shadow text-center">
          <div class="text-4xl mb-2">🎮</div>
          <div class="font-bold text-sm">文磚挑戰</div>
          <div class="text-xs text-gray-400">打磚塊遊戲</div>
        </button>
        <button onclick="startGame('matching')"
          class="bg-white rounded-xl p-4 shadow hover:shadow-md transition-shadow text-center">
          <div class="text-4xl mb-2">🃏</div>
          <div class="font-bold text-sm">文言配對</div>
          <div class="text-xs text-gray-400">配對遊戲</div>
        </button>
      </div>
      <div id="game-container" class="hidden"></div>
    </div>

    <!-- Test tab -->
    <div id="tab-test" class="tab-content p-5 hidden">
      <div id="test-box">
        <p class="text-sm text-gray-500 mb-4">準備好了嗎？以下將生成 5 題 AI 測驗。</p>
        <button onclick="loadQuiz()"
          class="w-full bg-ink text-gold font-bold py-2.5 rounded-lg hover:bg-ink-light transition-colors">
          開始測驗
        </button>
      </div>
      <div id="quiz-questions" class="hidden"></div>
      <div id="quiz-feedback" class="hidden text-center mt-4"></div>
      <button id="btn-quiz-submit" onclick="submitQuiz()"
        class="hidden w-full mt-4 bg-ink text-gold font-bold py-2.5 rounded-lg">提交答案</button>
    </div>

    <!-- Spinner inside modal -->
    <div id="modal-spinner" class="hidden p-8 flex justify-center">
      <div class="w-8 h-8 border-4 border-gold border-t-transparent rounded-full animate-spin"></div>
    </div>
  </div>
</div>

<script>
const CSRF_TOKEN = '<?= htmlspecialchars(csrf_token_generate(), ENT_QUOTES, 'UTF-8') ?>';
let _currentLevel = 0, _currentAuthor = '', _currentText = '';

const authorNames = { sushe: '蘇軾', hanyu: '韓愈' };
const levelTitles = <?= json_encode(array_map(fn($a) => array_map(fn($l) => $l['title'], $a['levels']), $authors)) ?>;
const levelTexts  = <?= json_encode($level_texts) ?>;

function openLevel(lvl, author) {
  _currentLevel  = lvl;
  _currentAuthor = author;
  _currentText   = (levelTexts[author] && levelTexts[author][lvl]) || '';
  _tranLoaded    = false;
  document.getElementById('modal-title').textContent = `第 ${lvl} 關：${levelTitles[author][lvl]}`;
  document.getElementById('read-author-info').textContent = authorNames[author] + '　' + (author === 'sushe' ? '宋代' : '唐代');
  document.getElementById('level-modal').classList.remove('hidden');
  // Reset translation UI
  document.getElementById('tran-result-inline').innerHTML = '';
  document.getElementById('tran-error-inline').classList.add('hidden');
  document.getElementById('tran-loading-inline').classList.add('hidden');
  const label = document.getElementById('btn-translate-label');
  if (label) label.textContent = '開始翻譯';
  const btn = document.getElementById('btn-translate-read');
  if (btn) btn.disabled = false;
  switchTab('read');
  renderReadText();
}

function closeLevel() {
  document.getElementById('level-modal').classList.add('hidden');
  document.getElementById('game-container').classList.add('hidden');
  document.getElementById('game-container').innerHTML = '';
}

function switchTab(tab) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
  document.querySelectorAll('.tab-btn').forEach(el => {
    el.classList.remove('border-gold', 'text-gold', 'opacity-100');
    el.classList.add('opacity-50', 'border-transparent');
  });
  document.getElementById('tab-' + tab).classList.remove('hidden');
  const btn = document.querySelector(`[data-tab="${tab}"]`);
  btn.classList.remove('opacity-50', 'border-transparent');
  btn.classList.add('border-gold', 'text-gold', 'opacity-100');
}

function renderReadText() {
  const box = document.getElementById('read-text');
  const chars = _currentText.split('');
  box.innerHTML = chars.map((ch, i) => {
    if (ch === '\n') return '<br>';
    if (ch.trim() === '') return ch;
    return `<span class="inline-block cursor-pointer hover:bg-yellow-100 hover:text-brush rounded px-0.5 transition-colors" data-idx="${i}" onclick="annotateChar(this, '${ch.replace("'", "\\'")}')">${ch}</span>`;
  }).join('');
}

const annotationCache = {};
async function annotateChar(el, ch) {
  if (!ch.match(/[\u4e00-\u9fff]/)) return;
  const tip = document.getElementById('annotation-tip');
  const rect = el.getBoundingClientRect();
  tip.classList.remove('hidden');
  tip.style.left = (rect.left + window.scrollX) + 'px';
  tip.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
  tip.textContent = '🔍 查詢字義中…';

  if (annotationCache[ch]) {
    showTip(tip, annotationCache[ch]);
    return;
  }

  const sentence = _currentText;
  try {
    const r = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'annotate', word: ch, sentence})
    });
    const {success, data} = await r.json();
    if (success && data) {
      annotationCache[ch] = data.meaning || '暫無注釋';
      showTip(tip, annotationCache[ch]);
    } else {
      tip.classList.add('hidden');
    }
  } catch(e) { tip.classList.add('hidden'); }
}

function showTip(tip, text) {
  tip.textContent = text;
  setTimeout(() => tip.classList.add('hidden'), 4000);
}

document.addEventListener('click', (e) => {
  if (!e.target.dataset.idx) document.getElementById('annotation-tip').classList.add('hidden');
});

// Quiz
let _quizData = [];
async function loadQuiz() {
  document.getElementById('test-box').innerHTML = '';
  document.getElementById('modal-spinner').classList.remove('hidden');
  try {
    const r = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'quiz', text: _currentText})
    });
    const {success, data} = await r.json();
    document.getElementById('modal-spinner').classList.add('hidden');
    if (!success || !Array.isArray(data)) {
      document.getElementById('quiz-questions').innerHTML = '<p class="text-red-500 text-sm">⚠️ 無法生成題目，請稍後再試。</p>';
      document.getElementById('quiz-questions').classList.remove('hidden');
      return;
    }
    _quizData = data;
    renderQuizUI(data);
  } catch(e) {
    document.getElementById('modal-spinner').classList.add('hidden');
  }
}

function renderQuizUI(qs) {
  const box = document.getElementById('quiz-questions');
  box.innerHTML = qs.map((q, i) =>
    `<div class="mb-5 bg-white rounded-xl p-4 shadow-sm">
      <p class="font-semibold text-sm mb-2">${i+1}. ${q.question}</p>
      ${q.options.map((opt, j) =>
        `<label class="flex items-center gap-2 text-sm py-1 cursor-pointer hover:text-gold">
          <input type="radio" name="lq${i}" value="${j}" class="accent-yellow-600"> ${opt}
        </label>`
      ).join('')}
    </div>`
  ).join('');
  box.classList.remove('hidden');
  document.getElementById('btn-quiz-submit').classList.remove('hidden');
}

function submitQuiz() {
  let score = 0;
  _quizData.forEach((q, i) => {
    const sel = document.querySelector(`input[name="lq${i}"]:checked`);
    const correct = parseInt(sel?.value ?? -1) === q.answer;
    if (correct) score++;
    document.querySelectorAll(`input[name="lq${i}"]`).forEach(inp => {
      const lbl = inp.parentElement;
      if (parseInt(inp.value) === q.answer) lbl.classList.add('text-green-700', 'font-semibold');
      else if (inp.checked) lbl.classList.add('text-red-500', 'line-through');
      inp.disabled = true;
    });
  });
  const pct   = Math.round(score / _quizData.length * 100);
  const stars = pct >= 100 ? 3 : pct >= 80 ? 2 : pct >= 60 ? 1 : 0;
  const fb    = document.getElementById('quiz-feedback');
  fb.innerHTML = `<div class="text-2xl mb-1">${stars > 0 ? '🎉' : '💪'}</div>
    <div class="font-bold text-lg">${score}/${_quizData.length}（${pct} 分）</div>
    <div class="text-yellow-400 text-xl my-1">${'⭐'.repeat(stars)}${'☆'.repeat(3-stars)}</div>
    <div class="text-sm text-gray-500">${stars >= 1 ? '已解鎖下一關！' : '需達 60 分才能解鎖下一關'}</div>`;
  fb.classList.remove('hidden');
  document.getElementById('btn-quiz-submit').classList.add('hidden');

  if (stars >= 1) saveProgress(_currentAuthor, _currentLevel, stars);
}

async function saveProgress(author, level, stars) {
  const fd = new FormData();
  fd.append('action', 'save');
  fd.append('csrf_token', CSRF_TOKEN);
  fd.append('author_id', author);
  fd.append('level', level);
  fd.append('stars', stars);
  try {
    await fetch('/api/progress.php', { method: 'POST', body: fd });
    // Reload page progress
    setTimeout(() => location.reload(), 2000);
  } catch(e) {}
}

// Game launcher (links to games.php with context)
function startGame(type) {
  window.open(`/games?type=${type}&author=${_currentAuthor}&level=${_currentLevel}`, '_blank');
}

// ── Translation (inline in reading tab) ──────────────────────────
let _tranLoaded = false;

async function triggerTranslation() {
  const btn   = document.getElementById('btn-translate-read');
  const label = document.getElementById('btn-translate-label');
  const loading = document.getElementById('tran-loading-inline');
  const result  = document.getElementById('tran-result-inline');
  const errBox  = document.getElementById('tran-error-inline');

  if (!_currentText) return;

  // If already loaded, just toggle visibility
  if (_tranLoaded) {
    const visible = result.style.display !== 'none';
    result.style.display  = visible ? 'none' : '';
    label.textContent = visible ? '顯示翻譯' : '隱藏翻譯';
    return;
  }

  btn.disabled = true;
  label.textContent = '翻譯中…';
  loading.classList.remove('hidden');
  errBox.classList.add('hidden');
  result.innerHTML = '';

  try {
    const r = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'translate', text: _currentText})
    });
    const {success, data} = await r.json();
    loading.classList.add('hidden');
    btn.disabled = false;

    if (!success || !data) {
      errBox.textContent = '⚠️ 翻譯失敗，請稍後再試。';
      errBox.classList.remove('hidden');
      label.textContent = '重試翻譯';
      return;
    }

    displayTranslationInline(data);
    _tranLoaded = true;
    label.textContent = '隱藏翻譯';
  } catch(e) {
    loading.classList.add('hidden');
    btn.disabled = false;
    errBox.textContent = '⚠️ 網絡錯誤，請稍後再試。';
    errBox.classList.remove('hidden');
    label.textContent = '重試翻譯';
  }
}

function displayTranslationInline(rawText) {
  rawText = rawText.replace(/<think>[\s\S]*?<\/think>/g, '');
  rawText = _cleanTranText(rawText);

  const blocks = _splitTranBlocks(rawText);
  let html = '';
  let hasOpenBlock = false;

  for (const block of blocks) {
    if (block.type === 'original') {
      if (hasOpenBlock) { html += `</div>`; hasOpenBlock = false; }
      const fmt = _formatTranOrig(block.content);
      html += `<div class="tran-block">
        <div class="tran-block-title">📜 原文</div>
        <div class="tran-original">${fmt}</div>`;
      hasOpenBlock = true;
    } else if (block.type === 'translation') {
      html += `<div class="tran-block-title">🔤 語譯</div>
        <div class="tran-translation">${_buildTranHidden(block.content)}</div>`;
    } else if (block.type === 'breakdown') {
      html += `<div class="tran-breakdown"><strong>🔍 逐字解釋</strong>${_buildTranBreakdown(block.content)}</div>`;
      if (hasOpenBlock) { html += `</div>`; hasOpenBlock = false; }
    }
  }
  if (hasOpenBlock) html += `</div>`;

  const box = document.getElementById('tran-result-inline');
  box.innerHTML = html;

  // Click-to-reveal hidden sentences
  box.querySelectorAll('.tran-hidden').forEach(el => {
    el.addEventListener('click', function() { this.classList.toggle('revealed'); });
  });
  // Toggle character explanations
  box.querySelectorAll('.tran-char').forEach(ch => {
    ch.addEventListener('click', function() {
      const exp = this.nextElementSibling;
      if (exp && exp.classList.contains('tran-char-exp')) exp.classList.toggle('revealed');
    });
  });
}

function _splitTranBlocks(text) {
  const blocks = [];
  let cur = null;
  for (const line of text.split('\n')) {
    const t = line.trim();
    if (/^原文[：:]?\s*/.test(t)) {
      if (cur) blocks.push(cur);
      cur = {type:'original', content: t.replace(/^原文[：:]\s*/,'')};
    } else if (/^語譯[：:]?\s*/.test(t)) {
      if (cur) blocks.push(cur);
      cur = {type:'translation', content: t.replace(/^語譯[：:]\s*/,'')};
    } else if (/^逐字解釋[：:]?\s*/.test(t)) {
      if (cur) blocks.push(cur);
      cur = {type:'breakdown', content: t.replace(/^逐字解釋[：:]\s*/,'')};
    } else if (cur) {
      cur.content += '\n' + line;
    }
  }
  if (cur) blocks.push(cur);
  return blocks;
}

function _cleanTranText(t) {
  t = t.replace(/^\*+\s*$/gm,'');
  t = t.replace(/^-{3,}\s*$/gm,'');
  t = t.replace(/\n{3,}/g,'\n\n');
  return t.trim();
}

function _cleanTranSection(t) {
  return t.trim().replace(/^[：:]+/,'').replace(/[：:]+$/,'').replace(/^\*+/,'').replace(/\*+$/,'').trim();
}

function _formatTranOrig(t) {
  return _escHtml(_cleanTranSection(t))
    .replace(/\n/g,'<br>')
    .replace(/\*\*([\s\S]*?)\*\*/g,'<span class="tran-common">$1</span>');
}

function _buildTranHidden(t) {
  t = _cleanTranSection(t);
  return t.split('\n').filter(s=>s.trim()).map(s =>
    `<div class="tran-sentence-wrap"><span class="tran-hidden">${_escHtml(s.trim())}</span></div>`
  ).join('');
}

function _buildTranBreakdown(t) {
  t = _cleanTranSection(t);
  return t.split('\n').filter(s=>s.trim()).map(s => {
    s = s.replace(/^\*+/,'').replace(/\*+$/,'').trim();
    const parts = s.split(/[：:]/);
    if (parts.length >= 2) {
      const ch  = parts[0].trim();
      const exp = parts.slice(1).join(':').trim();
      if (/[\u4e00-\u9fff]/.test(ch)) {
        return `<div class="tran-char-item">
          <span class="tran-char">${_escHtml(ch)}</span>
          <div class="tran-char-exp">：${_escHtml(exp)}</div>
        </div>`;
      }
    }
    return s.trim() ? `<div>${_escHtml(s)}</div>` : '';
  }).join('');
}

function _escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
