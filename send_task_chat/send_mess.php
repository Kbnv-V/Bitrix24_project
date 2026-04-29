<?php

// url нашего хука
$url = 'https://test2.arlift.net/rest/api/1700/e6gu0tslofkb140u/';

//функция для запросов
function request($method, $url, $param = null)
{
    $url = rtrim($url, '/') . '/' . $method;

    $ch = curl_init();

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($param, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ];

    curl_setopt_array($ch, $options);

    $res = curl_exec($ch);

    if ($res === false) {
        $res = curl_error($ch);
    }

    curl_close($ch);

    return $res;
}

$params = [
    'fields' =>
    [
        'taskId' => 739464,
        'text' => 'text',
    ]
];

$send_mess = request('tasks.task.chat.message.send', $url, $params);
$result = json_decode($send_mess, true);

echo "<pre>";
print_r($result);
echo "</pre>";
