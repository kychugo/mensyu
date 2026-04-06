<?php
/**
 * includes/footer.php — Common HTML footer
 */
?>
</main>
</div><!-- end md:ml-52 -->

<footer class="hidden md:block md:ml-52 bg-ink text-paper text-center text-xs py-4 opacity-60">
  &copy; <?= date('Y') ?> 文樞 Mensyu — DSE 文言文學習平台
</footer>
<script>
function handleLogout(e) {
  e.preventDefault();
  const fd = new FormData();
  fd.append('action', 'logout');
  fetch('/api/auth.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { window.location.href = d.redirect || '/'; })
    .catch(() => { window.location.href = '/'; });
}
</script>
</body>
</html>
