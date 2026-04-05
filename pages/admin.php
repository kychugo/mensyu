<?php
/**
 * pages/admin.php — Admin panel
 */

require_once __DIR__ . '/../config/db.php';

session_require_admin();

$page_title  = '管理面板';
$page_active = 'admin';
$csrf = csrf_token_generate();

include __DIR__ . '/../includes/header.php';
?>

<div class="px-4 md:px-8 py-6 max-w-6xl mx-auto">
  <div class="flex items-center gap-3 mb-6">
    <span class="text-3xl">⚙️</span>
    <div>
      <h1 class="text-2xl font-bold text-ink">管理面板</h1>
      <p class="text-xs text-gray-500">文樞 Mensyu — Admin Console</p>
    </div>
  </div>

  <!-- Tab bar -->
  <div class="flex gap-2 flex-wrap mb-6 border-b border-gray-200 pb-2">
    <?php foreach ([
      ['dashboard', '📊', '儀表板'],
      ['users',     '👥', '用戶管理'],
      ['errors',    '🐛', '錯誤日誌'],
      ['usage',     '📈', '使用統計'],
      ['content',   '📝', '內容管理'],
      ['cron',      '⏰', 'Cron / 貼文'],
      ['settings',  '⚙️', '系統設定'],
    ] as [$id, $ico, $lbl]): ?>
    <button data-tab="<?= $id ?>" onclick="switchTab('<?= $id ?>')"
      class="tab-btn px-4 py-1.5 rounded-full text-sm font-medium transition-colors hover:bg-gold hover:text-ink">
      <?= $ico ?> <?= $lbl ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- ── Dashboard ──────────────────────────────────────────────── -->
  <div id="tab-dashboard" class="tab-content">
    <div id="dash-stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="col-span-full text-center py-8 text-gray-400 animate-pulse">載入中…</div>
    </div>
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <h3 class="font-bold text-ink mb-2">🐛 最近錯誤</h3>
        <div id="dash-errors" class="space-y-2"></div>
      </div>
      <div>
        <h3 class="font-bold text-ink mb-2">👤 最近注冊</h3>
        <div id="dash-users" class="space-y-2"></div>
      </div>
    </div>
  </div>

  <!-- ── Users ──────────────────────────────────────────────────── -->
  <div id="tab-users" class="tab-content hidden">
    <div class="flex justify-between items-center mb-4">
      <h2 class="font-bold text-ink">用戶列表</h2>
      <span id="user-total" class="text-sm text-gray-500"></span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm bg-white rounded-xl shadow overflow-hidden">
        <thead class="bg-ink text-gold">
          <tr>
            <th class="px-3 py-2 text-left">ID</th>
            <th class="px-3 py-2 text-left">用戶名</th>
            <th class="px-3 py-2 text-left">注冊時間</th>
            <th class="px-3 py-2 text-center">管理員</th>
            <th class="px-3 py-2 text-center">封禁</th>
            <th class="px-3 py-2 text-center">操作</th>
          </tr>
        </thead>
        <tbody id="user-tbody"></tbody>
      </table>
    </div>
    <div id="user-pagination" class="flex gap-2 mt-3 justify-center"></div>
  </div>

  <!-- ── Error logs ─────────────────────────────────────────────── -->
  <div id="tab-errors" class="tab-content hidden">
    <div class="flex flex-wrap gap-2 items-center mb-4">
      <h2 class="font-bold text-ink flex-1">錯誤日誌</h2>
      <select id="error-level-filter" onchange="loadErrors(1)"
        class="text-sm border border-gray-200 rounded px-2 py-1 bg-white">
        <option value="">全部</option>
        <option value="ERROR">ERROR</option>
        <option value="WARNING">WARNING</option>
        <option value="EXCEPTION">EXCEPTION</option>
        <option value="GEO_BLOCK">GEO_BLOCK</option>
        <option value="PHP">PHP</option>
      </select>
      <button onclick="clearErrors()"
        class="bg-red-500 text-white text-xs px-3 py-1.5 rounded-lg hover:bg-red-600">
        清除日誌
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm bg-white rounded-xl shadow overflow-hidden">
        <thead class="bg-ink text-gold">
          <tr>
            <th class="px-3 py-2 text-left">時間</th>
            <th class="px-3 py-2 text-left">級別</th>
            <th class="px-3 py-2 text-left">訊息</th>
            <th class="px-3 py-2 text-left">URL</th>
            <th class="px-3 py-2 text-left">IP</th>
          </tr>
        </thead>
        <tbody id="error-tbody"></tbody>
      </table>
    </div>
    <div id="error-pagination" class="flex gap-2 mt-3 justify-center"></div>
  </div>

  <!-- ── Usage ──────────────────────────────────────────────────── -->
  <div id="tab-usage" class="tab-content hidden">
    <div class="flex gap-2 items-center mb-4">
      <h2 class="font-bold text-ink flex-1">使用統計</h2>
      <select id="usage-days" onchange="loadUsage()"
        class="text-sm border border-gray-200 rounded px-2 py-1 bg-white">
        <option value="1">今天</option>
        <option value="7" selected>7 天</option>
        <option value="30">30 天</option>
      </select>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl shadow p-4">
        <h3 class="font-bold text-sm text-ink mb-3">📄 各頁面瀏覽</h3>
        <div id="usage-by-page"></div>
      </div>
      <div class="bg-white rounded-xl shadow p-4">
        <h3 class="font-bold text-sm text-ink mb-3">📅 每日瀏覽量</h3>
        <div id="usage-daily"></div>
        <p class="text-xs text-gray-400 mt-2">AI 調用次數：<span id="usage-ai">—</span></p>
      </div>
    </div>
  </div>

  <!-- ── Content ────────────────────────────────────────────────── -->
  <div id="tab-content" class="tab-content hidden">
    <div class="flex justify-between items-center mb-4">
      <h2 class="font-bold text-ink">茶館貼文管理</h2>
      <span id="content-total" class="text-sm text-gray-500"></span>
    </div>
    <div class="space-y-3" id="content-list"></div>
    <div id="content-pagination" class="flex gap-2 mt-4 justify-center"></div>
  </div>

  <!-- ── Cron ───────────────────────────────────────────────────── -->
  <div id="tab-cron" class="tab-content hidden">
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow p-6 text-center">
      <div class="text-5xl mb-4">⏰</div>
      <h2 class="font-bold text-ink text-lg mb-2">古人自動發文</h2>
      <p class="text-sm text-gray-500 mb-4">
        平台會在每次有用戶訪問時自動檢查是否需要觸發發文。<br>
        你也可以在下方手動觸發一次。
      </p>
      <p class="text-xs text-gray-400 mb-6">
        上次執行：<span id="cron-last-run">載入中…</span>
      </p>
      <button onclick="runCron()"
        class="w-full bg-ink text-gold font-bold py-3 rounded-xl hover:bg-ink-light transition-colors">
        ▶ 立即觸發發文
      </button>
      <p id="cron-msg" class="mt-3 text-sm hidden"></p>
    </div>
  </div>

  <!-- ── Settings ───────────────────────────────────────────────── -->
  <div id="tab-settings" class="tab-content hidden">
    <div class="max-w-xl space-y-5" id="settings-form">
      <div class="text-center text-gray-400 py-8 animate-pulse">載入中…</div>
    </div>
  </div>
