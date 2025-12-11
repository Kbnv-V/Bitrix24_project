<?php

// url нашего хука
$url = 'https://arlift.net/rest/1700/9eun56t52qdbtb9s/';

$taskID = $_REQUEST['data']['FIELDS_AFTER']['ID'] ?? null;

$groupId = 144;

$stageId = 2848;

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
//sleep(1);

//получаем задачу по ID
$getInfo = request('tasks.task.get', $url, ['taskId' => $taskID]);
$taskInfo = json_decode($getInfo, true);

$task_info_group_id = $taskInfo['result']['task']['groupId']; // вытаскиваем ID проекта из полученной задачи
$task_info_stage_id = $taskInfo['result']['task']['stageId']; // вытаскиваем ID стадии из полученной задачи

if($task_info_group_id == (int)$groupId && $task_info_stage_id == (int)$stageId){
    
    $task_name = $taskInfo['result']['task']['title']; //забираем имя задачи
    $task_executor = $taskInfo['result']['task']['creator']['id']; // забираем id постановщика
    $task_responsible = $taskInfo['result']['task']['responsible']['id']; // забираем id исполнителя
    $final_taskID = $taskInfo['result']['task']['id'];

    //создаем элемент смарт-процесса по опросу инициатора задачи
    $crm_item_add = request('crm.item.add', $url, ['entityTypeId' => 1032, 'fields' => ['title' => $task_name, 'ufCrm5_1755785759' => $task_executor, 'ufCrm5_1756466878' => $task_responsible, 'ufCrm5_1756470912' => $final_taskID]]);

}else{
    exit();
}

echo "<pre>";
print_r($taskInfo);
echo "</pre>";