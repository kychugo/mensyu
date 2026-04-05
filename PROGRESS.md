# 文樞（Mensyu）開發進度追蹤

> 最後更新：2026-04-05

## 進度總覽

| Phase | 名稱 | 狀態 |
|-------|------|------|
| Phase 0 | 資料準備（essays.json） | ✅ 完成 |
| Phase 1 | PHP 基礎架構 | ✅ 完成 |
| Phase 2 | 登入系統 | ✅ 完成 |
| Phase 3 | AI 調用模組 | ✅ 完成 |
| Phase 4 | 翻譯 + 範文 | ✅ 完成 |
| Phase 5 | 學習關卡系統 | ✅ 完成 |
| Phase 6 | 遊戲廳 | ✅ 完成 |
| Phase 7 | 古人茶館 | ✅ 完成 |
| Phase 8 | SEO + 響應式 | ✅ 完成 |
| Phase 9 | 成就系統 + 最終整合 | ✅ 完成 |

---

## Phase 0 — 資料準備 ✅
- [x] `data/essays.json` — 16 篇 DSE 文言範文（無 vocab 欄位）

## Phase 1 — PHP 基礎架構 ✅
- [x] `config/db.php` — MySQL PDO 連線封裝
- [x] `config/ai.php` — AI API 金鑰與模型設定
- [x] `includes/session.php` — Session 管理、CSRF token
- [x] `includes/header.php` — 公用頭部 + 響應式導航
- [x] `includes/footer.php` — 公用底部
- [x] `index.php` — 主路由分發
- [x] `.htaccess` — URL 重寫 + 安全設定

## Phase 2 — 登入系統 ✅
- [x] `api/auth.php` — 注冊/登入/登出 API
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
- [x] `api/cron_post.php` — 古人自動發文（cron job）
- [x] `pages/teahouse.php` — Instagram 風格 feed

## Phase 8 — SEO + 響應式 ✅
- [x] `sitemap.xml` — 站點地圖
- [x] `robots.txt` — 爬蟲規則
- [x] `pages/home.php` — 首頁（作者卡片、進度圓圈、快速入口）

## Phase 9 — 成就系統 + 最終整合 ✅
- [x] `pages/profile.php` — 個人主頁（進度、成就徽章）
- [x] `api/achievements.php` — 成就系統 API

---

## 資料庫連線資訊
- Host: `sql111.infinityfree.com`
- Database: `if0_41581260_mensyu`
- 建表 SQL 見 PLAN.md 第 2.4 節

## 部署後操作清單
- [ ] 在 MySQL 執行 PLAN.md 中的建表 SQL
- [ ] 設置 cron job：每 2 小時執行 `api/cron_post.php`
- [ ] 到 Google Search Console 提交 `sitemap.xml`
- [ ] 確認 `.htaccess` 在主機上生效（需 mod_rewrite）
