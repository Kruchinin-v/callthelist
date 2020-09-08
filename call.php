<?php

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$dateN = date(DATE_RFC822);
$file = '/var/www/html/amocrm/autoamo/answ.json';
$current = file_get_contents($file);
$current .= "\n". $dateN . "\n" . var_export($_POST,true) . "\n";
file_put_contents($file, $current);
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

include 'functions/getContact.php';
include 'functions/getPhones.php';
include 'functions/asteriskModule.php';

require('../tokens/access_token.php');

require_once 'functions/connection.php'; // подключаем скрипт

# подключение к  базе amo
$link = mysqli_connect($host, $user, $password, $database_amo)
or die("Ошибка " . mysqli_error($link));

$query = "SELECT date, id_lead, count_call from leads";

$result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

if(!$result) {
    echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
}

while ($row = $result->fetch_assoc()) {
//    echo " date = " . $row['date'] . "\n";
//    echo " id_lead = " . $row['id_lead'] . "\n";
    print(var_export($row, true));
}

//print(var_export($result,true) );
print("\n");


exit(1);

# получение id контакта и ответственного, для получения их номеров телефонов
list ($id_contact, $id_user) = getContact($id_lead, $access_token);

$id_lead = '29005789';





# получение номеров телефонов контакта
$phones = getPhones($id_contact, $access_token);



/**
 * Подключение к базе данных
 */


# подключение к  базе
$link = mysqli_connect($host, $user, $password, $database_freepbx)
    or die("Ошибка " . mysqli_error($link));

$query = "select id from sip where data=" . $id_user;

$result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

if(!$result) {
    echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
}

# получили внутренний номер пользователя
$phone_user =mysqli_fetch_array($result)[0];





$now_date = date("Y-m-d");
//$phone_user = '101';



$query = "INSERT INTO `phones` (`date`, `phone_number`, `phone_mp`, `count_call`) 
VALUES (
'$now_date',
'$phones[0]',
'$phone_user',
0
)";


$result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

if(!$result) {
    echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
}

# получили внутренний номер пользователя
//$phone_user =mysqli_fetch_array($result)[0];
// запуск функции из asteriskModule.php
//$a = calling($phone_user,$phones[0]);


