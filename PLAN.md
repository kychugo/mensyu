# 文樞（Mensyu）完整開發計劃 v3

> **狀態：已更新，待確認後開始開發**
> 本文件整合現有六個版本（v1、v2、v3、mensyu、mensyu2、mensyu-tran）的所有優點，設計一套完整的文言文遊戲學習旅程。
> 最後更新：2026-04-05

---

## ▌已確認移除項目

| 移除項目 | 原位置 | 說明 |
|---------|--------|------|
| 填詞挑戰（句子填空，拖曳正確字詞） | 遊戲廳 遊戲三 | 完全移除，不保留任何相關代碼 |
| 第 5 關 → 請作者品評自創文言文 | 學習關卡系統 | 完全移除，關卡上限改為 4 關 |
| essays.json 字詞注釋（vocab 欄位） | 範文數據 | 改由 AI 動態生成，json 只存原文 |

---

## 一、現有版本盤點（保留元素）

| 版本 | 核心特色 | 保留元素 |
|------|---------|---------|
| **v1** | 基礎翻譯工具，點擊顯示釋義 | 翻譯 API 調用邏輯、字句逐一解釋 |
| **v2** | 浮動原文視窗、響應式佈局 | 浮動視窗、行動裝置適配 |
| **v3** | 雙作者（蘇軾/韓愈）、關卡制、社交動態、問答測試 | 關卡架構、作者系統、社交互動、AI prompt |
| **mensyu** | 三種模式（閱讀/測驗/遊戲），3D 打磚塊遊戲，閱讀紀錄 | 遊戲引擎、測驗模式、歷史紀錄 |
| **mensyu2** | 流暢的作者卡片與進度追蹤、個性化 AI 貼文、fallback 模型邏輯 | 個性化 AI 回應、進度圓圈視覺化、API fallback 結構 |
| **mensyu-tran** | 輕量翻譯、浮動參考視窗 | 極簡翻譯入口 |

---

## 二、技術平台（PHP + MySQL）

### 2.1 平台語言與架構

- **後端語言**：PHP（模組化多檔案結構）
- **資料庫**：MySQL（`sql111.infinityfree.com`）
- **前端**：HTML + CSS（Tailwind）+ JavaScript（原生）
- **AI API**：pollinations.ai（`https://gen.pollinations.ai`）

### 2.2 檔案結構

```
/mensyu/
├── config/
│   ├── db.php          # 資料庫連線設定（敏感資訊，不提交明文金鑰）
│   └── ai.php          # AI API 金鑰設定（server-side 保密）
├── api/
│   ├── ai_text.php     # 文字 AI 調用中介（server-side，保護 sk_ 金鑰）
│   ├── ai_image.php    # 圖片 AI 調用中介（server-side，保護 sk_ 金鑰）
│   ├── auth.php        # 登入 / 登出 / 注冊
│   ├── posts.php       # 茶館貼文 CRUD
│   ├── progress.php    # 用戶進度讀寫
│   ├── essays.php      # 文言範文讀取
│   └── cron_post.php   # 古人自動發文（由 cron job 觸發）
├── pages/
│   ├── home.php        # 首頁
│   ├── learning.php    # 學習關卡頁
│   ├── games.php       # 遊戲廳（打磚塊 + 文言配對）
│   ├── teahouse.php    # 古人茶館（Instagram 風格）
│   ├── translate.php   # 翻譯頁
│   └── profile.php     # 個人主頁
├── includes/
│   ├── header.php      # 公用頭部（含 nav）
│   ├── footer.php      # 公用底部
│   └── session.php     # Session 初始化與檢查
├── data/
│   └── essays.json     # DSE 指定文言範文（只含原文，不含字詞注釋）
├── index.php           # 主入口（路由分發）
├── sitemap.xml         # SEO 用
├── robots.txt          # SEO 用
└── .htaccess           # URL 重寫 / 安全設定
```

### 2.3 資料庫設定

```
Host:     sql111.infinityfree.com
Port:     3306
User:     if0_41581260
Password: hfy23whc
Database: if0_41581260_mensyu
```

