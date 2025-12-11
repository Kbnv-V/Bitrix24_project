<?php

// url нашего хука
$url = 'https://test2.arlift.net/rest/1700/cbg46ss890p9xfbq/';

$document_name = $_REQUEST['properties']['document_name'] ?? null;
$listId = $_REQUEST['properties']['list_ID'] ?? null;

file_put_contents(__DIR__ . '/log.txt', $document_name . '/n' . $listId . '/n' . '-----------------------' PHP_EOL, FILE_APPEND);

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

$params = [
    'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID' => $listId,
    'filter' => ['%NAME' => $document_name]
];

$get_element_list = request('lists.element.get', $url, $params);
$element_info = json_decode($get_element_list, true);

$arr_id = [];

if(!empty($element_info['result'])){
    foreach($element_info['result'] as $value){
        array_push($arr_id, $value['ID']);
}
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'return_values' => [
        'element_id' => array_map('strval', $arr_id)
    ]
], JSON_UNESCAPED_UNICODE);
exit;

/*
echo "<pre>";
print_r($arr_id);
echo "</pre>";
*/