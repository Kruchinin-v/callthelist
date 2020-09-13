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

function get_determination_of_mode() {
    date_default_timezone_set(ini_get('date.timezone'));

    $dayofweek = date("l");
    $weekends = ['Saturday', 'Sunday'];

    # на время теста
    $dayofweek = "asd";

    if (in_array($dayofweek, $weekends)) {
        print("Сегодня выходной\n");
        exit(1);
    }

    $now_time = date("H:i");

    # на время теста
    $now_time = "10:00";

    switch ($now_time) {
        case "10:00":
            $mode = 1;
            break;
        case "13:00":
            $mode = 2;
            break;
        case "16:00":
            $mode = 3;
            break;
        default:
            $mode = 0;
            print("Не верное время запуска $now_time\n");
            exit(1);
    }
    return $mode;
}

function get_leads_fromsql() {
    require_once 'functions/connection.php'; // подключаем скрипт
    # подключение к  базе amo
    $link = mysqli_connect($host, $user, $password, $database_amo)
    or die("Ошибка " . mysqli_error($link));

    $query = "SELECT date, id_lead, count_call from leads";

    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

    if(!$result) {
        echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
    }

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}



$mode = get_determination_of_mode();
$leads = get_leads_fromsql();


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


