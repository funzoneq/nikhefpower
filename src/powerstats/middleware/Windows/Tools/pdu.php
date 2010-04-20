<?php
require "php_serial.class.php";
include("var.php");

// *** CONSTANTS ***
// ************************** Included from the VAR file ********************************


define('DB_HOST_NAME'		, $sqlhost);	
define('DB_PORT'		, $sqlport);	
define('DB_USERNAME'		, $sqluser);	
define('DB_PASSWORD'		, $sqlpass);	
define('DB_DATABASE'		, $sqlbase);
define('SERIAL_PORT'		, $comadr);
define('DEFAULT_SCAN_FROM'	, $deffrom);
define('DEFAULT_SCAN_TO'	, $deftoo);

// *************************** Database variables from the VAR file ***********************

// *************************** Tables

$TABLE_M_UNITS		= $measureunits;
$TABLE_S_UNITS		= $switchingunits;
$TABLE_PDU_LOG		= $pdulogging;

// *************************** Colums

$FIELD_UNIT		= $unitindication;
$FIELD_UNIT_VERSION	= $versioning;
$FIELD_ONLINE		= $onlinestatus;
$FIELD_UNIT_NAME	= $namedunit;
$FIELD_PDU		= $pdunitid;
$FIELD_LOG_ID		= $loggingid;
$FIELD_TIME		= $daytimestamp;
$FIELD_KWH_TOTAL	= $kwhtotalusage;
$FIELD_KWH_DISPLAY	= $kwhtotaldisplay;
$FIELD_CURRENT		= $currentcurrent;
$FIELD_TEMPERATURE	= $tempindication;



// *************************** END VAR included variables ***************************

define('COMM_NR_OF_RETRIES'			, 3);
define('COMM_WAIT_RESPONSE_DELAY'		, 80000);		// in microseconds
define('COMM_WAIT_BETWEEN_MESSAGES'		, 110000);		// in microseconds
define('COMM_RETRY_DELAY'			, 150000);		// in microseconds

// Commands
define("CMD_CREATE_TABLES"			, 1);
define("CMD_SCAN_BUS"				, 2);
define("CMD_READ_DATA"				, 3);
define("CMD_MONITOR"				, 4);
define("CMD_SWITCH_OUTLETS"			, 5);
define("CMD_QUERY_SWITCH"			, 6);
define("CMD_RESET_DISPLAY"			, 7);



// *** FUNCTIONS ***

// Description: exits the PHP script with the given error
// In:          $msg_txt			- error text to display
// Out:         -
function message_die($msg_text = "")
{
	print($msg_text);
	exit;
}


// Description: connects to database server and selects the database
// In:          $create_db		(OPTIONAL)	- true if the database has to be created first, false if not
// Out:         link identifier
function connect_to_database($create_db = false)
{
	// establish connection with database server
	if (!($link = mysql_connect(DB_HOST_NAME .":". DB_PORT, DB_USERNAME, DB_PASSWORD)))
	{
		message_die("Could not connect to the database\n");
	}

	// create database if requested
	if ($create_db)
	{
		$sql = "CREATE DATABASE ". DB_DATABASE;
		if (!mysql_query($sql)) message_die(mysql_error($db) ."\n");
	}
	
	// select database
	if (!mysql_select_db(DB_DATABASE, $link))
	{
		message_die("Database does not exist\n");
	}
	
	return $link;
}


