<?php
include_once(__DIR__.'/crest.php');

// Если есть, то заполняется массив фильтров
$leadFilter = [];
if(isset($_GET['BtnFilter']))
{
    if(!empty($_GET['direction']))
    {
        $leadFilter["=UF_CRM_1669707455"] = $_GET['direction']; 
    }
    if(!empty($_GET['employee']))
    {
        $leadFilter["=ASSIGNED_BY_ID"] = $_GET['employee']; 
    }
    if(!empty($_GET['start_date']))
    {
       $leadFilter[">=DATE_CREATE"] = $_GET['start_date']; 
    }
    if(!empty($_GET['end_date']))
    {
        $leadFilter["<=DATE_CREATE"] = $_GET['end_date']; 
    }
}

// Функция которая вернет все лиды (если есть фильтры, то с учётом фильтров)
function call_all_lead ($leadFilter)
{
    $bacthGetLeads = [
        'method' => 'crm.lead.list',
        'params' => [
            'order' => [ "STATUS_ID" => "ASC" ],
            'filter' => $leadFilter,
            'select' => [ "ID","STATUS_ID", "TITLE", "OPPORTUNITY", "CURRENCY_ID","DATE_CREATE","UF_CRM_1669707376","UF_CRM_1669707455", "ASSIGNED_BY_ID"],
        ]
    ];
    $totalGetLeads = CRest::call('crm.lead.list',[
        'order' => [ "STATUS_ID" => "ASC" ],
        'filter' => $leadFilter,
        'select' => [ "ID" ],
    ],)["total"];
    
    $listGetLeads = ceil($totalGetLeads / 50);
    $resultGetLeads = [];
    // Мой говно код
    // $COUNTER = 0;
    // for ($i = 0; $i < $listGetLeads; $i++) {
    //     $current_list = CRest::call('crm.lead.list',[
    //         'order' => [ "STATUS_ID" => "ASC" ],
    //         'filter' => $leadFilter,
    //         'select' => [ "ID","STATUS_ID", "TITLE", "OPPORTUNITY", "CURRENCY_ID","DATE_CREATE","UF_CRM_1669707376","UF_CRM_1669707455", "ASSIGNED_BY_ID"],
    //         'start' => $i*50
    //     ]);
    //     $resultGetLeads = array_merge($resultGetLeads, $current_list['result']);
    //     $COUNTER++;
    //     sleep(2);
    // }
    
    // return $resultGetLeads;
    
    
    // Конец моего говнокода
    
    

    $arrBatchGetLeads = [];
    for ($i = 0; $i < $listGetLeads; $i++) {
        $batchParams = $bacthGetLeads;
        $batchParams['params']['start'] = $i * 50;
        $arrBatchGetLeads[(int)($i / 49)]["list_" . $i] = $batchParams;
    }
    $resultGetLeads = [];
    foreach ($arrBatchGetLeads as $key => $cmd_arr) {
        sleep(2); //Щадяший режим лучше ставить 2 секунды
        $batchResult = CRest::callBatch($cmd_arr, false)['result']['result'];
        foreach ($batchResult as $elementGetLeads) {
            $resultGetLeads = array_merge($resultGetLeads, $elementGetLeads);
        }
    } 
    return $resultGetLeads;
}
// Функция для получения всех пользователей
function call_all_users ()
{
    $bacthGetUsers = [
        'method' => 'user.get',
    ];
    $totalGetUsers = CRest::call('user.get')["total"];
    $listGetUsers = ceil($totalGetUsers / 50);
    $arrBatchGetUsers = [];
    for ($i = 0; $i < $listGetUsers; $i++) {
        $batchParams = $bacthGetUsers;
        $batchParams['params']['start'] = $i * 50;
        $arrBatchGetUsers[(int)($i / 49)]["list_" . $i] = $batchParams;
    }
    $resultGetUsers = [];
    foreach ($arrBatchGetUsers as $key => $cmd_arr) {
        sleep(2); //Щадяший режим лучше ставить 2 секунды
        $batchResult = CRest::callBatch($cmd_arr, false)['result']['result'];
        foreach ($batchResult as $elementGetUsers) {
            $resultGetUsers = array_merge($resultGetUsers, $elementGetUsers);
        }
    }
    return $resultGetUsers;
}

// Метод для получения описания всех полей Лида
$LeadFields = CRest::call(
    "crm.lead.fields",
);

// Метод для получения названия всех стадий Лида
$LeadStage = CRest::call(
    "crm.status.list", 
    [ 
        'filter' => [
            'ENTITY_ID' => 'STATUS',
        ]
    ],
)['result'];

//Вызов нужных функций
$totalLead = call_all_lead($leadFilter);
$NameUsers = call_all_users();

// Конвертирование времени
function convertDateTime($dateTimeString) {
    $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $dateTimeString);
    return $dateTime->format('d.m.Y H:i:s');
}
// Обработка данных
$FinalArrayLead = [];
foreach ($totalLead as $value) {
    $newLead = [
        'ID' => $value['ID'],
        'STATUS_ID' => getStatusName($value['STATUS_ID'], $LeadStage),
        'TITLE' => $value['TITLE'],
        'OPPORTUNITY' => $value['OPPORTUNITY'],
        'CURRENCY_ID' => $value['CURRENCY_ID'],
        'DATE_CREATE' => convertDateTime($value['DATE_CREATE']),
        'ASSIGNED_BY_ID' => getAssignedUser($value['ASSIGNED_BY_ID'], $NameUsers),
        'UF_CRM_1669707376' => getFieldData($value['UF_CRM_1669707376'], $LeadFields, 'UF_CRM_1669707376'),
        'UF_CRM_1669707455' => getFieldData($value['UF_CRM_1669707455'], $LeadFields, 'UF_CRM_1669707455')
    ];
    $FinalArrayLead[] = $newLead;
}

