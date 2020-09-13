<?php

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$dateN = date(DATE_RFC822);
$file = '/var/www/html/amocrm/autoamo/answ.json';
$current = file_get_contents($file);
$current .= "\n". $dateN . "\n" . var_export($_POST,true) . "\n";
file_put_contents($file, $current);
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

exit(1);
require('../tokens/access_token.php');

if (isset($_POST['leads']['status'][0]['id'])) {
    $id_lead = $_POST['leads']['status'][0]['id'];
}
else {
    echo "Нет необходимых данных\n";
    return 0;
}

//$id_lead = '28933281';

/**
 * Подключение к базе данных
 */
require_once 'functions/connection.php'; // подключаем скрипт

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



