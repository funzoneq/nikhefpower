''' initiate django meuk '''
from django.core.management import setup_environ
import powerbar.settings

setup_environ(powerbar.settings)

from django.db import models
from powerbar.beheer.models import Powerbar, History, HistoryCompressed, Rack, PollerRun, Customer

from datetime import datetime, timedelta
from django.db import connection, transaction

import simplejson as json

class DatabaseAbstract:
	def savePowerbars(self, powerbars):
		rack = Rack.objects.get(rack=1)
		customer = Customer.objects.get(customer=1)
		for u in powerbars:
			try:	
				p = Powerbar(bar=int(u),rack=rack,customer=customer,fase=0,feed='a',rail=0)
				p.save()
			except:
				pass

	def saveHistory(self, results):
		time = datetime.now().isoformat()
		pr = PollerRun()
		pr.save();
		
		for u in sorted(results.keys()):
			h = History(bar_id=u,kwht=int(results[u][3]),kwhd=int(results[u][2]),curr=int(results[u][1]),temp=int(results[u][0]),time=time,run=pr)
			h.save()

	def getPowerbars(self):
		return Powerbar.objects.all()
	
	def getPowerbarsPerRack(self, rack):
		return Powerbar.objects.filter(rack=rack)

	def getPowerbarHistory(self, unit, fromdate):
		h = History.objects.filter( time__gte=(datetime.now()-timedelta(days=fromdate)), bar=unit ).order_by("-id")
		return h

	def getPowerbarCompressedHistory(self, unit, fromdate):
		return HistoryCompressed.objects.filter( time__gte=(datetime.now()-timedelta(days=fromdate)), bar=unit )
	
	def getPowerbarHistoryJson(self, unit, fromdate):
		history = self.getPowerbarCompressedHistory(unit, fromdate)
		jsonout = {}
		for h in history:
 	               jsonout[h.id] = {'kwht': h.kwht, 'kwhd': h.kwhd, 'curr': h.curr, 'temp': str(h.temp), 'time': h.time.strftime("%m/%d %H:%M")}
		
		return json.dumps(jsonout)

	def getCustomer(self, customerid):
		return Customer.objects.get(customer=customerid)
	
	def getMaxRun(self):
		return PollerRun.objects.order_by('-id')[0]
	
	def getRackView(self):
		maxrun = self.getMaxRun()
		sql  = 'SELECT h . * , rack_id '
		sql += 'FROM `beheer_history` h, `beheer_powerbar` p, `beheer_rack` r '
		sql += 'WHERE `run_id` = %d '
		sql += 'AND h.bar_id = p.bar AND p.rack_id = r.rack'

		allhist = History.objects.raw( sql % maxrun.id )

		perrack = {}
		result = []
		for h in allhist:
			if h.rack_id not in perrack:
                                perrack[h.rack_id] = {'curr': [], 'temp': [], 'kwht': [], 'kwhd': [], 'time': []}

			perrack[h.rack_id]['curr'].append( h.curr )
			perrack[h.rack_id]['temp'].append( h.temp )
			perrack[h.rack_id]['kwht'].append( h.kwht )
			perrack[h.rack_id]['kwhd'].append( h.kwhd )
			perrack[h.rack_id]['time'].append( h.time )
	
		for k,v in perrack.items():
			curr = sum(v['curr']) / len(v['curr'])
			temp = sum(v['temp']) / len(v['temp'])
			kwht = max(v['kwht'])
			kwhd = max(v['kwhd'])
			time = max(v['time'])
			result.append( Rackhistory(k, round(curr, 1), round(temp, 1), kwht, kwhd, time) )
		
		return result

class Rackhistory:
	def  __init__(self, rack, curr, temp, kwht, kwhd, time):
		self.rack = rack
		self.curr = curr
		self.temp = temp
		self.kwht = kwht
		self.kwhd = kwhd
		self.time = time
	
	def __repr__(self):
		'Return a nicely formatted representation string'
		return 'Rackhistory(rack=%r, curr=%r, temp=%r, kwht=%r, kwhd=%r, time=%r)' % self
