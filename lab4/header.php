<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Библиотека</h1>
        </div>
    </header>
    <nav class="main-menu">
        <div class="container">
            <ul>
                <li><a href="index.php">Главная</a></li>
                <li><a href="readers.php">Читатели</a></li>
                <li><a href="books.php">Книги</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="profile.php">Личный кабинет</a></li>
                    <li><a href="logout.php">Выход</a></li>
                <?php else: ?>
                    <li><a href="login.php">Вход</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</body>
</html>