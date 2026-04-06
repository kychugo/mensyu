<?php
/**
 * pages/translate.php — Translation page (DSE essay selector + free input)
 * Design based on mensyu-tran.html
 */

$page_title  = '翻譯';
$page_active = 'translate';
include __DIR__ . '/../includes/header.php';

$essay_id = (int)($_GET['essay_id'] ?? 0);
?>

<style>
:root {
  --tran-primary:   #7fb3d5;
  --tran-secondary: #a2d9ce;
  --tran-highlight: #7dcea0;
  --tran-light-acc: #aed6f1;
  --tran-bg:        #f8fafa;
  --tran-border-r:  12px;
  --tran-border-rs: 6px;
  --tran-shadow-l:  0 2px 12px rgba(127,179,213,.15);
  --tran-shadow-m:  0 4px 24px rgba(127,179,213,.2);
  --tran-trans:     all 0.3s cubic-bezier(.4,0,.2,1);
}

/* ── Layout ─────────────────────────────────────────────────────── */
.tran-wrap {
  max-width: 1300px;
  margin: 0 auto;
  padding: 24px 16px 80px;
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 28px;
}
@media(max-width:1024px){ .tran-wrap { grid-template-columns:1fr; } }

/* ── Panel cards ─────────────────────────────────────────────────── */
.tran-card {
  background:#fff;
  border-radius: var(--tran-border-r);
  box-shadow: var(--tran-shadow-l);
  padding: 28px;
  border: 2px solid rgba(127,179,213,.08);
  transition: var(--tran-trans);
  position: relative;
  overflow: hidden;
}
.tran-card::before {
  content:'';
  position:absolute; top:0; left:0; right:0; height:4px;
  background: linear-gradient(135deg, var(--tran-primary), var(--tran-secondary));
}
.tran-card:hover { box-shadow: var(--tran-shadow-m); transform:translateY(-2px); }

.tran-input-card { position:sticky; top:20px; height:fit-content; }
@media(max-width:1024px){ .tran-input-card { position:static; } }

/* ── Section title ───────────────────────────────────────────────── */
.tran-title {
  color: var(--tran-primary);
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--tran-light-acc);
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}
.tran-title::before {
  content:''; width:8px; height:8px;
  background: var(--tran-secondary); border-radius:50%;
}

/* ── Essay selector & textarea ───────────────────────────────────── */
#tran-essay-select {
  width:100%; margin-bottom:16px;
  padding:10px 14px;
  border:2px solid var(--tran-light-acc); border-radius:var(--tran-border-rs);
  font-size:14px; background:#fafafa;
  transition: var(--tran-trans);
}
#tran-essay-select:focus { outline:none; border-color:var(--tran-primary); }

