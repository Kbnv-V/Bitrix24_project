<?php
 
use Bitrix\Main\Loader;

class NotificationNewUserAdd
{
    private static $inProgress = false;

    public static function SendMessage(&$arFields)
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

            if (!\Bitrix\Main\Loader::includeModule('im')) 
            {
                return;
            }

            $userId = $arFields['ID']; //записываем ID пользователя

            //$log = "Отправка сообщения о добавлении пользователя. ID - " . $userId . " " . date('Y-m-d H:i:s');

            //file_put_contents(__DIR__ . '/log_send_mess.txt', $log . PHP_EOL, FILE_APPEND);

            //отправляем сообщение о новом сотруднике в чат кадровику
            \CIMChat::AddMessage(
            [
                'TO_USER_ID' => 11027,
		        'FROM_USER_ID' => 11302,
                'MESSAGE'    => "На портале добавлен новый пользователь. ID - " . $userId . "\nПеренесите сотрудника в соответствующее подразделение\n Ссылка на профиль пользователя: http://b2b.btcgroup.local/company/personal/user/$userId/",
		        //'SYSTEM'     => 'Y',
            ]);
            


            // ----- ФУНКЦИЯ ДЛЯ ОТПРАВКИ СООБЩЕНИЯ В ОБЩИЙ ЧАТ -----
            $message = "На портале добавлен новый пользователь. ID - " . $userId . "\n\n Ссылка на профиль пользователя: http://b2b.btcgroup.local/company/personal/user/$userId/";
            $dialogId = "chat1270";

            $sendToChat = function(string $dialogId, string $message): void 
            {
                if (!preg_match('~^chat(\d+)$~', $dialogId, $m)) 
                {
                    throw new \InvalidArgumentException('DIALOG_ID должен быть вида chatXXXX');
                }
                $chatId = (int)$m[1];

                \CIMChat::AddMessage(
                [
                    'TO_CHAT_ID' => $chatId,
		            'FROM_USER_ID' => 11302,
                    'MESSAGE'    => $message,
		            //'SYSTEM'     => 'Y',
                ]);
            };
            
            $sendToChat($dialogId, $message); //вызов функции

            // ----- ФУНКЦИЯ ДЛЯ ОТПРАВКИ СООБЩЕНИЯ В ОБЩИЙ ЧАТ -----


            

            //мменяем отдел нового сотрудника. Все будут  в головном отделе структуры
            $fields = 
            [
                'UF_DEPARTMENT' => [1],
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