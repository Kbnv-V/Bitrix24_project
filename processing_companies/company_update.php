<?php

require_once __DIR__ . '/company_append.php';
require_once __DIR__ . '/correction_contact_data.php';

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Crm\FieldMultiTable;

Loader::includeModule('crm');
Loader::includeModule('highloadblock');

class CompanyUpdate
{
    private static $inProgress = false;
    private static $blockId = 2;

    private static function getDataClass()
    {
        $arHLBlock = HighloadBlockTable::getById(self::$blockId)->fetch(); //Метод возвращает выборку по первичному ключу сущности.
        $obEntity = HighloadBlockTable::compileEntity($arHLBlock); //Метод возвращает ID сущности-владельца полей highload-блок
        return $obEntity->getDataClass(); //получает объект класса для дальнейшей работы с ним
    }

    //тут сверка номеров в hightload и номеров в CRM
    private static function updatePhonesCompanyCrm($companyId, $phoneCompanyHightload)
    {
        //----------------- получение номеров из компании в CRM -----------------
        $phonesCompanyCrm = [];

        $getCompanyPhones = FieldMultiTable::getList(['select' => ['VALUE'], 'filter' => ['=ELEMENT_ID' => $companyId, '=TYPE_ID' => 'PHONE']]);

        $arrPhones = $getCompanyPhones->fetchAll();

        foreach($arrPhones as $value)
        {
            $phonesCompanyCrm[] = $value['VALUE'];
        } 

        //----------------- переводим номера компании в hightload в массив -----------------

        $arrHightloadPhones = explode(",", $phoneCompanyHightload);

        //----------------- перебираем номера из highload и проверяем есть ли они в crm -----------------

        file_put_contents(__DIR__ . '/log_update.txt', '#Телефоны из CRM до добавления' . PHP_EOL . print_r($phonesCompanyCrm, true) . PHP_EOL, FILE_APPEND);

        $newPhones = [];

        //НОВЫЙ ФУНКЦИОНАЛ С РЕГУЛЯРКОЙ
        foreach($arrHightloadPhones as $value)
        {
            $value = trim($value);

            $normPhones = CorrectionData::correctionPhones($value);

            foreach($normPhones as $phone)
            {
                if(!in_array($phone, $phonesCompanyCrm))
                {
                    $newPhones[] = $phone;
                }
            }

            /*
            if(!in_array($value, $phonesCompanyCrm))
            {
                //FieldMultiTable::add(['=ENTITY_ID' => 'COMPANY', '=ELEMENT_ID' => $companyId, '=TYPE_ID' => 'PHONE', 'VALUE_TYPE' => 'WORK', 'VALUE' => $value]);
                //file_put_contents(__DIR__ . '/log_update.txt', 'В компании ' . $companyId . ' новый номер - ' . $value . PHP_EOL, FILE_APPEND);

                $newPhones[] = $value;
            }
            */
        }

        if($newPhones)
        {
            file_put_contents(__DIR__ . '/log_update.txt', '#Есть новые номера: ' . print_r($newPhones, true) . PHP_EOL, FILE_APPEND);
        }
        else
        {
            file_put_contents(__DIR__ . '/log_update.txt', '#Новых номеров нет' . PHP_EOL, FILE_APPEND);
        }

        //file_put_contents(__DIR__ . '/log_update.txt', 'Массив из новых телефонов' . PHP_EOL . print_r($newPhones, true) . PHP_EOL, FILE_APPEND);

        $i = 0;

        $fullArrNewPhones = [];

        if($newPhones)
        {
            foreach($newPhones as $value)
            {
                $fullArrNewPhones['n' . $i] = ['VALUE' => $value, 'VALUE_TYPE' => 'WORK'];
                $i++;
            }

            file_put_contents(__DIR__ . '/log_update.txt', '#Готовый массив новых телефонов' . PHP_EOL . print_r($fullArrNewPhones, true) . PHP_EOL, FILE_APPEND);
        }

        return $fullArrNewPhones;

        //file_put_contents(__DIR__ . '/log_update.txt', "Номера компании в CRM" . PHP_EOL . print_r($phones, true) . PHP_EOL, FILE_APPEND);
    }

