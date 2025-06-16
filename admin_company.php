<?php
require_once 'db.php';

$filter = $_GET['filter'] ?? '';
$selectedId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$message = '';

// Обновление данных компании
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_К'])) {
    $id = (int)$_POST['id_К'];
    $Название_К = $_POST['Название_К'] ?? '';
    $ОГРН = $_POST['ОГРН'] ?? '';
    $ИНН = $_POST['ИНН'] ?? '';
    $Адрес = $_POST['Адрес'] ?? '';
    $Малое_предприятие = isset($_POST['Малое_предприятие']) ? 1 : 0;
    $id_СН = $_POST['id_СН'] ?: null;

    $stmt = $pdo->prepare("UPDATE компании SET
        Название_К = ?,
        ОГРН = ?,
        ИНН = ?,
        Адрес = ?,
        Малое_предприятие = ?,
        id_СН = ?
        WHERE id_К = ?");
    $stmt->execute([$Название_К, $ОГРН, $ИНН, $Адрес, $Малое_предприятие, $id_СН, $id]);

    $message = "Данные компании обновлены.";
    $selectedId = $id;
}

// Получаем список компаний по фильтру
$stmt = $pdo->prepare("SELECT id_К, Название_К FROM компании WHERE Название_К LIKE ? ORDER BY Название_К");
$stmt->execute(["%$filter%"]);
$allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные выбранной компании
$company = null;
if ($selectedId > 0) {
    $stmt = $pdo->prepare("
        SELECT c.*, sn.название_СН 
        FROM компании c
        LEFT JOIN системы_налогообложения sn ON c.id_СН = sn.id_СН
        WHERE c.id_К = ?
        LIMIT 1
    ");
    $stmt->execute([$selectedId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Получаем системы налогообложения
$stmt = $pdo->query("SELECT id_СН, название_СН FROM системы_налогообложения ORDER BY id_СН");
$taxSystems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем виды подписок
$stmt = $pdo->query("SELECT id_ВП, Название_ВП FROM виды_подписок ORDER BY id_ВП");
$subscriptionTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем доходы/расходы с налогами, если выбрана компания
$taxData = [];
if ($company) {
    $stmt = $pdo->prepare("
        SELECT dr.*, n.Описание_Н, n.Процент_Н
        FROM доходырасходы dr
        LEFT JOIN Налоги n ON dr.id_Н = n.id_Н
        WHERE dr.id_ДР = ?
    ");
    $stmt->execute([$company['id_ДР']]);
    $taxData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <title>Редактирование компании</title>
</head>
<body>
<?php include 'header_admin.php'; ?>

<div class="container">

<?php if ($message): ?>
    <div class="message" style="color:green; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!$company): ?>
    <!-- Фильтр и список компаний -->
    <form method="get" action="admin_company.php" style="margin-bottom: 20px;">
        <label for="filter">Поиск компании по названию:</label>
        <input type="text" name="filter" id="filter" value="<?= htmlspecialchars($filter) ?>" placeholder="Введите часть названия">
        <button type="submit" class="button">Найти</button>
    </form>

    <?php if ($allCompanies): ?>
        <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th>Компания</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allCompanies as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['Название_К']) ?></td>
                    <td>
                    <button type="button" onclick="window.location.href='?company_id=<?= $c['id_К'] ?>&filter=<?= urlencode($filter) ?>'" class="button">Редактировать</button>

                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Компании не найдены.</p>
    <?php endif; ?>

<?php else: ?>
    <!-- Форма редактирования компании -->
    <form method="post" action="admin_company.php?filter=<?= urlencode($filter) ?>">

        <input type="hidden" name="id_К" value="<?= $company['id_К'] ?>">

        <p>
            <label for="Название_К">Название компании:</label><br>
            <input type="text" name="Название_К" id="Название_К" required value="<?= htmlspecialchars($company['Название_К']) ?>">
        </p>

        <p>
            <label for="ОГРН">ОГРН:</label><br>
            <input type="text" name="ОГРН" id="ОГРН" value="<?= htmlspecialchars($company['ОГРН']) ?>">
        </p>

        <p>
            <label for="ИНН">ИНН:</label><br>
            <input type="text" name="ИНН" id="ИНН" value="<?= htmlspecialchars($company['ИНН']) ?>">
        </p>

        <p>
            <label for="Адрес">Адрес:</label><br>
            <input type="text" name="Адрес" id="Адрес" value="<?= htmlspecialchars($company['Адрес']) ?>">
        </p>

        <p>
            <label>
                <input type="checkbox" name="Малое_предприятие" value="1" <?= $company['Малое_предприятие'] ? 'checked' : '' ?>>
                Малое предприятие
            </label>
        </p>

        <p>
            <label for="id_СН">Система налогообложения:</label><br>
            <select name="id_СН" id="id_СН">
                <option value="">— Не выбрано —</option>
                <?php foreach ($taxSystems as $sn): ?>
                    <option value="<?= $sn['id_СН'] ?>" <?= $company['id_СН'] == $sn['id_СН'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sn['название_СН']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="id_ВП">Вид подписки:</label><br>
            <select name="id_ВП" id="id_ВП">
                <option value="">— Не выбрано —</option>
                <?php foreach ($subscriptionTypes as $vp): ?>
                    <option value="<?= $vp['id_ВП'] ?>" <?= ($company['id_ВП'] == $vp['id_ВП']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($vp['Название_ВП']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <h3>Доходы и расходы с налогами</h3>
        <?php if ($taxData): ?>
            <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%; margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th>Доход (СуммаД)</th>
                        <th>Расход (СуммаР)</th>
                        <th>Налог (Описание и %)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Получим все налоги один раз для селекта
                    $allTaxes = $pdo->query("SELECT id_Н, CONCAT(Описание_Н, ' (', Процент_Н, '%)') AS descr FROM Налоги ORDER BY Описание_Н")->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php foreach ($taxData as $idx => $dr): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="dr_id[]" value="<?= $dr['id_ДР'] ?>">
                            <input type="text" name="dr_СуммаД[]" value="<?= htmlspecialchars($dr['СуммаД']) ?>">
                        </td>
                        <td>
                            <input type="text" name="dr_СуммаР[]" value="<?= htmlspecialchars($dr['СуммаР']) ?>">
                        </td>
                        <td>
                            <select name="dr_id_Н[]">
                                <option value="">— Не выбрано —</option>
                                <?php foreach ($allTaxes as $tax): ?>
                                    <option value="<?= $tax['id_Н'] ?>" <?= ($tax['id_Н'] == $dr['id_Н']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tax['descr']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Данные о доходах/расходах отсутствуют.</p>
        <?php endif; ?>

        <button type="submit" class="button">Сохранить изменения</button>
        <button type="button" onclick="window.location.href='admin_company.php?filter=<?= urlencode($filter) ?>'" class="button">Отмена</button>

    </form>
<?php endif; ?>

</div>

</body>
</html>
