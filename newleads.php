<?php

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$dateN = date(DATE_RFC822);
$file = '/var/www/html/amocrm/autoamo/answ.json';
$current = file_get_contents($file);
$current .= "\n". $dateN . "\n" . var_export($_POST,true) . "\n";
file_put_contents($file, $current);
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

require('/var/www/html/amocrm/tokens/access_token.php');

/**
 * Проверяет, указан ли id leads верно
 * @return mixed: id leads который получил из запроса
 */
function check_params() {
    if (isset($_POST['leads']['status'][0]['id'])) {
        $id_lead = $_POST['leads']['status'][0]['id'];
        return $id_lead;
    }
    else {
        echo "Нет необходимых данных\n";
        exit(1);
    }
}

/**
 * Проверяет наличие добавляемого лида в списке. Если уже есть, то завершает работу скрипта
 * @param $id_lead: id лида, которого хотят добавить
 * @return int: если такого лида нет в базе, тогда 0
 */
function check_lead_sql($id_lead) {
    include 'functions/get_leads_fromsql.php';
    $leads = get_leads_fromsql();

    foreach ($leads as $lead) {
        if ($lead['id_lead'] == $id_lead) {
            print("Такой лид уже есть в списке обзвона");
            exit(1);
        }
    }
    return 0;
}

/**
 * Добавляет лид в список для обзвона
 * @param $id_lead: id лида, которого нужно добавить в базу
 */
function add_lead($id_lead) {
    /**
     * Подключение к базе данных
     */
    require 'functions/connection.php'; // подключаем скрипт

# подключение к  базе amo
    $link = mysqli_connect($host, $user, $password, $database_amo)
    or die("Ошибка " . mysqli_error($link));

    $now_date = date("Y-m-d");

    $query = "INSERT INTO `leads` (`date`, `id_lead`, `count_call`) 
VALUES (
'$now_date',
'$id_lead',
0
)";


    $result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link));

    if(!$result) {
        echo "<p>Выполнение запроса прошло не успешно</p>\n\n";
    }
}

//$id_lead = '28933281';

$id_lead = check_params();

check_lead_sql($id_lead);

add_lead($id_lead);



