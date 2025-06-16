<?php
session_start();
require_once 'db.php';
function userErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
{
    // отметка времени
    $dt = date("Y-m-d H:i:s");
    // читаемые типы ошибок
    $errorTypes = [
        E_ERROR             => 'Ошибка',
        E_WARNING           => 'Предупреждение',
        E_PARSE             => 'Ошибка разбора',
        E_NOTICE            => 'Уведомление',
        E_CORE_ERROR        => 'Ядро: ошибка',
        E_CORE_WARNING      => 'Ядро: предупреждение',
        E_COMPILE_ERROR     => 'Компиляция: ошибка',
        E_COMPILE_WARNING   => 'Компиляция: предупреждение',
        E_USER_ERROR        => 'Пользовательская ошибка',
        E_USER_WARNING      => 'Пользовательское предупреждение',
        E_USER_NOTICE       => 'Пользовательское уведомление',
        E_STRICT            => 'Совместимость',
        E_RECOVERABLE_ERROR => 'Восстановимая фатальная ошибка',
    ];
    $type = isset($errorTypes[$errno]) ? $errorTypes[$errno] : "Неизвестный тип ($errno)";

    // выводим в лог (здесь – просто в файл)
    $log  = "[$dt] [$type] $errstr в $errfile:$errline\n";
    $log .= "Context: " . print_r($errcontext, true) . "\n\n";
    error_log($log, 3, __DIR__ . '/errors.log');

    /* Не останавливаем выполнение, если это НЕ фатальная ошибка */
    return !($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR));
}

// ставим наш обработчик
set_error_handler('userErrorHandler');

// на случай «не ловимых» фатальных ошибок
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR|E_CORE_ERROR|E_COMPILE_ERROR|E_USER_ERROR))) {
        userErrorHandler($err['type'], $err['message'], $err['file'], $err['line'], []);
    }
});

// Настройка PDO на выброс исключений
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Справочники
$domains = ['gmail.com','yahoo.com','outlook.com','mail.ru','example.com'];
$posStmt = $pdo->query("SELECT `id_Д`, `Название_Д` FROM `должности` ORDER BY `Название_Д`");
$positions = $posStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$errors = [];

// Генерация капчи
function generateCaptcha() {
    global $captcha;
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $captcha = [];
    for ($i = 0; $i < 5; $i++) {
        $captcha[] = $letters[random_int(0, 25)];
    }
    $_SESSION['captcha_letters'] = $captcha;
    $_SESSION['captcha_answer']  = implode('', array_reverse($captcha));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    generateCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expected    = $_SESSION['captcha_answer'] ?? '';

    $invite_code = trim($_POST['invite_code']     ?? '');
    $posId       = intval($_POST['position']      ?? 0);
    $surname     = trim($_POST['surname']         ?? '');
    $name        = trim($_POST['name']            ?? '');
    $patronym    = trim($_POST['patronym']        ?? '');
    $login       = trim($_POST['login']           ?? '');
    $email_dom   = trim($_POST['email_dom']       ?? '');
    $custom_dom  = trim($_POST['custom_dom']      ?? '');
    $password    = $_POST['password']             ?? '';
    $password2   = $_POST['password_confirm']     ?? '';
    $captcha_in  = trim($_POST['captcha']         ?? '');

    if (!isset($positions[$posId])) {
        $errors[] = 'Выберите должность.';
    }

    if ($surname === '') $errors[] = 'Укажите фамилию.';
    if ($name === '') $errors[] = 'Укажите имя.';

    if ($login === '') {
        $errors[] = 'Логин не может быть пустым.';
    } elseif (!preg_match('/^[A-Za-z0-9]+$/', $login)) {
        $errors[] = 'Логин может содержать только латинские буквы и цифры.';
    } else {
        $u = $pdo->prepare("SELECT COUNT(*) FROM `сотрудники` WHERE `Логин` = :login");
        $u->execute([':login' => $login]);
        if ($u->fetchColumn() > 0) {
            $errors[] = "Логин «" . htmlspecialchars($login) . "» уже занят.";
        }
    }

    if ($email_dom === 'custom') {
        if (!preg_match('/^[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$/', $custom_dom)) {
            $errors[] = 'Неверный собственный домен.';
        }
        $email_domain = $custom_dom;
    } else {
        $email_domain = $email_dom;
    }
    $email = $login . '@' . $email_domain;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email некорректен.';
    }
        // Проверка минимальной длины пароля (8 символов)
    if (strlen($password) < 8) {
        $errors[] = 'Пароль должен содержать минимум 8 символов.';
    }
    if (!preg_match('/^[A-Za-z0-9\\.\\,\\=\\+\\_\\-\\&\\$]+$/', $password)) {
        $errors[] = 'Пароль: латиница, цифры и . , = + _ - & $.';
    }
    if ($password !== $password2) {
        $errors[] = 'Пароли не совпадают.';
    }

    if (strtoupper($captcha_in) !== $expected) {
        $errors[] = 'Капча введена неверно.';
    }

    // Получить id должности "Директор"
    $dirStmt = $pdo->prepare("SELECT id_Д FROM должности WHERE Название_Д = 'Директор'");
    $dirStmt->execute();
    $directorId = $dirStmt->fetchColumn();

    // Проверка конфликтной ситуации
    if ($posId == $directorId && $invite_code !== '') {
        $errors[] = 'Если вы директор, уберите код приглашения. Будет создана новая компания.';
    }

    if (empty($errors)) {
        try {
            if ($posId == $directorId && $invite_code === '') {
                // Создание новой компании
                $newCode = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);

                $newdrId = $pdo->query("SELECT IFNULL(MAX(id_ДР), 0) + 1 AS next FROM доходырасходы")->fetch()['next'];
                $stmt = $pdo->prepare("INSERT INTO доходырасходы (id_ДР, id_Н, СуммаД, СуммаР)
                                       VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $newdrId,
                    1,
                    0,
                    0
                ]);

                $newCompanyId = $pdo->query("SELECT IFNULL(MAX(id_К), 0) + 1 AS next FROM компании")->fetch()['next'];
                $stmt = $pdo->prepare("INSERT INTO компании (id_К, Название_К, ОГРН, ИНН, Адрес, Код_приглашения, id_Н, id_ДР)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $newCompanyId,
                    'Международная Корпорация',
                    str_pad(random_int(0, 9999999999999), 13, '0', STR_PAD_LEFT),
                    str_pad(random_int(0, 999999999999), 12, '0', STR_PAD_LEFT),
                    'г. Пример, ул. Образцовая, д. 1',
                    $newCode,
                    1,
                    $newdrId
                ]);

                $companyId = $newCompanyId;
            } else {
                $c = $pdo->prepare("SELECT `id_К` FROM `компании` WHERE `Код_приглашения` = ?");
                $c->execute([$invite_code]);
                $companyId = $c->fetchColumn();
                if (!$companyId) {
                    $errors[] = 'Неверный код приглашения.';
                    generateCaptcha();
                    return;
                }
            }

            $newId = $pdo->query("SELECT MAX(`id_С`) AS m FROM `сотрудники`")->fetch()['m'] + 1;
            $inn   = str_pad(random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);

            $pdo->prepare("INSERT INTO `сотрудники` (`id_С`,`id_К`,`id_Д`,`Фамилия`,`Имя`,`Отчество`,`ИНН`, `Email`,`Логин`,`Пароль`,`СуммаЗП`) VALUES (:id,:comp,:pos,:sur,:nm,:pat,:inn,:em,:lg,:pw,0)")->execute([
                ':id'   => $newId,
                ':comp' => $companyId,
                ':pos'  => $posId,
                ':sur'  => $surname,
                ':nm'   => $name,
                ':pat'  => $patronym,
                ':inn'  => $inn,
                ':em'   => $email,
                ':lg'   => $login,
                ':pw'   => $password,
            ]);

            unset($_SESSION['captcha_letters'], $_SESSION['captcha_answer']);
            header('Location: login.php?registered=1');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Ошибка базы: ' . $e->getMessage();
        }
        generateCaptcha();
    }
}

