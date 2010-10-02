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

$FIELD_UNIT			= $unitindication;
$FIELD_UNIT_VERSION	= $versioning;
$FIELD_ONLINE		= $onlinestatus;
$FIELD_UNIT_NAME	= $namedunit;
$FIELD_PDU			= $pdunitid;
$FIELD_LOG_ID		= $logginid;
$FIELD_TIME			= $daytimestamp;
$FIELD_KWH_TOTAL	= $kwhtotalusage;
$FIELD_KWH_DISPLAY	= $kwhtotaldisplay;
$FIELD_CURRENT		= $currentcurrent;
$FIELD_TEMPERATURE	= $tempindication;



// ******************** End data variables from the VAR file **************************


define('COMM_NR_OF_RETRIES'				, 3);
define('COMM_WAIT_RESPONSE_DELAY'		, 120000);		// in microseconds
define('COMM_WAIT_BETWEEN_MESSAGES'		, 120000);		// in microseconds
define('COMM_RETRY_DELAY'				, 150000);		// in microseconds




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
	//	print($text);
		
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

//		print "$unit\t";
		
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
			//print "ok\n";
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
			//print "missing\n";
		}

		// update online status of this unit
		$sql = "UPDATE $TABLE_M_UNITS SET $FIELD_ONLINE='$online' WHERE $FIELD_UNIT=$unit";
		if (!mysql_query($sql)) print(mysql_error($db) ."\n");
		
		$i++;
	}
	mysql_free_result($mysql_result);
	
	//print("\n");
}


// Description: reads the data from all meters listed in the database
// In:          -
// Out:         -
function do_read_data()
{
	global	$TABLE_PDU_LOG;
	global	$FIELD_UNIT, $FIELD_KWH_TOTAL, $FIELD_KWH_DISPLAY, $FIELD_CURRENT, $FIELD_TEMPERATURE;
	global	$a_unit, $a_temp, $a_kwhtotal, $a_kwhdisplay, $a_current;

//	print("Reading...\n\n");

	$db = connect_to_database();

	$serial = new phpSerial;
	$serial->deviceSet(SERIAL_PORT);
	$serial->configure();
	$serial->deviceOpen();

	// get all data from the units
//	print("kWh total\n");
	$result = send_message($serial, 56, 0, 0, 0, 0, "");								// read kWh total
	read_all_units($db, $serial, 56);													// read kWh total from all units

//	print("kWh display\n");
	$result = send_message($serial, 143, 0, 0, 0, 0, "");								// read kWh display
	read_all_units($db, $serial, 143);													// read kWh display from all units

//	print("Current\n");
	$result = send_message($serial, 68, 0, 0, 0, 0, "");								// read current
	read_all_units($db, $serial, 68);													// read current from all units

//	print("Temperature\n");
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



do_read_data();

?>