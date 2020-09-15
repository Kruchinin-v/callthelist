#! /usr/bin/env python3

import sys
import time
import random
import subprocess
from threading import Thread

class MyThread(Thread):
    """
    A threading example
    """

    def __init__(self, pathPHP, script):
        """Инициализация потока"""
        Thread.__init__(self)
        self.pathPHP = pathPHP
        self.script = script

    def run(self):
        """Запуск потока"""
        command = f"{self.pathPHP} {self.script}"
        answer = subprocess.run(command, shell=True, stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE, encoding='UTF-8')
        print(answer.stdout)

def main():
    """
    Запускаем программу
    Для примера запуска потока
    """
    print("Begin")

    """
    Создаем группу потоков
    """
    for i in range(5):
        name = f"Thread №{i}"
        my_thread = MyThread(name)
        my_thread.setDaemon(True)
        my_thread.start()

    print("End")


if __name__ == "__main__":
    try:
        main()
    except:
        raise
