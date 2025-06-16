<?php
session_start();
require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM Сотрудники WHERE Логин = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && $user['Пароль'] === $password) {
        $_SESSION['user_id'] = $user['id_С'];
        header('Location: cabinet.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
include 'header.php';
?>
<div class="container" style="max-width:400px; margin:40px auto; padding:0 20px;">
    <h2>Авторизация</h2>
    <form method="post">
        <label>Логин:<br>
            <input type="text" name="login" required style="width:100%; padding:8px; margin:8px 0;">
        </label>
        <label>Пароль:<br>
            <input type="password" name="password" required style="width:100%; padding:8px; margin:8px 0;">
        </label>
        <button type="submit" class="button">
            Войти
        </button>
		<p style="margin-top:1em;">
        Ещё нет аккаунта? 
        <a href="register.php" style="color:#007BFF;">Зарегистрироваться</a>
    </p>
    </form>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>
