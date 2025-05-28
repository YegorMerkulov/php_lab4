<?php
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Обработка действий администратора
if ($_SESSION['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-защита
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка безопасности!");
    }

    // Добавление книги
    if (isset($_POST['add_book'])) {
        $name = trim($_POST['name']);
        $year = trim($_POST['pub_year']);
        
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO books (name, pub_year) VALUES (?, ?)");
            $stmt->execute([$name, $year]);
        }
    }

    // Удаление книги
    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$delete_id]);
    }

    // Редактирование книги
    if (isset($_POST['edit_book'])) {
        $id = (int)$_POST['book_id'];
        $name = trim($_POST['name']);
        $year = (int)$_POST['pub_year'];
        
        $stmt = $pdo->prepare("UPDATE books SET name = ?, pub_year = ? WHERE id = ?");
        $stmt->execute([$name, $year, $id]);
    }
}

// Получение списка книг
$stmt = $pdo->query("SELECT * FROM books ORDER BY name");
$books = $stmt->fetchAll();

// Генерация CSRF-токена
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Книги</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="container">
        <h2>Список книг</h2>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="admin-panel">
                <div class="container login-page">
                <h3>Добавить новую книгу</h3>
                <form class="login-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="text" name="name" placeholder="Название книги" required>
                    <input type="number" name="pub_year" placeholder="Год издания" min="1800" max="<?= date('Y') ?>" required>
                    <button type="submit" name="add_book">Добавить</button>
                </form>
                </div>
            </div>
        <?php endif; ?>

        <table class="books-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Год издания</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= htmlspecialchars($book['name']) ?></td>
                        <td><?= $book['pub_year'] ?></td>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <td class="actions">

                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <input type="text" name="name" value="<?= htmlspecialchars($book['name']) ?>" required>
                                    <input type="number" name="pub_year" value="<?= $book['pub_year'] ?>" required>
                                    <button type="submit" name="edit_book" class="btn-edit">✏️</button>
                                    <a href="?delete_id=<?= $book['id'] ?>" class="btn-delete" 
                                       onclick="return confirm('Вы уверены?')">❌</a>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php require_once 'footer.php'; ?>
</body>
</html>