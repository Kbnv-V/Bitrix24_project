<?php

// url нашего хука
$url = 'адрес хука';

$documentId = $_REQUEST['properties']['document_ID'] ?? null;
$listId = $_REQUEST['properties']['list_ID'] ?? null;

//функция для запросов
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

$getInfo = request('bizproc.workflow.instances', $url, ['filter' => ['MODULE_ID' => 'lists'],'select' => ['ID', 'MODIFIED', 'OWNED_UNTIL', 'MODULE_ID', 'ENTITY', 'DOCUMENT_ID', 'STARTED', 'STARTED_BY', 'TEMPLATE_ID']]);
$bizInfo = json_decode($getInfo, true);

$logText = "=== " . date("Y-m-d H:i:s") . " ===\n";
$logText .= "Result: " . print_r($bizInfo, true) . "\n\n";

file_put_contents(__DIR__ . '/debug.log', $logText, FILE_APPEND);

//$biz_ID = $bizInfo['result'][0]['ID'];

//$stop_biz = request('bizproc.workflow.terminate', $url, ['ID' => $biz_ID, 'STATUS' => 'Отмена командировки']);
//$stop_bizInfo = json_decode($stop_biz, true);


echo "<pre>";
print_r($bizInfo);
echo "</pre>";

