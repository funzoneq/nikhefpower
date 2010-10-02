''' initiate django meuk '''
from django.core.management import setup_environ
import powerbar.settings

setup_environ(powerbar.settings)

from django.db import models
from powerbar.beheer.models import History, HistoryCompressed

import time
from datetime import datetime, timedelta
import sys

class DataManager:
	def CompressHistory(self):
		moment = {}
		gisteren = datetime.now() - timedelta(days=1);
		
		moment[0] = datetime(gisteren.year, gisteren.month, gisteren.day, 0, 0, 0)
		moment[1] = datetime(gisteren.year, gisteren.month, gisteren.day, 5, 59, 59)
		moment[2] = datetime(gisteren.year, gisteren.month, gisteren.day, 11, 59, 59)
		moment[3] = datetime(gisteren.year, gisteren.month, gisteren.day, 17, 59, 59)
		moment[4] = datetime(gisteren.year, gisteren.month, gisteren.day, 23, 59, 59)
			
		for k in range(4):
			history = History.objects.raw("""SELECT id, bar_id, max(kwht) AS kwht, max(kwhd) AS kwhd, avg(curr) AS curr, avg(temp) AS temp
        	        FROM `beheer_history`
        	        WHERE time BETWEEN %s AND %s
        	        GROUP BY bar_id""", [moment[k], moment[k+1]])
			
			for h in history:
				n = HistoryCompressed(bar_id=h.bar_id, kwht=h.kwht, kwhd=h.kwhd, curr=h.curr, temp=h.temp, time=moment[k+1])
				n.save()

def DM():
	DM = DataManager()
	DM.CompressHistory()

if __name__ == "__main__":
        DM()

