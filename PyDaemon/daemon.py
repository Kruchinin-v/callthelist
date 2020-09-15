#!/usr/bin/env python3

import sys
import os
import time
import atexit
import signal
import subprocess

class SignalHandler:
        
                                                   
    SIGNALS = () 
    
    def register(self, signum, callback):                                                                              
        self.SIGNALS += (SigAction(signum, callback), )                       

    def getActions(self):                                                       
        return self.SIGNALS                                           

    def handler(self, signum, frame):
        assert 0, "You must define a handler(signum, frame) method in %s" %(self)

    def __repr__(self):                                             
        return "<Class:%s>" %(self.__class__.__name__)        

class SigAction(SignalHandler):
    
    
    def __init__(self, signum, callback):                                                                  
        self.signum = signum                                                  
        self.callback = callback                                              
        signal.signal(self.signum, self.handler)                              

    def handler(self, signum, frame):
        self.callback()

    def __repr__(self):
        return "<Class:%s signal:%s>" %(self.__class__.__name__, self.signum)

class Daemon:
    
    
    def __init__(self, pidfile, stdin='/dev/null', stdout='/dev/null', stderr='/dev/null'):
        self.stdin = stdin
        self.stdout = stdout
        self.stderr = stderr
        self.pidfile = pidfile
        
    def metaInit(self, sigdict):
        self.sigDict = sigdict
    
    def daemonize(self):
        """
        do the UNIX double-fork magic, see Stevens' "Advanced 
        Programming in the UNIX Environment" for details (ISBN 0201563177)
        http://www.erlenstar.demon.co.uk/unix/faq_2.html#SEC16
        """
        try: 
            pid = os.fork() 
            if pid > 0:
                # exit first parent
                sys.exit(0) 
        except OSError as e:
            sys.stderr.write("fork #1 failed: %d (%s)\n" % (e.errno, e.strerror))
            sys.exit(1)
    
        # decouple from parent environment
        os.chdir("/") 
        os.setsid() 
        os.umask(0) 
    
        # do second fork
        try: 
            pid = os.fork() 
            if pid > 0:
                # exit from second parent
                sys.exit(0) 
        except OSError as e:
            sys.stderr.write("fork #2 failed: %d (%s)\n" % (e.errno, e.strerror))
            sys.exit(1) 
    
        # redirect standard file descriptors
        sys.stdout.flush()
        sys.stderr.flush()
        si = open(self.stdin, 'r')
        so = open(self.stdout, 'a+')
        se = open(self.stderr, 'a+')
        os.dup2(si.fileno(), sys.stdin.fileno())
        os.dup2(so.fileno(), sys.stdout.fileno())
        os.dup2(se.fileno(), sys.stderr.fileno())
    
        # write pidfile
        atexit.register(self.delpid)
        pid = str(os.getpid())
        open(self.pidfile, 'w+').write("%s\n" % pid)

    def delpid(self):
        os.remove(self.pidfile)
        
    def signalAssign(self):
        assignee = SignalHandler()
        for i in iter(self.sigDict):
            assignee.register(i, self.sigDict[i])

    def start(self):
        """
        Start the daemon
        """
        # Check for a pidfile to see if the daemon already runs
        try:
            pf = open(self.pidfile, 'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:
            pid = None
    
        if pid:
            check = f"ps -p {pid}"
            answer = subprocess.run(check, shell=True, stdout=subprocess.PIPE,
                                    stderr=subprocess.PIPE, encoding='UTF-8')
            if len(answer.stdout) != 28:
                message = "pidfile %s already exist. Daemon already running?\n"
                sys.stderr.write(message % self.pidfile)
                sys.exit(1)
        
        # Start the daemon
        self.daemonize()
        self.signalAssign()
        self.run()

    def status(self):
        try:
            pf = open(self.pidfile, 'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:

            pid = None

        if pid:
            check = f"ps -p {pid}"
            answer = subprocess.run(check, shell=True, stdout=subprocess.PIPE,
                                    stderr=subprocess.PIPE, encoding='UTF-8')
            if len(answer.stdout) != 28:
                message = "status: ranning\n"
                sys.stdout.write(message)
                sys.exit(1)
            else:
                message = "status: ranning\n"
                sys.stdout.write(message)
                sys.exit(1)
        else:
            message = "status: not ranning\n"
            sys.stdout.write(message)
            sys.exit(1)

    sigDict = {}
    
    def stop(self):
        """
        Stop the daemon
        """
        # Get the pid from the pidfile
        try:
            pf = open(self.pidfile,'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:
            pid = None
    
        if not pid:
            message = "pidfile %s does not exist. Daemon not running?\n"
            sys.stderr.write(message % self.pidfile)
            return # not an error in a restart
            
        # Try killing the daemon process
        try:
            while 1:
                os.kill(pid, signal.SIGTERM)
                time.sleep(0.1)
                print("Successfully stopped")
        except OSError as err:
            err = str(err)
            if err.find("No such process") > 0:
                if os.path.exists(self.pidfile):
                    os.remove(self.pidfile)
            else:
                print(str(err))
                sys.exit(1)

    def restart(self):
        """
        Restart the daemon
        """
        self.stop()
        self.start()

    def run(self):
        print("dummy")
