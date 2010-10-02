from django.template import Context, loader
from django.http import HttpResponse
import DatabaseAbstract
from operator import itemgetter, attrgetter

db = DatabaseAbstract.DatabaseAbstract()

def index(request, unit=1465):
	jsonout = db.getPowerbarHistoryJson(unit, 2)
	history24 = db.getPowerbarHistory(unit, 1)

	t = loader.get_template('grafiek.html')
	c = Context({'data': jsonout, 'history24': history24})
	return HttpResponse(t.render(c))

def rackview(request):
	rv = db.getRackView()
	rack1 = rv[:12]
	rack2 = rv[12:23]
	rack3 = rv[23:35]
	rack4 = rv[35:48]
	rack1.reverse()
	rack2.reverse()
	rack3.reverse()
	rack4.reverse()
	t = loader.get_template('rackview.html')
        c = Context({'latest_poll1': rack1, 'latest_poll2': rack2, 'latest_poll3': rack3, 'latest_poll4': rack4})
        return HttpResponse(t.render(c))

def perrack(request, rack):
	powerbars = db.getPowerbarsPerRack(rack);
	data = {}
	
	for p in powerbars:
		data[p.bar] = db.getPowerbarHistoryJson(p.bar, 2)

	t = loader.get_template('rackdetail.html')
        c = Context({'powerbars': powerbars, 'meuk': data})

	return HttpResponse(t.render(c))

def static(request, path):
	load = 'statisch/'
	load += path
	t = loader.get_template(load);
	c = Context({});
	return HttpResponse(t.render(c))
