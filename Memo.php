<?php
session_start(); // 啟動 session，用來保存登入資訊

require_once 'dp.php'; // 引入資料庫連接檔案

$userId = $_SESSION['user_id'] ?? null; // 從 session 取得使用者 ID，如果沒有則為 null
$username = $_SESSION['username'] ?? ''; // 從 session 取得使用者名稱，如果沒有則為空字串

if ($userId === null) { // 檢查使用者是否已登入
    header('Location: BWMenu.html'); // 若未登入，重新導向到選單頁面
    exit; // 停止執行程式
}

$memos = []; // 初始化備忘錄陣列

try { // 開始異常捕捉區塊
    $stmt = $pdo->prepare('SELECT id, content, image_path, thumb_path, created_at FROM dbmemo WHERE user_id = ? ORDER BY created_at DESC, id DESC'); // 準備 SQL 查詢語句，取得該使用者的所有備忘錄，按建立時間倒序排列
    $stmt->execute([(int) $userId]); // 執行查詢，傳入使用者 ID
    $memos = $stmt->fetchAll(PDO::FETCH_ASSOC); // 取得所有查詢結果並轉為關聯式陣列
} catch (Throwable $e) { // 捕捉任何異常
    $memos = []; // 若出錯，設定為空陣列
}

$cardsHtml = ''; // 初始化卡片 HTML 變數

if (!$memos) { // 檢查是否有備忘錄
    $cardsHtml = '<div class="memo-card"><p>目前還沒有備忘，先新增第一筆吧！</p></div>'; // 若沒有，顯示提示訊息
} else { // 若有備忘錄
    foreach ($memos as $memo) { // 逐個遍歷每個備忘錄
        $memoId = (int) ($memo['id'] ?? 0); // 取得備忘錄 ID，轉為整數
        $content = (string) ($memo['content'] ?? ''); // 取得備忘錄內容
        $displayImage = (string) (($memo['thumb_path'] ?? '') !== '' ? $memo['thumb_path'] : ($memo['image_path'] ?? '')); // 優先顯示縮圖，如果沒有則顯示原圖

        $cardsHtml .= '<div class="memo-card">'; // 開始卡片 div
        $cardsHtml .= '<p>' . nl2br($content) . '</p>'; // 顯示備忘錄內容，nl2br 將換行符轉為 <br>

        if ($displayImage !== '') { // 檢查是否有圖片
            $cardsHtml .= '<img src="' . $displayImage . '" alt="備忘圖片">'; // 若有則顯示圖片
        }

        $cardsHtml .= '<form action="update.php" method="post" enctype="multipart/form-data">'; // 開始編輯表單
        $cardsHtml .= '<input type="hidden" name="id" value="' . $memoId . '">'; // 隱藏欄位放備忘錄 ID
        $cardsHtml .= '<textarea name="content">' . $content . '</textarea>'; // 編輯內容文字區
        $cardsHtml .= '<button class="submit-btn">修改</button>'; // 修改按鈕
        $cardsHtml .= '</form>'; // 結束編輯表單

        $cardsHtml .= '<form action="delete.php" method="post">'; // 開始刪除表單
        $cardsHtml .= '<input type="hidden" name="id" value="' . $memoId . '">'; // 隱藏欄位放備忘錄 ID
        $cardsHtml .= '<button class="submit-btn">刪除</button>'; // 刪除按鈕
        $cardsHtml .= '</form>'; // 結束刪除表單

        $cardsHtml .= '</div>'; // 結束卡片 div
    }
}

$templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'Memo.html'; // 組合模板檔案的完整路徑

if (!is_file($templatePath)) { // 檢查模板檔案是否存在
    http_response_code(500); // 返回 500 伺服器錯誤
    echo 'Template file Memo.html not found.'; // 顯示錯誤訊息
    exit; // 停止執行程式
}

$template = file_get_contents($templatePath); // 讀取模板檔案內容

if ($template === false) { // 檢查檔案讀取是否成功
    http_response_code(500); // 返回 500 伺服器錯誤
    echo 'Unable to load template.'; // 顯示錯誤訊息
    exit; // 停止執行程式
}

$pattern = '/<div class="memo-card">[\s\S]*?<\/div>/'; // 定義正則表達式，用來尋找模板中的卡片區域
$result = preg_replace($pattern, $cardsHtml, $template, 1); // 用動態生成的卡片 HTML 替換模板中的卡片區域，只替換第一次出現

if ($result === null) { // 檢查正則替換是否成功
    http_response_code(500); // 返回 500 伺服器錯誤
    echo 'Failed to render template.'; // 顯示錯誤訊息
    exit; // 停止執行程式
}

echo $result; // 輸出完整的 HTML 頁面到瀏覽器