> ⚠️ 所有憑證只存於 `config/db.php`，透過 PHP 保密，不寫入前端或 git。

### 2.4 資料表設計

```sql
-- 用戶帳號
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 用戶進度（每位作者每關）
CREATE TABLE user_progress (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    author_id   VARCHAR(20) NOT NULL,   -- e.g. 'sushe', 'hanyu'
    level       TINYINT NOT NULL,       -- 1–4
    stars       TINYINT DEFAULT 0,      -- 0–3
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_progress (user_id, author_id, level)
);

-- 茶館貼文（最多 50 筆，超過刪除最舊）
CREATE TABLE teahouse_posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,                    -- NULL = 古人自動發文
    author_persona VARCHAR(20),         -- 'sushe' / 'hanyu' / NULL(用戶)
    username    VARCHAR(50),            -- 顯示名稱
    content     TEXT NOT NULL,
    image_url   VARCHAR(512),           -- AI 生成圖片 URL（可為 NULL）
    post_type   ENUM('ai','user') DEFAULT 'user',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 茶館留言 / 回覆
CREATE TABLE teahouse_comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    user_id     INT,                    -- NULL = AI 回覆
    username    VARCHAR(50),
    content     TEXT NOT NULL,
    is_ai       TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 成就徽章
CREATE TABLE achievements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    badge_id    VARCHAR(50) NOT NULL,
    earned_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_badge (user_id, badge_id)
);
```

---

## 三、登入系統

- **注冊 / 登入 / 登出**：PHP session 管理
- **密碼儲存**：`password_hash()` bcrypt
- **未登入**：可瀏覽首頁、翻譯頁、範文列表，但無法儲存進度或發文
- **進度同步**：所有用戶進度、成就、對話歷史儲存至 MySQL（不再只用 localStorage）
- **Session 安全**：`session_regenerate_id(true)` 防 session fixation；敏感操作加 CSRF token

---

## 四、AI 調用系統

### 4.1 文字 AI（`api/ai_text.php`）

**金鑰（server-side 保管）：**
- Secret Key：`sk_I9LbeRaewORSMEdm2ontKkJEHgimbE1v`（server-side 使用，不暴露前端）
- Publishable Key：`pk_ZQ4XnvfBU2tu6riY`（備用）

**可用文字模型（依序 fallback）：**
1. `deepseek`
2. `glm`
3. `qwen-large`
4. `qwen-safety`

**Auto-switch 邏輯（沿用 mensyu2 結構，調整模型列表）：**
```php
function ai_text_call(array $messages): ?string {
    $models = ['deepseek', 'glm', 'qwen-large', 'qwen-safety'];
    foreach ($models as $model) {
        $result = ai_request($model, $messages);
        if ($result !== null) return $result;
    }
    return null; // 全部失敗，回傳 null，前端靜默處理
}
```

**AI 回應格式 fallback 規則：**
- 所有 AI 調用均在 system prompt 中明確要求回應格式（JSON / 純文字 / 指定結構）
- 若解析 JSON 失敗，嘗試 regex 提取，再失敗則靜默返回 null
- 翻譯 API：若格式不符，fallback 為直接顯示原始文字
- 問答題 API：若無法解析題目，跳過顯示（不顯示錯誤訊息）

### 4.2 圖片 AI（`api/ai_image.php`）

**圖片生成 API Endpoint：**
```
GET https://gen.pollinations.ai/prompt/{encoded_prompt}?model={model}&key={sk_key}&width=512&height=512&nologo=true
```

**可用圖片模型（依序 fallback）：**
1. `gptimage`
2. `wan-image`
3. `qwen-image`
4. `klein`
5. `zimage`
6. `flux`

**圖片生成邏輯：**
- 圖片生成在 **後端觸發**，前端不直接調用圖片 API
- 生成後只儲存返回的 **URL** 至 `teahouse_posts.image_url`（不儲存二進制）
- 若任一模型生成失敗（HTTP 非 200 / timeout），嘗試下一個模型
- 全部模型失敗：`image_url` 留 `NULL`，貼文正常發出（無圖版本）
- **前端永不顯示任何圖片生成錯誤訊息**
- **圖片只在 `image_url` 非空時才渲染 `<img>` 標籤**

