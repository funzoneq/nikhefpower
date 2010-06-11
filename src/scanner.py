from struct import *
import serial
import time

class BinairePoortMeuk:
	def __init__(self):
		self.ser = serial.Serial(1, timeout=0)

	def readPort(self, command, unit, data1, data2):
		addrh = (unit >> 8) & 255;
		addrl = unit & 255;
		sum = (command + addrh + addrl + data1 + data2) & 255;

		print unit,
		print "Debug command: "+str(command)+" "+str(addrh)+" "+str(addrl)+" "+str(data1)+" "+str(data2);

		scan = pack('BBBBBBB', command, addrh, addrl, data1, data2, sum, 13);

		self.ser.write(scan)
		time.sleep(0.1);
		s = self.ser.read(7)

		if(len(s) == 0):
			return

		data = unpack("BBBBBBB", s);

		if (data[2] == 0x99 and data[3] == 0x99 and data[4] == 0x99):
			print("No data available");
			return
		elif (data[0] == 0x00 and data[1] == 0x00 and data[2] == 0x00 and data[3] == 0x00 and data[4] == 0x00):
			print("Unit busy");
			return 
		else:
			return (data[2] << 16) + (data[3] << 8) + data[4];
		
	def readEEProm(self, unit):
		return bin.readPort(7, unit, 61, 0);

	def writeEEProm(self, unit, version):
		return bin.readPort(44, unit, 61, version);

	def sendEchoMsg(self, unit):
		return bin.readPort(73, unit, 0, 0);

	def scanPort(self, unit):
		result = bin.readEEProm(unit);
		print "Reading unit: "+str(unit) + "\tresult:" + str(result);
		print "\n\n"
		version = 99

		#if (result > 0 and result != None):
			#if (result == 255):
				# we have a response, but the type is either not initialized or an 'old' switch
				#result = bin.sendEchoMsg(unit);

				#if (result > 0):
				#	version = result & 255
				#else:
				#	verion = 0
				#
				#print str(result) + " " + str(version);
				#bin.writeEEProm(unit, version)
			#else:
			#	version = result & 255

		if (version == 255):
			version = 1
		
		#if (version == 0):
		#	print "\tMeter found"
		#else:
		#	print "\tSwitch found"

	def rangeScan(self, start, to):
		for i in range(start, to):
			bin.scanPort(i);


if __name__ == "__main__":
	bin = BinairePoortMeuk();
	bin.rangeScan(1001, 1002)
