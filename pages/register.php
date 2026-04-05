<?php
/**
 * pages/register.php
 */

require_once __DIR__ . '/../includes/session.php';

if (session_check_auth()) {
    header('Location: /');
    exit;
}

$page_title  = '注冊';
$page_active = '';
include __DIR__ . '/../includes/header.php';
$csrf = csrf_token_generate();
?>
<div class="min-h-screen flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-ink tracking-widest">文樞</h1>
      <p class="text-gold mt-1">建立帳戶</p>
    </div>

    <div id="msg" class="hidden mb-4 rounded-lg p-3 text-sm text-center"></div>

    <form id="reg-form" class="bg-white rounded-2xl shadow-lg p-6 space-y-4">
      <input type="hidden" name="action" value="register">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <div>
        <label class="block text-sm font-medium text-ink mb-1">用戶名稱</label>
        <input type="text" name="username" required autocomplete="username" minlength="2" maxlength="50"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gold text-sm"
               placeholder="2–50 字元，可包含漢字">
      </div>
      <div>
        <label class="block text-sm font-medium text-ink mb-1">密碼</label>
        <input type="password" name="password" required autocomplete="new-password" minlength="6"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gold text-sm"
               placeholder="至少 6 個字元">
      </div>
      <div>
        <label class="block text-sm font-medium text-ink mb-1">確認密碼</label>
        <input type="password" name="confirm" required autocomplete="new-password"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gold text-sm">
      </div>
      <button type="submit"
              class="w-full bg-ink text-gold font-bold py-2.5 rounded-lg hover:bg-ink-light transition-colors tracking-widest">
        注　冊
      </button>
    </form>

    <p class="text-center text-sm text-ink opacity-60 mt-4">
      已有帳戶？ <a href="/login" class="text-gold hover:underline">立即登入</a>
    </p>
  </div>
</div>

<script>
document.getElementById('reg-form').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const btn  = form.querySelector('button[type=submit]');
  btn.disabled = true;
  btn.textContent = '注冊中…';

  const res  = await fetch('/api/auth.php', { method: 'POST', body: new FormData(form) });
  const data = await res.json();
  const msg  = document.getElementById('msg');
  msg.classList.remove('hidden', 'bg-green-100', 'text-green-700', 'bg-red-100', 'text-red-700');

  if (data.success) {
    msg.classList.add('bg-green-100', 'text-green-700');
    msg.textContent = '注冊成功！正在跳轉…';
    msg.classList.remove('hidden');
    setTimeout(() => window.location.href = '/', 900);
  } else {
    msg.classList.add('bg-red-100', 'text-red-700');
    msg.textContent = data.message;
    msg.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = '注　冊';
  }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
