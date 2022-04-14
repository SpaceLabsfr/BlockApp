#
#   Hello World client in Python
#   Connects REQ socket to tcp://localhost:5555
#   Sends "Hello" to server, expects "World" back
#

import zmq

context = zmq.Context()

#  Socket to talk to server
print("Connecting to hello world server…")
socket = context.socket(zmq.REQ)
socket.connect("tcp://localhost:5555")



socket.send(b"time.sleep(1)\ncar.steering_gain = -0.65\ncar.steering_offset = -0.25\nif car.steering_offset != -0.25 : exit()\ncar.throttle = -0.5\ntime.sleep(15)")

#  Get the reply.
message = socket.recv()
print("Received reply [ %s ]" % (message))
