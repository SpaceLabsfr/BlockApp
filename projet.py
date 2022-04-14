import zmq
context = zmq.Context()
print('Connecting to hello world server…')
socket = context.socket(zmq.REQ)
socket.connect('tcp://localhost:5555')
script = b"#car.throttle = -0.5#time.sleep(1)#car.steering = -1#"
script = script.replace('#','\n')
socket.send(script)
message = socket.recv()
print("Received reply [ %s ]" % (message))#