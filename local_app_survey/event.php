<?php

require_once('crest.php');

$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';

    if($action === 'install_item'){
        $install = CRest::call(
    'event.bind',
    [
        'event' => 'ONCRMDYNAMICITEMADD',
        'handler' => 'адрес до исполняемого файла', 
    ]
    );
    
    if(!empty($install['error'])){
        $message = 'Ошибка при подписке создания элемента смарт-процесса:' . ' ' .  $install['error_description'];
    }
    else{
        $message = 'Подписка на событие создания элемента смарт-процесса прошла успешно!';
    }
    }elseif($action === 'unistall_item'){
    $unistall = CRest::call(
    'event.unbind',
    [
        'event' => 'ONCRMDYNAMICITEMADD',
        'handler' => 'адрес до исполняемого файла'
    ]
    );
    
    if(!empty($unistall['error'])){
        $message = 'Ошибка при отписке от события создания элемента смарт-процесса:' . ' ' .  $unistall['error_description'];
    }
    else{
        $message = 'Отписка от события создания элемента смарт-процесса прошла успешно!';
    }
    }elseif($action === 'install_task'){
        $install = CRest::call(
    'event.bind',
    [
        'event' => 'ONTASKUPDATE',
        'handler' => 'адрес до исполняемого файла', 
    ]
    );
    
    if(!empty($install['error'])){
        $message = 'Ошибка при подписке на событие изменения задачи:' . ' ' .  $install['error_description'];
    }
    else{
        $message = 'Подписка на событие изменения задачи прошла успешно!';
    }
    }elseif($action === 'unistall_task'){
        $unistall = CRest::call(
    'event.unbind',
    [
        'event' => 'ONTASKUPDATE',
        'handler' => 'адрес до исполняемого файла'
    ]
    );
    
    if(!empty($unistall['error'])){
        $message = 'Ошибка при отписке от события изменения задачи:' . ' ' .  $unistall['error_description'];
    }
    else{
        $message = 'Отписка от события изменения задачи прошла успешно!';
    }
    }

}
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Salt: опрос инициаторов</title>
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
        <td><b>Подписка на событие создания элемента смарт-процесса</b></td>
        <td class="actions">
            <form method="post" style="display:inline-block; margin-right:10px;">
                <input type="hidden" name="action" value="install_item">
                <button type="submit">Подписаться</button>
            </form>
                <form method="post">
                    <input type="hidden" name="action" value="unistall_item">
                    <button type="submit">Отписаться</button>
                </form>
        </td>
    </tr>

    <tr>
        <td><b>Подписка на событие изменения задач</b></td>
        <td class="actions">
            <form method="post" style="display:inline-block; margin-right:10px;">
                <input type="hidden" name="action" value="install_task">
                <button type="submit">Подписаться</button>
            </form>
            <form method="post" style="display:inline-block;">
                <input type="hidden" name="action" value="unistall_task">
                <button type="submit">Отписаться</button>
            </form>
        </td>
    </tr>
</table>

</body>

</html>
