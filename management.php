<?php
session_start();
require_once 'db.php';

// Определяем роль и компанию текущего пользователя
$stmtUser = $pdo->prepare("SELECT id_К, id_Д FROM `сотрудники` WHERE id_С = ?");
$stmtUser->execute([$_SESSION['user_id']]);
list($myCompany, $myRole) = $stmtUser->fetch(PDO::FETCH_NUM);

// Разрешаем доступ только менеджерам (5) и бухгалтерам (2)
if (!in_array((int)$myRole, [2,5,8], true)) {
    header('Location: index.php');
    exit;
}

// Фильтры из GET
$surnameFilter   = $_GET['surname']   ?? '';
$positionFilter  = $_GET['position']  ?? '';
$minZpFilter     = $_GET['min_zp']    ?? '';
$maxZpFilter     = $_GET['max_zp']    ?? '';
$sortField       = $_GET['sort']      ?? '';
$sortOrder       = $_GET['order']     ?? 'asc';

// Справочник должностей
$posStmt   = $pdo->query("SELECT id_Д, Название_Д FROM `должности` ORDER BY Название_Д");
$positions = $posStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Диапазон зарплат для слайдера
$rangeStmt = $pdo->prepare("SELECT MIN(СуммаЗП) AS mn, MAX(СуммаЗП) AS mx FROM `сотрудники` WHERE id_К = ?");
$rangeStmt->execute([$myCompany]);
$range = $rangeStmt->fetch();
$minZpAll = (float)$range['mn'];
$maxZpAll = (float)$range['mx'];
if ($minZpFilter === '') $minZpFilter = $minZpAll;
if ($maxZpFilter === '') $maxZpFilter = $maxZpAll;

// Обработка POST: сохранение и увольнение
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        $id    = (int)$_POST['id'];
        $fio   = trim($_POST['fio']);
        $inn   = trim($_POST['inn']);
        $email = trim($_POST['email']);
        $zp    = (float)$_POST['zp'];
        $posId = (int)$_POST['position'];
        [$fam, $im, $ot] = array_pad(explode(' ', $fio, 3), 3, '');

        $upd = $pdo->prepare("
            UPDATE `сотрудники`
               SET `Фамилия` = ?, `Имя` = ?, `Отчество` = ?,
                   `ИНН` = ?, `Email` = ?, `СуммаЗП` = ?, `id_Д` = ?
             WHERE `id_С` = ? AND `id_К` = ?
        ");
        $upd->execute([$fam, $im, $ot, $inn, $email, $zp, $posId, $id, $myCompany]);
    }
    if (isset($_POST['fire'])) {
        $id = (int)$_POST['id'];
        $fire = $pdo->prepare("
            UPDATE `сотрудники`
               SET `id_К` = NULL, `id_Д` = 9, `СуммаЗП` = 0
             WHERE `id_С` = ? AND `id_К` = ?
        ");
        $fire->execute([$id, $myCompany]);
    }
    header('Location: management.php'
      . '?surname=' . urlencode($surnameFilter)
      . '&position=' . urlencode($positionFilter)
      . '&min_zp=' . urlencode($minZpFilter)
      . '&max_zp=' . urlencode($maxZpFilter)
      . '&sort=' . urlencode($sortField)
      . '&order=' . urlencode($sortOrder));
    exit;
}

// Определяем, кого редактируем
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;

// Формируем запрос сотрудников своей компании
$sql = "
    SELECT
      s.id_С,
      CONCAT(s.Фамилия,' ',s.Имя,' ',s.Отчество) AS fio,
      s.ИНН, s.Email, s.СуммаЗП, s.id_Д, d.Название_Д
    FROM `сотрудники` s
    JOIN `должности` d ON s.id_Д = d.id_Д
    WHERE s.id_К = :myComp
";
$params = [':myComp' => $myCompany];

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

