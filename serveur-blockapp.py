from jetracer.nvidia_racecar import NvidiaRacecar
import time
import sys
from multiprocessing import Process, Value
import zmq
import Jetson.GPIO as GPIO

pinrun = 'DAP4_SCLK' #12
pinbouton = 'SPI2_SCK' #13
pinau = 'SPI2_CS1' #16
autrepin = 'SPI2_CS0' #18 

GPIO.setmode(GPIO.TEGRA_SOC)
GPIO.setup(pinrun, GPIO.OUT)
GPIO.output(pinrun, 0)

GPIO.setup(pinau, GPIO.OUT)
GPIO.output(pinau, 0)

GPIO.setup(pinbouton, GPIO.IN)

context = zmq.Context()
socket = context.socket(zmq.REP)
socket.bind("tcp://*:5555")

currentlyRunning = Value('b', False)

def RunScript(script, data):
	#print("running " + script)
	GPIO.output('DAP4_SCLK', 1)
	data.value = True
	exec(script)
	print(f"currentlyRunning from process {currentlyRunning.value}")
	print("Script terminé avec succès")
	data.value = False
	print(f"currentlyRunning from process {currentlyRunning.value}")
	GPIO.output('DAP4_SCLK', 0)

def BumperChock(data):
	print("Detection pare-chocs")
	if currentlyRunning.value:
		runThread.terminate() # sends a SIGTERM
		currentlyRunning.value = False;
		car.throttle = 0.001
		car.throttle = 0
		GPIO.output(pinrun, 0)
		GPIO.output(pinau, 1)
		data.value = False

car = NvidiaRacecar()
car.steering_gain = -0.65
car.steering_offset = -0.25
if car.steering_offset != -0.25 : exit()

print("Car ready")

GPIO.add_event_detect(pinbouton, GPIO.FALLING, callback=lambda x: BumperChock(currentlyRunning), bouncetime=10)
		
while True:
	try:
		print("en attente recv...")
		message = socket.recv()
		GPIO.output(pinau, 0)
		socket.send(b"OK")
		message = message.decode("utf-8")
		
		#print("Received request: %s" % message)
		f = open("/KDesir_Tests/logging.txt", "a")

		t = time.strftime('%d/%m/%Y-%H:%M:%S', time.localtime()) + ","
		log = message.replace("\n", "\n" + t)
		f.write(t + log + "\n")
		f.close()
		
		#print(message)

		if "ArretUrgence" in message:
			runThread.terminate() # sends a SIGTERM
			#socket.send(b"AU_Done")
			print("Arrêt d'urgence déclenché")
			currentlyRunning.value = False;
			raise
		else:
			print(f"currentlyRunning from main script {currentlyRunning.value}")
			if not currentlyRunning.value:
				print(f"currentlyRunning {currentlyRunning.value}")
				runThread=Process(target=RunScript,args=(message, currentlyRunning))
				runThread.start()
			else:
				print("Impossible d'exécuter le script car un autre est déjà en cours")

	except Exception as e:
		print(e)

		car.throttle = 0.001
		car.throttle = 0
		GPIO.output(pinrun, 0)
		GPIO.output(pinau, 1)

	#finally:
#		GPIO.cleanup()

sys.exit("Fin du programme")
