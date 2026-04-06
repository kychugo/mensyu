<?php
/**
 * pages/translate.php — Translation page (DSE essay selector + free input)
 */

$page_title  = '翻譯';
$page_active = 'translate';
include __DIR__ . '/../includes/header.php';

$essay_id = (int)($_GET['essay_id'] ?? 0);
?>
<div class="px-4 md:px-8 py-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-ink mb-6">🔤 文言文翻譯</h1>

  <!-- Essay selector -->
  <div id="essay-list-section" class="mb-6">
    <label class="block text-sm font-medium text-ink mb-2">選擇 DSE 範文（可選）</label>
    <select id="essay-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gold">
      <option value="">── 自由輸入 ──</option>
    </select>
  </div>

  <!-- Text input -->
  <div class="mb-4">
    <label class="block text-sm font-medium text-ink mb-2">輸入文言文</label>
    <textarea id="input-text" rows="6"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold resize-y font-serif"
      placeholder="請輸入文言文，或從上方選擇範文…"></textarea>
  </div>

  <div class="flex gap-3 mb-6">
    <button id="btn-translate"
      class="flex-1 bg-ink text-gold font-bold py-2.5 rounded-lg hover:bg-ink-light transition-colors tracking-widest">
      翻　譯
    </button>
    <button id="btn-quiz" title="生成測驗題"
      class="px-4 bg-gold text-ink font-bold py-2.5 rounded-lg hover:bg-gold-light transition-colors">
      ✏️ 測驗
    </button>
    <button id="btn-clear" title="清除"
      class="px-4 bg-gray-100 text-ink rounded-lg hover:bg-gray-200 transition-colors text-sm">
      清除
    </button>
  </div>

  <!-- Translation result -->
  <div id="result-section" class="hidden">
    <h2 class="text-lg font-bold text-ink mb-3">📝 翻譯結果</h2>
    <div id="result-box" class="bg-white rounded-xl p-4 shadow text-sm leading-8 whitespace-pre-wrap font-serif min-h-[80px]"></div>
    <!-- Floating reference toggle (mobile drawer) -->
    <button id="btn-float" class="mt-3 text-xs text-gold underline">浮動原文視窗</button>
  </div>

  <!-- Quiz result -->
  <div id="quiz-section" class="hidden mt-6">
    <h2 class="text-lg font-bold text-ink mb-3">✏️ 測驗題目</h2>
    <div id="quiz-box"></div>
    <div id="quiz-result" class="hidden mt-4 text-center font-bold text-lg"></div>
    <button id="btn-submit-quiz" class="hidden mt-4 w-full bg-ink text-gold font-bold py-2.5 rounded-lg">提交答案</button>
  </div>

  <!-- Spinner -->
  <div id="spinner" class="hidden flex justify-center py-8">
    <div class="w-8 h-8 border-4 border-gold border-t-transparent rounded-full animate-spin"></div>
  </div>

  <!-- Floating original text panel -->
  <div id="float-panel" class="hidden fixed inset-x-0 bottom-16 md:bottom-0 md:right-4 md:top-auto md:inset-x-auto
       bg-white border border-gold rounded-t-2xl md:rounded-2xl shadow-xl z-30 w-full md:w-80 max-h-64 overflow-y-auto p-4">
    <div class="flex justify-between items-center mb-2">
      <span class="font-bold text-sm text-ink">原文</span>
      <button id="btn-float-close" class="text-gray-400 hover:text-ink text-lg leading-none">&times;</button>
    </div>
    <p id="float-text" class="text-sm font-serif leading-7 text-ink whitespace-pre-wrap"></p>
  </div>
</div>

<script>
// Load essays into select
fetch('/api/essays.php?action=list')
  .then(r => r.json())
  .then(({data}) => {
    const sel = document.getElementById('essay-select');
    data.forEach(e => {
      const opt = new Option(`${e.title} — ${e.author}`, e.id);
      sel.appendChild(opt);
    });
    // Pre-select if essay_id provided
    const preId = <?= $essay_id ?>;
    if (preId) {
      sel.value = preId;
      loadEssay(preId);
    }
  });