</div>

<script>
const CSRF = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';
let _userPage = 1, _errorPage = 1, _contentPage = 1;

// ── Tab switching ─────────────────────────────────────────────────
const tabLoaded = {};
function switchTab(id) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
  document.querySelectorAll('.tab-btn').forEach(el =>
    el.classList.toggle('bg-ink', false) && el.classList.toggle('text-gold', false)
  );
  document.getElementById('tab-' + id)?.classList.remove('hidden');
  document.querySelectorAll('.tab-btn').forEach(el => {
    if (el.dataset.tab === id) {
      el.classList.add('bg-ink', 'text-gold');
    } else {
      el.classList.remove('bg-ink', 'text-gold');
    }
  });
  if (!tabLoaded[id]) { tabLoaded[id] = true; loadTab(id); }
}

function loadTab(id) {
  if (id === 'dashboard') loadDashboard();
  if (id === 'users')     loadUsers(1);
  if (id === 'errors')    loadErrors(1);
  if (id === 'usage')     loadUsage();
  if (id === 'content')   loadContent(1);
  if (id === 'cron')      loadCronInfo();
  if (id === 'settings')  loadSettings();
}

// ── Dashboard ─────────────────────────────────────────────────────
async function loadDashboard() {
  const {stats, recent_errors, recent_users} = await api('GET', {action:'dashboard'});

  document.getElementById('dash-stats').innerHTML = [
    ['👥 用戶總數',   stats.total_users,  `今天 +${stats.users_today}`],
    ['📝 茶館貼文',   stats.total_posts,  ''],
    ['🐛 錯誤記錄',   stats.total_errors, `今天 ${stats.errors_today}`],
    ['📄 頁面瀏覽',   stats.views_week,   `今天 ${stats.views_today}`],
  ].map(([lbl, n, sub]) =>
    `<div class="bg-white rounded-xl shadow p-4 text-center">
       <div class="text-2xl font-bold text-ink">${n}</div>
       <div class="text-xs font-medium text-gray-600 mt-1">${lbl}</div>
       ${sub ? `<div class="text-xs text-gray-400 mt-0.5">${sub}</div>` : ''}
     </div>`
  ).join('');

  const errBox = document.getElementById('dash-errors');
  errBox.innerHTML = recent_errors.length ? recent_errors.map(e =>
    `<div class="bg-white rounded-lg p-2 shadow-sm text-xs">
       <span class="font-bold ${levelColor(e.error_level)}">[${e.error_level}]</span>
       <span class="ml-1 text-ink">${truncate(e.message, 80)}</span>
       <div class="text-gray-400 mt-0.5">${e.created_at}</div>
     </div>`
  ).join('') : '<p class="text-xs text-gray-400">無錯誤記錄 ✅</p>';

  const usrBox = document.getElementById('dash-users');
  usrBox.innerHTML = recent_users.map(u =>
    `<div class="bg-white rounded-lg p-2 shadow-sm text-xs flex justify-between">
       <span class="font-medium">${u.username} ${u.is_admin ? '👑' : ''}</span>
       <span class="text-gray-400">${u.created_at?.slice(0,10)}</span>
     </div>`
  ).join('');
}

