<?php

$url = 'https://bitrix.sksevgavan.ru/rest/90/2ry3e33gen1nfx/';
$smart_id = '175';
$arr_url_doc = []; //переменная для ссылок на договоры

// функция для запроса в битрикс24
function request($method, $url, $param = null){

    $params = http_build_query($param);

    $full_url = $url . $method . "/";
    $ch = curl_init();
    $options = [
    CURLOPT_URL            => $full_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS => $params,
    ];

    curl_setopt_array($ch, $options);

    $res = curl_exec($ch);

    curl_close($ch);

    return $res;

}

//функция для перебора полученного массива
function enumeration(array $val): array{

    $result_arr = [];

    foreach($val as $value){
        $id = $value['id'];
        $title = $value['title'];
        $result_arr[] = "• $title. [URL=https://bitrix.sksevgavan.ru/page/dogovory/dogovory_2/type/175/details/$id/]Ссылка[/URL]\n";
}
    return $result_arr;
}

$date = date('Y-m-d', strtotime('+10 days'));

//echo $date;

//находоим договора, у когорых осталось 10 дней до окончания срока действия
$params_doc = array(
    'entityTypeId' => $smart_id,
    'select' => ['id', 'title', 'ufCrm2_1712833465'],
    'filter' => [
        'ufCrm2_1712833465' => $date,
    ],
);

$doc_list = request('crm.item.list', $url, $params_doc);
$info_doc = json_decode($doc_list, true);

$array_ids_doc = enumeration($info_doc['result']['items']);

//преобразуем массив ссылок в строку для отправки сообщения
$string_url_doc = implode(" ", $array_ids_doc);
echo "Ссылки просроков: $string_url_doc";

if(!empty($string_url_doc)){
    $params_message = array(
        "DIALOG_ID" => "chat2849",
        "MESSAGE"   => "Список договоров, у которых через 10 дней заканчивается срок действия:\n\n $string_url_doc"
);

$send_message = request('im.message.add', $url, $params_message);
$result_send = json_decode($send_message, true);
}
$arr_url_doc = []; //чистим массив для ссылок после отправки сообщения

$today = date('Y-m-d');

//задаем параметры для поиска просроченных договоров
$params_doc_overdue = array(
    'entityTypeId' => $smart_id,
    'select' => ['id', 'title', 'ufCrm2_1712833465'],
    'filter' => [
        '<ufCrm2_1712833465' => $today,
    ],
);

$doc_overdue_list = request('crm.item.list', $url, $params_doc_overdue);
$info_doc_overdue = json_decode($doc_overdue_list, true);

/*
echo"<pre>";
print_r($info_doc_overdue);
echo"</pre>";
*/
$array_ids_doc_overdue = [];
$array_ids_doc_overdue = enumeration($info_doc_overdue['result']['items']);

//указывае шаг выгрузки перед циклом
$next = $info_doc_overdue['next'];

//цикл для получения договоров с других страниц
while(!empty($info_doc_overdue['next'])){

    $params_doc_overdue = array(
    'entityTypeId' => $smart_id,
    'select' => ['id', 'title', 'ufCrm2_1712833465'],
    'filter' => [
        '<ufCrm2_1712833465' => $today,
    ],
    'start' => $next,
    );

    $doc_overdue_list = request('crm.item.list', $url, $params_doc_overdue);
    $info_doc_overdue = json_decode($doc_overdue_list, true);

    $array_ids_doc_overdue = array_merge($array_ids_doc_overdue, enumeration($info_doc_overdue['result']['items']));
    
    if(!empty($info_doc_overdue['next'])){ 
        $next = $info_doc_overdue['next'];
    }


}

$string_url_doc_overdue = implode(" ", $array_ids_doc_overdue);
echo "Ссылки просроков: $string_url_doc_overdue";

if(!empty($string_url_doc_overdue)){
    $params_message_overdue = array(
        "DIALOG_ID" => "chat2849",
        "MESSAGE"   => "Список договоров, у которых закончился срок действия:\n\n $string_url_doc_overdue"
);

    $send_message_overdue = request('im.message.add', $url, $params_message_overdue);
    $result_send_overdue = json_decode($send_message_overdue, true);
}
/*
echo"<pre>";
print_r($result_send);
echo"</pre>";
*/