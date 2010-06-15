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
		print "Debug command: "+str(command)+" "+str(addrh)+" "+str(addrl)+" "+str(data1)+" "+str(data2)+" "+str(sum);

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
		return self.readPort(7, unit, 61, 0);

	def writeEEProm(self, unit, version):
		return self.readPort(44, unit, 61, version);

	def sendEchoMsg(self, unit):
		return self.readPort(73, unit, 0, 0);

	def scanPort(self, unit):
		result = self.readEEProm(unit);
		#print "Reading unit: "+str(unit) + "\tresult:" + str(result);
	
		if (result == None):
			print "\tEmpty port"
		else:
			if ((result & 255) == 255):
				print "!!Debug case!!"
				# we have a response, but the type is either not initialized or an 'old' switch
				#result = self.sendEchoMsg(unit);

				if (result > 0):
					version = result & 255
				else:
					verion = 0
				
				#print str(result) + " " + str(version);
				#bin.writeEEProm(unit, version)
			else:
				version = result & 255

			if (version == 255):
				version = 1
		
			if (version == 0):
				print "\tMeter found"
			else:
				print "\tSwitch found"

	def rangeScan(self, start, to):
		for i in range(start, to):
			self.scanPort(i);

	def initiateReadOut(self, command):
		result = self.readPort(command, 0, 0, 0)
		print result

	def readOutUnit(self, unit, instruction):
		result = self.readPort(93, unit, 0, 0)
		if(result != None):
			''' temperature '''
			if(instruction == 90):
				return result & 255

			number = 0;
			for j in range(5, -1, -1):
				number = (number * 10) + ((result >> (4 * j)) & 15);

			if(instruction == 68):
				return number / 10000;

			return number

	def getDictTemperature(self, units):
		results = {}
		self.initiateReadOut(90);
		for u in sorted(set(units)):
			results[u] = self.readOutUnit(u, 90);
		print results
			


if __name__ == "__main__":
	bin = BinairePoortMeuk();
	powerbars = [ 1001, 1421, 1453, 1413, 1429 ]
	bin.getDictTemperature(powerbars)

	#bin.scanPort(1001);
	#bin.rangeScan(500, 1000);