// ── Users ─────────────────────────────────────────────────────────
async function loadUsers(page) {
  _userPage = page;
  const {data, total} = await api('GET', {action:'users', page});
  document.getElementById('user-total').textContent = `共 ${total} 名用戶`;
  document.getElementById('user-tbody').innerHTML = data.map(u =>
    `<tr class="border-t border-gray-100 hover:bg-amber-50">
       <td class="px-3 py-2 text-gray-500">${u.id}</td>
       <td class="px-3 py-2 font-medium">${u.username}</td>
       <td class="px-3 py-2 text-gray-400">${u.created_at?.slice(0,10)}</td>
       <td class="px-3 py-2 text-center">${u.is_admin ? '👑' : '—'}</td>
       <td class="px-3 py-2 text-center">${u.is_banned ? '🔒' : '—'}</td>
       <td class="px-3 py-2 text-center">
         <button onclick="toggleAdmin(${u.id})"
           class="text-xs px-2 py-1 rounded bg-yellow-100 hover:bg-yellow-200 mr-1">
           ${u.is_admin ? '撤銷管理員' : '設為管理員'}
         </button>
         <button onclick="toggleBan(${u.id})"
           class="text-xs px-2 py-1 rounded ${u.is_banned ? 'bg-green-100 hover:bg-green-200' : 'bg-red-100 hover:bg-red-200'} mr-1">
           ${u.is_banned ? '解除封禁' : '封禁'}
         </button>
         <button onclick="deleteUser(${u.id}, '${u.username}')"
           class="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-gray-200">
           刪除
         </button>
       </td>
     </tr>`
  ).join('');
  renderPager('user-pagination', total, 20, page, loadUsers);
}

async function toggleAdmin(id) {
  await apiPost({action:'toggle_admin', user_id:id});
  loadUsers(_userPage);
}
async function toggleBan(id) {
  await apiPost({action:'toggle_ban', user_id:id});
  loadUsers(_userPage);
}
async function deleteUser(id, name) {
  if (!confirm(`確定刪除用戶「${name}」？此操作不可逆。`)) return;
  await apiPost({action:'delete_user', user_id:id});
  loadUsers(_userPage);
}

