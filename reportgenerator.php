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
if (!in_array($userPositionId, [8, 2])) {
    http_response_code(403);
    echo "Доступ запрещён.";
    exit;
}

// Получаем данные компании
$companyStmt = $pdo->prepare("
    SELECT к.Название_К, к.ИНН, к.ОГРН, н.id_Н, н.Процент_Н, н.Описание_Н, д.СуммаД, д.СуммаР
    FROM компании к
    LEFT JOIN налоги н ON к.id_Н = н.id_Н
    LEFT JOIN доходырасходы д ON к.id_ДР = д.id_ДР
    JOIN сотрудники с ON к.id_К = с.id_К
    WHERE с.id_С = ?
");
$companyStmt->execute([$userId]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    echo "Данные компании не найдены.";
    exit;
}

// Если в URL есть ?pdf=1 — генерируем PDF
if (isset($_GET['pdf']) && $_GET['pdf'] == 1) {
    $taxPercent = (float)$company['Процент_Н'];
    $income = (float)$company['СуммаД'];
    $expenses = (float)$company['СуммаР'];
    $taxId = (int)$company['id_Н'];

    if ($taxId === 3) {
        $taxBase = max(0, $income - $expenses);
        $taxAmount = ($taxBase * $taxPercent) / 100;
    } else {
        $taxAmount = ($income * $taxPercent) / 100;
    }
    $netIncome = $income - $expenses - $taxAmount;

    require_once __DIR__ . '/tcpdf/tcpdf.php';

    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Ваше приложение');
    $pdf->SetTitle('Отчёт по компании');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 14);

    $pdf->Cell(0, 10, 'Отчёт по компании', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('dejavusans', '', 12);
    $pdf->Cell(50, 8, 'Название компании:', 0, 0);
    $pdf->Cell(0, 8, $company['Название_К'], 0, 1);
    $pdf->Cell(50, 8, 'ИНН:', 0, 0);
    $pdf->Cell(0, 8, $company['ИНН'], 0, 1);
    $pdf->Cell(50, 8, 'ОГРН:', 0, 0);
    $pdf->Cell(0, 8, $company['ОГРН'], 0, 1);
    $pdf->Ln(4);
    $pdf->Cell(50, 8, 'Налоговая система:', 0, 0);
    $pdf->Cell(0, 8, $company['Описание_Н'], 0, 1);
    $pdf->Cell(50, 8, 'Процент налога:', 0, 0);
    $pdf->Cell(0, 8, number_format($taxPercent, 2, ',', ' ') . '%', 0, 1);
    $pdf->Ln(4);
    $pdf->Cell(50, 8, 'Доходы:', 0, 0);
    $pdf->Cell(0, 8, number_format($income, 2, ',', ' ') . ' руб.', 0, 1);
    $pdf->Cell(50, 8, 'Расходы:', 0, 0);
    $pdf->Cell(0, 8, number_format($expenses, 2, ',', ' ') . ' руб.', 0, 1);
    $pdf->Cell(50, 8, 'Сумма налога:', 0, 0);
    $pdf->Cell(0, 8, number_format($taxAmount, 2, ',', ' ') . ' руб.', 0, 1);
    $pdf->Cell(50, 8, 'Чистая прибыль:', 0, 0);
    $pdf->Cell(0, 8, number_format($netIncome, 2, ',', ' ') . ' руб.', 0, 1);

    $pdf->Output('report.pdf', 'I');
    exit;
}

// Ниже — HTML-страница с кнопкой
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<title>Генерация отчёта</title>
<link rel="stylesheet" href="styles.css" />
</head>
<body>
<?php include 'header.php'; ?>
<div class="container" >
    <h2>Генерация отчёта по компании</h2>
    <p><strong>Компания:</strong> <?= htmlspecialchars($company['Название_К']) ?></p>
    <p><strong>Налоговая система:</strong> <?= htmlspecialchars($company['Описание_Н']) ?> (<?= number_format($company['Процент_Н'], 2, ',', ' ') ?>%)</p>
    <p><strong>Доходы:</strong> <?= number_format($company['СуммаД'], 2, ',', ' ') ?> руб.</p>
    <p><strong>Расходы:</strong> <?= number_format($company['СуммаР'], 2, ',', ' ') ?> руб.</p>

    <form method="GET" target="_blank">
        <input type="hidden" name="pdf" value="1">
        <button type="submit" class="button">Сгенерировать PDF-отчёт</button>
    </form>
</div>
</body>
</html>
