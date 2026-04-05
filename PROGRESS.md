# 文樞（Mensyu）開發進度追蹤

> 最後更新：2026-04-05

## 進度總覽

| Phase | 名稱 | 狀態 |
|-------|------|------|
| Phase 0  | 資料準備（essays.json） | ✅ 完成 |
| Phase 1  | PHP 基礎架構 | ✅ 完成 |
| Phase 2  | 登入系統 | ✅ 完成 |
| Phase 3  | AI 調用模組 | ✅ 完成 |
| Phase 4  | 翻譯 + 範文 | ✅ 完成 |
| Phase 5  | 學習關卡系統 | ✅ 完成 |
| Phase 6  | 遊戲廳 | ✅ 完成 |
| Phase 7  | 古人茶館 | ✅ 完成 |
| Phase 8  | SEO + 響應式 | ✅ 完成 |
| Phase 9  | 成就系統 + 最終整合 | ✅ 完成 |
| Phase 10 | 自動資料庫安裝 + 偽 Cron | ✅ 完成 |
| Phase 11 | GeoIP 地區限制 + VPN 封鎖 | ✅ 完成 |
| Phase 12 | 管理面板 | ✅ 完成 |
| Phase 13 | 文檔（README + DEPLOY 指南） | ✅ 完成 |

---

## Phase 0 — 資料準備 ✅
- [x] `data/essays.json` — 16 篇 DSE 文言範文

## Phase 1 — PHP 基礎架構 ✅
- [x] `config/db.php` — MySQL PDO 連線封裝 + 自動建表觸發
- [x] `config/ai.php` — AI API 金鑰與模型設定
- [x] `config/local.php.example` — 憑證範本
- [x] `includes/session.php` — Session 管理、CSRF token、is_admin() 輔助函數
- [x] `includes/header.php` — 公用頭部 + 響應式導航（管理員顯示 ⚙️ 管理入口）
- [x] `includes/footer.php` — 公用底部
- [x] `index.php` — 主路由分發 + geo guard + 偽 cron + 使用追蹤
- [x] `.htaccess` — URL 重寫 + 安全設定 + admin API 路由

## Phase 2 — 登入系統 ✅
- [x] `api/auth.php` — 注冊/登入/登出 API（第一位用戶自動成為管理員）
- [x] `pages/login.php` — 登入頁面
- [x] `pages/register.php` — 注冊頁面

## Phase 3 — AI 調用模組 ✅
- [x] `api/ai_text.php` — 文字 AI（model fallback：deepseek→glm→qwen-large→qwen-safety）
- [x] `api/ai_image.php` — 圖片 AI（model fallback：gptimage→wan-image→qwen-image→klein→zimage→flux）

## Phase 4 — 翻譯 + 範文 ✅
- [x] `api/essays.php` — 範文列表/內容 API
- [x] `pages/translate.php` — 翻譯頁面（DSE 篇目選擇 + 任意輸入）

## Phase 5 — 學習關卡系統 ✅
- [x] `api/progress.php` — 用戶進度讀寫 API
- [x] `pages/learning.php` — 學習關卡頁（作者選擇、4 關架構、讀→練→驗）

## Phase 6 — 遊戲廳 ✅
- [x] `pages/games.php` — 打磚塊（Breakout）+ 文言配對（Matching Game）

## Phase 7 — 古人茶館 ✅
- [x] `api/posts.php` — 茶館貼文 CRUD API
- [x] `api/cron_post.php` — 古人自動發文（支援偽 cron 函數調用 + HTTP key 觸發）
- [x] `pages/teahouse.php` — Instagram 風格 feed

## Phase 8 — SEO + 響應式 ✅
- [x] `sitemap.xml` — 站點地圖
- [x] `robots.txt` — 爬蟲規則
- [x] `pages/home.php` — 首頁（作者卡片、進度圓圈、快速入口）

## Phase 9 — 成就系統 + 最終整合 ✅
- [x] `pages/profile.php` — 個人主頁（進度、成就徽章）
- [x] `api/achievements.php` — 成就系統 API

## Phase 10 — 自動資料庫安裝 + 偽 Cron ✅
- [x] `config/install.php` — 所有資料庫表格自動建立（CREATE TABLE IF NOT EXISTS）
  - 表格：`users`, `user_progress`, `achievements`, `teahouse_posts`,
           `teahouse_comments`, `error_logs`, `usage_stats`, `geo_cache`, `app_settings`
  - 預設設定：`geo_guard_enabled=0`, `cron_interval_hours=2`
