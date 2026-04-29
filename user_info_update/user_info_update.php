<?php

use Bitrix\Main\UserTable;  
use Bitrix\Main\Loader;

//Loader::includeModule('main');

Class UserUpdateInfo
{

    private static $inProgress = false;

    public static function UpdateUserInfo(&$arFields)
    {
        if(self::$inProgress)
        {
            return;
        }

        self::$inProgress = true;

        try
        {
            if(!Loader::includeModule('main'))
            {
                return;
            }

            $userId = $arFields['ID']; //записываем ID пользователя

            $log = "Обновляется инфа у пользователя. ID - " . $userId . " | Дата обновления: " . date('Y-m-d H:i:s');

            file_put_contents(__DIR__ . '/log.txt', $log . PHP_EOL, FILE_APPEND);

            //получаем инфу по пользователю
            $userGetInfo = UserTable::getList(['filter' => ['ID' => $userId],'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL', 'UF_USR_1776771559253'],])->fetch();

            //проверка на заполнение ФИО
            if(!$userGetInfo['NAME'] && $userGetInfo['LAST_NAME'])
            {
                return;
            }

            //узнаем кодировку текста в ФИО
            $getCodeName = mb_detect_encoding($userGetInfo['NAME']);
            $getCodeLastName = mb_detect_encoding($userGetInfo['LAST_NAME']);

            //проверяем кодировку
            if($getCodeName === 'UTF-8' && $getCodeLastName === 'UTF-8')
            {
                return;
            }

            //разбиваем строку с русским ФИО на массив и записываем в переменные
            $arrFullName = explode(" ", $userGetInfo['UF_USR_1776771559253']);

            $lastName = trim($arrFullName[0]);
            $name = trim($arrFullName[1]);
            $secondName = trim($arrFullName[2]);

            //устанавливаем параметры для обновления инфы у пользователя
            $fields = 
            [
                'LAST_NAME' => $lastName,
                'NAME' => $name,
                'SECOND_NAME' => $secondName,
            ];
        
            //тут обновление инфы
            $cUser = new CUser;
            $cUser->Update($userId, $fields);
        }
        finally
        {
            self::$inProgress = false;
        }
    }

}

//bak

/*
Class UserUpdateInfo
{

    private static $inProgress = false;

    public static function UpdateUserInfo(&$arFields)
    {
        if(self::$inProgress)
        {
            return;
        }

        self::$inProgress = true;
        
        $userId = $arFields['ID']; //записываем ID пользователя

        file_put_contents(__DIR__ . '/log.txt', "Обновляется инфа у пользователя. ID - " . $userId . PHP_EOL, FILE_APPEND);

        //получаем инфу по пользователю
        $userGetInfo = UserTable::getList(['filter' => ['ID' => $userId],'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL', 'UF_USR_1776771559253'],])->fetch();

        //проверка на заполнение ФИО
        if(!$userGetInfo['NAME'] && $userGetInfo['LAST_NAME'])
        {
            return;
        }

        //узнаем кодировку текста в ФИО
        $getCodeName = mb_detect_encoding($userGetInfo['NAME']);
        $getCodeLastName = mb_detect_encoding($userGetInfo['LAST_NAME']);

        //проверяем кодировку
        if($getCodeName === 'UTF-8' && $getCodeLastName === 'UTF-8')
        {
            return;
        }

        //разбиваем строку с русским ФИО на массив и записываем в переменные
        $arrFullName = explode(" ", $userGetInfo['UF_USR_1776771559253']);

        $lastName = trim($arrFullName[0]);
        $name = trim($arrFullName[1]);
        $secondName = trim($arrFullName[2]);

        //устанавливаем параметры для обновления инфы у пользователя
        $fields = 
        [
            'LAST_NAME' => $lastName,
            'NAME' => $name,
            'SECOND_NAME' => $secondName,
        ];
        
        //тут обновление инфы
        $cUser = new CUser;
        $cUser->Update($userId, $fields);
    }

}
*/