<?php

require_once __DIR__ . '/correction_contact_data.php';

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

Loader::includeModule('crm');
Loader::includeModule('highloadblock');

class CompanyAppend
{
    private static $inProgress = false;
    private static $blockId = 2;

    private static function getDataClass()
    {
        $arHLBlock = HighloadBlockTable::getById(self::$blockId)->fetch(); //Метод возвращает выборку по первичному ключу сущности.
        $obEntity = HighloadBlockTable::compileEntity($arHLBlock); //Метод возвращает ID сущности-владельца полей highload-блок
        return $obEntity->getDataClass(); //получает объект класса для дальнейшей работы с ним
    }

    //приведение телефонов к массиву, который требуется для загрузки
    public static function requisiteNormalizedPhone($phones)
    {
        $newPhones = [];
        $fullArrPhones = [];
        $i = 0;
        
        $arrPhones = explode(",", $phones);

        foreach($arrPhones as $value)
        {
            $value = trim($value);

            $normPhones = CorrectionData::correctionPhones($value);

            foreach($normPhones as $phone)
            {
                $newPhones[] = $phone;
            }
            /*
            $fullArrPhones['n' . $i] = ['VALUE' => $value, 'VALUE_TYPE' => 'WORK'];
            $i++;
            */
        }

        foreach($newPhones as $correctPhone)
        {
            $fullArrPhones['n' . $i] = ['VALUE' => $correctPhone, 'VALUE_TYPE' => 'WORK'];
            $i++; 
        }

        return $fullArrPhones;
    }

    //приведение email к массиву, который требуется для загрузки
    public static function requisiteNormalizedEmail($emails)
    {
        $newEmails = [];
        $fullArrEmails = [];
        $i = 0;
        
        $arrEmails = explode(",", $emails);

        foreach($arrEmails as $value)
        {
            $value = trim($value);

            $normEmails = CorrectionData::correctionEmails($value);

            foreach($normEmails as $email)
            {
                $newEmails[] = $email;
            }
            /*
            $fullArrEmails['n' . $i] = ['VALUE' => $value, 'VALUE_TYPE' => 'WORK'];
            $i++;
            */
        }

        foreach($newEmails as $correctEmail)
        {
            $fullArrEmails['n' . $i] = ['VALUE' => $correctEmail, 'VALUE_TYPE' => 'WORK'];
            $i++;
        }

        //file_put_contents(__DIR__ . '/log_append.txt', print_r($fullArrEmails, true) . PHP_EOL, FILE_APPEND);

        return $fullArrEmails;
    }

    //метод для изменения компании
    private static function companyAppend($getInfoElelementBlock) //второй аргумент $requisiteNormalized
    {
        if($getInfoElelementBlock['UF_UFPHONE'])
        {
            $normalizedPhone = self::requisiteNormalizedPhone($getInfoElelementBlock['UF_UFPHONE']);
        }

        if($getInfoElelementBlock['UF_UFEMAIL'])
        {
            $normalizedEmail = self::requisiteNormalizedEmail($getInfoElelementBlock['UF_UFEMAIL']);
        }

        $params = 
        [
            'TITLE' => $getInfoElelementBlock['UF_NAME'],
            'COMPANY_TYPE' => 'CUSTOMER',
            'UF_CRM_1770108054084' => $getInfoElelementBlock['UF_KPP'],
            'UF_CRM_1770108037362' => $getInfoElelementBlock['UF_INN'],
            'UF_CRM_1779189888010' => $getInfoElelementBlock['UF_UFFIZADRESS'],
            'UF_CRM_1779190006014' => $getInfoElelementBlock['UF_UFYRADRESS'],

            'FM' =>
            [
                'PHONE' => $normalizedPhone,
                'EMAIL' => $normalizedEmail,
            ]
        ];

        $company = new \CCrmCompany(false);
        $companyId = $company->Add($params, true, ['CURRENT_USER' => 4623, 'DISABLE_USER_FIELD_CHECK' => true]);

        file_put_contents(__DIR__ . '/log_append.txt', "Создана новая компания - " . $companyId . PHP_EOL, FILE_APPEND);
        //file_put_contents(__DIR__ . '/log_append.txt', '[ТЕЛЕФОН]' . PHP_EOL . print_r($getInfoElelementBlock['UF_UFPHONE'], true) . PHP_EOL . '[ПОЧТА]' . PHP_EOL . print_r($getInfoElelementBlock['UF_UFEMAIL'], true) . PHP_EOL, FILE_APPEND);

        return $companyId;
    }
    
    
    public static function afterAdd($event)
    {
        if(self::$inProgress)
        {
            return true;
        }

        file_put_contents(__DIR__ . '/log_append.txt', "------------------------------------------------" . PHP_EOL . "Событие создания нового элемента " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);

        file_put_contents(__DIR__ . '/log_append.txt', print_r($event, true). PHP_EOL, FILE_APPEND);

        self::$inProgress = true;

        //$primary = $event->getParameter('primary'); //получаем параметры события
        $elementBlockId = (int)$event; //записываем ID созданного элемента

        file_put_contents(__DIR__ . '/log_append.txt', "ID созданного элементв блока - " . $elementBlockId . PHP_EOL, FILE_APPEND);

        $dataClass = self::getDataClass();

        $getInfoElelementBlock = $dataClass::getList(['select' => ['*'], 'filter' => ['ID' => $elementBlockId],'limit' => 1])->fetch(); //получаем инфу по созданному элементу

        //file_put_contents(__DIR__ . '/log_append.txt', print_r($getInfoElelementBlock, true) . PHP_EOL, FILE_APPEND);

        $companyId = self::companyAppend($getInfoElelementBlock); //создаем компанию

        $updateElementBlock = $dataClass::update($getInfoElelementBlock['ID'], ['UF_COMPANY_ID_B24' => $companyId]); //записываем ID компании с элемент блока

        self::$inProgress = false;
    }
}