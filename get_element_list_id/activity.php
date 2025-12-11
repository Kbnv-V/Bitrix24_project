<?php

require_once('crest.php');

$message = '';
$installed = false;

// Проверяем текущий статус действия
$getActivity = CRest::call('bizproc.activity.get', ['code' => 'get_element_list_id']);
$installed = !empty($getActivity['result']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        // Устанавливаем пользовательское действие
        $install = CRest::call(
            'bizproc.activity.add',
            [
                'CODE' => 'get_element_list_id',
                'HANDLER' => 'https://saltsite.saltpro.ru/rest_apps/edu/kbnv/get_element_list_id/get_element.php', 
                'AUTH_USER_ID' => 1,
                'USE_SUBSCRIPTION' => '',
                'NAME' => ['ru' => 'Поиск элемента списка'],
                'DESCRIPTION' => ['ru' => 'Действие возвращает ID найденного элемента списка по заданным параметрам'],
                'PROPERTIES' => [
                    'document_name' => [
                        'Name' => ['ru' => 'Название элемента'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'Y',
                        'Multiple' => 'N',
                        'Default' => '',
                    ],
                    'list_ID' => [
                        'Name' => ['ru' => 'Идетификатор списка'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'Y',
                        'Multiple' => 'N',
                        'Default' => '',
                    ]
                ],
                'RETURN_PROPERTIES' => [
                    'element_id' => [
                        'Name' => ['ru' => 'ID элемента'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'N',
                        'Multiple' => 'Y',
                        'Default' => '',
                    ],
                ],
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
            ['code' => 'get_element_list_id']
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
    <title>Salt: поиск элемента списка</title>
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
        <td><b>Установка действия для поиска элемента списка</b></td>
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