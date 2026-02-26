<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRONTAB', true);

$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('crm')) {
    throw new \RuntimeException('Модуль crm не подключен');
}
if (!\Bitrix\Main\Loader::includeModule('im')) {
    throw new \RuntimeException('Модуль im не подключен');
}

$smartId      = 175;
$chatDialogId = 'chat2849';
$dateField    = 'UF_CRM_2_1712833465';
$fromUserId = 90;

$baseUrl      = 'https://bitrix.sksevgavan.ru';
$linkTemplate = $baseUrl . "/page/dogovory/dogovory_2/type/{$smartId}/details/%d/";

$field = strtoupper($dateField);

//функция для форматирования результата в список ссылок
$formatRowsToLines = function(array $rows) use ($linkTemplate): array {
    $out = [];
    foreach ($rows as $row) {
        $id = (int)($row['ID'] ?? 0);
        $title = (string)($row['TITLE'] ?? '');
        if ($id <= 0) { continue; }
        $url = sprintf($linkTemplate, $id);
        $out[] = "• {$title}. [URL={$url}]Ссылка[/URL]";
    }
    return $out;
};

//функция для получения элементов СП
$getAllSmartItems = function(array $filter, int $pageSize = 50) use ($smartId): array {
    $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($smartId);
    if (!$factory) {
        throw new \RuntimeException("Не найдена фабрика смарт-процесса {$smartId}");
    }

    $result = [];
    $offset = 0;

    while (true) {
        $items = $factory->getItems([
            'select' => ['ID', 'TITLE', 'UF_CRM_2_1713166724'],
            'filter' => $filter,
            'order'  => ['ID' => 'ASC'],
            'limit'  => $pageSize,
            'offset' => $offset,
        ]);

        if (empty($items)) {
            break;
        }

        foreach ($items as $item) {
            $result[] = [
                'ID'    => $item->getId(),
                'TITLE' => (string)$item->getTitle(),
                'INITIATOR' => $item->get('UF_CRM_2_1713166724'),
            ];
        }

        if (count($items) < $pageSize) {
            break;
        }

        $offset += $pageSize;
    }

    return $result;
};

//функция для отправки сообщений в общий чат
$sendToChat = function(string $dialogId, string $message): void {
    if (!preg_match('~^chat(\d+)$~', $dialogId, $m)) {
        throw new \InvalidArgumentException('DIALOG_ID должен быть вида chatXXXX');
    }
    $chatId = (int)$m[1];

    \CIMChat::AddMessage([
        'TO_CHAT_ID' => $chatId,
		'FROM_USER_ID' => 90,
        'MESSAGE'    => $message,
		//'SYSTEM'     => 'Y',
    ]);
};

//функция для отправки сообщений в личку инициаторам
$sendToInitiators = function(array $rows, string $header, int $fromUserId) use ($formatRowsToLines): void {

    $byInitiator = [];

    foreach($rows as $row){
        $userId = (int)($row['INITIATOR'] ?? 0);
        if($userId <= 0){ 
            continue; 
        }
        $byInitiator[$userId][] = $row;
    }

    foreach($byInitiator as $userId => $userRows){
        $lines = $formatRowsToLines($userRows);
        if(!$lines){ 
            continue; 
        }

        $message = $header . implode("\n", $lines);

        \CIMMessage::Add([
            'FROM_USER_ID' => $fromUserId,
            'TO_USER_ID'   => $userId,
            'MESSAGE'      => $message,
        ]);
    }
};

//ФОрматирование дат
$tsPlus10 = strtotime('today +10 days');
$yesterday  = strtotime('today -1 days');

$datePlus10 = \Bitrix\Main\Type\Date::createFromTimestamp($tsPlus10);
$dateYesterday = \Bitrix\Main\Type\Date::createFromTimestamp($yesterday);

//----------------------------------------- Поиск договоров, у которых срок заканчивается через 10 дней -----------------------------------------
$rowsPlus10 = $getAllSmartItems(['=' . $field => $datePlus10], 50);
$linesPlus10 = $formatRowsToLines($rowsPlus10);
$stringPlus10 = implode("\n", $linesPlus10);


//echo "Ссылки (через 10 дней):\n" . $stringPlus10 . "\n\n";

//условие на заполнение массива с ссылками
if(!empty($stringPlus10)){
    //отправка в общий чат
    $sendToChat($chatDialogId, "Список договоров, у которых через 10 дней заканчивается срок действия:\n\n" . $stringPlus10);

    //отправка в личку инициаторам
    $sendToInitiators($rowsPlus10, "Список договоров, у которых через 10 дней заканчивается срок действия:\n\n", $fromUserId);
}

//----------------------------------------- Поиск просроченных договоров -----------------------------------------
$rowsOverdue = $getAllSmartItems(['=' . $field => $dateYesterday], 50);
$linesOverdue = $formatRowsToLines($rowsOverdue);
$stringOverdue = implode("\n", $linesOverdue);


//echo "Ссылки просроков:\n" . $stringOverdue . "\n\n";

if(!empty($stringOverdue)){
    //отправка в общий чат
    $sendToChat($chatDialogId, "Список договоров, у которых закончился срок действия:\n\n" . $stringOverdue);

    //отправка в личку инициаторам
    $sendToInitiators($rowsOverdue, "Список договоров, у которых закончился срок действия:\n\n", $fromUserId);
}

//echo "OK";


?>