document.getElementById('essay-select').addEventListener('change', function() {
  if (this.value) loadEssay(this.value);
  else document.getElementById('input-text').value = '';
});

async function loadEssay(id) {
  const r = await fetch(`/api/essays.php?action=get&id=${id}`);
  const {data} = await r.json();
  if (data) document.getElementById('input-text').value = data.content;
}

// Translate
document.getElementById('btn-translate').addEventListener('click', async () => {
  const text = document.getElementById('input-text').value.trim();
  if (!text) return;
  showSpinner(true);
  hideResults();
  try {
    const r = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'translate', text})
    });
    const {success, data} = await r.json();
    document.getElementById('result-section').classList.remove('hidden');
    document.getElementById('result-box').textContent = success ? data : '⚠️ AI 暫時無法回應，請稍後再試。';
    document.getElementById('float-text').textContent = text;
  } finally {
    showSpinner(false);
  }
});

// Quiz
document.getElementById('btn-quiz').addEventListener('click', async () => {
  const text = document.getElementById('input-text').value.trim();
  if (!text) return;
  showSpinner(true);
  hideResults();
  try {
    const r = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'quiz', text})
    });
    const {success, data} = await r.json();
    if (!success || !Array.isArray(data)) {
      document.getElementById('quiz-section').classList.remove('hidden');
      document.getElementById('quiz-box').innerHTML = '<p class="text-red-500 text-sm">⚠️ 無法生成題目，請稍後再試。</p>';
      return;
    }
    renderQuiz(data);
  } finally {
    showSpinner(false);
  }
});

let _quizData = [];
function renderQuiz(questions) {
  _quizData = questions;
  const box = document.getElementById('quiz-box');
  box.innerHTML = questions.map((q, i) =>
    `<div class="mb-5">
      <p class="font-semibold text-sm mb-2">${i+1}. ${q.question}</p>
      ${q.options.map((opt, j) =>
        `<label class="flex items-center gap-2 text-sm py-1 cursor-pointer">
           <input type="radio" name="q${i}" value="${j}" class="accent-gold"> ${opt}
         </label>`
      ).join('')}
    </div>`
  ).join('');
  document.getElementById('quiz-section').classList.remove('hidden');
  document.getElementById('btn-submit-quiz').classList.remove('hidden');
  document.getElementById('quiz-result').classList.add('hidden');
}

document.getElementById('btn-submit-quiz').addEventListener('click', () => {
  let score = 0;
  _quizData.forEach((q, i) => {
    const sel = document.querySelector(`input[name="q${i}"]:checked`);
    if (sel && parseInt(sel.value) === q.answer) score++;
    // Mark correct/wrong
    document.querySelectorAll(`input[name="q${i}"]`).forEach(inp => {
      const lbl = inp.parentElement;
      if (parseInt(inp.value) === q.answer) lbl.classList.add('text-green-700', 'font-semibold');
      else if (inp.checked) lbl.classList.add('text-red-500', 'line-through');
    });
  });
  const pct = Math.round(score / _quizData.length * 100);
  const res = document.getElementById('quiz-result');
  res.classList.remove('hidden');
  res.textContent = `得分：${score}/${_quizData.length}（${pct}分）${pct >= 60 ? ' 🎉 合格！' : ' 💪 繼續努力！'}`;
  document.getElementById('btn-submit-quiz').classList.add('hidden');
});

// Float panel
document.getElementById('btn-float').addEventListener('click', () => {
  document.getElementById('float-panel').classList.toggle('hidden');
});
document.getElementById('btn-float-close').addEventListener('click', () => {
  document.getElementById('float-panel').classList.add('hidden');
});

// Clear
document.getElementById('btn-clear').addEventListener('click', () => {
  document.getElementById('input-text').value = '';
  document.getElementById('essay-select').value = '';
  hideResults();
});

function showSpinner(v) {
  document.getElementById('spinner').classList.toggle('hidden', !v);
}
function hideResults() {
  document.getElementById('result-section').classList.add('hidden');
  document.getElementById('quiz-section').classList.add('hidden');
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