function getStatusName($statusId, $LeadStage) {
    foreach ($LeadStage as $value) {
        if ($value['STATUS_ID'] == $statusId) {
            return $value['NAME'];
        }
    }
    return '';
}

function getAssignedUser($assignedId, $NameUsers) {
    foreach ($NameUsers as $value) {
        if ($value['ID'] == $assignedId) {
            return $value['NAME'] . ' ' . $value['LAST_NAME'];
        }
    }
    return '';
}

function getFieldData($fieldId, $LeadFields, $fieldName) {
    if (!empty($fieldId) && isset($LeadFields['result'][$fieldName]['items'])) {
        foreach ($LeadFields['result'][$fieldName]['items'] as $value) {
            if ($value['ID'] == $fieldId) {
                return $value['VALUE'];
            }
        }
    }
    return 'Отсутствует';
}


    // Google
    // Подключаем клиент Google таблиц
    require_once (__DIR__ . '/sheets/vendor/autoload.php');

    // Наш ключ доступа к сервисному аккаунту
    $googleAccountKeyFilePath = "sheets-421314-488b0e28c9b1.json";
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleAccountKeyFilePath);
    // Создаем новый клиент
    $client = new Google_Client();
    // Устанавливаем полномочия
    $client->useApplicationDefaultCredentials();
    // Добавляем область доступа к чтению, редактированию, созданию и удалению таблиц
    $client->addScope('https://www.googleapis.com/auth/spreadsheets');
    $service = new Google_Service_Sheets($client);
    // ID таблицы
    $spreadsheetId = '1B_EKzufJAs3ELr1VE0F8jsvt3K_UxH9tMcDVrqYqakk';
    $range = 'test';
    // Объект - запрос очистки значений
    $clear = new Google_Service_Sheets_ClearValuesRequest();
    // Делаем запрос с указанием во втором параметре названия листа и диапазон ячеек для очистки
    $response = $service->spreadsheets_values->clear($spreadsheetId, $range, $clear);
    // Данные для добавления
    // Замена ключей на числовые индексы
    $dataNumericKeys = array_map('array_values', $FinalArrayLead);
    // Объект - диапазон значений
    $ValueRange = new Google_Service_Sheets_ValueRange();
    // Устанавливаем наши данные
    $ValueRange->setValues($dataNumericKeys);
    // Указываем в опциях обрабатывать пользовательские данные
    $options = ['valueInputOption' => 'USER_ENTERED'];
    // Добавляем наши значения в последнюю строку (где в диапазоне A1:Z все ячейки пустые)
    $service->spreadsheets_values->append($spreadsheetId, $range, $ValueRange, $options);
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);

?>


<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
    <script src="//api.bitrix24.com/api/v1/"></script>
    <title>Отчёт Гугл</title>
</head>

    <body>
        
        <div id="app">
           <form class="main__form" method="GET">
            <h1>Фильтрация данных</h1>
                <div class="row">
                    <div class="column">
                        <label for="start_date">Начальная дата:</label>
                        <input type="date" id="start_date" name="start_date" v-model="startDate"  :max="endDate">
                    </div>
                    <div class="column">
                        <label for="end_date">Конечная дата:</label>
                        <input type="date" id="end_date" name="end_date" v-model="endDate" :min="startDate">
                    </div>
                </div>
                <div class="row">
                    <input type="text" disabled class="select select__header employees" v-model="employees.name">
                    <span class="cross-icon" @click="clearInput">×</span>
                    <input type="text" hidden name="employee" v-model="employees.id">
                    <button  @click="addDep" class="main__form-button">Пользователи</button>
                </div>
                <div class="column-select">
                    <p>Направление</p>
                    <select class="select select__header" name="direction">
			        		<option class="select__item" value="0" disabled selected><b>Выберите направление</b></option>
                            <?php foreach($LeadFields['result']['UF_CRM_1669707455']['items'] as $values)
                            { ?>
                            <option class="select__item" value="<?=$values['ID']?>"><b><?=$values['VALUE']?></b></option>
                            <?php } ?>
                    </select>
                </div>
                <div class="row">
                    <button class="main__form-button" name="BtnClear">Выгрузить</button>
                    <button class="main__form-button" name="BtnFilter">Выгрузить с фильтрами</button>
                </div>
            </form>
    
            <table class="table">
                <tr class="table-line">
                    <th>id</th>
                    <th>Стадия</td>
                    <th>Название</th>
                    <th>Сумма</th>
                    <th>Дата создания</th>
                    <th>Подразделение</th>
                    <th>Направление</th>
                    <th>Ответственный</th>
                </tr>
                <?php foreach ($FinalArrayLead as $key => $value) { ?>
                        <tr class="table-line">
                            <td> <?=$value["ID"]?></td>
                            <td> <?=$value["STATUS_ID"]?></td>
                            <td> <?=$value["TITLE"]?></td>
                            <td> <?=$value["OPPORTUNITY"] . " " . $value["CURRENCY_ID"]?></td>
                            <td> <?=$value["DATE_CREATE"]?></td>
                            <td> <?=$value["UF_CRM_1669707376"]?></td>
                            <td> <?=$value["UF_CRM_1669707455"]?></td>
                            <td> <?=$value["ASSIGNED_BY_ID"]?></td>
                        </tr>
                <?php }?>
            </table>
        </div>
    </body>
    <script src="script/main.js"> </script>
</html>

<?php

?>