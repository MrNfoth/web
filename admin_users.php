<?php
session_start();
require_once 'db.php';

// Проверка, что вошёл админ
$stmt = $pdo->prepare("SELECT isadmin FROM сотрудники WHERE id_С = ?");
$stmt->execute([$_SESSION['admin_id'] ?? 0]);
$isadmin = (bool)$stmt->fetchColumn();
if (!$isadmin) {
    header('Location: adminlog.php');
    exit;
}

// Справочник должностей
$posStmt   = $pdo->query("SELECT id_Д, Название_Д FROM `должности` ORDER BY Название_Д");
$positions = $posStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Фильтры из GET
$surnameFilter   = $_GET['surname']   ?? '';
$positionFilter  = $_GET['position']  ?? '';
$minZpFilter     = $_GET['min_zp']    ?? '';
$maxZpFilter     = $_GET['max_zp']    ?? '';
$sortField       = $_GET['sort']      ?? '';
$sortOrder       = $_GET['order']     ?? 'asc';
$editId          = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

// Диапазон зарплат
$rangeStmt = $pdo->query("SELECT MIN(СуммаЗП) AS mn, MAX(СуммаЗП) AS mx FROM `сотрудники`");
$range = $rangeStmt->fetch();
$minZpAll = (float)$range['mn'];
$maxZpAll = (float)$range['mx'];
if ($minZpFilter === '') $minZpFilter = $minZpAll;
if ($maxZpFilter === '') $maxZpFilter = $maxZpAll;

// Обработка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $id     = (int)$_POST['id'];
        $fio    = trim($_POST['fio']);
        $inn    = trim($_POST['inn']);
        $email  = trim($_POST['email']);
        $zp     = (float)$_POST['zp'];
        $posId  = (int)$_POST['position'];
        $login  = trim($_POST['login']);
        $pass   = trim($_POST['password']);
        $isadm  = isset($_POST['isadmin']) ? 1 : 0;
        [$fam, $im, $ot] = array_pad(explode(' ', $fio, 3), 3, '');

        $upd = $pdo->prepare("UPDATE `сотрудники` SET `Фамилия`=?,`Имя`=?,`Отчество`=?,`ИНН`=?,`Email`=?,`СуммаЗП`=?,`id_Д`=?,`Логин`=?,`Пароль`=?,`isadmin`=? WHERE `id_С`=?");
        $upd->execute([$fam, $im, $ot, $inn, $email, $zp, $posId, $login, $pass, $isadm, $id]);
    }
    if (isset($_POST['fire'])) {
        $id = (int)$_POST['id'];
        $fire = $pdo->prepare("UPDATE `сотрудники` SET `id_К`=NULL, `id_Д`=9, `СуммаЗП`=0 WHERE `id_С` = ?");
        $fire->execute([$id]);
    }
    header('Location: adminpanel.php');
    exit;
}

// Фильтрация и сортировка
$sql = "SELECT s.*, d.Название_Д FROM `сотрудники` s JOIN `должности` d ON s.id_Д = d.id_Д WHERE 1";
$params = [];
if ($surnameFilter !== '') {
    $sql .= " AND s.Фамилия LIKE :surname";
    $params[':surname'] = "%{$surnameFilter}%";
}
if ($positionFilter !== '' && isset($positions[$positionFilter])) {
    $sql .= " AND s.id_Д = :posId";
    $params[':posId'] = $positionFilter;
}
$sql .= " AND s.СуммаЗП BETWEEN :minZp AND :maxZp";
$params[':minZp'] = $minZpFilter;
$params[':maxZp'] = $maxZpFilter;

