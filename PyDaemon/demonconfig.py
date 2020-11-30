import sys
import os
import subprocess
import time
from custom_thread import MyThread
from time import strftime

path_call = "/var/www/html/amocrm/callthelist/call.php"

class SigFunctionsCon:

    def __init__(self, ourdaemon):
        self.__ourdaemon = ourdaemon

    def SIGTERM(self):
        now_time = strftime("%H:%M %d.%m", time.localtime())
        sys.stdout.write(f"Остановлен в {now_time}\n")
        sys.exit(0)


class ReactFunctionCon:

    def __init__(self, ourdaemon):
        self.__ourdaemon = ourdaemon

    def start(self):
        self.__ourdaemon.start()

    def stop(self):
        self.__ourdaemon.stop()

    def status(self):
        self.__ourdaemon.status()

    def restart(self):
        self.__ourdaemon.restart()

    def stmess(self, message):
        print(message)
        self.__ourdaemon.start()


def parse_time():
    expression = r"egrep -o '^\s*\[\"time\" => \"[0-9]{1,2}:[0-9]{1,2}\"' " \
                 + path_call + " | egrep -o '[0-9]{1,2}:[0-9]{1,2}'"

    answer = subprocess.run(expression, shell=True,
                            stdout=subprocess.PIPE,
                            stderr=subprocess.DEVNULL, encoding='UTF-8')

    if answer.returncode != 0:
        return 3

    times = answer.stdout.split('\n')
    times.pop()

    if len(times) == 0:
        return 3

    return times


class StatCon:
    commands = "{start|stop|status}"
    strHelp = f"usage: {sys.argv[0]} {commands}"

    def time_alignment(self):
        """
        функция выравнивает время до ровной минуты
        :return:
        """
        now_time = strftime("%S", time.localtime())
        while now_time != '59':
            now_time = strftime("%S", time.localtime())
            # print(now_time)
            time.sleep(1)

    def run(self):
        now_time = strftime("%H:%M %d.%m", time.localtime())
        print(f"\nЗапуск pydaemon в {now_time}")
        # время, когда нужно будет запускать скрипт
        time_run = parse_time()
        if time_run == 3:
            print("Не удалось получить время из скрипта php, взято дефолтное")
            time_run = ["11:00", "15:00"]
        print(f"время запуска: {time_run}")
        while True:
            self.time_alignment()
            now_time = strftime("%H:%M", time.localtime())
            if (now_time in time_run) and True:
                print(f"Запуск php скрипта в {now_time}")
                my_thread = MyThread("/usr/bin/php", path_call)
                my_thread.start()
            time.sleep(53)
            now_time = strftime("%H:%M", time.localtime())

    pidFile = "/var/log/python-daemon/daemon-naprimer.pid"

    inputter = "/dev/null"

    outputter = "/var/log/python-daemon/out-daemon.log"

    errorer = "/var/log/python-daemon/err-daemon.log"


if __name__ == "__main__":
    parse_time()