// Description: sends a message to a unit and handles the response
// In:          $serial			- serial port object
//				$instruction	- instruction to send
//				$addrh			- high byte of the unit number
//				$addrl			- low byte of the unit number
//				$data1			- first data byte
//				$data2			- second data byte
//				$text			- text to print with every retry
// Out:         -1 when no response,
//				-2 when no data is available
//				-3 when unit is busy
//				otherwise the value '3rd byte << 16 + 4th byte << 8 + 5th byte' of the response
function send_message($serial, $instruction, $addrh, $addrl, $data1, $data2, $text)
{
	usleep(COMM_WAIT_BETWEEN_MESSAGES);

	$sum = ($instruction + $addrh + $addrl + $data1 + $data2) & 255;
	$message = pack("CCCCCCC", $instruction, $addrh, $addrl, $data1, $data2, $sum, 13);
	$try = 0;
	$result = -1;
	do
	{
		print($text);
		
		$serial->sendMessage($message);
		
		usleep(COMM_WAIT_RESPONSE_DELAY);
		
		$read = $serial->readPort(7);
		
		if (strlen($read) == 7)
		{
			$data = unpack("C*", $read);	// first item has index 1 !!!
			
			if ($data[3] == 0x99 and $data[4] == 0x99 and $data[5] == 0x99)
			{
//				print("No data available");
				$result = -2;
			}
			else if ($data[1] == 0x00 and $data[2] == 0x00 and $data[3] == 0x00 and $data[4] == 0x00 and $data[5] == 0x00)
			{
//				print("Unit busy");
				$result = -3;
			}
			else
			{
				$result = ($data[3] << 16) + ($data[4] << 8) + $data[5];
			}
		}
		
		if ($result < 0 and ++$try < COMM_NR_OF_RETRIES)
		{	// retry
			usleep(COMM_RETRY_DELAY);
		}
	} while ($result < 0 and $try < COMM_NR_OF_RETRIES);
	
	return $result;
}


// Description: creates the PDU tables in the database
// In:          $create_db		(OPTIONAL)	- true if the database has to be created first, false if not
// Out:         -
function do_create_tables($create_db = false)
{
	global	$TABLE_M_UNITS, $TABLE_S_UNITS, $TABLE_PDU_LOG;
	global	$FIELD_UNIT_VERSION, $FIELD_UNIT, $FIELD_ONLINE, $FIELD_UNIT_NAME, $FIELD_PDU;
	global	$FIELD_LOG_ID, $FIELD_TIME, $FIELD_KWH_TOTAL, $FIELD_KWH_DISPLAY, $FIELD_CURRENT, $FIELD_TEMPERATURE;

	print("Creating tables...\n\n");
	
	$db = connect_to_database($create_db);
	
	$sql = "CREATE TABLE $TABLE_M_UNITS (
				$FIELD_UNIT INT unsigned NOT NULL,
				$FIELD_UNIT_VERSION INT,
			    $FIELD_UNIT_NAME varchar(80) default NULL,
				$FIELD_ONLINE varchar(1) default 'Y',
			    $FIELD_PDU INT unsigned,
			    PRIMARY KEY ($FIELD_UNIT))";
	if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");

	$sql = "CREATE TABLE $TABLE_S_UNITS (
				$FIELD_UNIT INT unsigned NOT NULL,
				$FIELD_UNIT_VERSION INT,
				$FIELD_UNIT_NAME varchar(80) default NULL,
				$FIELD_ONLINE varchar(1) default 'Y',
				$FIELD_PDU INT unsigned,
				PRIMARY KEY ($FIELD_UNIT))";
	if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");

	$sql = "CREATE TABLE $TABLE_PDU_LOG (
				$FIELD_LOG_ID INT unsigned NOT NULL auto_increment,
				$FIELD_TIME timestamp(14) NOT NULL,
				$FIELD_UNIT INT unsigned default NULL,
				$FIELD_KWH_TOTAL decimal(8,1) default NULL,
				$FIELD_KWH_DISPLAY decimal(8,1) default NULL,
				$FIELD_CURRENT decimal(8,1) default NULL,
				$FIELD_TEMPERATURE decimal(8,1) default NULL,
				PRIMARY KEY ($FIELD_LOG_ID))";
	if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");

	mysql_close($db);
}


