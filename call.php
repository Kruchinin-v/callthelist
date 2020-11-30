<?php

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$dateN = date(DATE_RFC822);
$file = '/var/www/html/amocrm/callthelist/answ_call.json';
$current = file_get_contents($file);
$current .= "\n". $dateN . "\n" . var_export($_POST,true) . "\n";
file_put_contents($file, $current);
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

include 'functions/getContact.php';
include 'functions/getPhones.php';
include 'functions/asteriskModule.php';
include 'functions/environment.php';
include 'functions/get_leads_fromsql.php';
include 'functions/moveLead.php';
include 'functions/getLead.php';

require('/var/www/html/amocrm/tokens/access_token.php');

# переменная хранит массив с временем запуска скрипта.
# время запуска достаточно указывать только здесь
$times = [
    ["time" => "11:00", "mode" => 1],
    ["time" => "15:00", "mode" => 2],
];


/**
 * Функция проверки времени запуска срипта
 * @param $times
 * @return int: возвращает режим, основанный на времени запуска
 */
function get_determination_of_mode($times) {
    date_default_timezone_set(ini_get('date.timezone'));

    $dayofweek = date("l");
    $weekends = ['Saturday', 'Sunday'];

    # на время теста
//    $dayofweek = "asd";

    if (in_array($dayofweek, $weekends)) {
        print("Сегодня выходной\n");
        exit(1);
    }

    $now_time = date("H:i");
    $mode = 0;

    # на время теста
//    $now_time = "14:11";
//    $now_time = $times[0]['time'];

    foreach ($times as $time) {
        if ($now_time == $time['time']) {
            $mode = $time['mode'];
        }
    }
    if ($mode == 0) {
        print("Не верное время запуска $now_time\n");
        exit(1);
    }

    return $mode;
}


/**
 * Функия для поиска в бд asterisk внутренний номер менеджера
 * @param $id_user: id amocrm пользователя
 * @return mixed: внутренний номер менеджера
 */
function get_manager_phone($id_user) {
    /**
     * Подключение к базе данных
     */
    require 'functions/connection.php'; // подключаем скрипт

    # подключение к  базе
    $link = mysqli_connect($host, $user, $password, $database_freepbx)
    or die("Ошибка " . mysqli_error($link) . "\n");

    $query = "select id from sip where data=" . $id_user;

    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

    if(!$result) {
        echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
    }

    # получили внутренний номер пользователя
    return mysqli_fetch_array($result)[0];
}


/**
 * Функция прибавляет количество дней, если сегодня это первый звонок
 * @param $id_lead
 */
function check_day($id_lead) {
    require 'functions/connection.php'; // подключаем скрипт
    # подключение к  базе amo
    $link = mysqli_connect($host, $user, $password, $database_amo)
    or die("Ошибка " . mysqli_error($link));

    $now_date = date("Y-m-d");
    $query = "select date_last_call from leads where id_lead = $id_lead;";
    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

    $result = $result->fetch_assoc();

    # если сегодня небыло звонков
    if ($result['date_last_call'] != $now_date) {
        $query = "update leads set count_day = count_day + 1 where id_lead = $id_lead;";
        $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));
    }
}

/**
 * Функция увеличивает значение количества звонокв для лида
 * @param $id_lead: id лида.
 */
function increment_count_call($id_lead){
    require 'functions/connection.php'; // подключаем скрипт
    # подключение к  базе amo
    $link = mysqli_connect($host, $user, $password, $database_amo)
    or die("Ошибка " . mysqli_error($link));

    $now_date = date("Y-m-d");

    check_day($id_lead);

    $query = "update leads set count_call = count_call + 1, date_last_call = '$now_date' where id_lead = $id_lead;";

    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

    if(!$result) {
        echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
    }
}

/**
 * Перебирает лиды из списка и запускает звонки. Прибавляет количество звонков
 * @param $leads: Список лидов из бд
 * @param $correct_amount: корректные количества звонков
 * @param $mode: режим определение вермени звонка
 * @param $access_token: ключ для зпросов
 */
