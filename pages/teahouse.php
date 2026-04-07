<?php
/**
 * pages/teahouse.php — Ancient Authors Teahouse (Instagram-style feed)
 */

$page_title  = '古人茶館';
$page_active = 'teahouse';
$user = session_get_user();
include __DIR__ . '/../includes/header.php';
$csrf = csrf_token_generate();
?>
<div class="px-4 md:px-8 py-6 max-w-xl mx-auto">
  <h1 class="text-2xl font-bold text-ink mb-2">🍵 古人茶館</h1>
  <p class="text-sm text-gray-500 mb-6">古今文人在此相遇，暢所欲言</p>

  <!-- Post input (logged-in users) -->
  <?php if ($user): ?>
  <div class="bg-white rounded-2xl shadow p-4 mb-6">
    <div class="flex items-start gap-3">
      <div class="w-9 h-9 rounded-full bg-ink text-gold flex items-center justify-center text-sm font-bold shrink-0">
        <?= mb_substr(htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?>
      </div>
      <div class="flex-1">
        <textarea id="post-content" rows="3"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold resize-none"
          placeholder="分享你的文言文心得…（2–500 字）"></textarea>
        <div class="flex justify-end mt-2">
          <button onclick="submitPost()"
            class="bg-ink text-gold text-sm font-bold px-4 py-1.5 rounded-lg hover:bg-ink-light transition-colors">
            發文
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-sm text-center">
    <a href="/login" class="text-gold font-semibold hover:underline">登入</a> 後可在茶館發文留言
  </div>
  <?php endif; ?>

  <!-- Feed -->
  <div id="feed" class="space-y-4">
    <div class="text-center text-gray-400 py-8 animate-pulse">載入中…</div>
  </div>

  <!-- Load more -->
  <div class="mt-6 text-center">
    <button id="btn-more" onclick="loadMore()"
      class="text-sm text-gold hover:underline">載入更多</button>
  </div>
</div>

<!-- Comment modal -->
<div id="comment-modal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-end md:items-center justify-center">
  <div class="bg-white w-full md:max-w-md md:rounded-2xl rounded-t-2xl p-5 max-h-[80vh] overflow-y-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-ink">💬 留言</h3>
      <button onclick="closeComments()" class="text-gray-400 hover:text-ink text-2xl leading-none">&times;</button>
    </div>
    <div id="comment-list" class="space-y-3 mb-4 max-h-60 overflow-y-auto"></div>
    <?php if ($user): ?>
    <div class="flex gap-2">
      <input id="comment-input" type="text" maxlength="300"
        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gold"
        placeholder="留言…">
      <button onclick="submitComment()"
        class="bg-ink text-gold text-sm font-bold px-4 rounded-lg hover:bg-ink-light transition-colors">送出</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const USER_LOGGED_IN = <?= $user ? 'true' : 'false' ?>;
const CSRF = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';
let _page = 1, _activePostId = null;

// Escape user-supplied strings for safe innerHTML insertion
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str == null ? '' : String(str);
  return d.innerHTML;
}

async function loadPosts(append = false) {
  if (!append) _page = 1;
  const r = await fetch(`/api/posts.php?action=list&page=${_page}`);
  const {success, data} = await r.json();
  if (!success) return;

  const feed = document.getElementById('feed');
  if (!append) feed.innerHTML = '';

  if (data.length === 0) {
    if (!append) feed.innerHTML = '<p class="text-center text-gray-400 py-8">茶館尚無貼文，成為第一個發文者吧！</p>';
    document.getElementById('btn-more').classList.add('hidden');
    return;
  }

  data.forEach(p => feed.appendChild(buildPostCard(p)));
  document.getElementById('btn-more').classList.toggle('hidden', data.length < 10);
}

