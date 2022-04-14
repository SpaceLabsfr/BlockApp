from jetracer.nvidia_racecar import NvidiaRacecar
import time
import sys
from multiprocessing import Process, Value
import zmq

context = zmq.Context()
socket = context.socket(zmq.REP)
socket.bind("tcp://*:5555")

currentlyRunning = Value('b', False)

def RunScript(script, data):
	#print("running " + script)
	data.value = True
	exec(script)
	print(f"currentlyRunning from process {currentlyRunning.value}")
	print("Script terminé avec succès")
	data.value = False
	print(f"currentlyRunning from process {currentlyRunning.value}")

car = NvidiaRacecar()
print("Car ready")

while True:
	try:
		print("en attente recv...")
		message = socket.recv()
		socket.send(b"OK")
		message = message.decode("utf-8")
		#print("Received request: %s" % message)
		
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
        
	time.sleep(1) # vérifier si on a toujours besoin de ça

sys.exit("Fin du programme")