include 'header.php';
?>


<div class="container" style="max-width:500px; margin:20px auto;">
  <h2>Регистрация</h2>

  <?php if ($errors): ?>
    <ul style="color:red; margin-bottom:15px;">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post">
    <div class="form-group">
      <label>Код приглашения</label>
      <input type="text" name="invite_code" value="<?= htmlspecialchars($invite_code ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Должность</label>
      <select name="position" required>
        <option value="">— Выберите должность —</option>
        <?php foreach ($positions as $id => $title): ?>
          <option value="<?= $id?>" <?= (isset($posId) && $posId==$id)?'selected':''?>>
            <?= htmlspecialchars($title) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Фамилия</label>
      <input type="text" name="surname" value="<?= htmlspecialchars($surname ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Имя</label>
      <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Отчество</label>
      <input type="text" name="patronym" value="<?= htmlspecialchars($patronym ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Логин</label>
      <input type="text" name="login" id="loginField"
             value="<?= htmlspecialchars($login ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label>Email</label>
      <div style="display:flex; gap:5px; align-items:center;">
        <input type="text" name="email_local" id="emailLocal" readonly>
        <span>@</span>
        <select name="email_dom" id="emailDom">
          <?php foreach ($domains as $dom): ?>
            <option value="<?= $dom?>" <?= (isset($email_dom)&&$email_dom==$dom)?'selected':''?>>
              <?= htmlspecialchars($dom) ?>
            </option>
          <?php endforeach; ?>
          <option value="custom" <?= ($email_dom==='custom')?'selected':'' ?>>Другое</option>
        </select>
      </div>
      <input type="text" name="custom_dom" id="customDom" placeholder="ваш.домен"
             style="display:none; margin-top:5px; width:100%;">
    </div>

    <div class="form-group">
      <label>Пароль</label>
      <input type="password" name="password" required>
    </div>
    <div class="form-group">
      <label>Повтор пароля</label>
      <input type="password" name="password_confirm" required>
    </div>

    <div class="form-group">
      <label>Введите код в обратном порядке</label>
      <div class="captcha" style="margin-bottom:5px;">
        <?php foreach ($_SESSION['captcha_letters'] as $ltr):
            $top  = random_int(-3, 3);
            $left = random_int(-2, 2);
            $rot  = random_int(-15, 15);
        ?>
          <span style="
            position: relative;
            top: <?= $top ?>px;
            left: <?= $left ?>px;
            transform: rotate(<?= $rot ?>deg);
            font-size: 20px;
            font-weight: bold;
            user-select: none;
          "><?= htmlspecialchars($ltr) ?></span>
        <?php endforeach; ?>
      </div>
      <input type="text" name="captcha" required placeholder="Код">
    </div>

    <button type="submit" class="button" style="margin-top:10px;">Зарегистрироваться</button>
  </form>
</div>

<script>
const loginField = document.getElementById('loginField');
const emailLocal = document.getElementById('emailLocal');
loginField.addEventListener('input', ()=> emailLocal.value = loginField.value);
emailLocal.value = loginField.value;

document.getElementById('emailDom').addEventListener('change', function(){
    document.getElementById('customDom').style.display =
      this.value==='custom' ? 'block' : 'none';
});
</script>