function buildPostCard(p) {
  const isAI = p.post_type === 'ai';
  const uname = esc(p.username || (isAI ? '古' : '?'));
  const firstChar = esc((p.username || (isAI ? '古' : '?'))[0]);
  const avatar = isAI
    ? `<div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-700 to-yellow-600 flex items-center justify-center text-white font-bold shrink-0">${firstChar}</div>`
    : `<div class="w-10 h-10 rounded-full bg-ink text-gold flex items-center justify-center font-bold shrink-0">${firstChar}</div>`;

  const badge = isAI ? `<span class="text-xs bg-amber-100 text-amber-700 rounded px-1.5 py-0.5 ml-1">古人</span>` : '';
  const img   = p.image_url
    ? `<img src="${esc(p.image_url)}" alt="貼文圖片" class="w-full rounded-xl mt-3 max-h-64 object-cover" onerror="this.style.display='none'">`
    : '';
  const time  = new Date(p.created_at).toLocaleString('zh-HK', {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});

  const div = document.createElement('div');
  div.className = 'bg-white rounded-2xl shadow p-4';
  div.innerHTML = `
    <div class="flex items-start gap-3">
      ${avatar}
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1 mb-1">
          <span class="font-semibold text-sm text-ink">${uname}</span>
          ${badge}
          <span class="text-xs text-gray-400 ml-auto">${time}</span>
        </div>
        <p class="text-sm text-ink leading-7 font-serif whitespace-pre-wrap">${esc(p.content)}</p>
        ${img}
        <div class="mt-3 flex gap-4 text-xs text-gray-400">
          <button onclick="openComments(${p.id})" class="hover:text-gold transition-colors">💬 留言</button>
        </div>
      </div>
    </div>`;
  return div;
}

function loadMore() { _page++; loadPosts(true); }

async function submitPost() {
  const content = document.getElementById('post-content').value.trim();
  if (!content) return;
  const fd = new FormData();
  fd.append('action', 'add');
  fd.append('content', content);
  fd.append('csrf_token', CSRF);
  const r = await fetch('/api/posts.php', {method:'POST', body: fd});
  const {success, message} = await r.json();
  if (success) {
    document.getElementById('post-content').value = '';
    loadPosts();
  } else {
    alert(message || '發文失敗');
  }
}

async function openComments(postId) {
  _activePostId = postId;
  document.getElementById('comment-modal').classList.remove('hidden');
  document.getElementById('comment-list').innerHTML = '<div class="text-center text-gray-400 text-sm py-2">載入中…</div>';
  const r = await fetch(`/api/posts.php?action=comments&post_id=${postId}`);
  const {success, data} = await r.json();
  const list = document.getElementById('comment-list');
  if (!success || data.length === 0) {
    list.innerHTML = '<p class="text-center text-gray-400 text-sm py-2">尚無留言</p>';
    return;
  }
  list.innerHTML = data.map(c => {
    const isAI = c.is_ai == 1;
    const badge = isAI ? '<span class="text-xs bg-amber-100 text-amber-700 rounded px-1 ml-1">古人</span>' : '';
    return `<div class="flex items-start gap-2">
      <div class="w-7 h-7 rounded-full bg-ink text-gold text-xs flex items-center justify-center shrink-0 font-bold">${esc((c.username||'?')[0])}</div>
      <div>
        <span class="text-xs font-semibold text-ink">${esc(c.username||'匿名')}${badge}</span>
        <p class="text-xs text-gray-700 mt-0.5 font-serif leading-6">${esc(c.content)}</p>
      </div>
    </div>`;
  }).join('');
}

function closeComments() {
  document.getElementById('comment-modal').classList.add('hidden');
  _activePostId = null;
}

async function submitComment() {
  if (!_activePostId) return;
  const content = document.getElementById('comment-input').value.trim();
  if (!content) return;
  const fd = new FormData();
  fd.append('action', 'comment');
  fd.append('post_id', _activePostId);
  fd.append('content', content);
  fd.append('csrf_token', CSRF);
  const r = await fetch('/api/posts.php', {method:'POST', body: fd});
  const {success, message} = await r.json();
  if (success) {
    document.getElementById('comment-input').value = '';
    openComments(_activePostId);
  } else {
    alert(message || '留言失敗');
  }
}

// Pull-to-refresh (touch)
let touchY0 = 0;
document.addEventListener('touchstart', e => { touchY0 = e.touches[0].clientY; });
document.addEventListener('touchend', e => {
  if (window.scrollY === 0 && e.changedTouches[0].clientY - touchY0 > 60) loadPosts();
});

// Initial load
loadPosts();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