// Description: scans the AP databus for units
// In:          $scan_from		- unit number to start with
//				$scan_to		- unit number to end with
//              $erase			- true = erase tables first, false = don't erase
// Out:         -
function do_scan_bus($scan_from, $scan_to, $erase)
{
	global	$TABLE_M_UNITS, $TABLE_S_UNITS;
	global	$FIELD_UNIT_VERSION, $FIELD_UNIT;

	print("Scanning for devices... (Ctrl-C to stop)\n\n");
	
	$db = connect_to_database();

	if ($erase)
	{
		// empty tables first
		$sql = "DELETE FROM $TABLE_M_UNITS";
		if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
		$sql = "DELETE FROM $TABLE_S_UNITS";
		if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
	}

	print("from $scan_from to $scan_to\n");
	
	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();

	for ($unit = $scan_from; $unit <= $scan_to; $unit++)
	{
		print("$unit\t");

		$addrh = ($unit >> 8) & 255;
		$addrl = $unit & 255;
		
		$result = send_message($serial, 7, $addrh, $addrl, 61, 0, "_");					// read eeprom location 61
		
		if ($result > 0)
		{
			if (($result & 255) == 255)
			{	// we have a response, but the type is either not initialized or an 'old' switch
				$result = send_message($serial, 73, $addrh, $addrl, 0, 0, ".");			// send echo message
				
				if ($result > 0)
					$version = $result & 255;
				else
					$version = 0;														// set as meter
					
				$result = send_message($serial, 44, $addrh, $addrl, 61, $version, "");	// write eeprom location 61
			}
			else
			{
				$version = $result & 255;
			}
			
			if ($version == 255) $version = 1;											// previously the old switches were type 1, we'll keep it that way
			
			if ($version == 0)
			{
				print("\tMeter found");
				$true = "Y";
				$sql = "INSERT IGNORE $TABLE_M_UNITS ($FIELD_UNIT) values ($unit)";
				if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
				$sql = "UPDATE $TABLE_M_UNITS SET $FIELD_UNIT_VERSION=$version WHERE $FIELD_UNIT=$unit";
				if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
			}
			else
			{
				print("\tSwitch found");
				$sql = "INSERT IGNORE $TABLE_S_UNITS ($FIELD_UNIT) values ($unit)";
				if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
				$sql = "UPDATE $TABLE_S_UNITS SET $FIELD_UNIT_VERSION=$version WHERE $FIELD_UNIT=$unit";
				if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
			}
		}
		print("\n");
	}

	$serial->deviceClose();
	mysql_close($db);
}


// Description: reads the databuffer of all measure units
// In:          $db				- handle of the database connection
//				$serial			- handle of the serial port connection
//              $instruction	- instruction number that has been sent to the unit
// Out:         -
function read_all_units($db, $serial, $instruction)
{
	global	$TABLE_M_UNITS;
	global	$FIELD_UNIT, $FIELD_UNIT_VERSION, $FIELD_ONLINE;
	global	$a_unit, $a_temp, $a_kwhtotal, $a_kwhdisplay, $a_current;

	$sql = "SELECT $FIELD_UNIT,$FIELD_UNIT_VERSION FROM $TABLE_M_UNITS";
	if (!($mysql_result = mysql_query($sql))) { print(mysql_error($db) ."\n"); return; }

	$i = 0;
	while ($row = mysql_fetch_array($mysql_result))
	{
		$unit = $row[0];
		$a_unit[$i] = $unit;

		print "$unit\t";
		
		$addrh = ($unit >> 8) & 255;
		$addrl = $unit & 255;

		$result = send_message($serial, 93, $addrh, $addrl, 0, 0, "");					// read databuffer
		
		if ($result >= 0)
		{
			if ($instruction == 90)														// temperature
			{
				$a_temp[$i] = ($result & 255);
			}
			else
			{
				$number = 0;
				for ($j = 5; $j >= 0; $j--)
					$number = ($number * 10) + (($result >> (4 * $j)) & 15);
					
				switch ($instruction)
				{
					case 56:															// KWh total
						$a_kwhtotal[$i] = $number;
						break;
					case 143:															// KWh display
						$a_kwhdisplay[$i] = $number;
						break;
					case 68:															// current
						$a_current[$i] = $number / 10000;
						break;
				}
			}
			$online = "Y";
			print "ok\n";
		}
		else
		{
			switch ($instruction)
			{
				case 90:	$a_temp[$i] = 0;		break;
				case 56:	$a_kwhtotal[$i] = 0;	break;
				case 143:	$a_kwhdisplay[$i] = 0;	break;
				case 68:	$a_current[$i] = 0;		break;
			}
			$online = "N";
			print "missing\n";
		}

		// update online status of this unit
		$sql = "UPDATE $TABLE_M_UNITS SET $FIELD_ONLINE='$online' WHERE $FIELD_UNIT=$unit";
		if (!mysql_query($sql)) print(mysql_error($db) ."\n");
		
		$i++;
	}
	mysql_free_result($mysql_result);
	
	print("\n");
}


