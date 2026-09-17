<?php

use Bitrix\Crm\Service\Container;
\Bitrix\Main\Loader::includeModule('bizproc');
\Bitrix\Main\Loader::includeModule('crm');

class StartAdaptation
{
    private static int $entityTypeId = 1056;
    private static int $workflowTemplateId = 171;
    private static string $loggerPath = __DIR__ . '/logger.txt';

    //метод для создания элемента смарт-процесса
    public static function CreateElement(&$arFields)
    {
        self::WriteLogs(print_r($arFields, true));
        $factory = Container::getInstance()->getFactory(self::$entityTypeId);

        if ($factory) 
        {
            // 3. Создаем новый объект элемента
            $item = $factory->createItem([
                'TITLE' => 'Адаптация нового сотрудника [' . $arFields['LAST_NAME'] . " " . $arFields['NAME'] . ']',
                'ASSIGNED_BY_ID' => 11027,
                'UF_CRM_42_1726731712' => 11027,
                'UF_CRM_42_1726731692' => $arFields['ID'],
            ]);

        // 4. Сохраняем элемент
        $result = $item->save();

        if ($result->isSuccess()) 
        {
            $newElementId = $item->getId();
		    self::StartWorkFlow(self::$entityTypeId, $newElementId);
        } 
        else 
        {
            self::WriteLogs($result->getErrorMessages());
        }
        }
    }

    //метод для запуска бизнеспроцесса по элементу
    public static function StartWorkFlow($entityTypeId, $elementId)
    {
        $errors = [];
        $parameters = [];
        $documentId = ['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic', 'DYNAMIC_' . $entityTypeId . '_' . $elementId];
	    $workflowId = CBPDocument::StartWorkflow(
    	    self::$workflowTemplateId,
    	    $documentId,
            $parameters,
            $errors,
	    );

	    if (!empty($errors)) 
	    {
    	    // логирование ошибок
    	    self::WriteLogs($errors);
	    }
    }

    //метод для записи логов
    public static function WriteLogs($logs)
    {
        file_put_contents(self::$loggerPath, $logs . PHP_EOL, FILE_APPEND);
    }
}