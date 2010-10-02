''' inititate poller meuk '''
import BinairePoortMeuk

def CommandLine():
	bin = BinairePoortMeuk.BinairePoortMeuk()
	bin.switchOutlets(1317, 255, 0)

if __name__ == "__main__":
        CommandLine()
