<?php
require_once 'config.php';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    // Проверка на пустые поля
    if (empty($login) || empty($password)) {
        $_SESSION['error'] = "Заполните все поля!";
        header("Location: login.php");
        exit;
    }

    try {
        // Поиск пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Авторизация успешна
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: profile.php");
            exit;
        } else {
            $_SESSION['error'] = "Неверный логин или пароль!";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        die("Ошибка базы данных: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container login-page">
    <div class="login-header">
        <h2>Вход в библиотечную систему</h2>
        <p>Пожалуйста, авторизуйтесь для продолжения</p>
    </div>

    <?php if (isset($error)): ?>
    <div class="error-message">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form class="login-form" method="POST">
        <div class="form-group">
            <input type="text" 
                   class="form-input" 
                   name="login" 
                   placeholder="Логин"
                   required>
        </div>
        
        <div class="form-group">
            <input type="password" 
                   class="form-input" 
                   name="password" 
                   placeholder="Пароль"
                   required>
        </div>

        <button type="submit" class="login-button">Войти</button>
    </form>
</div>
    
<?php require_once 'footer.php'; ?>

</body>
</html>
