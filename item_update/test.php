<?php
"тестовый файл для отладки"
// url нашего хука
$url = 'адрес хука';
$product_id = $_REQUEST['properties']['product_id'] ?? null;
$token = $_REQUEST['event_token'] ?? '';   // очень важно
$entity_id = 1050; //идентификатор смарта

//функция для запросов
function request($method, $url, $param = null){

    $data = http_build_query($param);

    $url = $url . $method . "/";
    $ch = curl_init(); //инициализируем новую сессию
    $options = [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS => $data,
    ];

    curl_setopt_array($ch, $options);

    $res = curl_exec($ch);

    curl_close($ch);

    return $res;

}

$product_fields_info = request('crm.product.get', $url, ['id' => 4218]);
$product_info = json_decode($product_fields_info, true);

$product_category_id = $product_info['result']['PROPERTY_98']['value'];

$item_fields_info = request('crm.item.get', $url, ['entityTypeId' => $entity_id, 'id' => $product_category_id, 'useOriginalUfNames' => 'N']);
$item_element_info = json_decode($item_fields_info, true);

$lead_manager_id = $item_element_info['result']['item']['ufCrm7_1743504649'];

$baseForEvent = $url;

$paramsEvent  = [
    'event_token'   => $token,
    'return_values' => ['product_category_id' => $product_category_id, 'lead_manager_id' => $lead_manager_id],
];

if (!empty($_REQUEST['auth']['domain']) && !empty($_REQUEST['auth']['access_token'])) {
    $baseForEvent = 'https://' . $_REQUEST['auth']['domain'] . '/rest/';
    $paramsEvent['auth'] = $_REQUEST['auth']['access_token'];
}

$eventResponse = request('bizproc.event.send', $baseForEvent, $paramsEvent);

/*
print_r('manager_id: ' . $lead_manager_id . "<br/>");
print_r('product_category_id: ' . $product_category_id);
echo "<pre>";
print_r($item_element_info);
echo "</pre>";
*/