$allowedSort = ['fio'=>'fio','inn'=>'s.ИНН','email'=>'s.Email','zp'=>'s.СуммаЗП','position'=>'d.Название_Д'];
if (isset($allowedSort[$sortField])) {
    $dir = strtolower($sortOrder)==='desc'?'DESC':'ASC';
    $sql .= " ORDER BY {$allowedSort[$sortField]} $dir";
} else {
    $sql .= " ORDER BY s.Фамилия ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

include 'header.php';
?>
<style>
  .container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
  }

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

  .range-container {
    flex: 1 1 300px;
  }
  .range-labels {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .slider-track {
    position: relative;
    height: 4px;
    background: #ddd;
    border-radius: 2px;
    overflow: hidden;
  }
  .slider-track .range-fill {
    position: absolute;
    height: 100%;
    background: #007bff;
    border-radius: 2px;
    left: 0; right: 0;
  }
  .thumb {
    position: absolute;
    top: 50%;
    width: 16px; height: 16px;
    background: #007bff;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    cursor: pointer;
    z-index: 2;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }
  th, td {
    padding: 10px 8px;
    border: 1px solid #ddd;
    text-align: left;
  }


.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.action-buttons .button {
  padding: 6px 10px;
  min-width: 90px;
  text-align: center;
  font-size: 14px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  transition: background 0.2s ease;
}

.button.edit {
  background-color: #ffc107; /* жёлтая кнопка */
  color: #000;
}

.button.save {
  background-color: #28a745; /* зелёная кнопка */
  color: #fff;
}

.button.fire {
  background-color: #dc3545; /* красная кнопка */
  color: #fff;
}

.button:hover {
  opacity: 0.9;
}
  .form-inline {
    display: inline-block;
    margin: 0;
  }
</style>



<div class="container">
  <h2>Управление сотрудниками</h2>

  <form method="get" action="management.php" class="filter-form">
    <label>Фамилия
      <input type="text" name="surname" value="<?= htmlspecialchars($surnameFilter) ?>">
    </label>
    <label>Должность
      <select name="position">
        <option value="">Все</option>
        <?php foreach ($positions as $pid => $pname): ?>
          <option value="<?= $pid?>" <?= $pid==$positionFilter?'selected':'' ?>><?= htmlspecialchars($pname) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    
    <div class="range-container">
        <label>Зарплата</label>
        <div class="range-labels">
            <span id="minLabel"></span>
            <span id="maxLabel"></span>
        </div>
        <div class="slider-track" id="sliderTrack">
            <div class="range-fill" id="rangeFill"></div>
            <div class="thumb" id="thumbMin"></div>
            <div class="thumb" id="thumbMax"></div>
        </div>
        <input type="hidden" name="min_zp" id="minZpInput" value="<?= $minZpFilter ?>">
        <input type="hidden" name="max_zp" id="maxZpInput" value="<?= $maxZpFilter ?>">
    </div>
    <label>Сортировать
      <select name="sort">
        <option value="">По умолчанию</option>
        <option value="fio" <?= $sortField==='fio'?'selected':''?>>ФИО</option>
        <option value="inn" <?= $sortField==='inn'?'selected':''?>>ИНН</option>
        <option value="email" <?= $sortField==='email'?'selected':''?>>Email</option>
        <option value="zp" <?= $sortField==='zp'?'selected':''?>>ЗП</option>
        <option value="position" <?= $sortField==='position'?'selected':''?>>Должность</option>
      </select>
    </label>
    <label>Порядок
      <select name="order">
        <option value="asc" <?= $sortOrder==='asc'?'selected':''?>>По возрастанию</option>
        <option value="desc" <?= $sortOrder==='desc'?'selected':''?>>По убыванию</option>
      </select>
    </label>
    <button type="submit" class="button">Применить</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>ФИО</th>
        <th>ИНН</th>
        <th>Email</th>
        <th>ЗП</th>
        <th>Должность</th>
        <th style="width:180px;">Действия</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($list as $row): ?>
      <tr>
        <?php if ($editId === (int)$row['id_С']): ?>
          <form method="post">
          <td><input type="text" name="fio" value="<?= htmlspecialchars($row['fio']) ?>" style="width:100%"></td>
          <td><input type="text" name="inn" value="<?= htmlspecialchars($row['ИНН']) ?>"></td>
          <td><input type="email" name="email" value="<?= htmlspecialchars($row['Email']) ?>"></td>
          <td><input type="number" step="0.01" name="zp" value="<?= $row['СуммаЗП'] ?>"></td>
          <td>
            <select name="position">
              <?php foreach ($positions as $pid => $pname): ?>
              <option value="<?= $pid?>" <?= $pid===(int)$row['id_Д']?'selected':''?>><?= htmlspecialchars($pname) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <input type="hidden" name="id" value="<?= $row['id_С'] ?>">
            <button name="save" class="button">Сохранить</button>
          </td>
          </form>
        <?php else: ?>
          <td><?= htmlspecialchars($row['fio']) ?></td>
          <td><?= htmlspecialchars($row['ИНН']) ?></td>
          <td><?= htmlspecialchars($row['Email']) ?></td>
          <td><?= number_format($row['СуммаЗП'],2,'.',' ') ?></td>
          <td><?= htmlspecialchars($row['Название_Д']) ?></td>
            <td>
            <div class="action-buttons">
                <a href="?edit=<?= $row['id_С'] ?>&surname=<?= urlencode($surnameFilter) ?>&position=<?= urlencode($positionFilter) ?>&min_zp=<?= $minZpFilter ?>&max_zp=<?= $maxZpFilter ?>&sort=<?= $sortField ?>&order=<?= $sortOrder ?>"
                class="button edit">Редактировать</a>
                
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

<script>
(function(){
  const minVal = <?= $minZpAll ?>;
  const maxVal = <?= $maxZpAll ?>;
  let curMin = <?= $minZpFilter ?>;
  let curMax = <?= $maxZpFilter ?>;

  const track   = document.getElementById('sliderTrack');
  const fill    = document.getElementById('rangeFill');
  const thumbMin = document.getElementById('thumbMin');
  const thumbMax = document.getElementById('thumbMax');
  const minLabel = document.getElementById('minLabel');
  const maxLabel = document.getElementById('maxLabel');
  const minInput = document.getElementById('minZpInput');
  const maxInput = document.getElementById('maxZpInput');
  // Обновить позиции и лейблы
  function update() {
    const rect = track.getBoundingClientRect();
    const pctMin = (curMin - minVal) / (maxVal - minVal);
    const pctMax = (curMax - minVal) / (maxVal - minVal);
    const xMin = rect.left + pctMin * rect.width;
    const xMax = rect.left + pctMax * rect.width;
    thumbMin.style.left = (pctMin*100) + '%';
    thumbMax.style.left = (pctMax*100) + '%';
    fill.style.left  = (pctMin*100) + '%';
    fill.style.width = ((pctMax-pctMin)*100) + '%';
    minLabel.textContent = curMin.toFixed(2);
    maxLabel.textContent = curMax.toFixed(2);
    minInput.value = curMin.toFixed(2);
    maxInput.value = curMax.toFixed(2);
  }

  // Обработка перетаскивания
  function bindDrag(thumb, isMin) {
    thumb.addEventListener('mousedown', startDrag);
    function startDrag(e) {
      e.preventDefault();
      document.addEventListener('mousemove', onDrag);
      document.addEventListener('mouseup', stopDrag);
    }
    function onDrag(e) {
      const rect = track.getBoundingClientRect();
      let pct = (e.clientX - rect.left) / rect.width;
      pct = Math.max(0, Math.min(1, pct));
      const val = minVal + pct * (maxVal - minVal);
      if (isMin) {
        curMin = Math.min(val, curMax);
      } else {
        curMax = Math.max(val, curMin);
      }
      update();
    }
    function stopDrag() {
      document.removeEventListener('mousemove', onDrag);
      document.removeEventListener('mouseup', stopDrag);
    }
  }

  // Инициализация
  bindDrag(thumbMin, true);
  bindDrag(thumbMax, false);
  update();

})();
</script>