    private static function updateEmailsCompanyCrm($companyId, $emailCompanyHightload)
    {
        //----------------- получение почтовых адресов из компании в CRM -----------------
        $emailsCompanyCrm = [];

        $getCompanyEmails = FieldMultiTable::getList(['select' => ['VALUE'], 'filter' => ['=ELEMENT_ID' => $companyId, '=TYPE_ID' => 'EMAIL']]);

        $arrEmail = $getCompanyEmails->fetchAll();

        foreach($arrEmail as $value)
        {
            $emailsCompanyCrm[] = $value['VALUE'];
        }

        //----------------- переводим номера компании в hightload в массив -----------------

        $arrHightloadEmails = explode(",", $emailCompanyHightload);

        //----------------- перебираем номера из highload и проверяем есть ли они в crm -----------------

        //file_put_contents(__DIR__ . '/log_update.txt', '#Почтовые адреса из CRM до добавления' . PHP_EOL . print_r($emailsCompanyCrm, true) . PHP_EOL, FILE_APPEND);

        $newEmails = [];

        foreach($arrHightloadEmails as $value)
        {
            $value = trim($value);

            $normEmails = CorrectionData::correctionEmails($value);

            foreach($normEmails as $email)
            {
                if(!in_array($email, $emailsCompanyCrm))
                {
                    $newPhones[] = $email;
                }
            }
            /*
            if(!in_array($value, $emailsCompanyCrm))
            {
                //$pushEmail = FieldMultiTable::add(['=ENTITY_ID' => 'COMPANY', '=ELEMENT_ID' => $companyId, '=TYPE_ID' => 'EMAIL', 'VALUE_TYPE' => 'WORK', 'VALUE' => $value]);
                //file_put_contents(__DIR__ . '/log_update.txt', 'В компании ' . $companyId . ' новый почтовый адрес - ' . $value . PHP_EOL, FILE_APPEND);

                //array_push($emailsCompanyCrm, $value);
                $newEmails[] = $value;
            }
                */
        }

        if($newEmails)
        {
            file_put_contents(__DIR__ . '/log_update.txt', '#Есть новые почтовые адреса: ' . print_r($newEmails, true) . PHP_EOL, FILE_APPEND);
        }
        else
        {
            file_put_contents(__DIR__ . '/log_update.txt', '#Новых почтовых адресов нет' . PHP_EOL, FILE_APPEND);
        }

        //file_put_contents(__DIR__ . '/log_update.txt', 'Новые почтовые адреса' . PHP_EOL . print_r($newEmails, true) . PHP_EOL, FILE_APPEND);

        $i = 0;

        $fullArrNewEmails = [];

        foreach($newEmails as $value)
        {
            $fullArrNewEmails['n' . $i] = ['VALUE' => $value, 'VALUE_TYPE' => 'WORK'];
            $i++;
        }

        file_put_contents(__DIR__ . '/log_update.txt', 'Готовый массив новых почтовых адресов' . PHP_EOL . print_r($fullArrNewEmails, true) . PHP_EOL, FILE_APPEND);

        return $fullArrNewEmails;
    }

    //метод для добавления компании
    private static function companyUpdate($getInfoElelementBlock)
    {
        if($getInfoElelementBlock['UF_COMPANY_ID_B24']) //если компания уже есть
        {
            $checkPhones = self::updatePhonesCompanyCrm($getInfoElelementBlock['UF_COMPANY_ID_B24'], $getInfoElelementBlock['UF_UFPHONE']);
            $checkEmails = self::updateEmailsCompanyCrm($getInfoElelementBlock['UF_COMPANY_ID_B24'], $getInfoElelementBlock['UF_UFEMAIL']);

            $paramsUpdate = 
            [
                'TITLE' => $getInfoElelementBlock['UF_NAME'],
                'COMPANY_TYPE' => 'CUSTOMER',
                'UF_CRM_1770108037362' => $getInfoElelementBlock['UF_INN'],
                'FM' =>
                [
                    'PHONE' => $checkPhones,
                    'EMAIL' => $checkEmails
                    ]
            ];

            $companyId = $getInfoElelementBlock['UF_COMPANY_ID_B24'];
            $company = new \CCrmCompany(false);
            $companyUpdate = $company->Update($companyId, $paramsUpdate);

            if(!$companyUpdate)
            {
                file_put_contents(__DIR__ . '/log_update.txt', 'Ошибка обновления компании ' . $companyId . ' - ' . $company->LAST_ERROR . PHP_EOL, FILE_APPEND);
            }
            else
            {
                file_put_contents(__DIR__ . '/log_update.txt', "Внесены изменения в компанию - " . $companyId . PHP_EOL, FILE_APPEND);

            }

            return $companyId;
        }
        else //если компании нет
        {
            if($getInfoElelementBlock['UF_UFPHONE'])
            {
                $normalizedPhone = CompanyAppend::requisiteNormalizedPhone($getInfoElelementBlock['UF_UFPHONE']);
            }
                
            if($getInfoElelementBlock['UF_UFEMAIL'])
            {
                $normalizedEmail = CompanyAppend::requisiteNormalizedEmail($getInfoElelementBlock['UF_UFEMAIL']);
            }
            
            $paramsAdd = 
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
                    'EMAIL' => $normalizedEmail
                    ]
            ];
            $company = new \CCrmCompany(false);
            $companyId = $company->Add($paramsAdd, true, ['CURRENT_USER' => 4623, 'DISABLE_USER_FIELD_CHECK' => true]);

            file_put_contents(__DIR__ . '/log_update.txt', "Компания отсутствовала в CRM! Создана новая компания - " . $companyId . PHP_EOL, FILE_APPEND);

            return $companyId;
        }
    }
    
    
    public static function afterUpdate($event)
    {
        if(self::$inProgress)
        {
            return true;
        }

        file_put_contents(__DIR__ . '/log_update.txt', "------------------------------------------------" . PHP_EOL . "Событие изменения элемента " . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);

        self::$inProgress = true;

        //file_put_contents(__DIR__ . '/log_update.txt', print_r($event, true) . PHP_EOL, FILE_APPEND);

        $elementBlockId = $event['ID']; //записываем ID измененнного элемента

        file_put_contents(__DIR__ . '/log_update.txt', "ID измененного элементв блока - " . $elementBlockId . PHP_EOL, FILE_APPEND);

        $dataClass = self::getDataClass();

        $getInfoElelementBlock = $dataClass::getList(['select' => ['*'], 'filter' => ['ID' => $elementBlockId],'limit' => 1])->fetch(); //получаем инфу по измененному элементу

        //file_put_contents(__DIR__ . '/log_update.txt', print_r($getInfoElelementBlock, true) . PHP_EOL, FILE_APPEND);

        $companyUpdate = self::companyUpdate($getInfoElelementBlock); //создаем компанию

        if($getInfoElelementBlock['UF_COMPANY_ID_B24'])
        {
            $updateElementBlock = $dataClass::update($getInfoElelementBlock['ID'], ['UF_COMPANY_ID_B24' => $companyUpdate]); //записываем ID компании с элемент блока
        }

        self::$inProgress = false;
    }
}