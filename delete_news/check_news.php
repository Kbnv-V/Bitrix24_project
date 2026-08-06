<?php

use Bitrix\Main\UserTable;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\Loader;

Loader::includeModule('socialnetwork');
Loader::includeModule('im');

Class CheckNews
{
    private static $departments = [1261, 1332, 1333, 1271];
    private static $userGroupsdId = [1];
    public static function ChekingNews(&$arFields)
    {
        $userId = (int)$arFields["USER_ID"];

        file_put_contents(__DIR__ . '/log.txt', print_r($arFields, true) . PHP_EOL, FILE_APPEND);

        //получаем инфу по пользователю
        $userGetInfo = UserTable::getList(['filter' => ['ID' => $userId], 'select' => ['UF_DEPARTMENT']])->fetch();

        $paramsGetGroups = 
        [
            'filter' => ['=USER_ID' => $userId],
            'select' =>
            [
                'GROUP_ID',
            ]
        ];

        //получаем список групп пользователя
        $userGetGroups = UserGroupTable::getList($paramsGetGroups)->fetchAll();

        if(!$userGetInfo || empty($userGetInfo['UF_DEPARTMENT']))
        {
            file_put_contents(__DIR__ . '/log.txt', 'Падает на первом условии' . PHP_EOL, FILE_APPEND);
            return false;
        }

        foreach($userGetInfo['UF_DEPARTMENT'] as $value)
        {
            if(in_array($value, self::$departments))
            {
                return true;
            }
        }
        
        foreach($userGetGroups as $value)
        {
            if(in_array($value['GROUP_ID'], self::$userGroupsdId))
            {
                return true;
            }
        }

        file_put_contents(__DIR__ . '/log.txt', 'Дошел до отправки' . PHP_EOL, FILE_APPEND);
        
        //отправка сообщения отправителю, если не прошли условия для отправки сообщения в ленту
        self::sendMess($userId);

        /*
        global $APPLICATION;
        $APPLICATION->ThrowException('Недостаточно прав для публикации.');
        */
        
        return false;
        
        
    }
    
    private static function sendMess($userId)
    {
        CIMNotify::Add(
            [
                'TO_USER_ID' => $userId,
                'FROM_USER_ID' => 0,
                'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
                'NOTIFY_MODULE' => 'main',
                'NOTIFY_TAG' => 'CHECK_NEWS',
                'NOTIFY_MESSAGE' => 'У вас нет прав на создание сообщений/опросов в ленте новостей! Обратитесь к HR.'
            ]
        );
    }
    
}

?>