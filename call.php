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
include 'functions/environment.php';
include 'functions/get_leads_fromsql.php';
include 'functions/moveLead.php';
include 'functions/getLead.php';

require('../tokens/access_token.php');

$correct_amount = [
    [0, 3, 6, "Звонок в 10:00"],
    [1, 4, 7, "Звонок в 13:00"],
    [2, 5, 8, "Звонок в 16:00"]
];

/**
 * Функция проверки времени запуска срипта
 * @return int: возвращает режим, основанный на времени запуска
 */
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
    $now_time = "16:00";

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
 * Функция увеличивает значение количества звонокв для лида
 * @param $id_lead: id лида.
 */
function increment_count_call($id_lead){
    require 'functions/connection.php'; // подключаем скрипт
    # подключение к  базе amo
    $link = mysqli_connect($host, $user, $password, $database_amo)
    or die("Ошибка " . mysqli_error($link));

    $query = "update leads set count_call = count_call + 1 where id_lead = $id_lead;";

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
function autoobzvon($leads, $correct_amount, $mode, $access_token) {
    $busy_managers = [];

    foreach ($leads as $lead) {
        if (!in_array($lead['count_call'], $correct_amount[$mode - 1])) {
            continue;
        }
        $id_lead = $lead['id_lead'];
        # получение id контакта и ответственного, для получения их номеров телефонов
        list ($id_contact, $id_user) = getContact($id_lead, $access_token);
        # получение номеров телефонов контакта
        $phones = getPhones($id_contact, $access_token);
        # получение номера менеджера из базы данных asterisk
        $phone_user = get_manager_phone($id_user);

        # для тестов
        $phone_user = '100';
        if ($id_lead == '29101135') {
            $phones = ['123', 1];
            $phone_user = '100';
        }

/*        if (in_array($phone_user, $busy_managers)) {
            print("Менеджер $phone_user уже занят\n");
            continue;
        }*/

        print("Звоним $phones[0] для менедера $phone_user" . "\n");
        # запуск функции из asteriskModule.php
        $a = calling($phone_user,$phones[0], 1);
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
        if ($lead['count_call'] != 9) {
            continue;
        }
        else {
            print($lead['id_lead'] . " - пора переносить на $status_id_next\n");
            moveLead($lead['id_lead'], $status_id_next);
            delete_lead($lead['id_lead']);
        }
    }
}

/**
 * Актуализация списка лидов с этапом обзвона
 * @param $status_id_main: id эатпа с обзвоном
 */
function check_relevance_list($status_id_main) {
    $leads = get_leads_fromsql();

    foreach ($leads as $lead) {
        $status_id = getLead($lead['id_lead'])['status_id'];
        if ($status_id != $status_id_main) {
            print("Лид " . $lead['id_lead'] . " больше не в этапе с обзвоном. Удалить\n");
            delete_lead($lead['id_lead']);
        }
    }
}

$mode = get_determination_of_mode();
check_relevance_list($status_id_main);

$leads = get_leads_fromsql();

# вывести время звонка
print($correct_amount[$mode - 1][3] . "\n");

# запуск обзвона по номерам
autoobzvon($leads, $correct_amount, $mode, $access_token);

if ($mode == 3) {
    # проверка номеров, может какие уже отзвонили свое
    check_leads($status_id_next, $access_token);
}


