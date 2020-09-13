<?php
/**
 * Функция берет из базы лиды, по котором нужны звонки
 * @return array: массив лидов
 */
function get_leads_fromsql() {
    require 'connection.php'; // подключаем скрипт
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