**圖片 Prompt 範本（古人自動發文時）：**
```
Ancient Chinese landscape painting style, {古人名} era aesthetics,
{貼文內容的意境摘要（英文）}, ink wash painting, traditional Chinese art,
8K, no text, no watermark
```

---

## 五、DSE 指定文言範文（essays.json）

### 5.1 JSON 結構（無 vocab 欄位，AI 動態生成字詞注釋）

```json
[
  {
    "id": 1,
    "list_id": 1,
    "title": "廉頗藺相如列傳",
    "author": "司馬遷",
    "dynasty": "漢",
    "genre": "史傳",
    "content": "廉頗者，趙之良將也..."
  }
]
```

> 字詞注釋（vocab）不再預設存入 JSON，每次用戶點擊字詞時由 AI 實時生成，結果 cache 在 localStorage 或 session。

### 5.2 篇目清單（共 16 篇）

| id | 篇目 | 作者 | 朝代 | 體裁 |
|----|------|------|------|------|
| 1 | 廉頗藺相如列傳 | 司馬遷 | 漢 | 史傳 |
| 2 | 山居秋暝 | 王維 | 唐 | 詩 |
| 3 | 月下獨酌 | 李白 | 唐 | 詩 |
| 4 | 登樓 | 杜甫 | 唐 | 詩 |
| 5 | 師說 | 韓愈 | 唐 | 論說文 |
| 6 | 岳陽樓記 | 范仲淹 | 宋 | 記 |
| 7 | 始得西山宴遊記 | 柳宗元 | 唐 | 記 |
| 8 | 念奴嬌·赤壁懷古 | 蘇軾 | 宋 | 詞 |
| 9 | 青玉案 | 辛棄疾 | 宋 | 詞 |
| 10 | 聲聲慢 | 李清照 | 宋 | 詞 |
| 11 | 逍遙遊（節錄） | 莊子 | 先秦 | 哲學散文 |
| 12 | 出師表 | 諸葛亮 | 三國 | 表 |
| 13 | 六國論 | 蘇洵 | 宋 | 論說文 |
| 14 | 勸學（節錄） | 荀子 | 先秦 | 哲學散文 |
| 15 | 論仁、論孝、論君子 | 孔子 | 先秦 | 語錄 |
| 16 | 魚我所欲也 | 孟子 | 先秦 | 論說文 |

---

## 六、學習關卡系統

### 6.1 作者與關卡（每位作者 4 大關，移除原第 5 關）

**蘇軾（sushe）**

| 大關 | 篇目 | 難度 |
|------|------|------|
| 第 1 關 | 記承天寺夜遊 | ⭐ |
| 第 2 關 | 永遇樂 并序 | ⭐⭐ |
| 第 3 關 | 超然臺記 | ⭐⭐⭐ |
| 第 4 關 | 前赤壁賦 | ⭐⭐⭐⭐ |

**韓愈（hanyu）**

| 大關 | 篇目 | 難度 |
|------|------|------|
| 第 1 關 | 雜說四（馬說） | ⭐ |
| 第 2 關 | 送孟東野序 | ⭐⭐ |
| 第 3 關 | 答李翊書 | ⭐⭐⭐ |
| 第 4 關 | 祭十二郎文 | ⭐⭐⭐⭐ |

### 6.2 每大關三子階段：讀 → 練 → 驗

```
子階段 A：「讀」— 閱讀模式
  - 展示原文，點擊字詞即可呼叫 AI 生成釋義（動態，不依賴 vocab 預設）
  - 浮動原文參考視窗（源自 v2）
  - 作者背景小卡（第 1 關顯示）

子階段 B：「練」— 遊戲（二選一）
  - 🎮 文磚挑戰（Breakout）
  - �� 文言配對（Matching Game）

子階段 C：「驗」— 問答測驗（5 題）
  - AI 動態生成選擇題（沿用 mensyu2 prompt）
  - 即時對錯回饋；達 60 分解鎖下一關
  - 星級評分（1–3 星）儲存至 MySQL
```

---

## 七、遊戲廳（只保留 2 個遊戲）