// Description: reads the data from all meters listed in the database
// In:          -
// Out:         -
function do_read_data()
{
	global	$TABLE_PDU_LOG;
	global	$FIELD_UNIT, $FIELD_KWH_TOTAL, $FIELD_KWH_DISPLAY, $FIELD_CURRENT, $FIELD_TEMPERATURE;
	global	$a_unit, $a_temp, $a_kwhtotal, $a_kwhdisplay, $a_current;

	print("Reading...\n\n");

	$db = connect_to_database();

	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();

	// get all data from the units
	print("kWh total\n");
	$result = send_message($serial, 56, 0, 0, 0, 0, "");								// read kWh total
	read_all_units($db, $serial, 56);													// read kWh total from all units

	print("kWh display\n");
	$result = send_message($serial, 143, 0, 0, 0, 0, "");								// read kWh display
	read_all_units($db, $serial, 143);													// read kWh display from all units

	print("Current\n");
	$result = send_message($serial, 68, 0, 0, 0, 0, "");								// read current
	read_all_units($db, $serial, 68);													// read current from all units

	print("Temperature\n");
	$result = send_message($serial, 90, 0, 0, 0, 0, "");								// read temperature
	read_all_units($db, $serial, 90);													// read temperature from all units

	// store the data in the database
	for ($i = 0; $i < count($a_unit); $i++)
	{
		if ($a_unit[$i] > 0)
		{
			$sql = "INSERT INTO $TABLE_PDU_LOG ($FIELD_UNIT, $FIELD_KWH_TOTAL, $FIELD_KWH_DISPLAY, $FIELD_CURRENT, $FIELD_TEMPERATURE) ".
			       "VALUES ($a_unit[$i],$a_kwhtotal[$i],$a_kwhdisplay[$i],$a_current[$i],$a_temp[$i])";
			if (!($mysql_result = mysql_query($sql))) print(mysql_error($db) ."\n");
		}
	}

	$serial->deviceClose();
	mysql_close($db);
}


// Description: updates the online status for all units
// In:          -
// Out:         -
function do_monitor()
{
	global	$TABLE_M_UNITS, $TABLE_S_UNITS;
	global	$FIELD_UNIT, $FIELD_UNIT_VERSION, $FIELD_ONLINE;

	print("Monitoring devices...\n\n");

	$db = connect_to_database();

	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();

	$sql = "SELECT $FIELD_UNIT,$FIELD_UNIT_VERSION FROM $TABLE_M_UNITS UNION SELECT $FIELD_UNIT,$FIELD_UNIT_VERSION FROM $TABLE_S_UNITS";
	if (!($mysql_result = mysql_query($sql))) message_die(mysql_error($db) ."\n");

	while ($row = mysql_fetch_array($mysql_result))
	{
		$unit = $row[0];
		$unit_version = $row[1];
		
		print("$unit\t");

		$addrh = ($unit >> 8) & 255;
		$addrl = $unit & 255;

		$result = send_message($serial, 7, $addrh, $addrl, 0, 0, "");					// read eeprom location 0
		
		if ($result >= 0)
		{
			print("found");
			$online = "Y";
		}
		else
		{
			print("missing");
			$online = "N";
		}

		// update online status in database		
		if ($unit_version == 0)
			$sql = "UPDATE $TABLE_M_UNITS SET $FIELD_ONLINE='$online' WHERE $FIELD_UNIT=$unit";
		else
			$sql = "UPDATE $TABLE_S_UNITS SET $FIELD_ONLINE='$online' WHERE $FIELD_UNIT=$unit";
		if (!mysql_query($sql)) print(mysql_error($db) ."\n");
		
		print("\n");
	}
	mysql_free_result($mysql_result);

	$serial->deviceClose();
	mysql_close($db);
}


// Description: prints the outlet header on the screen
// In:          $unit		- unit number to switch outlet(s) of
// Out:         -
function print_outlet_header($unit)
{
	print("Unit number $unit, relais 1 to 8:\n");
}


