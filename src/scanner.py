''' initiate django meuk '''
from django.core.management import setup_environ
import powerbar.settings

setup_environ(powerbar.settings)

from django.db import models
from powerbar.beheer.models import Powerbar, History

''' inititate scanner meuk '''
import BinairePoortMeuk
import DatabaseAbstract

def Scan(start=1, end=1700):
	bin = BinairePoortMeuk.BinairePoortMeuk()
	db = DatabaseAbstract.DatabaseAbstract()

	bin.rangeScan(start, end)
	meters = bin.getMeters()

	db.savePowerbars(meters)

if __name__ == "__main__":
	Scan()
