import zmq

context = zmq.Context()

#  Socket to talk to server
print("Connecting to hello world server")
socket = context.socket(zmq.REQ)
socket.connect("tcp://localhost:5555")

f = open("/KDesir_Tests/projet.py", "r")
script = bytes(f.read(), 'utf-8')

socket.send(script)

#  Get the reply.
message = socket.recv()
print("Received reply [ %s ]" % (message))
