<?php
class wstation{
 
    // database connection and table name
    private $conn;
    private $table_name = "weatherstations";
 
    // object properties

/* | idAWDN        | varchar(7)  | NO   | PRI | NULL    |       |
| stnName       | varchar(20) | YES  |     | NULL    |       |
| stnLat        | varchar(10) | YES  |     | NULL    |       |
| stnLong       | varchar(10) | YES  |     | NULL    |       |
| stnStartDate  | date        | YES  |     | NULL    |       |
| stnEndDate    | date        | YES  |     | NULL    |       |
| stnElev       | varchar(10) | YES  |     | NULL    |       |
| stnStatus     | varchar(45) | YES  |     | 1       |       |
| stnState      | varchar(45) | YES  |     | NE      |       |
| stnDataSource | varchar(45) | YES  |     | AWDN    |       |
*/
    public $idAWDN;
    public $stnName;
    public $stnLat;
    public $stnLong;
    public $stnStartDate;
    public $stnEndDate;
    public $stnElev;
    public $stnStatus;
    public $stnState;
    public $stnDataSource;
 
    // constructor with $db as database connection
    public function __construct($db){
        $this->conn = $db;
    }

   // read wstations
   function read(){
 
    // select all query
    $query = "SELECT
                *
            FROM
                " . $this->table_name . " p
            ORDER BY
                p.idAWDN";
 
    // prepare query statement
    $stmt = $this->conn->prepare($query);
 
    // execute query
    $stmt->execute();
 
    return $stmt;
}

   function querry() { 

	$query = "SELECT * from ". $this->table_name ;

  	if (!empty($this->idAWDN) && !empty($this->stnName))
	  $wclause = " WHERE idAWDN = '". $this->idAWDN . "' and stnName = '". $this->stnName ."'";
	else if (!empty($this->idAWDN))
  	  $wclause = " WHERE idAWDN = '". $this->idAWDN. "'";	
	else if (!empty($this->stnName))
          $wclause = " WHERE stnName like '%". $this->stnName . "%'";
	else if (!empty($this->stnState))	
          $wclause = " WHERE stnState = '". $this->stnState . "'";

	$query = $query. $wclause;
    	$stmt = $this->conn->prepare($query);
    	$stmt->execute();

	return $stmt; 
  }

  //Function for nearest weather station
  function queryByField($userLat,$userLong, $selectState="ALL") {
	$distance=array();
	$lat=array();
	$long=array();
	$b_date=array();
	$e_date=array();
	$stationKeys=array();
	$stationsQuery  = 'SELECT stnLat, stnLong from weatherstations where stnStatus = "1" AND idAWDN IN (SELECT DISTINCT (idAWDN) AS idAWDN FROM weatherdata.normdatane)';
	if($selectState != "ALL"){
  		$stationsQuery = $stationsQuery . ' AND stnState = "' . $selectState . '";';
	}

    	$stmt = $this->conn->prepare($stationsQuery);
    	$result = $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
  		array_push($lat,$row['stnLat']);
  		array_push($long,$row['stnLong']);
	//Returns distance in miles.
  		$dis= $this->distance($userLat,$userLong,$row['stnLat'],$row['stnLong']);
  		array_push($distance,$dis);
	}
	$index = array_search(min($distance), $distance);
	$this->stnLat=$lat[$index];
	$this->stnLong=$long[$index];
	$query = "SELECT * from ". $this->table_name . " where stnLat=".$this->stnLat. " and stnLong=".$this->stnLong;
    	$stmt = $this->conn->prepare($query);
    	$stmt->execute();

	return $stmt; 
     }

  function distance($lat1, $lon1, $lat2, $lon2, $unit="") {
  $lat1=round($lat1, 6);
  $lon1=round($lon1, 6);
  $lat2=round($lat2, 6);
  $lon2=round($lon2, 6); 
  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  //Handle the case where input to cos is slightly greater than 1 Ex: min(1.00001,1) = 1 avoid returning NaN
  $dist = acos(min($dist,1));
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);
  if ($unit == "K") {
    return ($miles * 1.609344);
  }
  else if ($unit == "NM") {
	//NM - Nautical Mile
    return ($miles * 0.8684);
  }
  else {
    return $miles;
  }
}

}