### 🎮 遊戲一：文磚挑戰（Breakout）

沿用 mensyu 的 3D Canvas 打磚塊引擎：
- 每塊磚顯示一個文言字詞
- 球接近磚塊時，底部出現 4 個翻譯選項（AI 動態生成）
- 選對才能擊破磚塊，答錯扣血（♥♥♥）
- 完美通關額外加星

### 🃏 遊戲二：文言配對（Matching Game）

新增，類似 mensyu 的配對記憶牌：
- 可選 DSE 範文篇目，或學習關卡篇目
- 牌面：文言字詞 ↔ 現代語譯（AI 動態生成配對對）
- 難度分級：初級 8 對 / 中級 12 對 / 高級 16 對
- 計時挑戰，限時 90 秒
- 支援觸控（mobile swipe/tap）

---

## 八、古人茶館（Instagram 風格，全面重設計）

### 8.1 UI 設計原則

- 類 Instagram 的貼文 feed（垂直捲動）
- 每篇貼文顯示：
  - 頭像（古人固定圖示 / 用戶預設頭像）
  - 用戶名 + 發文時間
  - 文字內容
  - 圖片（僅 `image_url` 非空時渲染，`loading="lazy"`）
  - 留言按鈕 + 留言列表（可展開）
- 所有登入用戶可見所有人貼文（共享 feed）

### 8.2 貼文規則

| 類型 | 觸發時機 | 圖片 |
|------|----------|------|
| 古人自動發文 | 每 2 小時（cron job） | 嘗試 AI 生成圖片；失敗則無圖 |
| 用戶發文 | 用戶手動發送 | 無圖（用戶不能上傳圖片，簡化設計） |
| 古人回覆用戶貼文 | 每天早上（cron job，批次處理昨日新貼文） | 無圖 |
| 古人立即回覆留言 | 用戶在古人貼文留言後，立即觸發 | 無圖 |

### 8.3 資料庫限制

- `teahouse_posts` 最多 50 筆
- 超過時 PHP 自動刪除最舊的一筆（FIFO）
- 留言不設上限（每篇貼文）

### 8.4 古人 AI 人格（保留自原始 mensyu2 prompt，略作調整）

**蘇軾人格：**
- 豁達樂觀，熱愛自然，善於哲理思考，喜歡用比喻和典故
- 經歷過多次貶謫，但始終保持樂觀態度，善於在逆境中尋找樂趣
- 文風優雅，常引用詩詞，語言富有哲理性

**韓愈人格：**
- 嚴謹治學，重視道德修養，推崇古文，有教育家風範
- 性格直率，敢於批評時政，主張復古改革
- 文風簡潔有力，喜歡說理，常有教誨意味

### 8.5 自動發文 Prompt（沿用 mensyu2，調整模型）

**發文 Prompt（保留原版，僅去除 Firebase 相關部分）：**
```
你現在是{古人名}，以{personality}的性格，使用{style}的文風，
撰寫一篇社群動態貼文（約50-100字），內容可以是日常生活感悟、
哲理思考或文學創作。請確保內容新穎，與以下歷史內容的主題、
觀點、用字或具體表達不重複：

{pastContent || '無歷史內容'}

使用繁體中文，適當融入粵語元素，但內容主要用字需要文言文，
確保語氣自然且符合角色性格，內容不要太過離地，要年輕及近代化，
用現代中學生易懂的文言混合粵語引用自身作品化解負面情緒保持豁達本色
並帶入生活化比喻，內容不要包含「tag(#)」或任何「註」
```

**留言 Prompt（保留原版）：**
```
你現在是{古人名}，以{personality}的性格，使用{style}的文風，
對以下貼文進行留言（約30-50字）：

{context}

請確保留言內容新穎，與以下歷史內容的主題、觀點或具體表達不重複：

{pastContent || '無歷史內容'}

請以繁體中文回應，適當融入粵語元素，但內容主要用字需要文言文，
語氣需符合角色性格並與貼文內容相關，內容不要太過離地，要年輕及近代化，
用現代中學生易懂的文言混合粵語引用自身作品化解負面情緒保持豁達本色
並帶入生活化比喻，內容不要包含「tag(#)」或任何「註」
```

