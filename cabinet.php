<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

// Получаем данные сотрудника + название компании
$stmt = $pdo->prepare("
  SELECT 
    e.id_С,
    e.Фамилия, e.Имя, e.Отчество,
    e.Email, e.СуммаЗП,
    d.Название_Д AS Должность,
    c.Название_К AS Компания
  FROM Сотрудники e
  JOIN Компании c ON e.id_К = c.id_К
  JOIN Должности d ON e.id_Д = d.id_Д
  WHERE e.id_С = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

include 'header.php';
?>
<div class="container" style="max-width:600px; margin:40px auto; padding:0 20px;">
    <h2>Добро пожаловать, <?= htmlspecialchars($user['Фамилия'] . ' ' . $user['Имя']) ?></h2>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?></p>
    <p><strong>Должность:</strong> <?= htmlspecialchars($user['Должность']) ?></p>
    <p><strong>Компания:</strong> <?= htmlspecialchars($user['Компания'] ?? '—') ?></p>
    <p><strong>Зарплата:</strong> <?= number_format($user['СуммаЗП'], 2, '.', ' ') ?> руб.</p>
</div>
