<?php

// These variables may/should be altered to suite your needs.
// In case of doubt consult the manual or contact your supplier

// ***************************** MySQL Server variables **************************

$sqlhost = "localhost";				// The SQL server Hostname, usually localhost
$sqlport = "3306";				// The port for the MySQL server, usually 3306
$sqluser = "root";				// The user able to controle the database
$sqlpass = "";					// The user's password to controle the database
$sqlbase = "PDU_db";				// The name of the database in MySQL


// ************************ Table information for automatic creation *************


$measureunits		= "munits";		// Table name for the Kwh measure units
$switchingunits		= "sunits";		// Table name for the switch units
$pdulogging		= "pdulog";		// Table name for the measured logs


$unitindication		= "unit";		// Field indicating the unit
$versioning		= "version";		// Field indicating Unit's version
$onlinestatus		= "online";		// Field indicating wheter aunit is present
$namedunit		= "name";		// Field indicating optional unit name
$pdunitid		= "pduid";		// Field indicating PDU id
$loggingid		= "id";			// Field indicating the ID for the logs
$daytimestamp		= "dtstamp";		// Field indicating the Day and Time of the recorded log
$kwhtotalusage		= "kwh_total";		// Field indicating the logged total Kwh usage
$kwhtotaldisplay	= "kwh_display";	// Field indicating the logged Kwh display
$currentcurrent		= "current";		// Field indicating the logged current 
$tempindication		= "temperature";	// Field indicating the logged temperature
		

// ***************************** Hardware adres for the Rs232 port *****************

$comadr  = "COM1";				// The COM port: windows >> COMX, Linux >> /dev/ttySX
						// Where X = the comport number

// ******************************** scanning options *******************************

$deffrom = "1000";				// Default scanning start position
$deftoo  = "1010";				// Default scanning end position





?>
