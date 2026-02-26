<?php

include_once(__DIR__.'/crest.php');
include_once(__DIR__.'/button_code.php');

session_start(); //функция для фиксации сессии, чтобы не слетал $full_deal_list при запуске рассылки

$url = 'https://b24-xc8a4k.bitrix24.eu/rest/1/or8ab5hvqhudmzdj/';

function request($method, $url, $param = null){

    $datatask = http_build_query($param);

    $url = $url . $method . "/";
    $ch = curl_init(); //инициализируем новую сессию
    $options = [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS => $datatask,
    ];

    curl_setopt_array($ch, $options);

    $res = curl_exec($ch);

    curl_close($ch);

    return $res;

}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $key_word = $_POST['key_word'];
    $mail_topic = $_POST['mail_topic'];
    $mail_body = $_POST['mail_body'] ?? '';
    $mail_body_2 = $_POST['mail_body_2'] ?? '';
    $action = $_POST['action'];

    //если надали кнопку "Сформировать список"
    if($action === 'get_deal') {

        $start = 0;
        $full_deal_list = [];
        $mail_full_list = [];

        $params = [
        'SELECT' => ['TITLE', 'ID', 'CONTACT_ID', 'DATE_CREATE', 'UF_CRM_1768914295233'],
        'FILTER' => ['%UF_CRM_1768914295233' => $key_word, '>=DATE_CREATE' => $start_date, '<=DATE_CREATE' => $end_date],
        'ORDER' => ['TITLE' => 'ASC', 'OPPORTUNITY' => 'ASC',],
        'start' => $start,
        ];
        
        $result = Crest::call('crm.deal.list', $params);
        $full_deal_list = array_merge($result['result'], $full_deal_list);
        $start = $result['next'] ?? NULL;
        
        while(!empty($start)){
        
            $params['start'] = $start;
            $result = Crest::call('crm.deal.list', $params);
            $full_deal_list = array_merge($result['result'], $full_deal_list);
            $start = $result['next'] ?? NULL;
        }

        foreach($full_deal_list as $key => &$value){

            $value['CONTACT_EMAIL'] = ''; // для добавления полученных email в массив найденных сделок
            $get_contact = Crest::call('crm.contact.get', ['ID' => $value['CONTACT_ID']]); // получение почты по каждому контакту
            
            if($get_contact['result']['UF_CRM_1770364878874']){
                unset($full_deal_list[$key]);
                continue;
            }

            if(!empty($get_contact['result']['EMAIL'])){
                foreach($get_contact['result']['EMAIL'] as $mail_list){
            
                    if($mail_list['VALUE_TYPE'] == 'HOME'){
                        $value['CONTACT_EMAIL'] = $mail_list['VALUE'];
                        $mail_full_list[] = $mail_list['VALUE'];
                        break;
                   }
               }
           }
        }

    unset($value); //обнуляем переменную
    $_SESSION['full_deal_list'] = $full_deal_list;
    $_SESSION['mail'] = $mail_body; //сохраняем массив для рассылки
    $_SESSION['mail_list'] = $mail_full_list;
    }

    /*
    echo "<pre>";
    print_r($full_deal_list);
    echo "</pre>";
    
    echo "Параметры поиска: \n";
    echo "Начальная дата - $start_date \n Конечная дата - $end_date \n Ключевое слово - $key_word \n ";
    echo '<hr><h3>Предпросмотр письма:</h3>';
    echo "Тема: $mail_topic";
    echo "Текст:\n $mail_body";
    */

    //если нажали на кнопку "Зупустить рассылку"
    if($action === 'run_mailing') {
        
        
        foreach($_SESSION['full_deal_list'] as $value){
            
            //тут подставляем полученный email в HTML код кнопки для рассылки
            $email = $value['CONTACT_EMAIL'] ?? '';
            $personalButton = str_replace('{EMAIL}', $email, $button_code);

            //тут формируется конечное письмо. Тело письма из формы + код кнопки с актуальной ссылкой
            $personalBody = $mail_body . $personalButton;

            $fields = [
                "SUBJECT" => $mail_topic, //тема письма
                "DESCRIPTION" => $personalBody, //тело письма
                "DESCRIPTION_TYPE" => 3,
                "COMPLETED" => "Y",
                "DIRECTION" => 2,
                "OWNER_ID" => $value['CONTACT_ID'], //id контакта
                "OWNER_TYPE_ID" => 3, //тип объекта
                "TYPE_ID" => 4, //тип активности
                "COMMUNICATIONS" => [
                    [
                        'VALUE' => $value['CONTACT_EMAIL'],
                        'ENTITY_ID' => $value['CONTACT_ID'],
                        'ENTITY_TYPE_ID' => 3,
                    ]
                ],
                "START_TIME" => date("Y-m-d H:i:s", time()),
                "END_TIME" => date("Y-m-d H:i:s", time() + 3600),
                "RESPONSIBLE_ID" => 1,
                'SETTINGS' => [
                    'MESSAGE_FROM' => implode(
                        ' ',
                        ['Printpoint Photo LTD Ireland', '<' . 'info@printpoint.ie' . '>']
                    ),
                ],
            ];

            //отправка писем каждому контакту
            $send_message = request('crm.activity.add', $url, ['fields' => $fields]);
              
        }
        
        /*
        $date = date('Y-m-d H:i:s');

        $element_code = uniqid('mailing_log_', true); //генерируе код элемента для вставки в параметр

        //создание элемента списка как лога со всеми полученными данными и запуск по нему БП для отправки сообщений
        $params_list_log = [
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => 29,
                //'IBLOCK_CODE' => 29,
                'ELEMENT_CODE' => $element_code,
                'FIELDS' => [
                    'NAME' => "Mailing list - $date;",
                    'PROPERTY_105' => $_SESSION['mail_list'],
                    'PROPERTY_107' => $mail_topic,
                    'PROPERTY_109' => $mail_body,
                    'DETAIL_TEXT' => $mail_body,
                ]
            ];

        $get_element_list = CRest::call('lists.element.add', $params_list_log); //создание элемента списка

        $element_id = $get_element_list['result'];

        $params_workflows = [
            'TEMPLATE_ID' => 275,
            'DOCUMENT_ID' => [
                'lists',
                'Bitrix\\Lists\\BizprocDocumentLists',
                (string)$element_id,
            ],
        ];

        $run_workflow = Crest::call('bizproc.workflow.start', $params_workflows); //запуск БП по элементу списка
        */
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<meta charset="utf-8">
    <head>
        <title>Интерфейс_тест</title>
        <link rel="stylesheet" href="styles/new_styles.css">
    </head>

    <body>

        <h2>Параметры рассылки:</h2>

        <form method = "POST">

            <div class = "date">
                <p>Начальная дата: <input id = "start_date" name = "start_date" type = "date" value="<?= htmlspecialchars($start_date) ?>"/></p>
                <p>Конечная дата: <input id = "end_date" name = "end_date" type = "date" value="<?= htmlspecialchars($end_date) ?>"/></p>
            </div>

            <div class = "txt">
                <p>Ключевое слово: <input id = "key_word" name = "key_word" type = "text" value="<?= htmlspecialchars($key_word) ?>"/></p>
            </div>

            <div class = "mail">
                <p>Тема письма: <input id = "mail_topic" name = "mail_topic" type = "text" size="70" value="<?= htmlspecialchars($mail_topic) ?>"/></p>
                <p>Отображение формы/текст письма:</p>
                <textarea id="mail_body" name="mail_body"><?= htmlspecialchars($mail_body) ?></textarea>
                <br><br>
            </div>

            <div class = "button">
                <p>
                    <button type = "submit" name = "action" value = "get_deal">
                        Сформировать список
                    </button>
                </p>

                <p>
                    <button type = "submit" name = "action" value = "run_mailing">
                        Запустить рассылку
                    </button>
                </p>

                <!--
                <p>

                    <a class="btn-link" href="https://b24-xc8a4k.bitrix24.eu/company/lists/29/bp_edit/275/" target="_blank" rel="noopener noreferrer" title="Настроить HTML-форму">
                        Настроить HTML-форму
                    </a>
                </p>
                -->
                
            </div>

        </form>
        <script src="js/tinymce/tinymce.min.js"></script>
        <script>
        tinymce.init({
            selector: '#mail_body',
            license_key: 'gpl',
            plugins: 'lists link image code',
            toolbar: 'undo redo | fontfamily fontsize | bold italic underline | bullist numlist | link image | code', //тут редактируется меню поля для письма
            height: 400,
            menubar: false
            });
        </script>
        <script>
        document.querySelector('form').addEventListener('submit', function () {
            if (window.tinymce) tinymce.triggerSave();});
        </script>


    <?php if($action === 'get_deal'){ ?>
    
    <p>Список сделок для рассылки:</p>

    <table>
        <tr>
            <th>№</th>
            <th>Наименование сделки</th>
            <th>ID сделки</th>
            <th>Email</th>
            <th>Дата создания</th>
            <th>Товары</th> 
        </tr>

    <?php 
    
    $number = 0;

    foreach($full_deal_list as $value){ ?>
        <tr>
            <td><?= $number += 1; ?></td>
            <td><?= $value['TITLE']; ?></td>
            <td><?= $value['ID']; ?></td>
            <td><?= $value['CONTACT_EMAIL']; ?></td>
            <td><?= $date = date('Y-m-d', strtotime($value['DATE_CREATE'])); ?></td>
            <td><?= implode(", ", $value['UF_CRM_1768914295233']); ?></td>
        </tr>
        
    <?php

    }
    }

    ?> 

    </table>

    <p>
        <?php 
        
        if($action === 'run mailing'){
            echo"Рассылка запущена!";
            echo"<pre>";
            print_r($send_message);
            echo"<pre>";
        }
        
        ?>
    </p>

    </body>

</html>


    