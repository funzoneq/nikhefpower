''' inititate scanner meuk '''
from struct import *
import serial
import time
import MySQLdb
import getopt, sys

class BinairePoortMeuk:
	def __init__(self):
		self.ser = serial.Serial(1, timeout=0)
		self.meters = []

	def readPort(self, command, unit, data1, data2):
		addrh = (unit >> 8) & 255;
		addrl = unit & 255;
		sum = (command + addrh + addrl + data1 + data2) & 255;

		scan = pack('BBBBBBB', command, addrh, addrl, data1, data2, sum, 13);

		self.ser.write(scan)
		time.sleep(0.1);
		s = self.ser.read(7)

		if(len(s) == 0):
			#print(unit,"Empty result");
			return

		try:
			data = unpack("BBBBBBB", s);
		except struct.error:
			return

		if (data[2] == 0x99 and data[3] == 0x99 and data[4] == 0x99):
			print(unit,"No data available");
			return
		elif (data[0] == 0x00 and data[1] == 0x00 and data[2] == 0x00 and data[3] == 0x00 and data[4] == 0x00):
			print(unit,"Unit busy");
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

		if (unit == 0):
			print result
	
		if (result != None):
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
				self.meters.append(unit)
				print unit,"\tMeter found"
			else:
				self.meters.append(unit)
				print unit,"\tSwitch found"

	def rangeScan(self, start, to):
		for i in range(start, to):
			self.scanPort(i);

	def initiateReadOut(self, command):
		result = self.readPort(command, 0, 0, 0)

	def readOutUnit(self, unit, instruction):
		result = self.readPort(93, unit, 0, 0)

		''' check if unit busy '''
		if(result == None):
			return 0
		else:
			''' temperature '''
			if(instruction == 90):
				return result & 255

			number = 0;
			for j in range(5, -1, -1):
				number = (number * 10) + ((result >> (4 * j)) & 15);

			if(instruction == 68):
				return number / 10000.0;

			return number
	
	def getAbstractValue(self, units, instruction):
		results = {}
                self.initiateReadOut(instruction);
		for u in units:
			tempvar = self.readOutUnit(u.bar, instruction);
			if(tempvar != None):
				results[u.bar] = tempvar;
                return results

	def getDictTemperature(self, units):
		return self.getAbstractValue(units, 90)
	
	def getDictCurrent(self, units):
                return self.getAbstractValue(units, 68);

	def getDictKWhDisplay(self, units):
                return self.getAbstractValue(units, 143);

	def getDictKWhTotal(self, units):
		return self.getAbstractValue(units, 56);

	def getMeters(self):
		return self.meters;
