<?php

require_once('crest.php');

$message = '';
$installed = false;

// Проверяем текущий статус действия
$getActivity = CRest::call('bizproc.activity.get', ['code' => 'stop_bizpoc']);
$installed = !empty($getActivity['result']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        // Устанавливаем пользовательское действие
        $install = CRest::call(
            'bizproc.activity.add',
            [
                'CODE' => 'stop_bizpoc',
                'HANDLER' => 'https://saltsite.saltpro.ru/rest_apps/edu/kbnv/activity_stop_biz/stop_biz.php', 
                'AUTH_USER_ID' => 1,
                'USE_SUBSCRIPTION' => '',
                'NAME' => ['ru' => 'Остановка бизнес-процесса'],
                'DESCRIPTION' => ['ru' => 'Действие действие останавливает запущенный по элементу бизнес-процесс'],
                'PROPERTIES' => [
                    'document_ID' => [
                        'Name' => ['ru' => 'ID документа'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'N',
                        'Multiple' => 'N',
                        'Default' => '',
                    ],
                    'list_ID' => [
                        'Name' => ['ru' => 'Идетификатор списка'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'N',
                        'Multiple' => 'N',
                        'Default' => '',
                    ]
                ],
                'RETURN_PROPERTIES' => [],
            ]
        );

        if (!empty($install['error'])) {
            $message = 'Ошибка установки: ' . $install['error_description'];
        } else {
            $message = 'Действие успешно установлено!';
            $installed = true;
        }

    } elseif ($action === 'uninstall') {
        // Удаляем пользовательское действие
        $delete = CRest::call(
            'bizproc.activity.delete',
            ['code' => 'stop_bizpoc']
        );

        if (!empty($delete['error'])) {
            $message = 'Ошибка удаления: ' . $delete['error_description'];
        } else {
            $message = 'Действие успешно удалено!';
            $installed = false;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Salt: остановка бизнес-процессов</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .green { color: green; }
        .red { color: red; }
        .actions {
        display: flex;
        gap: 20px;   /* ← расстояние между кнопками */
    }
    .actions button {
        padding: 6px 14px;
        cursor: pointer;
    }
    </style>
</head>
<body>

<?php if (!empty($message)): ?>
    <div style="margin-bottom: 20px;"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>Название действия</th>
        <th>Действия</th>
    </tr>
    <tr>
        <td><b>Установка действия для остановки бизнес-процесса в редактор бизнес-процесса</b></td>
        <td class="actions">
            <form method="post" style="display:inline-block; margin-right:10px;">
                <input type="hidden" name="action" value="install">
                <button type="submit">Установить</button>
            </form>
                <form method="post">
                    <input type="hidden" name="action" value="uninstall">
                    <button type="submit">Удалить</button>
                </form>
        </td>
    </tr>
</table>

</body>
</html>