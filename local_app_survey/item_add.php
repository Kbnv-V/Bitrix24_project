<?php

// url нашего хука
$url = 'https://arlift.net/rest/1700/9eun56t52qdbtb9s/';

$item_id = $_REQUEST['data']['FIELDS']['ID'] ?? null;
$entity_id = $_REQUEST['data']['FIELDS']['ENTITY_TYPE_ID'] ?? null;

$hard_entity_id = 1032;
$bizpoc_id = 970;

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

if($entity_id == $hard_entity_id){

    $add_item = request('bizproc.workflow.start', $url, ['TEMPLATE_ID' => $bizpoc_id, 'DOCUMENT_ID' => ['crm', 'Bitrix\\Crm\\Integration\\BizProc\\Document\\Dynamic', 'DYNAMIC_' . $hard_entity_id . '_' . $item_id]]);

}

