# 文樞 Mensyu — 部署指南

> 無需命令列、無需手動 SQL、無需設定 Cron Job！

---

## 目錄

1. [前置準備](#前置準備)
2. [Step 1 — 上傳檔案](#step-1--上傳檔案)
3. [Step 2 — 建立設定檔](#step-2--建立設定檔)
4. [Step 3 — 訪問網站（自動建表）](#step-3--訪問網站自動建表)
5. [Step 4 — 注冊第一個帳戶（管理員）](#step-4--注冊第一個帳戶管理員)
6. [管理面板說明](#管理面板說明)
7. [GeoIP 地區限制設定](#geoip-地區限制設定)
8. [AI 自動發文（Cron）](#ai-自動發文cron)
9. [常見問題](#常見問題)

---

## 前置準備

| 項目 | 說明 |
|------|------|
| [InfinityFree](https://app.infinityfree.com) 帳戶 | 免費 PHP + MySQL 主機 |
| MySQL 資料庫 | 在 InfinityFree 控制台建立一個資料庫 |
| Pollinations.ai API Key | 前往 [pollinations.ai](https://pollinations.ai) 免費取得 |
| FTP 工具 / 檔案管理器 | 例如 FileZilla，或使用 InfinityFree 網頁檔案管理器 |

---

## Step 1 — 上傳檔案

1. 將本倉庫所有檔案（含子資料夾）上傳至 InfinityFree 的 **`htdocs/`** 資料夾。
2. 確保以下目錄/檔案都已上傳：
   - `.htaccess`（重要！需啟用 mod_rewrite）
   - `api/`, `config/`, `data/`, `includes/`, `pages/`
   - `index.php`, `sitemap.xml`, `robots.txt`
3. **不要上傳** `config/local.php`（機密檔案，下一步建立）

> ⚠️ InfinityFree 預設不顯示隱藏檔案（`.htaccess`）。上傳後請確認它存在。

---

## Step 2 — 建立設定檔

### 方法一：網頁檔案管理器（推薦）

1. 在 InfinityFree 控制台開啟「**Online File Manager**」
2. 導覽至 `htdocs/config/`
3. 點擊「New File」→ 命名為 `local.php`
4. 貼入以下內容並填入你的資料：

```php
<?php
// 資料庫設定（在 InfinityFree MySQL Databases 頁面查閱）
define('DB_HOST', 'sql111.infinityfree.com');  // 你的 MySQL 主機
define('DB_PORT', '3306');
define('DB_NAME', 'if0_XXXXXXX_mensyu');       // 你的資料庫名稱
define('DB_USER', 'if0_XXXXXXX');              // 你的資料庫用戶名
define('DB_PASS', 'YOUR_DB_PASSWORD');         // 你的資料庫密碼

// Pollinations.ai API Keys
define('AI_SECRET_KEY',      'sk_YOUR_SECRET_KEY_HERE');
define('AI_PUBLISHABLE_KEY', 'pk_YOUR_PUBLISHABLE_KEY_HERE');
```

5. 儲存檔案。

### 方法二：FTP

1. 複製 `config/local.php.example` 為 `config/local.php`
2. 用文字編輯器填入真實資料
3. 透過 FTP 上傳至 `htdocs/config/local.php`

> 🔒 `config/local.php` 已列入 `.gitignore`，不會被提交到 Git。

---

## Step 3 — 訪問網站（自動建表）

1. 在瀏覽器開啟你的網站（例如 `https://yoursite.infinityfreeapp.com`）
2. **資料庫表格會自動建立**——無需執行任何 SQL！
3. 系統會自動建立以下表格：
   - `users`, `user_progress`, `achievements`
   - `teahouse_posts`, `teahouse_comments`
   - `error_logs`, `usage_stats`, `geo_cache`, `app_settings`

> 📝 建表完成後會在 `data/` 目錄建立一個 `.installed` 標記檔。若要重新安裝，刪除此檔案即可。

---

## Step 4 — 注冊第一個帳戶（管理員）

1. 點擊右上角「注冊」或前往 `/register`
2. 填寫用戶名稱和密碼
3. **第一位注冊的用戶自動成為平台管理員** 👑
4. 登入後導航欄會出現「⚙️ 管理」入口

---

## 管理面板說明

前往 `/admin`（需管理員帳戶）：

| 頁籤 | 功能 |
|------|------|
| 📊 儀表板 | 用戶數、貼文數、錯誤統計、頁面瀏覽量 |
| 👥 用戶管理 | 列出所有用戶、設定/撤銷管理員、封禁/解封、刪除 |
| 🐛 錯誤日誌 | 查看所有 PHP 錯誤、例外、GeoIP 封鎖記錄 |
| 📈 使用統計 | 各頁面瀏覽量、每日趨勢、AI 調用次數 |
| 📝 內容管理 | 查看並刪除茶館貼文 |
| ⏰ Cron / 發文 | 手動觸發古人 AI 發文 |
| ⚙️ 系統設定 | 開關地區限制、調整發文間隔 |

---

## GeoIP 地區限制設定

平台支援**只允許香港用戶訪問**並封鎖 VPN/代理伺服器。

### 啟用方法

1. 前往管理面板 → 「⚙️ 系統設定」
2. 開啟「🔒 香港地區限制 + VPN 封鎖」開關
3. 設定立即生效（使用 ip-api.com 查詢，結果快取 24 小時）

> ⚠️ **注意**：管理員帳戶永遠不受地區限制影響。
> 建議先確認自己在香港才開啟此功能，以免把自己鎖在外面。
> 若 ip-api.com 不可用，系統會自動允許所有請求（fail-open）。

---

## AI 自動發文（Cron）

**無需設定任何 Cron Job！**

本平台使用**偽 Cron（Pseudo-Cron）**機制：
- 每次有用戶訪問頁面時，系統會自動檢查是否已超過設定的發文間隔（預設 2 小時）
- 若是，系統會自動讓蘇軾或韓愈發一則 AI 貼文到茶館
- 你也可以在管理面板 → Cron / 發文 → 手動觸發

**調整發文間隔**：管理面板 → 系統設定 → 自動發文間隔

---

## 常見問題

### Q: 網站顯示資料庫錯誤？
**A:** 確認 `config/local.php` 的 DB_HOST、DB_NAME、DB_USER、DB_PASS 是否正確。  
InfinityFree 的 MySQL 主機格式通常是 `sqlXXX.infinityfree.com`。

### Q: `.htaccess` 不生效？
**A:** InfinityFree 支援 mod_rewrite。確認 `.htaccess` 已上傳到 `htdocs/` 根目錄（注意隱藏檔案）。

### Q: AI 翻譯不工作？
**A:** 確認 `config/local.php` 中的 AI_SECRET_KEY 是否正確。  
AI 使用 [Pollinations.ai](https://pollinations.ai)，需要有效的 API Key。

### Q: 地區限制把我鎖在外面了怎麼辦？
**A:** 直接連到資料庫（透過 InfinityFree phpMyAdmin），執行：
```sql
UPDATE app_settings SET setting_value='0' WHERE setting_key='geo_guard_enabled';
```

### Q: 如何重置資料庫？
**A:** 在 InfinityFree phpMyAdmin 中刪除所有表格，然後刪除 `data/.installed` 檔案（透過檔案管理器），再訪問首頁即可重新安裝。

### Q: 如何新增管理員？
**A:** 在管理面板 → 用戶管理 → 找到目標用戶 → 點擊「設為管理員」。
