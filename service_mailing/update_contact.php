<?php

include_once(__DIR__.'/crest.php');

$contact_email = $_GET['email'];

$params_get = [
    'FILTER' => [
        'EMAIL' => $contact_email,
    ],
    'SELECT' => [
        'UF_*',
    ]

];

//ищем контакт по полученно почте из GET. Должен выдавать один контакт. В базе не должно быть дублей!
$get_contact_ID = Crest::call('crm.contact.list', $params_get);

$params_update = [
    'ID' => $get_contact_ID['result'][0]['ID'],
    'FIELDS' => [
            'UF_CRM_1770364878874' => 'Y',
    ]

];

//получив ID, меняем поле контакта 
$update_contact = Crest::call('crm.contact.update', $params_update);

//если успешно изменили, то выводим сообщение об отписке
if($update_contact['result']){
    echo "You have unsubscribed from our mailing list.";
}
/*
echo "<pre>";
print_r($get_contact_ID);
echo "</pre>";
*/
?>