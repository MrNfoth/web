<?php
session_start();
require_once 'db.php';

$theme = 'light';
if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light','dark','contrast'])) {
    $theme = $_COOKIE['theme'];
}

$isAdmin = false;
if (isset($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("SELECT isadmin FROM сотрудники WHERE id_С = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $isAdmin = (bool)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>MyCompanyApp — Admin</title>
  <link rel="stylesheet" href="css/common.css">
  <script src="theme-switch.js" defer></script>
</head>
<body>
    <?php include 'header.php'; ?>
  <header class="header">
    <div class="header-inner">
      <a href="adminpanel.php" class="logo">Админ панель</a>

      <nav class="nav">
        <?php if ($isAdmin): ?>
          <a href="adminpanel.php">Панель администратора</a>
          <a href="admin_users.php">Пользователи</a>
          <a href="admin_company.php">Компании</a>
          <a href="admin_settings.php">Настройки</a>
        <?php endif; ?>
      </nav>
      </div>
    </div>
  </header>
