''' initiate django meuk '''
from django.core.management import setup_environ
import powerbar.settings

setup_environ(powerbar.settings)

from django.db import models
from powerbar.beheer.models import Powerbar, History

import MySQLdb
from datetime import datetime

class DatabaseAbstract:
	def savePowerbars(self, powerbars):
		for u in powerbars:
			try:	
				p = Powerbar(bar=int(u),rack_id=0,customer_id=0,fase=0,feed='a',distributionunit=0,rail=0)
				p.save()
			except:
				pass

	def saveHistory(self, results):
		time = datetime.now().isoformat()
		for u in sorted(results.keys()):
			h = History(bar_id=u,kwht=int(results[u][3]),kwhd=int(results[u][2]),curr=int(results[u][1]),temp=int(results[u][0]),time=time)
			h.save()

	def getPowerbars(self):
		powerbars = Powerbar.objects.all()
		return powerbars