### 8.6 圖片生成（古人發文時）

- 後端生成，不在前端調用
- Prompt 範本：
  ```
  Ancient Chinese ink wash painting, {author} era, {mood_keywords_in_english},
  traditional landscape, poetry atmosphere, 8K resolution,
  no text, no watermark, no modern elements
  ```
- 模型依序 fallback：`gptimage → wan-image → qwen-image → klein → zimage → flux`
- 成功：將圖片 URL 存入 `teahouse_posts.image_url`
- 失敗：`image_url` 留 NULL，不顯示錯誤，貼文仍正常發出

---

## 九、AI Prompt 整體設計（保留原版 + 新增）

### 9.1 翻譯 Prompt（沿用 mensyu2）

```
請將以下文言文逐字翻譯並解釋(直譯，不要意譯)，格式要求：
1. 每個字或詞組佔一行
2. 格式：字詞 - 解釋
3. 最後加上整句翻譯
4. 使用繁體中文
...
```

### 9.2 問答題 Prompt（沿用 mensyu2）

```
請基於以下文言文內容生成5道中文繁體選擇題，用來測試學習者的理解。
格式要求（JSON）：
[
  {
    "question": "問題",
    "options": ["A", "B", "C", "D"],
    "answer": 0,
    "explanation": "解析"
  }
]
...
```

**AI 格式 fallback 規則：**
- 若回應不是合法 JSON：用 regex 提取 `[...]` 區塊再嘗試解析
- 仍失敗：返回 null，前端靜默（不顯示錯誤）
- 翻譯：若格式不符，直接顯示 AI 原始文字（寬鬆解析）

### 9.3 字詞注釋 Prompt（新增，動態生成）

```
請解釋文言文中的「{字詞}」在以下句子中的意思：

{句子}

格式（JSON）：
{
  "word": "{字詞}",
  "meaning": "現代中文解釋（15字以內）",
  "example": "例句（可選）"
}

使用繁體中文，解釋要簡潔易懂。
```

### 9.4 配對遊戲字詞 Prompt（新增）

```
請從以下文言文段落中提取 {n} 個重要字詞及其現代語譯，
用作配對遊戲（字詞 ↔ 語譯）。

格式（JSON）：
[
  {"classical": "文言字詞", "modern": "現代語譯（5字以內）"}
]

每對字詞必須不同，選取最具代表性的詞語。
```

---

## 十、響應式與自適應設計

### 10.1 斷點策略

| 裝置 | 寬度 | 佈局 | 茶館 | 遊戲 |
|------|------|------|------|------|
| 手機 | < 768px | 單欄，底部 nav | 全螢幕貼文列表 | 全螢幕 canvas/牌面 |
| iPad | 768–1024px | 雙欄 70/30 | 側邊可折疊 | 適中尺寸 |
| 桌面 | > 1024px | 三欄或雙欄 | 完整 Instagram 佈局 | 大 canvas |

### 10.2 所有功能在所有裝置均可用

- **翻譯**：浮動視窗在手機改為底部抽屜（drawer）
- **打磚塊**：canvas 動態 resize，touch 事件支援（手指左右滑動控制板）
- **配對牌**：牌面 grid 根據螢幕寬度調整列數（手機 2 列，平板 4 列，桌面 4-6 列）
- **茶館 feed**：滾動加載，支援下拉刷新（touch event）
- **導航**：手機底部 tab bar；平板/桌面側邊 nav

### 10.3 圖片響應式

- 茶館貼文圖片：`max-width: 100%`，`height: auto`，`object-fit: cover`
- 圖片加載中顯示 placeholder skeleton
- 圖片加載失敗：隱藏 `<img>` 標籤（`onerror="this.style.display='none'"`）

---

## 十一、SEO（讓平台可在 Google 被搜尋）

### 11.1 技術 SEO

1. **`sitemap.xml`**：列出所有主要頁面 URL，每日更新（PHP 動態生成或靜態預設）
2. **`robots.txt`**：允許所有爬蟲，排除 `api/`、`config/`
3. **`.htaccess`**：URL 重寫（`/translate` 指向 `pages/translate.php` 等）
4. **HTTPS**：強制重定向至 HTTPS

