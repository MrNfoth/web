
<?php
// employee.php — Список сотрудников (с отступами и поддержкой тем)
require_once 'db.php';

// Фильтры из GET
$surnameFilter  = $_GET['surname']      ?? '';
$positionFilter = $_GET['position']     ?? '';
$minSalary      = $_GET['min_salary']   ?? '';
$maxSalary      = $_GET['max_salary']   ?? '';
$companyFilter  = $_GET['company_id']   ?? '';

// Компании для фильтра
$compStmt  = $pdo->query("SELECT id_К, Название_К FROM Компании ORDER BY Название_К");
$companies = $compStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Должности для фильтра
$posStmt   = $pdo->query("SELECT id_Д, Название_Д FROM Должности ORDER BY Название_Д");
$positions = $posStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Основной запрос
$sql = "
  SELECT 
    e.id_С,
    e.Фамилия, e.Имя, e.Отчество,
    e.Email, e.СуммаЗП,
    d.Название_Д AS Должность,
    c.Название_К AS Компания
  FROM Сотрудники e
  JOIN Компании c ON e.id_К = c.id_К
  JOIN Должности d ON e.id_Д = d.id_Д
  WHERE 1
";
$params = [];

// Добавляем условия
if ($surnameFilter !== '') {
    $sql .= " AND e.Фамилия LIKE :surname";
    $params[':surname'] = "%{$surnameFilter}%";
}
if ($positionFilter !== '' && isset($positions[$positionFilter])) {
    $sql .= " AND e.id_Д = :posId";
    $params[':posId'] = (int)$positionFilter;
}
if ($minSalary !== '') {
    $sql .= " AND e.СуммаЗП >= :minSalary";
    $params[':minSalary'] = (float)$minSalary;
}
if ($maxSalary !== '') {
    $sql .= " AND e.СуммаЗП <= :maxSalary";
    $params[':maxSalary'] = (float)$maxSalary;
}
if ($companyFilter !== '' && isset($companies[$companyFilter])) {
    $sql .= " AND e.id_К = :compId";
    $params[':compId'] = (int)$companyFilter;
}

$stmt     = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>
<style>

  .filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
    align-items: flex-end;
  }
  .filter-form label {
    flex: 1 1 200px;
    min-width: 150px;
    display: flex;
    flex-direction: column;
  }
  .filter-form input,
  .filter-form select {
    margin-top: 4px;
    padding: 6px;
    box-sizing: border-box;
  }
  /* Кнопки под полями, в ряд */
  .filter-actions {
    display: flex;
    gap: 10px;
    width: 100%;            /* чтобы кнопки начинались под полями */
    justify-content: flex-start;
  }
  .filter-actions .button {
    padding: 8px 16px;
    margin-top: 0;          /* убираем отступ сверху */
  }
</style>

<div class="container">
  <h1>Список сотрудников</h1>

  <form method="get" action="employee.php">
    <div class="filter-form">
      <label>
        Фамилия
        <input type="text" name="surname" value="<?= htmlspecialchars($surnameFilter) ?>">
      </label>
      <label>
        Компания
        <select name="company_id">
          <option value="">Все</option>
          <?php foreach ($companies as $id => $name): ?>
            <option value="<?=$id?>" <?= $id==$companyFilter?'selected':''?>>
              <?=htmlspecialchars($name)?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        Должность
        <select name="position">
          <option value="">Все</option>
          <?php foreach ($positions as $id => $title): ?>
            <option value="<?=$id?>" <?= $id==$positionFilter?'selected':''?>>
              <?=htmlspecialchars($title)?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        Зарплата от
        <input type="number" name="min_salary" min="0" value="<?= htmlspecialchars($minSalary) ?>">
      </label>
      <label>
        до
        <input type="number" name="max_salary" min="0" value="<?= htmlspecialchars($maxSalary) ?>">
      </label>
    </div>

    <div class="filter-actions">
      <button type="submit" class="button">Фильтровать</button>
      <button type="button" class="button" onclick="location='employee.php'">Сбросить</button>
    </div>
  </form>

    <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Фамилия</th>
        <th>Имя</th>
        <th>Отчество</th>
        <th>Email</th>
        <th>Зарплата</th>
        <th>Должность</th>
        <th>Компания</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($employees)): ?>
        <tr><td colspan="8">Нет сотрудников по заданным фильтрам</td></tr>
      <?php else: ?>
        <?php foreach ($employees as $e): ?>
          <tr>
            <td><?= $e['id_С'] ?></td>
            <td><?= htmlspecialchars($e['Фамилия']) ?></td>
            <td><?= htmlspecialchars($e['Имя']) ?></td>
            <td><?= htmlspecialchars($e['Отчество']) ?></td>
            <td><?= htmlspecialchars($e['Email']) ?></td>
            <td><?= number_format($e['СуммаЗП'], 0, '.', ' ') ?></td>
            <td><?= htmlspecialchars($e['Должность']) ?></td>
            <td><?= htmlspecialchars($e['Компания']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
