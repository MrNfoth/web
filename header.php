<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['page_views'])) {
    $_SESSION['page_views'] = 0;
}
$_SESSION['page_views']++;

// Определяем тему из cookie (по умолчанию light)
$theme = 'light';
if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], ['light','dark','contrast'])) {
    $theme = $_COOKIE['theme'];
}
$isPrivileged = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id_Д FROM `сотрудники` WHERE id_С = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = (int)$stmt->fetchColumn();
    // Менеджер — 5, Бухгалтер — 2
    if (in_array($role, [2, 5, 8], true)) {
        $isPrivileged = true;
    }
}
$isdirrector = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id_Д FROM `сотрудники` WHERE id_С = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = (int)$stmt->fetchColumn();
    if (in_array($role, [8], true)) {
        $isdirrector = true;
    }
}
$isbuhgalter = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id_Д FROM `сотрудники` WHERE id_С = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = (int)$stmt->fetchColumn();
    if (in_array($role, [2, 8], true)) {
        $isbuhgalter = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>MyCompanyApp</title>
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/<?= htmlspecialchars($theme) ?>.css">
  <script src="theme-switch.js" defer></script>
</head>
<body>
  <header class="header">
    <div class="header-inner">
      <a href="index.php" class="logo">MyCompanyApp</a>

      <nav class="nav">
        <a href="index.php">Главная</a>
        <a href="contacts.php">Контакты</a>
        <?php if ($isbuhgalter): ?>
          <a href="reportgenerator.php">Генератор отчётов</a>
        <?php endif; ?>
        <?php if ($isdirrector): ?>
          <a href="subscriptions.php">Подписки</a>
        <?php endif; ?>
        <?php if ($isPrivileged): ?>
          <a href="management.php">Менеджмент</a>
        <?php endif; ?>
        <?php if ($isdirrector): ?>
          <a href="companyredactor.php">Компания</a>
        <?php endif; ?>
      </nav>

      <div class="right-controls" style="display:flex; align-items:center;">
        <!-- Блок переключения тем -->
        <div class="theme-switcher">
          <button
            type="button"
            class="theme-btn <?= $theme==='light' ? 'active' : '' ?>"
            data-theme="light">
            🌞
          </button>
          <button
            type="button"
            class="theme-btn <?= $theme==='dark' ? 'active' : '' ?>"
            data-theme="dark">
            🌜
          </button>
          <button
            type="button"
            class="theme-btn <?= $theme==='contrast' ? 'active' : '' ?>"
            data-theme="contrast">
            ⚡
          </button>
        </div>

        <!-- Блок авторизации -->
        <div class="auth">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="cabinet.php" class="button">Личный кабинет</a>
            <a href="logout.php"   class="button">Выйти</a>
          <?php else: ?>
            <a href="login.php"    class="button">Войти</a>
          <?php endif; ?>
          <div class="page-view-counter" title="Количество посещённых страниц">
          👁️ <?= $_SESSION['page_views'] ?>
          </div>
        </div>
      </div>
    </div>
  </header>
