''' inititate poller meuk '''
import BinairePoortMeuk
import DatabaseAbstract

def Poller():
	bin = BinairePoortMeuk.BinairePoortMeuk();
	db = DatabaseAbstract.DatabaseAbstract();

	meters = db.getPowerbars()

	temp = bin.getDictTemperature(meters)
	curr = bin.getDictCurrent(meters)
	kwhd = bin.getDictKWhDisplay(meters)
	kwht = bin.getDictKWhTotal(meters)

	resultpermeter = {}

	for u in meters:
		resultpermeter[u.bar] = (temp[u.bar],curr[u.bar],kwhd[u.bar],kwht[u.bar])

	db.saveHistory(resultpermeter);

if __name__ == "__main__":
	Poller()