// ── Errors ────────────────────────────────────────────────────────
async function loadErrors(page) {
  _errorPage = page;
  const level = document.getElementById('error-level-filter').value;
  const {data, total} = await api('GET', {action:'errors', page, level});
  document.getElementById('error-tbody').innerHTML = data.map(e =>
    `<tr class="border-t border-gray-100 hover:bg-red-50 align-top">
       <td class="px-3 py-2 text-gray-400 text-xs whitespace-nowrap">${e.created_at?.slice(0,16)}</td>
       <td class="px-3 py-2"><span class="font-bold text-xs ${levelColor(e.error_level)}">${e.error_level}</span></td>
       <td class="px-3 py-2 text-xs max-w-xs break-words">${e.message}</td>
       <td class="px-3 py-2 text-xs text-gray-400 max-w-[120px] truncate">${e.url || '—'}</td>
       <td class="px-3 py-2 text-xs text-gray-400">${e.ip_address || '—'}</td>
     </tr>`
  ).join('') || '<tr><td colspan="5" class="text-center py-4 text-gray-400">無錯誤記錄 ✅</td></tr>';
  renderPager('error-pagination', total, 30, page, loadErrors);
}

async function clearErrors() {
  const level = document.getElementById('error-level-filter').value;
  const label = level ? `[${level}] 級別的` : '所有';
  if (!confirm(`確定清除${label}錯誤日誌？`)) return;
  await apiPost({action:'clear_errors', level});
  tabLoaded['errors'] = false;
  loadErrors(1);
}

// ── Usage ─────────────────────────────────────────────────────────
async function loadUsage() {
  const days = document.getElementById('usage-days').value;
  const {by_page, daily, ai_calls} = await api('GET', {action:'usage', days});

  const pageBar = by_page.map(r => {
    const max = by_page[0]?.cnt || 1;
    const pct = Math.round(r.cnt / max * 100);
    return `<div class="flex items-center gap-2 mb-1">
      <span class="text-xs w-20 truncate text-gray-600">${r.page || '/'}</span>
      <div class="flex-1 bg-gray-100 rounded-full h-2">
        <div class="bg-gold h-2 rounded-full" style="width:${pct}%"></div>
      </div>
      <span class="text-xs text-gray-500 w-8 text-right">${r.cnt}</span>
    </div>`;
  }).join('');
  document.getElementById('usage-by-page').innerHTML = pageBar || '<p class="text-xs text-gray-400">暫無資料</p>';

  const dailyList = daily.map(r =>
    `<div class="flex justify-between text-xs py-0.5">
       <span class="text-gray-500">${r.dt}</span>
       <span class="font-semibold">${r.cnt}</span>
     </div>`
  ).join('');
  document.getElementById('usage-daily').innerHTML = dailyList || '<p class="text-xs text-gray-400">暫無資料</p>';
  document.getElementById('usage-ai').textContent = ai_calls;
}

// ── Content ───────────────────────────────────────────────────────
async function loadContent(page) {
  _contentPage = page;
  const {data, total} = await api('GET', {action:'posts', page});
  document.getElementById('content-total').textContent = `共 ${total} 篇貼文`;
  document.getElementById('content-list').innerHTML = data.map(p =>
    `<div class="bg-white rounded-xl shadow p-4 flex items-start gap-3">
       <div class="flex-1 min-w-0">
         <div class="flex items-center gap-2 mb-1">
           <span class="font-semibold text-sm">${p.username}</span>
           ${p.post_type === 'ai' ? '<span class="text-xs bg-amber-100 text-amber-700 rounded px-1">古人</span>' : ''}
           <span class="text-xs text-gray-400 ml-auto">${p.created_at?.slice(0,16)}</span>
         </div>
         <p class="text-xs text-gray-700 font-serif">${p.content}</p>
       </div>
       <button onclick="deletePost(${p.id})"
         class="shrink-0 text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">刪除</button>
     </div>`
  ).join('') || '<p class="text-gray-400 text-sm text-center py-8">無貼文</p>';
  renderPager('content-pagination', total, 20, page, loadContent);
}

async function deletePost(id) {
  if (!confirm('確定刪除此貼文（包含所有留言）？')) return;
  await apiPost({action:'delete_post', post_id:id});
  loadContent(_contentPage);
}

// ── Cron ──────────────────────────────────────────────────────────
async function loadCronInfo() {
  const {stats} = await api('GET', {action:'dashboard'});
  const last = stats.cron_last_run;
  document.getElementById('cron-last-run').textContent = last
    ? new Date(last * 1000).toLocaleString('zh-HK')
    : '從未執行';
}

