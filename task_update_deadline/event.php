<?php

require_once('crest.php');

$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';

    if($action === 'install'){
        $install = CRest::call(
    'event.bind',
    [
        'event' => 'onTaskAdd',
        'handler' => 'адрес до исполняемого файла', 
    ]
    );
    
    if(!empty($install['error'])){
        $message = 'Ошибка при подписке' . $install['error_description'];
    }
    else{
        $message = 'Подписка на событие создания задач прошла успешно!';
    }
    }elseif($action === 'uninstall'){
    $uninstall = CRest::call(
    'event.unbind',
    [
        'event' => 'onTaskAdd',
        'handler' => 'https://saltsite.saltpro.ru/rest_apps/edu/kbnv/task_update_deadline_ETS/taskupdate.php',
        //'auth_type' => 1
    ]
    );
    
    if(!empty($unistall['error'])){
        $message = 'Ошибка при отписке' . $uninstall['error_description'];
    }
    else{
        $message = 'Отписка от события создания задач прошла успешно!';
    }
}

}

?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Salt: подписка на событие создания задач</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        .green { color: green; }
        .red { color: red; }
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
        <td><b>Подписка на событие создания задач</b></td>
        <td>
            <form method="post">
                <input type="hidden" name="action" value="install">
                <button type="submit">Подписаться</button>
            </form>
                <form method="post">
                    <input type="hidden" name="action" value="uninstall">
                    <button type="submit">Отписаться</button>
                </form>
        </td>
    </tr>
</table>

</body>

</html>
