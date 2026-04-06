# 文樞（Mensyu）平台邏輯流程說明

## 平台概覽

文樞（Mensyu）是一個為 DSE 學生設計的文言文互動學習平台，包含 AI 翻譯、遊戲學習、古人茶館等功能。  
範文庫：**DSE中文12篇指定文言範文**（共 16 篇，官方簡稱「12篇」）。

---

## 目錄結構

```
mensyu/
├── index.php           # 主路由
├── .htaccess           # URL 重寫 / API 白名單
├── config/
│   ├── db.php          # 資料庫連接 + 自動安裝
│   ├── ai.php          # AI API 金鑰設定
│   ├── install.php     # 首次執行 DB 初始化
│   └── local.php       # 本地憑證（已 gitignore）
├── includes/
│   ├── header.php      # 通用頁頭（Tailwind CSS 導航）
│   ├── footer.php      # 通用頁尾
│   ├── session.php     # 登入 / 管理員 session 管理
│   ├── error_tracker.php # 錯誤記錄（上限 2000 條）
│   └── geo_guard.php   # 地區限制（香港 IP）
├── pages/              # 前端頁面（PHP 生成 HTML）
│   ├── home.php        # 首頁
│   ├── learning.php    # 學習關卡
│   ├── games.php       # 遊戲廳
│   ├── translate.php   # 文言文翻譯
│   ├── teahouse.php    # 古人茶館（AI 角色扮演貼文）
│   ├── profile.php     # 用戶個人頁
│   ├── login.php       # 登入
│   ├── register.php    # 註冊
│   └── admin.php       # 管理員面板
├── api/                # 後端 API（JSON 回應）
│   ├── essays.php      # 範文列表 / 內容
│   ├── ai_text.php     # AI 文字（翻譯、測驗、注釋）
│   ├── ai_image.php    # AI 圖像生成
│   ├── auth.php        # 登入 / 登出 / 註冊
│   ├── posts.php       # 茶館貼文 CRUD
│   ├── progress.php    # 用戶學習進度
│   ├── achievements.php# 成就系統
│   ├── admin.php       # 管理員操作
│   └── cron_post.php   # 假 cron 自動發文
└── data/
    ├── essays.json     # DSE中文12篇指定文言範文（16 篇）
    └── essay.txt       # 原始資料來源（JS 格式）
```

---

## 請求流程

```
用戶瀏覽器
    │
    ▼
.htaccess（URL 重寫）
    │  /page-name  →  index.php?page=page-name
    │  /api/*.php  →  直接存取（白名單）
    │  其他 /api/* →  403 禁止
    ▼
index.php（主路由）
    │
    ├─ geo_guard()           # 地區檢查（HK IP，有 TTL=86400s 快取）
    ├─ 假 cron（1/10 機率）  # 定時自動發文
    ├─ 驗證 $page 白名單     # home/learning/games/teahouse/translate/profile/login/register/admin
    ├─ 管理員頁面需驗證      # session_is_admin()
    ├─ usage_track()         # 頁面瀏覽記錄
    └─ include pages/$page.php
```

---

## 資料庫自動初始化

```
config/db.php（db_connect()）
    │
    └─ 首次連接時，若 data/.installed 不存在
           └─ 執行 config/install.php
                  ├─ 建立資料表（users, posts, user_progress, app_settings 等）
                  └─ 寫入 data/.installed 標記
```

---

## 各頁面邏輯

### 首頁（home.php）
1. 若已登入，從 `user_progress` 讀取各作者進度
2. 顯示學習關卡（蘇軾 / 韓愈）進度卡
3. 顯示快速入口（翻譯、遊戲廳、茶館、範文列表）
4. 非同步載入 **DSE中文12篇指定文言範文** 列表（`GET /api/essays.php?action=list`）

### 翻譯頁（translate.php）
1. 下拉選單載入 **DSE中文12篇指定文言範文** 16 篇（`GET /api/essays.php?action=list`）
2. 選擇範文後，載入全文（`GET /api/essays.php?action=get&id=N`）
3. 點擊「翻譯」→ `POST /api/ai_text.php {action: 'translate', text}`
4. 點擊「測驗」→ `POST /api/ai_text.php {action: 'quiz', text}` → 生成 5 道選擇題
5. 浮動原文視窗可供參考

