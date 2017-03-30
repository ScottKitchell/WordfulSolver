<?php

/*	
	This page contains the database class structures 
	required to access data on the database.
	
	Example of use:	
	$SQL = new SQL("SELECT Email, Name FROM Users WHERE ID = '0123456789'");
	if($DB::Query($SQL, $result)){
		$Email = $result[0]['Email'];
		$Name = $result[0]['Name'];
	}
*/

// Require the configuration details
define("HOST", "localhost");     // The host you want to connect to.
define("USERNAME", "");    // The database username. 
define("PASSWORD", "");    // The database password. 
define("DATABASE", "");    // The database name.
define("SECURE", TRUE);    // The database encryption. 


// SQL class
class SQL{
	public $Query;
	public $Type;
	
	public function __construct($query) {
		$this->Query = $query;
		$this->Type = $this->DetermineType();
    }
	
	// Method use to determine the type of SQL querey 
	private function DetermineType(){
		if(preg_match("/^\b(?i)SELECT\b/",$this->Query)){
			return "SELECT";
		} else if(preg_match("/^\b(?i)INSERT\b/",$this->Query)){
			return "INSERT";
		} else if(preg_match("/^\b(?i)UPDATE\b/",$this->Query)){
			return "UPDATE";
		} else if(preg_match("/^\b(?i)REPLACE\b/",$this->Query)){
			return "REPLACE";
		} else if(preg_match("/^\b(?i)DELETE\b/",$this->Query)){
			return "DELETE";
		} else {
			return null;
		}
	}
	
	public function Append($string){
		$this->Query .= " ".$string;
	}
	
	public function __toString(){
		 return $this->Query;
	}
}


// Database class
//new DB(); // Initialise
class DB{
	private static $ServerName = HOST;
	private static $DBName = DATABASE;
	private static $Username = USERNAME;
	private static $Password = PASSWORD;
	public static $Secure = SECURE;
	
	//
	// Method for running a querey that returns records i.e. SELECT
	//
	public static function Query($sql, &$result = -1) {
 		// If it's not a select querey use the no result query method
		if($sql->Type != "SELECT"){
			return self::Query_NoResult($sql);
		}
		
		// If no output paramenter is given return false
		if($result == -1){
			//die("No output parameter supplied for Select statement '$sql'");
			return false;
		}
		
		// Setup the database connection
		$connect = new mysqli(self::$ServerName, self::$Username, self::$Password, self::$DBName);
		if($connect->connect_error) {
			//die("Database connection failed: ".$connect->connect_error);
			return false;
		}
		
		// Execute the MySQLi query
		$query = $connect->query($sql);
		
		// Fetch the database records as an associative array
		if($query->num_rows > 0) {
			$i=0;
			while($row = $query->fetch_assoc()){
				foreach($row as $colName => $value){
					$values[$i][$colName] = $value;
				}
				$i++;
			}
			$result = $values;
		}
		
		// Close the database connection
		$connect->close();
		
		// Return true if one or more records were found
		return ($i > 0)? true : false;
    }
	
	//
	// Method for running a querey that has no output values i.e. INSERT
	//
	private static function Query_NoResult($sql) {
		// If it's a select querey return false
		if($sql->Type == "SELECT"){
			die("No output parameter supplied for Select statement '$sql'");
			$connect->close();
			return false;
		}
		
		// Setup the database connection
		$connect = new mysqli(self::$ServerName, self::$Username, self::$Password, self::$DBName);
		if($connect->connect_error) {
			die("Database connection failed: ".$connect->connect_error);
			$connect->close();
			return false;
		}
		
		// Execute the MySQLi querey
		$query = $connect->query($sql); 
		if(!$query) {
			die("Database Error processing '".$sql."'. Details: ".$connect->error."");
			$connect->close();
			return false;
		}
		
		// Close the database connection
		$connect->close();
		
		// Return true
		return true;
    }
	
}



?>