#tran-textarea {
  width:100%; min-height:360px; padding:18px;
  border:2px solid var(--tran-light-acc); border-radius:var(--tran-border-rs);
  font-size:17px; line-height:1.85; resize:vertical;
  background:linear-gradient(to bottom,#fafafa,#fff);
  font-family:'Noto Serif TC',serif;
  transition: var(--tran-trans);
}
#tran-textarea:focus { outline:none; border-color:var(--tran-primary); box-shadow:0 0 0 4px rgba(127,179,213,.1); }
@media(max-width:768px){ #tran-textarea { min-height:260px; font-size:15px; } }

/* ── Buttons ─────────────────────────────────────────────────────── */
.tran-btns { display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; }
.tran-btn {
  flex:1; min-width:110px; padding:13px 22px;
  border:none; border-radius:var(--tran-border-rs);
  font-size:15px; font-weight:600; cursor:pointer;
  background: linear-gradient(135deg, var(--tran-primary), var(--tran-secondary));
  color:#fff; transition: var(--tran-trans);
  position:relative; overflow:hidden;
}
.tran-btn::before {
  content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);
  transition:left .5s;
}
.tran-btn:hover::before { left:100%; }
.tran-btn:hover { transform:translateY(-3px); box-shadow: var(--tran-shadow-m); }
.tran-btn:disabled { background:#bdc3c7; cursor:not-allowed; transform:none; box-shadow:none; }
.tran-btn-clear { background:linear-gradient(135deg,#e74c3c,#c0392b); }
.tran-btn-quiz  { background:linear-gradient(135deg,#f39c12,#e67e22); }
.tran-btn-reveal{ background:linear-gradient(135deg,#27ae60,#2ecc71); margin-top:16px; width:100%; display:block; }

/* ── Loading ─────────────────────────────────────────────────────── */
.tran-loading { display:none; text-align:center; padding:50px; color:var(--tran-primary); }
.tran-spinner {
  border:4px solid rgba(127,179,213,.2); border-radius:50%;
  border-top:4px solid var(--tran-primary);
  width:50px; height:50px;
  animation:tran-spin 1s linear infinite;
  margin:0 auto 16px;
}
.tran-loading-msg { font-size:15px; font-weight:500; color:var(--tran-primary); margin-top:6px; }
@keyframes tran-spin { to { transform:rotate(360deg); } }

/* ── Error bar ───────────────────────────────────────────────────── */
.tran-error {
  display:none; margin-top:16px; padding:14px;
  background:#fadbd8; border:2px solid #f1948a; border-radius:var(--tran-border-rs);
  color:#c0392b; font-size:14px;
}

/* ── Translation blocks ──────────────────────────────────────────── */
.tran-block {
  margin-bottom:32px; padding-bottom:24px;
  border-bottom:2px dashed var(--tran-light-acc);
}
.tran-block:last-child { border-bottom:none; }
.tran-block-title {
  font-weight:600; color:var(--tran-primary);
  margin-bottom:14px; font-size:1.1rem;
  font-family:'Noto Serif TC',serif;
}
.tran-original {
  font-size:17px; line-height:2;
  padding:18px; margin-bottom:20px;
  background:linear-gradient(135deg,var(--tran-light-acc),rgba(173,214,241,.3));
  border-left:5px solid var(--tran-primary);
  border-radius:var(--tran-border-rs);
  font-family:'Noto Serif TC',serif;
}
.tran-translation {
  padding:18px; border-radius:var(--tran-border-rs);
  background:linear-gradient(135deg,rgba(162,217,206,.2),rgba(118,215,196,.1));
  border-left:5px solid var(--tran-secondary);
  line-height:1.9; font-size:15px;
}
.tran-breakdown {
  margin-top:18px; padding:18px;
  background:linear-gradient(135deg,rgba(169,204,227,.2),rgba(125,206,160,.1));
  border-radius:var(--tran-border-rs);
  border-left:5px solid var(--tran-highlight);
  font-size:14px;
}
.tran-char-item {
  margin-bottom:10px; padding:6px 0;
  border-bottom:1px solid rgba(127,179,213,.1);
}
.tran-char-item:last-child { border-bottom:none; }
.tran-char {
  color:var(--tran-primary); font-weight:600; cursor:pointer;
  border-bottom:2px dotted var(--tran-primary);
  padding:2px 4px; border-radius:3px;
  transition: var(--tran-trans);
}
.tran-char:hover { background:rgba(127,179,213,.1); }
.tran-char-exp {
  display:none; padding:6px 0 6px 16px; color:#34495e;
  font-style:italic; margin-top:6px;
  border-left:3px solid var(--tran-secondary);
}
.tran-char-exp.revealed { display:block; animation:tran-slide .3s ease-out; }
@keyframes tran-slide { from{opacity:0;max-height:0} to{opacity:1;max-height:100px} }

/* ── Hidden text (click-to-reveal) ──────────────────────────────── */
.tran-sentence-wrap { margin-bottom:10px; }
.tran-hidden {
  background:#ecf0f1; color:transparent;
  border-radius:4px; padding:4px 8px;
  cursor:pointer; transition: var(--tran-trans);
  position:relative; display:inline;
  user-select:none;
}
.tran-hidden::before {
  content:'點擊顯示';
  position:absolute; top:50%; left:50%;
  transform:translate(-50%,-50%);
  color:var(--tran-primary); font-size:11px; opacity:.7;
  pointer-events:none;
}
.tran-hidden:hover { background:rgba(127,179,213,.2); }
.tran-hidden.revealed { background:transparent; color:inherit; }
.tran-hidden.revealed::before { display:none; }

/* ── Common word highlight ───────────────────────────────────────── */
.tran-common { background:linear-gradient(135deg,rgba(127,179,213,.2),rgba(162,217,206,.2)); padding:2px 5px; border-radius:4px; font-weight:600; }

/* ── Floating original panel ─────────────────────────────────────── */
.tran-float {
  display:none; position:fixed;
  background:#fff; border:2px solid var(--tran-primary);
  border-radius:var(--tran-border-r);
  box-shadow: 0 8px 36px rgba(127,179,213,.25);
  z-index:1000;
}
@media(min-width:1025px){
  .tran-float { top:50%; right:20px; transform:translateY(-50%); width:340px; max-height:70vh; overflow-y:auto; }
}
@media(max-width:768px){
  .tran-float { top:0; left:0; right:0; width:100%; border-radius:0 0 var(--tran-border-r) var(--tran-border-r); border-top:none; min-height:150px; }
  .tran-float.resizable { height:33.33vh; }
  .tran-float::after { content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:46px; height:5px; background:var(--tran-primary); border-radius:3px 3px 0 0; cursor:ns-resize; }
}
.tran-float.show { display:block; animation:tran-fadeIn .3s ease-out; }
@keyframes tran-fadeIn { from{opacity:0;transform:translateY(-50%) scale(.9)} to{opacity:1;transform:translateY(-50%) scale(1)} }
@media(max-width:768px){
  @keyframes tran-fadeIn { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }
}
.tran-float-head {
  background:linear-gradient(135deg,var(--tran-primary),var(--tran-secondary));
  color:#fff; padding:14px 18px;
  display:flex; justify-content:space-between; align-items:center;
  border-radius:var(--tran-border-r) var(--tran-border-r) 0 0;
}
@media(max-width:768px){ .tran-float-head { border-radius:0; } }
.tran-float-close {
  background:none; border:none; color:#fff; font-size:1.2rem;
  cursor:pointer; padding:4px; border-radius:50%; width:28px; height:28px;
  display:flex; align-items:center; justify-content:center;
  transition: var(--tran-trans);
}
.tran-float-close:hover { background:rgba(255,255,255,.2); transform:scale(1.1); }
.tran-float-body { padding:18px; overflow-y:auto; font-family:'Noto Serif TC',serif; font-size:15px; line-height:1.9; }
@media(max-width:768px){ .tran-float-body { height:calc(100% - 52px); font-size:16px; } }

/* ── Reopen float btn ────────────────────────────────────────────── */
.tran-reopen {
  display:none; position:fixed; top:70px; right:16px;
  background:linear-gradient(135deg,var(--tran-primary),var(--tran-secondary));
  color:#fff; border:none; border-radius:50%; width:48px; height:48px;
  font-size:1.1rem; cursor:pointer; box-shadow: var(--tran-shadow-m);
  z-index:999; align-items:center; justify-content:center;
  transition: var(--tran-trans);
}
@media(max-width:768px){ .tran-reopen.show { display:flex; } }
.tran-reopen:hover { transform:scale(1.1); }

/* ── Quiz section ────────────────────────────────────────────────── */
.tran-quiz-box .quiz-q { margin-bottom:20px; }
.tran-quiz-box .quiz-q p { font-weight:600; font-size:14px; margin-bottom:8px; }
.tran-quiz-box label { display:flex; align-items:center; gap:8px; font-size:14px; padding:4px 0; cursor:pointer; }
</style>

<div class="tran-wrap">

  <!-- ── Input panel ───────────────────────────────────────────── -->
  <div class="tran-card tran-input-card">
    <h2 class="tran-title">輸入文言文</h2>

    <!-- Essay selector -->
    <select id="tran-essay-select">
      <option value="">── 自由輸入 ──</option>
    </select>

    <textarea id="tran-textarea" placeholder="請在此輸入文言文篇章內容…"></textarea>

    <div class="tran-btns">
      <button id="btn-translate" class="tran-btn">🔤 翻　譯</button>
      <button id="btn-quiz"      class="tran-btn tran-btn-quiz">✏️ 測驗</button>
      <button id="btn-clear"     class="tran-btn tran-btn-clear">🗑 清除</button>
    </div>

    <div id="tran-error" class="tran-error"></div>
  </div>

  <!-- ── Output panel ──────────────────────────────────────────── -->
  <div class="tran-card">
    <h2 class="tran-title">翻譯結果</h2>

    <!-- Loading -->
    <div class="tran-loading" id="tran-loading">
      <div class="tran-spinner"></div>
      <div class="tran-loading-msg" id="tran-loading-msg">正在翻譯中，請稍候…</div>
    </div>

    <!-- Result -->
    <div id="tran-result"></div>

    <!-- Reveal all -->
    <button id="btn-reveal-all" class="tran-btn tran-btn-reveal hidden">
      👁 顯示全部解釋
    </button>

    <!-- Quiz -->
    <div id="quiz-section" class="hidden mt-6">
      <div class="tran-title mt-2">✏️ 測驗題目</div>
      <div id="quiz-box" class="tran-quiz-box"></div>
      <div id="quiz-result" class="hidden mt-4 text-center font-bold text-lg"></div>
      <button id="btn-submit-quiz" class="hidden tran-btn mt-4 w-full">提交答案</button>
    </div>
  </div>
</div>

<!-- Floating original text panel -->
<div id="tran-float" class="tran-float resizable">
  <div class="tran-float-head">
    <span style="font-weight:600;">原文</span>
    <button id="float-close" class="tran-float-close" title="關閉">✕</button>
  </div>
  <div id="float-body" class="tran-float-body"></div>
</div>

<!-- Re-open float button (mobile only) -->
<button id="tran-reopen" class="tran-reopen" title="顯示原文">📜</button>

<script>
// ── State ───────────────────────────────────────────────────────
let _floatEnabled  = true;
let _origTexts     = [];
let _quizData      = [];
const preId        = <?= $essay_id ?>;

// ── Init ────────────────────────────────────────────────────────
(async function init() {
  // Load essays into selector
  try {
    const {data} = await fetch('/api/essays.php?action=list').then(r => r.json());
    const sel = document.getElementById('tran-essay-select');
    data.forEach(e => {
      sel.appendChild(new Option(`${e.title} — ${e.author}`, e.id));
    });
    if (preId) { sel.value = preId; await loadEssay(preId); }
  } catch(e) {}

  initFloat();
})();

// ── Essay selector ───────────────────────────────────────────────
document.getElementById('tran-essay-select').addEventListener('change', async function() {
  if (this.value) await loadEssay(this.value);
  else document.getElementById('tran-textarea').value = '';
});

async function loadEssay(id) {
  const {data} = await fetch(`/api/essays.php?action=get&id=${id}`).then(r => r.json());
  if (data) document.getElementById('tran-textarea').value = data.content;
}

// ── Translate ────────────────────────────────────────────────────
document.getElementById('btn-translate').addEventListener('click', async () => {
  const text = document.getElementById('tran-textarea').value.trim();
  if (!text) { showError('請輸入文言文內容'); return; }
  hideError();
  hideResults();
  setLoading(true, '正在連接 AI，請稍候…');

  try {
    const r    = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'translate', text})
    });
    const {success, data} = await r.json();
    if (!success || !data) { showError('⚠️ AI 暫時無法回應，請稍後再試'); return; }
    displayTranslation(data);
  } catch(e) {
    showError('⚠️ 網絡錯誤，請稍後再試');
  } finally {
    setLoading(false);
  }
});

// ── Quiz ─────────────────────────────────────────────────────────
document.getElementById('btn-quiz').addEventListener('click', async () => {
  const text = document.getElementById('tran-textarea').value.trim();
  if (!text) { showError('請先輸入或選擇文言文'); return; }
  hideError();
  document.getElementById('quiz-section').classList.add('hidden');
  setLoading(true, '正在生成測驗題目…');

  try {
    const r    = await fetch('/api/ai_text.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'quiz', text})
    });
    const {success, data} = await r.json();
    if (!success || !Array.isArray(data)) {
      showError('⚠️ 無法生成題目，請稍後再試');
      return;
    }
    renderQuiz(data);
  } catch(e) {
    showError('⚠️ 網絡錯誤，請稍後再試');
  } finally {
    setLoading(false);
  }
});

function renderQuiz(questions) {
  _quizData = questions;
  const box = document.getElementById('quiz-box');
  box.innerHTML = questions.map((q, i) =>
    `<div class="quiz-q">
       <p>${i+1}. ${q.question}</p>
       ${q.options.map((opt, j) =>
         `<label><input type="radio" name="q${i}" value="${j}" class="accent-gold"> ${opt}</label>`
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
    document.querySelectorAll(`input[name="q${i}"]`).forEach(inp => {
      const lbl = inp.parentElement;
      if (parseInt(inp.value) === q.answer) lbl.classList.add('text-green-700','font-semibold');
      else if (inp.checked) lbl.classList.add('text-red-500','line-through');
    });
  });
  const pct = Math.round(score / _quizData.length * 100);
  const res = document.getElementById('quiz-result');
  res.classList.remove('hidden');
  res.textContent = `得分：${score}/${_quizData.length}（${pct}分）${pct >= 60 ? ' 🎉 合格！' : ' 💪 繼續努力！'}`;
  document.getElementById('btn-submit-quiz').classList.add('hidden');
});

// ── Clear ────────────────────────────────────────────────────────
document.getElementById('btn-clear').addEventListener('click', () => {
  document.getElementById('tran-textarea').value = '';
  document.getElementById('tran-essay-select').value = '';
  document.getElementById('tran-result').innerHTML = '';
  document.getElementById('btn-reveal-all').classList.add('hidden');
  document.getElementById('quiz-section').classList.add('hidden');
  _origTexts = [];
  hideFloat();
  hideError();
});

// ── Reveal all ───────────────────────────────────────────────────
document.getElementById('btn-reveal-all').addEventListener('click', () => {
  document.querySelectorAll('.tran-hidden').forEach(el => el.classList.add('revealed'));
  document.querySelectorAll('.tran-char-exp').forEach(el => el.classList.add('revealed'));
});

// ── Display translation result ───────────────────────────────────
function displayTranslation(text) {
  // strip <think>…</think>
  text = text.replace(/<think>[\s\S]*?<\/think>/g, '');
  text = cleanText(text);

  const blocks = splitBlocks(text);
  let html = '';
  _origTexts = [];

  for (const block of blocks) {
    if (block.type === 'original') {
      const fmt = formatOrig(block.content);
      _origTexts.push(fmt);
      html += `<div class="tran-block">
        <div class="tran-block-title">📜 原文</div>
        <div class="tran-original">${fmt}</div>`;
    } else if (block.type === 'translation') {
      html += `<div class="tran-block-title">🔤 語譯</div>
        <div class="tran-translation">${buildHiddenSentences(block.content)}</div>`;
    } else if (block.type === 'breakdown') {
      html += `<div class="tran-breakdown"><strong>🔍 逐字解釋</strong>${buildBreakdown(block.content)}</div>
        </div>`;
    }
  }

  const result = document.getElementById('tran-result');
  result.innerHTML = html;
  setupCharClicks();
  document.getElementById('btn-reveal-all').classList.remove('hidden');

  if (_origTexts.length > 0) showFloat();
}

// ── Block parser ─────────────────────────────────────────────────
function splitBlocks(text) {
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

function cleanText(t) {
  t = t.replace(/^\*+\s*$/gm,'');
  t = t.replace(/^-{3,}\s*$/gm,'');
  t = t.replace(/\n{3,}/g,'\n\n');
  return t.trim();
}

function cleanSection(t) {
  return t.trim().replace(/^[：:]+/,'').replace(/[：:]+$/,'').replace(/^\*+/,'').replace(/\*+$/,'').trim();
}

function formatOrig(t) {
  return cleanSection(t)
    .replace(/\n/g,'<br>')
    .replace(/\*\*([\s\S]*?)\*\*/g,'<span class="tran-common">$1</span>');
}

function buildHiddenSentences(t) {
  t = cleanSection(t);
  return t.split('\n').filter(s => s.trim()).map(s =>
    `<div class="tran-sentence-wrap"><span class="tran-hidden click-reveal">${escHtml(s.trim())}</span></div>`
  ).join('');
}

function buildBreakdown(t) {
  t = cleanSection(t)
    .replace(/\*\*([\s\S]*?)\*\*/g,'<span class="tran-common">$1</span>');
  return t.split('\n').filter(s=>s.trim()).map(s => {
    s = s.replace(/^\*+/,'').replace(/\*+$/,'').trim();
    const parts = s.split(/[：:]/);
    if (parts.length >= 2) {
      const ch   = parts[0].trim();
      const exp  = parts.slice(1).join(':').trim();
      if (/[\u4e00-\u9fff]/.test(ch)) {
        return `<div class="tran-char-item">
          <span class="tran-char">${escHtml(ch)}</span>
          <div class="tran-char-exp">：${escHtml(exp)}</div>
        </div>`;
      }
    }
    return s.trim() ? `<div>${s}</div>` : '';
  }).join('');
}

function setupCharClicks() {
  // Click-to-reveal hidden sentences
  document.querySelectorAll('.click-reveal').forEach(el => {
    el.addEventListener('click', function() { this.classList.toggle('revealed'); });
  });
  // Click to toggle character explanation
  document.querySelectorAll('.tran-char').forEach(ch => {
    ch.addEventListener('click', function() {
      const exp = this.nextElementSibling;
      if (exp && exp.classList.contains('tran-char-exp')) {
        exp.classList.toggle('revealed');
      }
    });
  });
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Floating panel ───────────────────────────────────────────────
function initFloat() {
  const isMobile = window.innerWidth <= 768;
  if (!isMobile) return;
  _floatEnabled = localStorage.getItem('tranFloatEnabled') !== 'false';
}

function showFloat() {
  const isMobile = window.innerWidth <= 768;
  if (!isMobile || !_floatEnabled) return;
  const body = document.getElementById('float-body');
  body.innerHTML = _origTexts.join('<br><br>');
  document.getElementById('tran-float').classList.add('show');
  document.getElementById('tran-reopen').classList.remove('show');
}

function hideFloat() {
  document.getElementById('tran-float').classList.remove('show');
  if (_origTexts.length > 0 && window.innerWidth <= 768) {
    document.getElementById('tran-reopen').classList.add('show');
  }
}

document.getElementById('float-close').addEventListener('click', () => {
  _floatEnabled = false;
  localStorage.setItem('tranFloatEnabled', 'false');
  hideFloat();
});

document.getElementById('tran-reopen').addEventListener('click', () => {
  _floatEnabled = true;
  localStorage.setItem('tranFloatEnabled', 'true');
  showFloat();
  document.getElementById('tran-reopen').classList.remove('show');
});

// Mobile resize handle
(function() {
  const panel = document.getElementById('tran-float');
  let isResize = false, startY = 0, startH = 0;
  panel.addEventListener('touchstart', e => {
    const rect = panel.getBoundingClientRect();
    if ((e.touches[0].clientY) > rect.bottom - 20) {
      isResize = true; startY = e.touches[0].clientY; startH = panel.offsetHeight;
      e.preventDefault();
    }
  }, {passive:false});
  document.addEventListener('touchmove', e => {
    if (!isResize) return;
    const h = Math.min(window.innerHeight * .8, Math.max(150, startH + (e.touches[0].clientY - startY)));
    panel.style.height = h + 'px';
  });
  document.addEventListener('touchend', () => { isResize = false; });
  window.addEventListener('resize', () => { initFloat(); if (_origTexts.length) showFloat(); });
})();

// ── UI helpers ────────────────────────────────────────────────────
function setLoading(show, msg = '正在翻譯中，請稍候…') {
  const el = document.getElementById('tran-loading');
  const msgEl = document.getElementById('tran-loading-msg');
  el.style.display = show ? 'block' : 'none';
  msgEl.textContent = msg;
  document.getElementById('btn-translate').disabled = show;
  document.getElementById('btn-quiz').disabled = show;
}

function hideResults() {
  document.getElementById('tran-result').innerHTML = '';
  document.getElementById('btn-reveal-all').classList.add('hidden');
  document.getElementById('quiz-section').classList.add('hidden');
}

function showError(msg) {
  const el = document.getElementById('tran-error');
  el.textContent = msg;
  el.style.display = 'block';
}

function hideError() {
  document.getElementById('tran-error').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