### 學習關卡（learning.php）
```
選擇作者（蘇軾 / 韓愈）
    │
    ▼
顯示 4 關（順序解鎖，需前一關 ≥1 星）
    │
    ▼
點擊「開始」→ 彈出關卡視窗
    │
    ├─ 📖 閱讀分頁
    │       └─ 點擊漢字 → POST /api/ai_text.php {action:'annotate', word, sentence}
    │                      → AI 注釋（快取避免重複請求）
    │
    ├─ 🎮 練習分頁
    │       ├─ 文磚挑戰（breakout 打磚塊）→ 開新分頁 /games?type=breakout&...
    │       └─ 文言配對（matching 配對）→ 開新分頁 /games?type=matching&...
    │
    └─ ✏️ 測驗分頁
            └─ 點擊「開始測驗」→ POST /api/ai_text.php {action:'quiz', text}
                    └─ 生成 5 題 → 提交 → 計分
                            └─ 分數 ≥ 60% → saveProgress() → POST /api/progress.php
```

### 茶館（teahouse.php）
- 以古代文人角色發文（AI 生成）
- 用戶可回覆、點讚
- 假 cron（`api/cron_post.php`）定時自動生成新貼文

### 管理員面板（admin.php）
- 只有首個註冊用戶擁有管理員權限
- 7 個分頁：dashboard / users / errors / usage / content / cron / settings
- 錯誤記錄上限：2000 條

---

## AI 調用流程

```
前端 fetch → POST /api/ai_text.php
    │
    ├─ action: 'translate'  → 文言文翻譯（現代漢語）
    ├─ action: 'quiz'       → 生成 5 道選擇題（JSON 格式）
    ├─ action: 'annotate'   → 單字注釋（字義解釋）
    └─ action: 其他         → 茶館角色扮演等
         │
         └─ config/ai.php 讀取 API 金鑰（env var 或 config/local.php）
                └─ 呼叫外部 AI API（結果回傳給前端）
```

---

## 範文資料流程（DSE中文12篇指定文言範文）

```
data/essay.txt（原始資料，JS 格式，16 篇）
    │
    └─ 已轉換為 data/essays.json（標準 JSON 格式）
            │
            └─ api/essays.php 讀取
                    │
                    ├─ GET ?action=list  → 回傳列表（id/title/author/dynasty/genre/type/category）
                    └─ GET ?action=get&id=N → 回傳完整內容
                            │
                            ├─ pages/home.php（首頁預覽列表）
                            ├─ pages/translate.php（翻譯頁選擇器）
                            └─ pages/learning.php（學習關卡閱讀）
```

---

## 用戶認證流程

```
POST /api/auth.php {action: 'register'}
    └─ 首個用戶自動成為管理員
    
POST /api/auth.php {action: 'login'}
    └─ 驗證密碼 → 設定 session
    
includes/session.php
    ├─ session_check_auth()     → 是否已登入
    ├─ session_get_user()       → 取得當前用戶資料
    ├─ session_is_admin()       → 是否為管理員
    └─ session_require_admin()  → 強制要求管理員（否則 redirect）
```

---

## 學習進度流程

```
POST /api/progress.php {action:'save', author_id, level, stars}
    │
    └─ INSERT/UPDATE user_progress 表
            │
            └─ 下次載入頁面時讀取進度
                    └─ 星數 ≥ 1 → 解鎖下一關
```

---

## 地區限制（Geo Guard）

```
每次頁面請求 → geo_guard()
    │
    ├─ 讀取 IP 地理位置（有快取 TTL=86400s）
    ├─ 只允許香港 IP 存取
    └─ VPN/代理檢測
```

---

## URL 路由規則（.htaccess）

| 請求 URL | 實際執行 |
|---|---|
| `/` | `index.php?page=home` |
| `/learning` | `index.php?page=learning` |
| `/translate` | `index.php?page=translate` |
| `/games` | `index.php?page=games` |
| `/teahouse` | `index.php?page=teahouse` |
| `/api/essays.php` | 直接存取（白名單） |
| `/api/ai_text.php` | 直接存取（白名單） |
| 其他 `/api/*` | 403 禁止 |