async function runCron() {
  const btn = document.querySelector('#tab-cron button');
  btn.disabled = true; btn.textContent = '執行中…';
  const {success, message} = await apiPost({action:'run_cron'});
  const msg = document.getElementById('cron-msg');
  msg.classList.remove('hidden', 'text-green-600', 'text-red-500');
  msg.textContent = message;
  msg.classList.add(success ? 'text-green-600' : 'text-red-500');
  msg.classList.remove('hidden');
  btn.disabled = false; btn.textContent = '▶ 立即觸發發文';
}

// ── Settings ──────────────────────────────────────────────────────
async function loadSettings() {
  const {data} = await api('GET', {action:'settings'});
  document.getElementById('settings-form').innerHTML = `
    <div class="bg-white rounded-xl shadow p-5 space-y-4">
      <div class="flex justify-between items-center">
        <div>
          <div class="font-semibold text-sm text-ink">🔒 香港地區限制 + VPN 封鎖</div>
          <div class="text-xs text-gray-400 mt-0.5">啟用後只允許香港 IP，並封鎖代理/VPN</div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" id="geo-toggle" ${data.geo_guard_enabled === '1' ? 'checked' : ''}
            onchange="saveSetting('geo_guard_enabled', this.checked ? '1' : '0')"
            class="sr-only peer">
          <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-gold rounded-full peer
                      peer-checked:after:translate-x-full peer-checked:after:border-white
                      after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                      after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                      peer-checked:bg-gold"></div>
        </label>
      </div>
      <hr>
      <div class="flex justify-between items-center gap-3">
        <div>
          <div class="font-semibold text-sm text-ink">⏰ 自動發文間隔（小時）</div>
          <div class="text-xs text-gray-400">古人自動發文的時間間隔</div>
        </div>
        <input type="number" min="1" max="24" value="${data.cron_interval_hours || 2}"
          onchange="saveSetting('cron_interval_hours', this.value)"
          class="w-20 border border-gray-200 rounded px-2 py-1 text-sm">
      </div>
      <hr>
      <div class="flex justify-between items-center gap-3">
        <div>
          <div class="font-semibold text-sm text-ink">🏷 平台名稱</div>
        </div>
        <input type="text" value="${data.platform_name || '文樞 Mensyu'}"
          onblur="saveSetting('platform_name', this.value)"
          class="w-40 border border-gray-200 rounded px-2 py-1 text-sm">
      </div>
    </div>`;
}

async function saveSetting(key, value) {
  const {success} = await apiPost({action:'update_setting', setting_key:key, setting_value:value});
  if (!success) alert('儲存失敗');
}

// ── Pagination ────────────────────────────────────────────────────
function renderPager(containerId, total, perPage, current, loadFn) {
  const pages = Math.ceil(total / perPage);
  if (pages <= 1) { document.getElementById(containerId).innerHTML = ''; return; }
  const btns = [];
  for (let i = 1; i <= pages; i++) {
    btns.push(`<button onclick="${loadFn.name}(${i})"
      class="px-3 py-1 rounded text-sm ${i === current ? 'bg-ink text-gold' : 'bg-white hover:bg-gray-100'}">${i}</button>`);
  }
  document.getElementById(containerId).innerHTML = btns.join('');
}

// ── API helpers ───────────────────────────────────────────────────
async function api(method, params = {}) {
  let url = '/api/admin?' + new URLSearchParams(params);
  const r = await fetch(url, {method: 'GET'});
  return r.json();
}

async function apiPost(params = {}) {
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  for (const [k, v] of Object.entries(params)) fd.append(k, v);
  const r = await fetch('/api/admin', {method: 'POST', body: fd});
  return r.json();
}

// ── Helpers ───────────────────────────────────────────────────────
function levelColor(l) {
  return l === 'ERROR' || l === 'EXCEPTION' ? 'text-red-600'
       : l === 'WARNING' ? 'text-yellow-600'
       : l === 'GEO_BLOCK' ? 'text-blue-600'
       : 'text-gray-600';
}
function truncate(s, n) {
  return s && s.length > n ? s.slice(0, n) + '…' : s || '';
}

// ── Boot ──────────────────────────────────────────────────────────
switchTab('dashboard');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