// Description: prints the outlet status on the screen
// In:          $value		- current state of the outlets
// Out:         -
function print_outlets($value)
{
	for ($i = 0; $i < 8; $i++)
	{
		printf(" %d ", ($value & 0x01));
		$value >>= 1;
	}
	print "\n";
}


// Description: switches the given outlet(s)
// In:          $unit		- unit number to switch outlet(s) of
//				$value		- 1 to turn outlet(s) on, 0 to turn outlet(s) off
//				$outlet		- 255 to switch all outlets, otherwise the number of the outlet to switch
// Out:         -
function do_switch_outlets($unit, $value, $outlet)
{
	global	$TABLE_S_UNITS;
	global	$FIELD_UNIT, $FIELD_UNIT_VERSION;

	print("Switching outlet(s)\n");
	
	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();

	print_outlet_header($unit);

	if ($value) $value = 255;														// value for switching all on
	if ($outlet != 255) $outlet = (1 << ($outlet - 1)) & 0xFF;						// convert outlet number to bit value

	if ($value == 255)
		print("Switching outlet(s) ON:   ");
	else
		print("Switching outlet(s) OFF:  ");
	print_outlets($outlet);

	$addrh = ($unit >> 8) & 255;
	$addrl = $unit & 255;

	$result = send_message($serial, 114, $addrh, $addrl, 0, 0, "");					// unlock
	$result = send_message($serial, 19, $addrh, $addrl, $outlet, $value, "");		// switch outlet(s)
	
	if ($result < 0) print("no reply from unit!");

	$serial->deviceClose();
}


// Description: queries the given unit for the status of the outlets
// In:          $unit		- unit number of the switch to query
// Out:         -
function do_query_switch($unit)
{
	print("Getting outlet status\n\n");

	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();
	
	$addrh = ($unit >> 8) & 255;
	$addrl = $unit & 255;

	$result = send_message($serial, 21, $addrh, $addrl, 0, 0, "");					// read switch
	
	if ($result >= 0)
	{
		print_outlets($result);
	}
	else
	{
		switch ($result)
		{
			case -1:	print("No reply from unit!\n");		break;
			case -3:	print("Zero reply\n");				break;
			default:	print("No status available...\n");	break;
		}
	}
	
	$serial->deviceClose();
}


// Description: resets the kWh display value to zero of the given units
// In:          $unit_from			- (start) unit number of the meter(s) to reset
//				$unit_to (OPTIONAL)	- end unit number of the meters to reset
// Out:         -
function do_reset_display($unit_from, $unit_to = 0)
{
	print("Clearing kWh display...\n\n");

	if (!$unit_to) $unit_to = $unit_from;
	
	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();
	
	for ($unit = $unit_from; $unit <= $unit_to; $unit++)
	{
		print("$unit\t");

		$addrh = ($unit >> 8) & 255;
		$addrl = $unit & 255;

		$result = send_message($serial, 33, $addrh, $addrl, 0, 0, "");					// reset kWh display
		
		if ($result >= 0)
			print("ok\n");
		else
			print("failed\n");
	}
	
	$serial->deviceClose();
}


// ************
// *** MAIN ***
// ************

print("PDU v1.0\n\n");

// default settings
$unit_from	= DEFAULT_SCAN_FROM;
$unit_to	= DEFAULT_SCAN_TO;
$erase		= false;
$create_db	= false;

