<?php
require_once 'db.php';

$message = '';

// Добавление должности
if (isset($_POST['add_position'])) {
    $position_name = trim($_POST['position_name'] ?? '');
    if ($position_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO должности (Название_Д) VALUES (?)");
        $stmt->execute([$position_name]);
        $message = "Должность '$position_name' добавлена.";
    } else {
        $message = "Введите название должности.";
    }
}
// Обработка формы добавления системы налогообложения + налога
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_system'], $_POST['new_tax_percent'], $_POST['new_tax_description'])) {
    $newSystem = trim($_POST['new_system']);
    $newTaxPercent = floatval($_POST['new_tax_percent']);
    $newTaxDescription = trim($_POST['new_tax_description']);

    if ($newSystem === '' || $newTaxPercent <= 0) {
        $message = "Введите корректные данные для системы налогообложения и налога.";
    } else {
        // Вставляем новую систему
        $stmt = $pdo->prepare("INSERT INTO системы_налогообложения (название_СН) VALUES (?)");
        $stmt->execute([$newSystem]);
        $newId = $pdo->lastInsertId();

        // Вставляем налог с тем же id
        $stmt2 = $pdo->prepare("INSERT INTO налоги (id_Н, Процент_Н, Описание_Н) VALUES (?, ?, ?)");
        $stmt2->execute([$newId, $newTaxPercent, $newTaxDescription]);

        $message = "Добавлена система налогообложения '$newSystem' с налогом $newTaxPercent%.";
    }
    
}

// Получаем список систем налогообложения и налогов для отображения
$stmt = $pdo->query("
    SELECT sn.id_СН, sn.название_СН, n.Процент_Н, n.Описание_Н
    FROM системы_налогообложения sn
    LEFT JOIN налоги n ON sn.id_СН = n.id_Н
    ORDER BY sn.id_СН
");
$systems = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Получаем список должностей
$positions = $pdo->query("SELECT id_Д, Название_Д FROM должности ORDER BY Название_Д")->fetchAll(PDO::FETCH_ASSOC);

// Получаем список систем налогообложения
$taxSystems = $pdo->query("SELECT id_СН, название_СН FROM системы_налогообложения ORDER BY название_СН")->fetchAll(PDO::FETCH_ASSOC);

// Получаем список налогов
$taxes = $pdo->query("SELECT id_Н, Описание_Н, Процент_Н FROM налоги ORDER BY Описание_Н")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Настройки - Системы налогообложения и налоги</title>
</head>
<body>
<?php include 'header_admin.php'; ?>

<div class="container">
    <h1>Настройки администратора</h1>

    <?php if ($message): ?>
        <p><b><?= htmlspecialchars($message) ?></b></p>
    <?php endif; ?>

    <section>
        <h2>Добавить должность</h2>
        <form method="post">
            <input type="text" name="position_name" placeholder="Название должности" required>
            <button type="submit" name="add_position" class="button">Добавить</button>
        </form>

        <?php if ($positions): ?>
            <h3>Существующие должности</h3>
            <ul>
                <?php foreach ($positions as $pos): ?>
                    <li><?= htmlspecialchars($pos['Название_Д']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <h2>Добавить новую систему налогообложения и налог</h2>
    <form method="post" action="admin_settings.php">
        <label>Название системы налогообложения:<br>
            <input type="text" name="new_system" required>
        </label><br><br>

        <label>Процент налога:<br>
            <input type="number" step="0.01" name="new_tax_percent" required>
        </label><br><br>

        <label>Описание налога:<br>
            <input type="text" name="new_system" required>
        </label><br><br>

        <button type="submit" class="button">Добавить налог и систему</button>
    </form>

    <h2>Существующие системы налогообложения и налоги</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название системы налогообложения</th>
                <th>Процент налога</th>
                <th>Описание налога</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($systems as $sys): ?>
                <tr>
                    <td><?= (int)$sys['id_СН'] ?></td>
                    <td><?= htmlspecialchars($sys['название_СН']) ?></td>
                    <td><?= htmlspecialchars($sys['Процент_Н']) ?>%</td>
                    <td><?= nl2br(htmlspecialchars($sys['Описание_Н'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