### 11.2 頁面 Meta 標籤（每頁）

```html
<title>文樞 Mensyu - DSE 文言文學習平台</title>
<meta name="description" content="文樞 Mensyu 是專為 DSE 學生設計的文言文互動學習平台，包含 16 篇 DSE 指定範文、AI 翻譯、遊戲學習、古人聊天等功能。">
<meta name="keywords" content="DSE 文言文, 文言翻譯, 廉頗藺相如, 師說, 岳陽樓記, 蘇軾, 韓愈, 文言遊戲">
<meta property="og:title" content="文樞 Mensyu - DSE 文言文學習平台">
<meta property="og:description" content="...">
<meta property="og:type" content="website">
```

### 11.3 結構化資料

```json
{
  "@context": "https://schema.org",
  "@type": "EducationalApplication",
  "name": "文樞 Mensyu",
  "description": "DSE 文言文互動學習平台",
  "applicationCategory": "EducationApplication",
  "inLanguage": "zh-HK"
}
```

### 11.4 上線後操作

- 到 Google Search Console 提交 sitemap
- 確保每頁有語義化 HTML（`<main>`, `<article>`, `<nav>`, `<header>`）
- 頁面加載速度優化（PHP 輸出壓縮、靜態資源 cache header）

---

## 十二、統一函式命名規範

**PHP（snake_case + 域前綴）：**

| 域 | 前綴 | 範例 |
|----|------|------|
| 用戶驗證 | `auth_` | `auth_login()`, `auth_register()`, `auth_logout()` |
| AI 文字 | `ai_text_` | `ai_text_call()`, `ai_text_translate()`, `ai_text_quiz()` |
| AI 圖片 | `ai_image_` | `ai_image_generate()`, `ai_image_fallback()` |
| 茶館 | `teahouse_` | `teahouse_get_posts()`, `teahouse_add_post()`, `teahouse_add_comment()` |
| 進度 | `progress_` | `progress_get()`, `progress_save()`, `progress_add_stars()` |
| 遊戲 | `game_` | `game_get_vocab()`, `game_generate_pairs()` |
| 翻譯 | `essay_` | `essay_list()`, `essay_get()`, `essay_translate()` |
| DB 工具 | `db_` | `db_connect()`, `db_query()`, `db_escape()` |

**JavaScript（camelCase + 域前綴）：**

| 域 | 前綴 | 範例 |
|----|------|------|
| 頁面導航 | `page` | `pageShow()`, `pageHide()`, `pageNavigate()` |
| AI 調用 | `ai` | `aiCallText()`, `aiTranslate()`, `aiGenerateQuiz()` |
| 茶館 | `teahouse` | `teahouseLoadPosts()`, `teahouseSubmitPost()`, `teahouseAddComment()` |
| 遊戲：打磚塊 | `breakout` | `breakoutInit()`, `breakoutStart()`, `breakoutReset()` |
| 遊戲：配對 | `matching` | `matchingInit()`, `matchingFlipCard()`, `matchingCheckPair()` |
| 進度 | `progress` | `progressLoad()`, `progressSave()`, `progressUpdateStars()` |
| 用戶 | `auth` | `authLogin()`, `authLogout()`, `authCheckSession()` |
| 翻譯 | `translate` | `translateText()`, `translateShowWord()`, `translateToggleFloat()` |

---

## 十三、成就與獎勵系統

### 成就徽章

| 徽章 | 解鎖條件 |
|------|---------|
| 🔰 初入文場 | 完成任一作者第 1 關 |
| 📖 博覽古籍 | 閱讀 10 篇不同文本 |
| ⚔️ 磚場勇士 | 打磚塊遊戲完美通關 3 次 |
| 🃏 古今配對 | 文言配對遊戲完成 5 次 |
| 🏆 文樞大師 | 完成所有作者所有關卡 |
| 💬 古今對話 | 在茶館發文或留言 10 次 |

### 星級評分（每關最多 3 星）