function autoobzvon($leads, $access_token) {
    $busy_managers = [];

    foreach ($leads as $lead) {
        /*if (!in_array($lead['count_call'], $correct_amount[$mode - 1])) {
            continue;
        }*/
        $id_lead = $lead['id_lead'];
        # получение id контакта и ответственного, для получения их номеров телефонов
        list ($id_contact, $id_user) = getContact($id_lead, $access_token);
        # получение номеров телефонов контакта
        $phones = getPhones($id_contact, $access_token);
        # получение номера менеджера из базы данных asterisk
        $phone_user = get_manager_phone($id_user);

        # для тестов
//        $phone_user = '100';
//        if ($id_lead == '29101135') {
//            $phones = ['123', 1];
//            $phone_user = '100';
//        }

/*        if (in_array($phone_user, $busy_managers)) {
            print("Менеджер $phone_user уже занят\n");
            continue;
        }*/

        print("Звоним $phones[0] для менедера $phone_user" . "\n");
        # запуск функции из asteriskModule.php
//        $a = calling($phone_user,$phones[0], 1);
        # добавить менеджера в список занятых, чтобы ему не было направленно 2 звонка
        $busy_managers[] = $phone_user;
        # прибавить количество сделаных вызовов
        increment_count_call($id_lead);
    }
}

/**
 * Удаляет лид из списка обзвона
 * @param $id_lead: id лида, которого нужно удалить из базы
 */
function delete_lead($id_lead) {
    require 'functions/connection.php'; // подключаем скрипт
    # подключение к  базе amo
    $link = mysqli_connect($host, $user, $password, $database_amo)
    or die("Ошибка " . mysqli_error($link));

    $query = "delete from leads where id_lead = $id_lead";

    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

    if(!$result) {
        echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
    }
}

/**
 * Ищет лиды, который полностью прозвонены
 * @param $status_id_next: этап, на который нужно перекинуть лида
 * @param $access_token: токен для работы с api
 */
function check_leads($status_id_next, $access_token) {
    print("Запускаем проверку после запуска звонка\n");
    $leads = get_leads_fromsql();

    foreach ($leads as $lead) {
        if ($lead['count_call'] != 5) {
            continue;
        }
        else {
            print($lead['id_lead'] . " - пора переносить на $status_id_next\n");
            moveLead($lead['id_lead'], $status_id_next, $access_token);
            delete_lead($lead['id_lead']);
        }
    }
}

/**
 * Актуализация списка лидов с этапом обзвона
 * @param $status_id_main : id эатпа с обзвоном
 * @param $access_token
 */
function check_relevance_list($status_id_main, $access_token) {
    $leads = get_leads_fromsql();

    foreach ($leads as $lead) {
        $status_id = getLead($lead['id_lead'], $access_token)['status_id'];
        if ($status_id != $status_id_main) {
            print("Лид " . $lead['id_lead'] . " больше не в этапе с обзвоном. Удалить\n");
            delete_lead($lead['id_lead']);
        }
    }
}

/**
 * Проверяет нужно ли звонить лиду
 * @param array $leads
 * @return array $leads
 */
function filter_leads($leads, $mode, $times) {

    foreach ($leads as $key => $value) {
        $now = date("Y-m-d");
        $calls_a_day = count($times); # сколько звонков в день должно быть
        $date_create = $value["date_create"];
        $date_last_call = $value["date_last_call"];
        $count_call = $value["count_call"];
        $count_day = $value["count_day"];

        $now = DateTime::createFromFormat("Y-m-d", $now);
        $date_create = DateTime::createFromFormat("Y-m-d", $date_create);
        $date_last_call = DateTime::createFromFormat("Y-m-d", $date_last_call);

        $interval_create = $now->diff($date_create); // количество дней с создания
        $interval_last = $now->diff($date_last_call); // количество дней с последнего звонка

        # если лид добавили сегодня
        if ($interval_create->d == 0) {
            unset($leads[$key]);
            continue;
        }

        # если он еще не прозванивался
        if ("$count_call" == "0") {
            continue;
        }

        # если сегодня звонил меньше чем должен был
        if ( $now == $date_last_call and  ($count_day * $count_call) < ($calls_a_day * $count_day * $count_day) ) {
            continue;
        }

        # если еще не прошло 2 дня с предыдущего звонка или уже прозвонил 3 дня
        if ($interval_last->d < 2  or $count_day == 3) {
            unset($leads[$key]);
            continue;
        }

    }

    return $leads;
}

$mode = get_determination_of_mode($times);

check_relevance_list($status_id_main, $access_token);

$leads = get_leads_fromsql();

# фильтрация лидов, по которым не нужно звонить
$leads = filter_leads($leads, $mode, $times);

# вывести время звонка
print("Звонок в " . $times[$mode -1]['time'] . "\n");

# запуск обзвона по номерам
autoobzvon($leads, $access_token);

/*if ($mode == 3) {
    # проверка номеров, может какие уже отзвонили свое
    check_leads($status_id_next, $access_token);
}*/


