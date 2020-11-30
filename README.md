#  CallTheList 
**Description:**  
Проект, который добавляет сделки в список обзвона при перемещении их на определенный этап,
 с помощью вебхука и скрипта `newleads.php`. Затем с помощью демона `pydemon`, который
 запускает скрипт `call.php` в определенное время, обзванивает клиентов данной сделки.
 
## Настройка
Необходимо заполнить значения переменных в файле `functions/environment.php`:  
1. `$pipeline_id` - id воронки, на которой находится функционал.   
Получить id всех воронок можно скриптом `functions/getPipelines.php`. Например:
2. `$status_id_main` - id этапа, на котором находятся номера для обзвона. Сделка, которая будет находится на этом этапе будет прозваниваться по времени.
3. `$status_id_next` - id этапа, на который попадают сделки после обзвона
```
# Пример использования скриптов для получения значений
# получить id Воронок
curl http://ats.karnavalnn.ru/amocrm/callthelist/functions/getPipelines.php

# получить id этапов воронки с id 3571748
curl http://ats.karnavalnn.ru/amocrm/callthelist/functions/getStatuses.php?id=3571748
```
---
#### Заполнение переменных времени
Заполнить переменную в файле `call.php`
```php
# пример заполнения
$times = [
    ["time" => "11:00", "mode" => 1],
    ["time" => "15:00", "mode" => 2],
];
```
---
В файле deminconfig.php находятся настройки запуска скрипта по времени, в данном случае `call.php`. 
Вот тут указано какой стрипт запускать. Чем(php) и путь скрипта. Скрипт запускается в потоке.
```
my_thread = MyThread("/usr/bin/php", path_call)
```
Так же проверить все переменные ниже. Пид файл, лог файл. Создать папку для логов.
 
---
#### Создание базы данных
Для создании базы необходимо:
- создать пустую базу
- натравить на нее файл `install/callthelist.sql`
```
mysql -e "create database callthelist;"
mysql callthelist < install/callthelist.sql
```
#### Настройка запуска через systemd
Есть готовый файл service, в нем нужно прописать путь до pid файла демона и путь до самого демона.
```
cp install/callthelist.service /etc/systemd/system
systemctl daemon-reload
# запускаем демона
systemctl start callthelist.service
# включаем автозапуск демона
systemctl enable callthelist.service

```