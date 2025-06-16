<?php
session_start();
require_once 'db.php';

// Проверка авторизации и должности
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id_Д FROM сотрудники WHERE id_С = ?");
$stmt->execute([$userId]);
$userPositionId = $stmt->fetchColumn();

if ($userPositionId != 8) {
    http_response_code(403);
    echo "Доступ запрещен: только для директора.";
    exit;
}

// Получаем компанию и связанные данные доходов/расходов
$companyStmt = $pdo->prepare("
    SELECT к.*, д.id_ДР, д.id_Н, д.СуммаД, д.СуммаР 
    FROM компании к 
    LEFT JOIN доходырасходы д ON к.id_ДР = д.id_ДР
    JOIN сотрудники с ON к.id_К = с.id_К 
    WHERE с.id_С = ?
");
$companyStmt->execute([$userId]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo "Компания не найдена.";
    exit;
}

// Справочник систем налогообложения
$posStmt = $pdo->query("SELECT id_СН, название_СН FROM системы_налогообложения ORDER BY название_СН");
$positions = $posStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';
$success = isset($_GET['success']) && $_GET['success'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $editMode) {
    // Обработка сохранения изменений
    $company['Название_К'] = trim($_POST['Название_К']);
    $company['ОГРН'] = trim($_POST['ОГРН']);
    $company['ИНН'] = trim($_POST['ИНН']);
    $company['Адрес'] = trim($_POST['Адрес']);
    $company['id_СН'] = (int)$_POST['id_СН'];
    $summaD = isset($_POST['СуммаД']) ? (float)$_POST['СуммаД'] : 0.0;
    $summaR = isset($_POST['СуммаР']) ? (float)$_POST['СуммаР'] : 0.0;

    // Обновляем компанию
    $update = $pdo->prepare("UPDATE компании SET Название_К = ?, ОГРН = ?, ИНН = ?, Адрес = ?, id_СН = ?, id_Н = ? WHERE id_К = ?");
    $update->execute([
        $company['Название_К'],
        $company['ОГРН'],
        $company['ИНН'],
        $company['Адрес'],
        $company['id_СН'],
        $company['id_СН'],
        $company['id_К']
    ]);

    // Обновляем доходырасходы
    $updateDr = $pdo->prepare("UPDATE доходырасходы SET id_Н = ?, СуммаД = ?, СуммаР = ? WHERE id_ДР = ?");
    $updateDr->execute([
        $company['id_СН'],
        $summaD,
        $summaR,
        $company['id_ДР']
    ]);

    // Редирект обратно в режим просмотра с сообщением об успехе
    header("Location: companyredactor.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Редактирование компании</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
<?php include 'header.php'; ?>
<div class="container" style="display: flex; gap: 40px;">
    <div style="flex: 1;">
        <h2>Информация о компании</h2>
        <?php if ($success): ?>
            <div class="alert alert-success">Данные обновлены.</div>
        <?php endif; ?>

        <?php if (!$editMode): ?>
            <!-- Режим просмотра -->
            <p><strong>Название компании:</strong> <?= htmlspecialchars($company['Название_К']) ?></p>
            <p><strong>ОГРН:</strong> <?= htmlspecialchars($company['ОГРН']) ?></p>
            <p><strong>ИНН:</strong> <?= htmlspecialchars($company['ИНН']) ?></p>
            <p><strong>Адрес:</strong> <?= htmlspecialchars($company['Адрес']) ?></p>
            <p><strong>Система налогообложения:</strong> <?= htmlspecialchars($positions[$company['id_СН']] ?? '') ?></p>
            <p><strong>Сумма доходов (СуммаД):</strong> <?= htmlspecialchars($company['СуммаД'] ?? '') ?></p>
            <p><strong>Сумма расходов (СуммаР):</strong> <?= htmlspecialchars($company['СуммаР'] ?? '') ?></p>

            <a href="?edit=1" class="button">Редактировать</a>

        <?php else: ?>
            <!-- Режим редактирования -->
            <form method="POST">
                <div>
                    <label>Название компании:</label>
                    <input type="text" name="Название_К" value="<?= htmlspecialchars($company['Название_К']) ?>" required>
                </div>
                <div>
                    <label>ОГРН:</label>
                    <input type="text" name="ОГРН" value="<?= htmlspecialchars($company['ОГРН']) ?>" required>
                </div>
                <div>
                    <label>ИНН:</label>
                    <input type="text" name="ИНН" value="<?= htmlspecialchars($company['ИНН']) ?>" required>
                </div>
                <div>
                    <label>Адрес:</label>
                    <input type="text" name="Адрес" value="<?= htmlspecialchars($company['Адрес']) ?>" required>
                </div>
                <div>
                    <label>Система налогообложения:</label>
                    <select name="id_СН">
                      <?php foreach ($positions as $pid => $pname): ?>
                      <option value="<?= $pid ?>" <?= ($pid === (int)$company['id_СН']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pname) ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Сумма доходов (СуммаД):</label>
                    <input type="number" step="0.01" name="СуммаД" value="<?= htmlspecialchars($company['СуммаД'] ?? '') ?>">
                </div>
                <div>
                    <label>Сумма расходов (СуммаР):</label>
                    <input type="number" step="0.01" name="СуммаР" value="<?= htmlspecialchars($company['СуммаР'] ?? '') ?>">
                </div>
                <br>
                <button type="submit" class="button">Сохранить изменения</button>
                <a href="companyredactor.php" class="button" style="margin-left:10px;">Отмена</a>
            </form>
        <?php endif; ?>
    </div>

    <!-- Блок информации о подписке -->
    <div style="width: 320px; border-left: 1px solid #ccc; padding-left: 20px;">
        <h3>Подписка</h3>
        <?php if (!empty($company['Дата_началаП']) && !empty($company['Дата_окончанияП'])): ?>
            <p><strong>Дата окончания:</strong> <?= htmlspecialchars($company['Дата_окончанияП']) ?></p>
            <?php
                $now = new DateTime();
                $end = new DateTime($company['Дата_окончанияП']);
                $status = ($now > $end) ? 'Истекла' : 'Активна';
                $color = ($now > $end) ? 'red' : 'green';
            ?>
            <p><strong>Статус:</strong> <span style="color: <?= $color ?>;"><?= $status ?></span></p>
        <?php else: ?>
            <p>Подписка не активирована.</p>
        <?php endif; ?>
        <h3>Код приглашения компании</h3>
        <p><strong>Код:</strong> <?= htmlspecialchars($company['Код_приглашения']) ?></p>
    </div>
</div>
</body>
</html>
