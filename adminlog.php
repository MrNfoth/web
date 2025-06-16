<?php
session_start();
require_once 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $errors[] = 'Заполните все поля.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM сотрудники WHERE Логин = ? AND isadmin = 1");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['Пароль'] === $password) {
            $_SESSION['admin_id'] = $user['id_С'];
            header('Location: adminpanel.php');
            exit;
        } else {
            $errors[] = 'Неверный логин или пароль, или у вас нет прав администратора.';
        }
    }
}

include 'header_admin.php';
?>

<div class="container" style="max-width:400px; margin:20px auto;">
  <h2>Вход для администраторов</h2>

  <?php if ($errors): ?>
    <ul style="color:red;">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post">
    <div class="form-group">
      <label>Логин</label>
      <input type="text" name="login" required>
    </div>
    <div class="form-group">
      <label>Пароль</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="button" style="margin-top:10px;">Войти</button>
  </form>
</div>