$allowedSort = ['fio'=>'s.Фамилия','inn'=>'s.ИНН','email'=>'s.Email','zp'=>'s.СуммаЗП','position'=>'d.Название_Д'];
if (isset($allowedSort[$sortField])) {
    $dir = strtolower($sortOrder)==='desc'?'DESC':'ASC';
    $sql .= " ORDER BY {$allowedSort[$sortField]} $dir";
} else {
    $sql .= " ORDER BY s.Фамилия ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

include 'header_admin.php';
?>

<div class="container" style="max-width: 100%; overflow-x: auto;">
  <h2>Панель администратора: управление сотрудниками</h2>

  <form method="get" class="filter-form" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
    <input type="text" name="surname" placeholder="Фамилия" value="<?= htmlspecialchars($surnameFilter) ?>">
    <select name="position">
      <option value="">Все должности</option>
      <?php foreach ($positions as $pid => $pname): ?>
        <option value="<?= $pid ?>" <?= $pid==$positionFilter?'selected':'' ?>><?= htmlspecialchars($pname) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" step="0.01" name="min_zp" placeholder="Мин. ЗП" value="<?= htmlspecialchars($minZpFilter) ?>">
    <input type="number" step="0.01" name="max_zp" placeholder="Макс. ЗП" value="<?= htmlspecialchars($maxZpFilter) ?>">
    <select name="sort">
      <option value="">Сортировка</option>
      <option value="fio" <?= $sortField==='fio'?'selected':''?>>ФИО</option>
      <option value="inn" <?= $sortField==='inn'?'selected':''?>>ИНН</option>
      <option value="email" <?= $sortField==='email'?'selected':''?>>Email</option>
      <option value="zp" <?= $sortField==='zp'?'selected':''?>>ЗП</option>
      <option value="position" <?= $sortField==='position'?'selected':''?>>Должность</option>
    </select>
    <select name="order">
      <option value="asc" <?= $sortOrder==='asc'?'selected':''?>>По возрастанию</option>
      <option value="desc" <?= $sortOrder==='desc'?'selected':''?>>По убыванию</option>
    </select>
    <button type="submit" class="button">Применить</button>
  </form>

  <table style="min-width: 1200px;">
    <thead>
      <tr>
        <th>ФИО</th>
        <th>ИНН</th>
        <th>Email</th>
        <th>ЗП</th>
        <th>Должность</th>
        <th>Логин</th>
        <th>Пароль</th>
        <th>isadmin</th>
        <th>Действия</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $row): ?>
      <tr>
        <?php if ($editId === (int)$row['id_С']): ?>
        <form method="post">
          <td><input type="text" name="fio" value="<?= htmlspecialchars(trim($row['Фамилия'].' '.$row['Имя'].' '.$row['Отчество'])) ?>"></td>
          <td><input type="text" name="inn" value="<?= htmlspecialchars($row['ИНН']) ?>"></td>
          <td><input type="email" name="email" value="<?= htmlspecialchars($row['Email']) ?>"></td>
          <td><input type="number" step="0.01" name="zp" value="<?= $row['СуммаЗП'] ?>"></td>
          <td>
            <select name="position">
              <?php foreach ($positions as $pid => $pname): ?>
              <option value="<?= $pid ?>" <?= $pid == $row['id_Д'] ? 'selected' : '' ?>><?= htmlspecialchars($pname) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="text" name="login" value="<?= htmlspecialchars($row['Логин']) ?>"></td>
          <td><input type="text" name="password" value="<?= htmlspecialchars($row['Пароль']) ?>"></td>
          <td><input type="checkbox" name="isadmin" <?= $row['isadmin'] ? 'checked' : '' ?>></td>
          <td>
            <input type="hidden" name="id" value="<?= $row['id_С'] ?>">
            <button name="save" class="button save">Сохранить</button>
            <a href="adminpanel.php" class="button">Отмена</a>
          </td>
        </form>
        <?php else: ?>
          <td><?= htmlspecialchars(trim($row['Фамилия'].' '.$row['Имя'].' '.$row['Отчество'])) ?></td>
          <td><?= htmlspecialchars($row['ИНН']) ?></td>
          <td><?= htmlspecialchars($row['Email']) ?></td>
          <td><?= number_format($row['СуммаЗП'], 2, '.', ' ') ?></td>
          <td><?= htmlspecialchars($row['Название_Д']) ?></td>
          <td><?= htmlspecialchars($row['Логин']) ?></td>
          <td><?= htmlspecialchars($row['Пароль']) ?></td>
          <td><?= $row['isadmin'] ? '✅' : '—' ?></td>
          <td>
            <div class="action-buttons">
              <a href="?edit=<?= $row['id_С'] ?>" class="button edit">Редактировать</a>
              <form method="post" onsubmit="return confirm('Уволить сотрудника?')" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id_С'] ?>">
                <button type="submit" name="fire" class="button fire">Уволить</button>
              </form>
            </div>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
