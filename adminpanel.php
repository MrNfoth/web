<?php
session_start();
require_once 'db.php';

// Проверка входа администратора
$stmt = $pdo->prepare("SELECT isadmin FROM сотрудники WHERE id_С = ?");
$stmt->execute([$_SESSION['admin_id'] ?? 0]);
$isadmin = (bool)$stmt->fetchColumn();
if (!$isadmin) {
    header('Location: adminlog.php');
    exit;
}

include 'header_admin.php';
?>

<div class="container" style="max-width: 800px; margin: 40px auto;">
  <h2>Добро пожаловать в панель администратора</h2>
  <p>Вы вошли как администратор. Здесь вы можете управлять системой:</p>

  <ul style="line-height: 1.8;">
    <li><a href="admin_users.php" class="button">Список пользователей</a></li>
    <li><a href="admin_settings.php" class="button">Настройки системы</a></li>
    <li><a href="index.php" class="button">На главную</a></li>
  </ul>
</div>
