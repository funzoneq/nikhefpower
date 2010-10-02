''' initiate django meuk '''
from django.core.management import setup_environ
import powerbar.settings

setup_environ(powerbar.settings)

from django.db import models
from powerbar.beheer.models import Powerbar, Customer, Rack

import DatabaseAbstract

db = DatabaseAbstract.DatabaseAbstract()
powerbars = db.getPowerbars()

customer = Customer.objects.get(customer=1)
rack = Rack.objects.get(rack=1)

for p in powerbars:
	p.customer = customer
	p.rack = rack
	p.save()

'''
for i in range(1,49):
	r = Rack(rack=i,room=2,X=0,Y=0);
	r.save()
'''
