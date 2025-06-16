<?php
session_start();
require_once 'db.php';
require_once 'mail_stub.php'; // Функция send_email_stub

// Проверка авторизации и должности
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id_Д, email, id_К FROM сотрудники WHERE id_С = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData || $userData['id_Д'] != 8) {
    http_response_code(403);
    echo "Доступ запрещен: только для директора.";
    exit;
}

$companyId = $userData['id_К'];
$email = $userData['email'];
$successMessage = "";

// Обработка выбора подписки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_ВП'])) {
    $selectedPlanId = (int)$_POST['id_ВП'];

    // Получаем данные выбранной подписки
    $planStmt = $pdo->prepare("SELECT * FROM виды_подписок WHERE id_ВП = ?");
    $planStmt->execute([$selectedPlanId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if ($plan) {
        $subscriptionTerm = (int)$plan['Срок'];

        // Получаем текущие даты подписки
        $subStmt = $pdo->prepare("SELECT Дата_началаП, Дата_окончанияП FROM компании WHERE id_К = ?");
        $subStmt->execute([$companyId]);
        $dates = $subStmt->fetch(PDO::FETCH_ASSOC);

        $now = new DateTime();
        $currentStart = $dates['Дата_началаП'] ? new DateTime($dates['Дата_началаП']) : null;
        $currentEnd = $dates['Дата_окончанияП'] ? new DateTime($dates['Дата_окончанияП']) : null;

        if ($currentEnd && $currentEnd > $now) {
            // Подписка активна — продлеваем окончание, начало не трогаем
            $newStart = $currentStart;
            $newEnd = clone $currentEnd;
            $newEnd->modify("+$subscriptionTerm days");
        } else {
            // Подписки нет или она истекла — начинаем с сегодня
            $newStart = clone $now;
            $newEnd = clone $now;
            $newEnd->modify("+$subscriptionTerm days");
        }

        // Обновление компании
        $update = $pdo->prepare("
            UPDATE компании 
            SET id_ВП = ?, 
                Дата_началаП = ?, 
                Дата_окончанияП = ?
            WHERE id_К = ?
        ");
        $update->execute([
            $selectedPlanId,
            $newStart->format('Y-m-d'),
            $newEnd->format('Y-m-d'),
            $companyId
        ]);

        // Отправка письма
        $subject = "Подписка: {$plan['Название_ВП']}";
        $body = "Вы выбрали план подписки: {$plan['Название_ВП']}.\n"
              . "Стоимость: {$plan['Цена']} руб.\n"
              . "Срок действия: с {$newStart->format('d.m.Y')} до {$newEnd->format('d.m.Y')}.\n\n"
              . "Пожалуйста, оплатите счёт в течение часа, иначе подписка будет аннулирована, а вы наказаны.";

        send_email_stub($email, $subject, $body);

        $successMessage = "Счёт на оплату подписки отправлен на почту {$email}. 
        Оплатите в течение часа, иначе подписка будет аннулирована, а вы наказаны.";
    }
}

// Получаем все подписки
$stmt = $pdo->query("SELECT * FROM виды_подписок ORDER BY Цена ASC");
$plans = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>

<div class="container" >
  <h2 class="mb-6">Доступные подписки</h2>

  <?php if ($successMessage): ?>
    <div class="alert alert-success" style="margin-bottom: 20px; padding: 10px; border-left: 4px solid green;">
      <?= htmlspecialchars($successMessage) ?>
    </div>
  <?php endif; ?>

  <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
    <?php foreach ($plans as $plan): ?>
      <div class="card">
        <h3><?= htmlspecialchars($plan['Название_ВП']) ?></h3>
        <p>Срок действия: <strong><?= (int)$plan['Срок'] ?> дней</strong></p>
        <p><strong><?= number_format($plan['Цена'], 2, '.', ' ') ?> руб.</strong></p>
        <form method="POST">
          <input type="hidden" name="id_ВП" value="<?= $plan['id_ВП'] ?>">
          <button class="button" style="margin-top: 10px;">Выбрать</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="margin-top:30px;">
    <p>Вы можете продлить подписку в любой момент через личный кабинет. Все тарифы имеют фиксированную стоимость и срок действия.</p>
  </div>
</div>
