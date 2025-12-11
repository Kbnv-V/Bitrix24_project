<?php

require_once('crest.php');

$message = '';
$installed = false;

// Проверяем текущий статус действия
$getActivity = CRest::call('bizproc.activity.get', ['code' => 'item_update']);
$installed = !empty($getActivity['result']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        // Устанавливаем пользовательское действие
        $install = CRest::call(
            'bizproc.activity.add',
            [
                'CODE' => 'item_update',
                'HANDLER' => 'https://saltsite.saltpro.ru/rest_apps/edu/kbnv/item_update_ETS/item_update.php', 
                'AUTH_USER_ID' => 1700,
                'USE_SUBSCRIPTION' => '',
                'NAME' => ['ru' => 'Обновление информации в элементе смарт-процесса'],
                'DESCRIPTION' => ['ru' => 'Действие возвращает ID товарной категории и ID ведущего менеджера'],
                'PROPERTIES' => [
                    'product_id' => [
                        'Name' => ['ru' => 'ID товара'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'Y',
                        'Multiple' => 'N',
                        'Default' => '',
                    ],
                ],
                'RETURN_PROPERTIES' => [
                    'product_category_id' => [
                        'Name' => ['ru' => 'ID товарной категории'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'N',
                        'Multiple' => 'N',
                        'Default' => '',
                    ],
                    'lead_manager_id' => [
                        'Name' => ['ru' => 'ID ведущего менеджера'],
                        'Description' => ['ru' => ''],
                        'Type' => 'string',
                        'Required' => 'N',
                        'Multiple' => 'N',
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
            ['code' => 'item_update']
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
    <title>Salt: обновление элемента смарт-процесса</title>
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
        <td><b>Установка действия для для обновления информации в элементе смарт-процесса</b></td>
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