if ($argc > 1)
{
	// process parameters
	for ($i = 1; $i < $argc; $i++)
	{
		switch ($argv[$i])
		{
			case "-c":
				$command = CMD_CREATE_TABLES;
				break;

			case "-d":
				$create_db = true;
				break;
				
			case "-s":
				$command = CMD_SCAN_BUS;
				break;

			case "-f":
				$unit_from = (int)$argv[++$i];
				break;

			case "-t":
				$unit_to = (int)$argv[++$i];
				break;

			case "-e":
				$erase = true;
				break;
				
			case "-r":
				$command = CMD_READ_DATA;
				break;
				
			case "-m":
				$command = CMD_MONITOR;
				break;

			case "-x":
				$command = CMD_SWITCH_OUTLETS;
				break;

			case "-u":
				$unit = (int)$argv[++$i];
				break;

			case "-v":
				$value = strtoupper($argv[++$i]) == "ON" ? 1 : 0;
				break;

			case "-o":
				$outlet = strtoupper($argv[++$i]) == "ALL" ? 255 : (int)$argv[$i];
				break;
				
			case "-q":
				$command = CMD_QUERY_SWITCH;
				if ($i + 1 < $argc)	$unit = (int)$argv[++$i];
				break;
				
			case "-z":
				$command = CMD_RESET_DISPLAY;
				unset($unit_from);
				unset($unit_to);
				break;
		}
	}
	
	// check if enough parameters are passed
	$ok = true;
	switch ($command)
	{
		case CMD_SWITCH_OUTLETS:
			if (!isset($unit) or !isset($value) or !isset($outlet)) $ok = false;
			break;
		
		case CMD_QUERY_SWITCH:
			if (!isset($unit)) $ok = false;
			break;
			
		case CMD_RESET_DISPLAY:
			if (!isset($unit) and (!isset($unit_from) or !isset($unit_to))) $ok = false;
			break;
	}

	if (!$ok)
	{
		print("Too few arguments!\n\n");
		unset($command);
	}
	
	// perform associated actions
	if (isset($command))
	{
		switch ($command)
		{
			case CMD_CREATE_TABLES:
				do_create_tables($create_db);
				break;
				
			case CMD_SCAN_BUS:
				do_scan_bus($unit_from, $unit_to, $erase);
				break;
				
			case CMD_READ_DATA:
				do_read_data();
				break;
				
			case CMD_MONITOR:
				do_monitor();
				break;
				
			case CMD_SWITCH_OUTLETS:
				do_switch_outlets($unit, $value, $outlet);
				break;
				
			case CMD_QUERY_SWITCH:
				do_query_switch($unit);
				break;
				
			case CMD_RESET_DISPLAY:
				if (isset($unit))
					do_reset_display($unit);
				else
					do_reset_display($unit_from, $unit_to);
				break;
		}
	}
}

if ($argc <= 1 or !isset($command))
{
	// no (correct) parameters given, so display usage info


printf("\nThis product has been brought to you by:\n\n
###############################################################################\n###########Bh99&################################################Hh3hA##########\n#######X.          :H#########;                    #########i           s######\n#####2                H#######;                    #######s               i####\n####.                  r######s                    ######                  :###\n###.                    s#####5                    #####                    r##\n##i        i###M:        #####5       #################s        i###A        H#\n##        #######2        ####5       #################        #######.      ;#\n#h       2########,       ####5       ################A       S########.iH#####\n#;       #########A       9###5                   ####r       #################\n#.       ##########       s###5                   ####,       #################\n#        ##########       r###5                   ####.       #################\n#.       ##########       s###5                   ####,       #################\n#:       #########A       X###5       ################r       #########ih######\n#2       G########;       ####5       ################&       G########    ;9##\n##        ########        ####5       #################        #######,       #\n##;        M####h        2####5       A################r       .#####;       ;#\n###                     .#####5                    H####                     ##\n####                   .######5                    ######                   ###\n#####,                r#######5                    #######,                ####\n#######:            r#########s                    B#######M,            h#####\n##########Gir;;;i&#############H#################B3############3s;;;rSA########\n###############################################################################\n######################### Original Electronic Concepts ########################\n############################# http://www.oec.nu ###############################\n###############################################################################\n

\nThis product is protected by copyright law. \n\nUse this product at own risk. \n\n");


	printf("Welcome to the OEC PDU controller:\n\n");
	print("Options:\n");
	print("   -c [-d]                               create tables, -d creates the database first\n");
	print("   -s [-f from] [-t to] [-e]             scan for units on bus, -e erases the tables first\n");
	print("   -r                                    read data from units\n");
	print("   -m                                    monitor devices, get online status\n");
	print("   -x -u unit -v ON|OFF -o outlet|ALL    switch outlet(s)\n");
	print("   -q unit                               query outlet status\n");
	print("   -z [-u unit]|[[-f from] [-t to]]      reset kWh display(s) to zero\n");
	printf("\n\n This software is protected by copyright law.");

}
else
{
	print("\nDone\n");
}

?>
