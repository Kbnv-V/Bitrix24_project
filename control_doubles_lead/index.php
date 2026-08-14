<?php

use Bitrix\Main\Loader;
Loader::includeModule('crm');
Loader::includeModule('im');

class ControlDuplicates
{
    //метод для поиска дублей
    public static function FindDuplicates(&$arFields)
    {
        file_put_contents(__DIR__ . '/log_lead_append.txt', "--- Начало работы метода FindDuplicates ---" . PHP_EOL, FILE_APPEND);
        file_put_contents(__DIR__ . '/log_lead_append.txt', "[arFields]" . PHP_EOL . "\n\n" . print_r($arFields, true) . PHP_EOL, FILE_APPEND);

        /*
        $newLeadId = $arFields["ID"]; //в $arFields хранятся данные о лиде с события создания лида

        if(empty($arFields["ID"]))
        {
            return;
        }

        */

        $requisite = $arFields["UF_CRM_1784104247222"];

        $assignedById = $arFields["ASSIGNED_BY_ID"];

        $doubles = [];

        file_put_contents(__DIR__ . '/log_lead_append.txt', "Дошел до поиска лидов!" . PHP_EOL, FILE_APPEND);

        if(!empty($requisite))
        {
            $getLeads = CCrmLead::GetListEx
            (
                ["ID" => "ASC"],
                [
                    //"!=ID" => $newLeadId,
                    "=UF_CRM_1784104247222" => $requisite,
                    //"!=STATUS_ID" => ["JUNK", "CONVERTED"],
                    //"CHECK_PREMISSION" => "N",
                ],
                false,
                false,
                ["ID", "TITLE", "UF_CRM_1784104247222"]
                //["*"]
            );
        }

        while($element = $getLeads->Fetch())
        {
            $doubles[] = $element;
        }

        if(!empty($doubles))
        {
            file_put_contents(__DIR__ . '/log_lead_append.txt', date('Y-m-d H:i:s') . " - Инициатор (ID" . $assignedById . "). " . "Найдены лиды в работе с ИНН $requisite! Количество: " . count($doubles) . PHP_EOL . print_r($doubles, true) . PHP_EOL, FILE_APPEND);

            //добавление коомента в лид
            //ControlDuplicates::CommentAdd($doubles, $newLeadId, $requisite, $assignedById);

            $message = self::FormationMessage($doubles, $requisite);

            ControlDuplicates::SendMessage($message, $assignedById);

            $arFields['RESULT_MESSAGE'] = 'Найдены дубликаты лидов с ИНН - ' . $requisite ;
            return false;
        }
        else
        {
            //file_put_contents(__DIR__ . '/log_lead_append.txt', date('Y-m-d H:i:s') . " - Дублей нет." . PHP_EOL, FILE_APPEND);
        }

        echo "<pre>";
        print_r($doubles);
        echo "</pre>";
    }

    //метод для добавления коммента в лид
    private static function CommentAdd($doubles, $newLeadId, $requizite, $assignedById)
    {
        $message = "Обнаружены лиды в работе с ИНН: $requizite\n\n";

        foreach($doubles as $value)
        {
            $leadId = $value["ID"];
            $message .= "- Лид ID $leadId - http://b2b.btcgroup.local/crm/lead/details/$leadId/\n";
        }

        $commentAdd = \Bitrix\Crm\Timeline\CommentEntry::create(
        [
		'TEXT' => $message,
		'SETTINGS' => [], // тут можно указать, что есть прикрепленные файлы
		'AUTHOR_ID' => 11302,
		'BINDINGS' =>
            [
                [
                    'ENTITY_TYPE_ID' => CCrmOwnerType::Lead, 
                    'ENTITY_ID' => $newLeadId,
                ]
            ]
        ]
        );

        //отправка сообщения в личку создателю лида
        ControlDuplicates::SendMessage($message, $assignedById);
    }

    
    //метод для отправки сообщения в личку создателю лида
    private static function SendMessage($message, $assignedById)
    {
        if(!\Bitrix\Main\Loader::includeModule('im'))
        {
            return;
        }

        \CIMChat::AddMessage(
        [
            'TO_USER_ID' => $assignedById,
		    'FROM_USER_ID' => 11302,
            'MESSAGE'    => $message,
		    //'SYSTEM'     => 'Y',
        ]);
    }

    private static function FormationMessage($doubles, $requisite)
    {
        $message = "Обнаружены лиды в работе с ИНН: $requisite\n\n";

        foreach($doubles as $value)
        {
            $leadId = $value["ID"];
            $message .= "- Лид ID $leadId - http://b2b.btcgroup.local/crm/lead/details/$leadId/\n";
        }

        return $message;
    }
    
}

/*
$test = new ControlDuplicates();

$test->FindDuplicates(["ID" => 99]);
*/



