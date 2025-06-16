<?php
// temp.php — Работа с временной таблицей
require_once 'db.php';

// Создаем таблицу в памяти, если не существует
$pdo->exec("
    CREATE TABLE IF NOT EXISTS memory(
        id INT AUTO_INCREMENT PRIMARY KEY,
        info VARCHAR(255)
    ) ENGINE=MEMORY
");

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        foreach ($_POST['infos'] as $info) {
            $info = trim($info);
            if ($info !== '') {
                $pdo->prepare("INSERT INTO memory(info) VALUES(?)")
                    ->execute([$info]);
            }
        }
    } elseif (isset($_POST['update'])) {
        $pdo->prepare("UPDATE memory SET info = ? WHERE id = ?")
            ->execute([$_POST['info'], $_POST['id']]);
    } elseif (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM memory WHERE id = ?")
            ->execute([$_POST['id']]);
    }
}

// Получаем все записи
$all = $pdo->query("SELECT * FROM memory ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Временная таблица — Система Подписок</title>
    <style>
        body {
            margin: 0;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        h2 {
            margin-top: 30px;
            font-weight: normal;
        }
        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #007BFF;
            text-decoration: none;
        }
        .back:hover {
            text-decoration: underline;
        }

        form.add-multiple {
            margin-bottom: 30px;
        }
        #new-fields input {
            display: block;
            width: 100%;
            max-width: 400px;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        form.add-multiple button {
            margin-right: 10px;
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            background-color: #111;
            color: #fff;
            cursor: pointer;
            transition: background-color .2s;
        }
        form.add-multiple button:hover {
            background-color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            text-align: left;
            font-size: 14px;
        }
        thead {
            background-color: #f4f4f4;
        }
        form.actions input[type="text"] {
            width: 100%;
            max-width: 300px;
            padding: 6px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        form.actions button {
            margin-right: 5px;
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 4px;
            background-color: #111;
            color: #fff;
            cursor: pointer;
            transition: background-color .2s;
        }
        form.actions button:hover {
            background-color: #333;
        }
    </style>
</head>
<body>

    <!-- Шапка -->
	<?php include 'header.php'; ?>

    <div class="container">
        <h2>Работа с временной таблицей</h2>

        <form method="post" class="add-multiple">
            <div id="new-fields">
                <input type="text" name="infos[]" placeholder="Новая запись">
            </div>
            <button type="button" onclick="addField()">+ Добавить ещё</button>
            <button name="add">Сохранить все</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Info</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all)): ?>
                <tr><td colspan="3">Записей нет.</td></tr>
                <?php else: ?>
                    <?php foreach ($all as $row): ?>
                    <tr>
                        <form method="post" class="actions">
                            <td><?= $row['id'] ?></td>
                            <td>
                                <input type="text" name="info" value="<?= htmlspecialchars($row['info']) ?>">
                            </td>
                            <td>
                                <button name="update">Обновить</button>
                                <button name="delete">Удалить</button>
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function addField() {
            var container = document.getElementById('new-fields');
            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'infos[]';
            input.placeholder = 'Новая запись';
            container.appendChild(input);
        }
    </script>
</body>
</html>
