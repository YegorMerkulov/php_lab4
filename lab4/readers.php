<?php
require_once 'config.php';
require_once 'header.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['role'] === 'admin');
$error = '';
$success = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    // CSRF-защита
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка безопасности!");
    }

    // Обновление записи
    if (isset($_POST['update'])) {
        $id = (int)$_POST['id'];
        $taken_at = $_POST['taken_at'];
        $returned_at = $_POST['returned_at'] ?: null;

        try {
            $stmt = $pdo->prepare("UPDATE log_taking SET taken_at = ?, returned_at = ? WHERE id = ?");
            $stmt->execute([$taken_at, $returned_at, $id]);
            $success = 'Запись обновлена!';
        } catch (PDOException $e) {
            $error = 'Ошибка обновления: ' . $e->getMessage();
        }
    }
}

// Обработка добавления новой записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_admin) {
    if (isset($_POST['add_record'])) {
        $reader_id = (int)$_POST['reader_id'];
        $book_id = (int)$_POST['book_id'];
        $taken_at = $_POST['taken_at'];
        $returned_at = $_POST['returned_at'] ?: null;

        // Валидация
        if ($reader_id < 1 || $book_id < 1) {
            $_SESSION['error'] = "Выберите читателя и книгу!";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO log_taking 
                    (reader_id, book_id, taken_at, returned_at)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$reader_id, $book_id, $taken_at, $returned_at]);
                $_SESSION['success'] = "Запись успешно добавлена!";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Ошибка: " . $e->getMessage();
            }
        }
        header("Location: readers.php");
        exit;
    }
}


// Получение списка читателей и книг для формы
$users = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM readers")->fetchAll();
$books = $pdo->query("SELECT id, name FROM books")->fetchAll();

// Удаление записи (только для админа)
if ($is_admin && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM log_taking WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Запись удалена!';
    } catch (PDOException $e) {
        $error = 'Ошибка удаления: ' . $e->getMessage();
    }
}

// Получение данных
try {
    if ($is_admin) {
        $stmt = $pdo->query("
            SELECT 
                r.id,
                r.reader_id,
                r.book_id,
                r.taken_at,
                r.returned_at,
                u.first_name,
                u.last_name,
                b.name AS book_title 
            FROM log_taking r
            LEFT JOIN readers u ON r.reader_id = u.id
            LEFT JOIN books b ON r.book_id = b.id
            ORDER BY r.taken_at DESC
        ");
    } else {
        $stmt = $pdo->prepare("
SELECT 
    r.id,
    r.reader_id,
    r.book_id,
    r.taken_at,
    r.returned_at,
    u.first_name,
    u.last_name,
    b.name AS book_title 
FROM log_taking r
-- Добавляем JOIN с таблицей users
JOIN readers u ON r.reader_id = u.id
JOIN books b ON r.book_id = b.id
WHERE r.reader_id = ?
ORDER BY r.taken_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}

// Генерация CSRF-токена
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Читатели</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <?php if ($is_admin): ?>
        <div class="container login-page">
            <div class="login-header">
            <h3>Добавить новую запись</h3>
            </div>
            <form class="login-form" method="POST">
                <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                </div>
                <div class="form-group">
                    <label>Читатель:</label>
                    <select class="login-form" name="reader_id" required>
                        <div class="form-group">
                        <option value="">Выберите читателя</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                        </div>
                    </select>
                </div>

                <div class="form-group">
                    <label>Книга:</label>
                    <select class="login-form form-input" name="book_id" required>
                        <option value="">Выберите книгу</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= $book['id'] ?>"><?= htmlspecialchars($book['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Дата взятия:</label>
                    <input class="login-form form-input" type="date" name="taken_at" 
                           value="<?= date('Y-m-d') ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label>Дата возврата:</label>
                    <input type="date" class="login-form form-input" name="returned_at">
                </div>

                <button type="submit" class="login-button" name="add_record" class="button">Добавить запись</button>
            </form>
        </div>
    <?php endif; ?>

<div class="container">
    <h2><?= $is_admin ? 'Все записи' : 'Мои книги' ?></h2>

    <?php if ($error): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <table class="records-table">
        <thead>
            <tr>
                <th>Имя</th>
                <th>Фамилие</th>
                <th>Книга</th>
                <th>Дата взятия</th>
                <th>Дата возврата</th>
                <?php if ($is_admin): ?>
                    <th>Действия</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?= htmlspecialchars($record['first_name']) ?></td>
                    <td><?= htmlspecialchars($record['last_name']) ?></td>
                    <td><?= htmlspecialchars($record['book_title']) ?></td>
                    <td><?= date('d.m.Y', strtotime($record['taken_at'])) ?></td>
                    <td>
                        <?php if ($record['returned_at']): ?>
                            <?= date('d.m.Y', strtotime($record['returned_at'])) ?>
                        <?php else: ?>
                            <span class="status-not-returned">Не возвращена</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($is_admin): ?>
                        <td class="actions">
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id" value="<?= $record['id'] ?>">
                                
                                <input type="date" name="taken_at" 
                                       value="<?= date('Y-m-d', strtotime($record['taken_at'])) ?>"
                                       required>
                                       
                                <input type="date" name="returned_at" 
                                       value="<?= $record['returned_at'] ? date('Y-m-d', strtotime($record['returned_at'])) : '' ?>">
                                       
                                <button type="submit" name="update" class="btn-edit">🔄</button>
                                <a href="?delete=<?= $record['id'] ?>" class="btn-delete" 
                                   onclick="return confirm('Удалить запись?')">❌</a>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

</div>
</body>