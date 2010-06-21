import MySQLdb

class DatabaseAbstract:
	def __init__(self):
		try:
			self.conn = MySQLdb.connect (host="localhost", user="powerbars", passwd="mYRttBr", db="powerbars")
			self.cursor = self.conn.cursor ()
		except MySQLdb.Error, e:
			print "Error %d: %s" % (e.args[0], e.args[1])

	def __del__(self):
		self.cursor.close()
   		self.conn.commit()
   		self.conn.close()

	def savePowerbars(self, powerbars):
		for u in powerbars:
			#try:
			p = Powerbar(bar=int(u))
			p.save()
			#	self.cursor.execute ("INSERT INTO `powerbar` (`barId`, `rackId`, `customerId`, `fase`, `feed`, `verdeelkast`, `rail`) VALUES ('%d',  '',  '',  '',  '',  '',  '');" % int(u));
			#except MySQLdb.Error, e:
			#	print "Error %d: %s" % (e.args[0], e.args[1])

	def saveHistory(self, results):
		for u in sorted(results.keys()):
			#h = History()
			try:
				self.cursor.execute (""" INSERT INTO `history` (`barId`, `kwhtotal`, `kwhdisplay`, `current`, `temperature`, `time`) 
					    VALUES
					    ('%d',  '%d',  '%d',  '%d',  '%d', UNIX_TIMESTAMP( )); """ % ( u, int(results[u][3]), int(results[u][2]), int(results[u][1]), int(results[u][0]) ) );
				self.conn.commit()
			except MySQLdb.Error, e:
				print u, " ", results[u]
                        	print "Error %d: %s" % (e.args[0], e.args[1])

	def getPowerbars(self):
		powerbars = []
		self.cursor.execute("SELECT barId FROM `powerbar`")
		rows = self.cursor.fetchall ()
		for row in rows:
			powerbars.append(row[0])
		return powerbars

