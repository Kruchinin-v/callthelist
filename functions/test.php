<?php

# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$dateN = date(DATE_RFC822);
$file = '/var/www/html/ovod-krv.ru/autoamo/answ.json';
$current = file_get_contents($file);
$current .= "\n". $dateN . "\n" . var_export($_POST,true) . "\n";
$current .= "\n". $dateN . "\n" . var_export($_GET,true) . "\n";
file_put_contents($file, $current);
# ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~