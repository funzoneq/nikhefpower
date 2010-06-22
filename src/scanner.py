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
	Scan(1, 1700)
