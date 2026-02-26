<?php

require_once('crest.php');

//действие удаления после нажатия кнопки
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    $field_ID = $_POST['action'];

    $delete_field = Crest::call('user.userfield.delete', ['id' => $field_ID]);

    if($delete_field['result']){
        echo "Удаление прошло успешно!";
    }/*else{
        echo "При удалении произошла ошибка! Обратитель к администратору.";
        echo "<pre>";
        print_r($delete_field['error_description']);
        echo "</pre>";
    
    }*/
}

$params = [
    'order' => ['id' => 'desc',]
];

//получаем список пользовательский полей сотрудников
$get_Fields_ID = CRest::call('user.userfield.list', [$params]);

//получаем список пользовательский полей сотрудников с читаемым названием
$get_Name_Fields = CRest::call('user.fields', []);

/*
echo"<pre>";
print_r($get_Name_Fields);
echo"</pre>";
*/

$userField_List = [];

//формируем массив со всеми пользовательскими полями, где будут отражены название, код, id полей
foreach($get_Fields_ID['result'] as $field){
    $field_name = $get_Name_Fields['result'][$field['FIELD_NAME']];
    $userField_List[] = ['NAME' => $field_name, 'FIELD_CODE' => $field['FIELD_NAME'], 'FIELD_ID' => $field['ID']];

}

?>

<!DOCTYPE html>
<html lang="ru">
<meta charset="utf-8">
    <head>
        <title>Удаление полей</title>
        <link rel="stylesheet" href="styles/styles.css">
    </head>

    <body>

        <h2>Список пользовательских полей</h2>

    </body>
</html>

    <table>
        <tr>
            <th>Название поля</th>
            <th>Действие</th>
        </tr>

    <?php 

    foreach($userField_List as $value){?>
        <tr>
            <td><?= $value['NAME'] ?></td>
            <td>
                <form method="POST" style="margin:0;">
                    <button type = "submit" name = "action" value = "<?= $value['FIELD_ID'] ?>">
                        Удалить
                    </button>
                </form>
            </td>
        </tr>
        
    <?php } ?> 

    </table>

</html>