- [x] `config/db.php` — db_connect() 觸發 install.php（使用 `data/.installed` 旗標檔）
- [x] `api/cron_post.php` — 重構為可 include 調用的 `cron_run()` 函數
- [x] `index.php` — 偽 Cron：每 ~10 次請求檢查一次，超時自動發文
- [x] **無需 cmd / curl / 手動 SQL**

## Phase 11 — GeoIP 地區限制 + VPN 封鎖 ✅
- [x] `includes/geo_guard.php`
  - 調用 ip-api.com（免費，無需 API key）
  - 結果快取至 `geo_cache` 表（24 小時 TTL）
  - 封鎖非 HK IP 及 proxy 偵測
  - 管理員帳戶永久繞過
  - 私有/本地 IP 永久繞過（本地開發）
  - API 不可用時 fail-open（不阻擋用戶）
- [x] `index.php` — 調用 `geo_guard()`
- [x] 可在管理面板 → 系統設定 隨時開關

## Phase 12 — 管理面板 ✅
- [x] `includes/session.php` — 新增 `session_is_admin()`, `session_require_admin()`
- [x] `includes/error_tracker.php`
  - PHP 錯誤處理器（WARNING + ERROR + EXCEPTION）
  - `error_log_db()` — 寫入 `error_logs` 表
  - `usage_track()` — 寫入 `usage_stats` 表
  - `get_real_ip()` — 支援 Cloudflare / X-Forwarded-For
- [x] `api/auth.php` — 第一位用戶自動成為管理員；登入時儲存 `is_admin` 至 session；封禁用戶拒絕登入
- [x] `api/admin.php` — 完整 Admin REST API
  - GET: `dashboard` / `users` / `errors` / `usage` / `posts` / `settings`
  - POST: `toggle_admin` / `toggle_ban` / `delete_user` / `clear_errors` / `delete_post` / `delete_comment` / `run_cron` / `update_setting`
- [x] `pages/admin.php` — 管理面板 UI（7 個頁籤）
  - 📊 儀表板：用戶數、貼文數、錯誤統計、瀏覽量卡片 + 最近記錄
  - 👥 用戶管理：分頁列表 + 設為管理員 / 封禁 / 刪除
  - 🐛 錯誤日誌：分頁 + 級別篩選 + 一鍵清除
  - 📈 使用統計：各頁面瀏覽橫條圖 + 每日趨勢 + AI 調用次數
  - 📝 內容管理：茶館貼文列表 + 一鍵刪除
  - ⏰ Cron / 發文：顯示上次執行時間 + 手動觸發按鈕
  - ⚙️ 系統設定：GeoIP 開關 / 發文間隔 / 平台名稱
- [x] `includes/header.php` — 管理員顯示 ⚙️ 管理入口
- [x] `.htaccess` — 允許 `api/admin.php` 路由

## Phase 13 — 文檔 ✅
- [x] `README.md` — 完整項目說明（功能、架構、目錄結構）
- [x] `DEPLOY.md` — 逐步部署指南（**無需 cmd，無需手動 SQL，無需 Cron 設定**）
- [x] `PROGRESS.md` — 本文件

---

## 資料庫表格清單

| 表格名 | 用途 |
|--------|------|
| `users` | 用戶帳戶（含 is_admin, is_banned） |
| `user_progress` | 學習進度 |
| `achievements` | 成就徽章 |
| `teahouse_posts` | 茶館貼文 |
| `teahouse_comments` | 茶館留言 |
| `error_logs` | PHP 錯誤/例外記錄 |
| `usage_stats` | 頁面瀏覽統計 |
| `geo_cache` | GeoIP 查詢快取 |
| `app_settings` | 系統設定（KV store） |

---

## 已知限制 / 後續優化方向

- [ ] 偽 Cron 依賴用戶訪問，若長時間無人訪問則不會自動發文
- [ ] ip-api.com 免費方案每分鐘限制 45 次請求（快取後影響較小）
- [ ] 可考慮使用 MaxMind GeoLite2 本地資料庫取代 ip-api.com
- [ ] 錯誤日誌目前保留最新 2000 條，可考慮按日期分頁歸檔
