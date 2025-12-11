<?php

// url нашего хука
$url = 'адрес хука';

//id потоков, в которых необохдимо менять dl, если заполнен желаемый срок 
$flowID = [28, 13, 29, 26, 19, 27, 22, 25, 47, 31, 35, 23, 24, 18, 44, 45, 46, 17, 14, 34, 40, 21, 20, 	
43, 42, 36, 41, 33, 32, 30, 15, 12, 11, 10];

$taskID = $_REQUEST['data']['FIELDS_AFTER']['ID'] ?? null;

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

//пауза будет спасать, если б24 будет задерживать создание новой задачи
sleep(3);

//получаем задачу по ID
$getInfo = request('tasks.task.get', $url, ['taskId' => $taskID, 'select' => ['ID', 'TITLE', 'FLOW_ID', 'UF_AUTO_580231558288', 'DEADLINE']]);
$taskInfo = json_decode($getInfo, true);

$flowIDTask = $taskInfo['result']['task']['flowId'];
$newDeadline = $taskInfo['result']['task']['ufAuto580231558288'];

if(empty($flowIDTask)){
    exit;
}

if(!in_array($flowIDTask, $flowID, true)){ 
    if(!empty($newDeadline)){
    $modifyDeadline = new DateTime($newDeadline);
    $modifyDeadline->modify('+18 hours');
    $newDeadlineFormat = $modifyDeadline->format('c');
}
    $taskUpdate = request('tasks.task.update', $url, ['taskId' => $taskID, 'fields' => ['DEADLINE' => $newDeadlineFormat]]);
    $resultUpdate = json_decode($taskUpdate, true);
}
else{
    exit;
}



