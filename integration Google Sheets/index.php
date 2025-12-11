<?php
include_once(__DIR__.'/crest.php');

// Если есть, то заполняется массив фильтров
$leadFilter = [];
$leadFilter[">=DATE_CREATE"] = "01.04.2024";
$leadFilter["<=DATE_CREATE"] = "30.04.2024";
$leadFilter["=UF_CRM_1669800436"] = "1076";
if(isset($_GET['BtnFilter']))
{
    if(!empty($_GET['direction']))
    {
        //    Поменять поля UF_CRM на направление и подразделение из Карточки Лида
        //    Исследовать код -> Выбрать поле и посмотреть в атрибутах -> Заменить!
        // direction старое значение UF_CRM_1669707455
        $leadFilter["=UF_CRM_1669800436"] = $_GET['direction']; 
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
    if(!empty($_GET['test'])) // потом убрать после теста. это подразделение
    {
        $leadFilter["<=UF_CRM_1669800409"] = $_GET['test']; 
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
                //    Поменять поля UF_CRM на направление и подразделение из Карточки Лида
                //    Исследовать код -> Выбрать поле и посмотреть в атрибутах -> Заменить!
            'select' => [ "ID","STATUS_ID", "TITLE", "OPPORTUNITY","DATE_CREATE","UF_CRM_1669800409","UF_CRM_1669800436", "ASSIGNED_BY_ID","SOURCE_ID"],
        ]
    ];
    $totalGetLeads = CRest::call('crm.lead.list',[
        'order' => [ "STATUS_ID" => "ASC" ],
        'filter' => $leadFilter,
        'select' => [ "ID" ],
    ])["total"];
    $listGetLeads = ceil($totalGetLeads / 50);
    $arrBatchGetLeads = [];
    for ($i = 0; $i < $listGetLeads; $i++) {
        $batchParams = $bacthGetLeads;
        $batchParams['params']['start'] = $i * 50;
        $arrBatchGetLeads[(int)($i / 49)]["list_" . $i] = $batchParams;
    }
    $resultGetLeads = [];
    foreach ($arrBatchGetLeads as $batch)
    {
        sleep(2);
        $batchResult = CRest::callBatch($batch, false)['result']['result'];
        foreach ($batchResult as $elementGetLeads) {
            $resultGetLeads = array_merge($resultGetLeads, $elementGetLeads);
        }
    }
    return $resultGetLeads;
}
$totalLead = call_all_lead($leadFilter);
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
    $batchResult = CRest::callBatch($arrBatchGetUsers[0], false)['result']['result'];
    foreach ($batchResult as $elementGetUsers) {
        $resultGetUsers = array_merge($resultGetUsers, $elementGetUsers);
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

$LeadSource = CRest::call(
    "crm.status.list", 
    [ 
        'filter' => [
            'ENTITY_ID' => 'SOURCE',
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
        'STATUS_ID' => getStatusAndSourceName($value['STATUS_ID'], $LeadStage),
        'TITLE' => $value['TITLE'],
        'OPPORTUNITY' => $value['OPPORTUNITY'],
        'SOURCE_ID' => getStatusAndSourceName($value['SOURCE_ID'], $LeadSource),
        'DATE_CREATE' => convertDateTime($value['DATE_CREATE']),
        'ASSIGNED_BY_ID' => getAssignedUser($value['ASSIGNED_BY_ID'], $NameUsers),

    //    Поменять поля UF_CRM на направление и подразделение из Карточки Лида
    //    Исследовать код -> Выбрать поле и посмотреть в атрибутах -> Заменить!
                            
        'UF_CRM_1669800409' => getFieldData($value['UF_CRM_1669800409'], $LeadFields, 'UF_CRM_1669800409'),
        'UF_CRM_1669800436' => getFieldData($value['UF_CRM_1669800436'], $LeadFields, 'UF_CRM_1669800436')
    ];
    $FinalArrayLead[] = $newLead;
}

function getStatusAndSourceName($statusId, $data) {
    foreach ($data as $value) {
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

    $googleAccountKeyFilePath = "service-422707-7765a262648d.json"; // название файла меняем
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $googleAccountKeyFilePath);
    // Создаем новый клиент
    $client = new Google_Client();
    // Устанавливаем полномочия
    $client->useApplicationDefaultCredentials();
    // Добавляем область доступа к чтению, редактированию, созданию и удалению таблиц
    $client->addScope('https://www.googleapis.com/auth/spreadsheets');
    $service = new Google_Service_Sheets($client);
    // ID таблицы
    $spreadsheetId = '1xHlkuIQw0JE3AfaW9dc1eeCk-MLSknSGIXBY3TnntUA';
    $range = 'ВыгрузкаАпрель';
    $range2 = 'Даты!B4';
    // Объект - запрос очистки значений
    $clear = new Google_Service_Sheets_ClearValuesRequest();
    // Делаем запрос с указанием во втором параметре названия листа и диапазон ячеек для очистки
    $response = $service->spreadsheets_values->clear($spreadsheetId, $range, $clear);
    $response = $service->spreadsheets_values->clear($spreadsheetId, $range2, $clear);
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
    // Создаем объект для значения даты
    $dateValue = new Google_Service_Sheets_ValueRange();
    $dateValue->setValues([ [date('d.m.Y H:i:s')] ]);
    // Записываем дату в таблицу
    $response = $service->spreadsheets_values->update($spreadsheetId, $range2, $dateValue, $options);
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
                            <?php foreach($LeadFields['result']['UF_CRM_1669800436']['items'] as $values)
                            { ?>
                            <option class="select__item" value="<?=$values['ID']?>"><b><?=$values['VALUE']?></b></option>
                            <?php } ?>
                    </select>
                </div>
                <div class="column-select">
                    <p>Подразделение</p>
                    <select class="select select__header" name="test"> 
			        		<option class="select__item" value="0" disabled selected><b>Выберите направление</b></option>
                            <?php foreach($LeadFields['result']['UF_CRM_1669800409']['items'] as $values)
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
                    <th>Источник</th>
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
                            <td> <?=$value["OPPORTUNITY"]?></td>
                            <td> <?=$value["SOURCE_ID"]?></td>
                            <td> <?=$value["DATE_CREATE"]?></td>
                            <!-- Поменять поля UF_CRM на направление и подразделение из Карточки Лида
                            Исследовать код -> Выбрать поле и посмотреть в атрибутах -> Заменить!
                            -->
                            <td> <?=$value["UF_CRM_1669800409"]?></td>
                            <td> <?=$value["UF_CRM_1669800436"]?></td>
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
