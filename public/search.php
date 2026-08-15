<?php
// Bật hiển thị lỗi để dễ dàng kiểm tra nếu có sự cố
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/footer.php';

$error_message = null;
$reason = null;
$sources = [];
$search_results = [];
$is_searched = false;

$keyword = trim($_GET['keyword'] ?? '');
$selected_source = trim($_GET['source'] ?? '');

$pdo = get_database_connection();

if ($pdo) {
    // 1. Lấy danh sách nguồn tác giả duy nhất để nạp vào thẻ Select/Combobox
    try {
        $source_query = 'SELECT DISTINCT source FROM quotes WHERE source IS NOT NULL AND source != \'\' ORDER BY source ASC';
        $stmt_sources = $pdo->query($source_query);
        $sources = $stmt_sources->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $error_message = 'Không thể lấy danh sách tác giả';
        $reason = $e->getMessage();
    }

    // 2. Xử lý khi người dùng nhấn nút Tìm kiếm (gửi qua phương thức GET)
    if (isset($_GET['keyword']) || isset($_GET['source'])) {
        $is_searched = true;
        $sql = 'SELECT id, quote, source, favorite FROM quotes WHERE 1=1';
        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND quote ILIKE ?';
            $params[] = '%' . $keyword . '%';
        }

        if ($selected_source !== '') {
            $sql .= ' AND source = ?';
            $params[] = $selected_source;
        }

        $sql .= ' ORDER BY date_entered DESC';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $search_results = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error_message = 'Không thể tìm kiếm trích dẫn';
            $reason = $e->getMessage();
        }
    }
} else {
    $error_message = 'Không thể kết nối đến cơ sở dữ liệu';
}

include __DIR__ . '/../partials/show_error.php';
?>

<?php render_page_header(); ?>
<h2>Tìm kiếm Trích dẫn</h2>

<!-- Form gửi truy vấn bằng phương thức HTTP GET -->
<form action="search.php" method="get">
    <p>
        <label>Từ khóa trích dẫn:
            <input type="text" name="keyword" value="<?= html_escape($keyword) ?>" placeholder="Nhập từ khóa cần tìm...">
        </label>
    </p>
    <p>
        <label>Tác giả / Nguồn:
            <select name="source">
                <option value="">-- Tất cả tác giả --</option>
                <?php foreach ($sources as $src): ?>
                    <option value="<?= html_escape($src) ?>" <?= ($selected_source === $src) ? 'selected' : '' ?>>
                        <?= html_escape($src) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    <p>
        <input type="submit" value="Tìm kiếm">
    </p>
</form>

<hr>

<!-- Hiển thị danh sách kết quả sau khi tìm kiếm -->
<?php if ($is_searched): ?>
    <h3>Kết quả tìm kiếm:</h3>
    <?php if (!empty($search_results)): ?>
        <?php foreach ($search_results as $quote): ?>
            <div>
                <blockquote><?= html_escape($quote['quote']) ?></blockquote>
                <p>— <?= html_escape($quote['source']) ?>
                    <?php if (!empty($quote['favorite'])): ?>
                        <strong> | Yêu thích!</strong>
                    <?php endif; ?>
                </p>
            </div>
            <br>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Không tìm thấy trích dẫn nào phù hợp với điều kiện tìm kiếm.</p>
    <?php endif; ?>
<?php endif; ?>

<?php render_page_footer(); ?>