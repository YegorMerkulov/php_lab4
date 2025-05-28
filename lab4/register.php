<?php
require_once 'config.php';
require_once 'config.php';
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $allowed_roles = ['user', 'admin'];

    // Валидация
    if (empty($login) || empty($password)) {
        $error = 'Все поля обязательны для заполнения!';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов!';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают!';
    } elseif (!in_array($role, $allowed_roles)) {
        $error = 'Недопустимая роль!';
    } else {
        try {
            // Проверка существующего пользователя
            $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
            $stmt->execute([$login]);
            
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким логином уже существует!';
            } else {
                // Регистрация
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (login, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$login, $hashed_password, 'user']); // По умолчанию роль 'user'
                $success = 'Регистрация успешна! <a href="login.php">Войти</a>';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка регистрации: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <div class="container login-page">
        <div class="login-header">
            <h2>Регистрация</h2>
        </div>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?= $success ?></div>
        <?php else: ?>
            <form class="login-form" method="POST">
                <div class="form-group">
                    <label>Логин:</label>
                    <input type="text" name="login" class="form-input" value="<?= htmlspecialchars($login ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Пароль:</label>
                    <input type="password" name="password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Подтвердите пароль:</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Роль:</label>
                    <select name="role" class="form-input" required>
                        <option value="user" selected>Пользователь</option>
                        <option value="admin">Администратор</option>
                    </select>
                </div>

                <button type="submit" class="login-button" class="button">Зарегистрироваться</button>
            </form>

        <?php endif; ?>
    </div>

<?php require_once 'footer.php'; ?>
</body>
</html>