- ⭐ 通過測驗（≥60 分）
- ⭐⭐ 高分通過（≥80 分）＋完成遊戲
- ⭐⭐⭐ 完美通關（滿分）＋遊戲不扣血

---

## 十四、開發執行階段（確認後按序進行）

### Phase 0 — 資料準備
- [ ] 建立 `data/essays.json`（16 篇 DSE 文言範文，只含 id/title/author/dynasty/genre/content，無 vocab）
- [ ] 確認 MySQL 連線可用，建立所有資料表

### Phase 1 — PHP 基礎架構
- [ ] `config/db.php`（MySQL 連線封裝）
- [ ] `config/ai.php`（AI 金鑰設定）
- [ ] `includes/session.php`, `header.php`, `footer.php`
- [ ] `index.php` 路由分發
- [ ] `.htaccess` URL 重寫 + 安全設定

### Phase 2 — 登入系統
- [ ] `api/auth.php`：注冊、登入、登出（`auth_*` 函式）
- [ ] 登入表單頁面，含 CSRF token
- [ ] Session 安全設定

### Phase 3 — AI 調用模組
- [ ] `api/ai_text.php`：文字 AI（`ai_text_*` 函式），model fallback（deepseek→glm→qwen-large→qwen-safety）
- [ ] `api/ai_image.php`：圖片 AI（`ai_image_*` 函式），model fallback（gptimage→wan-image→qwen-image→klein→zimage→flux）
- [ ] 格式解析 fallback（JSON → regex → null）

### Phase 4 — 翻譯 + 範文
- [ ] `data/essays.json`（16 篇，無 vocab）
- [ ] `api/essays.php`（`essay_*` 函式）
- [ ] `pages/translate.php`：支援 DSE 篇目選擇 + 任意輸入翻譯

### Phase 5 — 學習關卡系統
- [ ] `pages/learning.php`：作者選擇、4 關架構、讀→練→驗流程
- [ ] `api/progress.php`（`progress_*` 函式）
- [ ] 動態字詞注釋（點擊字詞呼叫 AI，結果 cache 至 localStorage）

### Phase 6 — 遊戲廳
- [ ] `pages/games.php`
- [ ] 打磚塊（Breakout）：沿用 mensyu canvas 引擎，加入 touch 支援
- [ ] 文言配對（Matching Game）：新建，支援 DSE 篇目，touch 支援

### Phase 7 — 古人茶館
- [ ] `pages/teahouse.php`：Instagram 風格 feed
- [ ] `api/posts.php`（`teahouse_*` 函式）：CRUD，max 50 貼文
- [ ] `api/cron_post.php`：古人自動發文（2小時）+ 晨間回覆（早上）
- [ ] 圖片生成邏輯（後端觸發，URL 存 DB）

### Phase 8 — SEO + 響應式潤飾
- [ ] `sitemap.xml`, `robots.txt`
- [ ] 各頁 `<meta>` 標籤 + Schema.org 結構化資料
- [ ] 全平台響應式測試（手機 / iPad / 桌面）
- [ ] 所有遊戲 touch 事件確認
- [ ] 打磚塊 canvas mobile resize 測試

### Phase 9 — 成就系統 + 最終測試
- [ ] 成就徽章系統（`achievements` 資料表）
- [ ] 個人主頁（`pages/profile.php`）
- [ ] 全平台函式命名統一審查
- [ ] 安全審查（SQL injection、XSS、CSRF）

---

## 十五、安全設計要點

| 風險 | 對策 |
|------|------|
| SQL Injection | PDO prepared statements，所有 DB 操作用 `db_query()` 封裝 |
| XSS | 所有輸出使用 `htmlspecialchars()` |
| CSRF | 表單加入 CSRF token，API 驗證 |
| Session fixation | 登入後 `session_regenerate_id(true)` |
| API 金鑰洩漏 | sk_ 只存 `config/ai.php`，所有 AI 調用走 PHP 後端，不暴露前端 |
| 目錄遍歷 | `.htaccess` 禁止直接存取 `config/`, `api/` 非入口端點 |

---

*請確認此計劃後，將按 Phase 0 → Phase 9 順序開始開發。*
