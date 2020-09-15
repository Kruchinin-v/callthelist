<?php
function moveLead($id_lead, $id_new_status, $access_token)
{
    /* Для начала нам необходимо инициализировать данные, необходимые для составления запроса. */
    $subdomain = 'korolevadarya'; #Наш аккаунт - поддомен

    /* Формируем ссылку для запроса */
    $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads/' . $id_lead;

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];


    $data = [
        'status_id' => (int)$id_new_status
    ];

    $curl = curl_init();
    /* Устанавливаем необходимые опции для сеанса cURL */
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'autoamo');
    curl_setopt($curl, CURLOPT_URL, $link);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__DIR__) . '/cookie.txt');
    curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__DIR__) . '/cookie.txt');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    /* Вы также можете передать дополнительный HTTP-заголовок IF-MODIFIED-SINCE, в котором указывается дата в формате D, d M Y
    H:i:s. При
    передаче этого заголовка будут возвращены сделки, изменённые позже этой даты. */
//curl_setopt($curl, CURLOPT_HTTPHEADER, array('IF-MODIFIED-SINCE: Mon, 01 Aug 2013 07:07:23'));
    /* Выполняем запрос к серверу. */
    $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    /* Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */

    $code = (int)$code;
    $errors = array(
        301 => 'Moved permanently',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable',
    );
    $Response = json_decode($out, true);
    try {
        /* Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке */
        if ($code != 200 && $code != 204) {
            print(var_export($Response,true));
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
        }
    } catch (Exception $E) {
        die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode()  . "\n");
    }
    /*
    Данные получаем в формате JSON, поэтому, для получения читаемых данных,
    нам придётся перевести ответ в формат, понятный PHP
    */


    return $code;
}

# для ручного запуска
//moveLead(29005789,34818343 );