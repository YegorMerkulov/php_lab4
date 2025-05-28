<?php
require_once 'config.php';
require_once 'header.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        $error = 'Все поля обязательны для заполнения!';
    } else {
        try {
            // Обновление или создание записи
            $stmt = $pdo->prepare("
                INSERT INTO readers (user_id, first_name, last_name) 
                VALUES (:user_id, :first_name, :last_name)
                ON DUPLICATE KEY UPDATE 
                first_name = VALUES(first_name), 
                last_name = VALUES(last_name)
            ");
            
            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'first_name' => $firstName,
                'last_name' => $lastName
            ]);
            
            $success = 'Данные успешно обновлены!';
        } catch (PDOException $e) {
            $error = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// Получение текущих данных
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM readers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $reader = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка получения данных: " . $e->getMessage());
}
?>

<div class="container">
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="form-group">
            <a href="register.php" class="login-button" style="text-decoration: none;" class="admin-button">
                Зарегистрировать пользователя
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="container">
    <h2>Добро пожаловать, <?= htmlspecialchars($reader['first_name'] ?? 'Пользователь') ?>!</h2>

    <?php if ($error): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <form class="login-form" method="POST">
        <div class="form-group">
            <label>Имя:</label>
            <input type="text" name="first_name" class="form-input" 
                   value="<?= htmlspecialchars($reader['first_name'] ?? '') ?>" 
                   required>
        </div>

        <div class="form-group">
            <label>Фамилия:</label>
            <input type="text" name="last_name" class="form-input"
                   value="<?= htmlspecialchars($reader['last_name'] ?? '') ?>" 
                   required>
        </div>

        <button type="submit" class="login-button">Сохранить</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>