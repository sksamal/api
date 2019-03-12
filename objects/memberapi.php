<?php
class memberapi{
 
    // database connection and table name
    private $conn;
    private $table_name = "memberapi";
 
    // object properties

/*
mysql> desc memberapi;
+-----------+--------------+------+-----+---------+-------+
| Field     | Type         | Null | Key | Default | Extra |
+-----------+--------------+------+-----+---------+-------+
| membersId | int(10)      | NO   | PRI | 0       |       |
| state     | varchar(5)   | NO   | PRI |         |       |
| apikey    | varchar(254) | YES  |     | NULL    |       |
+-----------+--------------+------+-----+---------+-------+
 */
    public $membersId;
    public $state;
    public $apikey;
 
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
                p.membersId";
 
    // prepare query statement
    $stmt = $this->conn->prepare($query);
 
    // execute query
    $stmt->execute();
 
    return $stmt;
}

   function querry() { 

	$query = "SELECT * from ". $this->table_name ;

  	if (!empty($this->apikey) && !empty($this->state))
	  $wclause = " WHERE apikey = '". $this->apikey . "' and state = '". $this->state ."'";
	else if (!empty($this->apikey))
  	  $wclause = " WHERE apikey = '". $this->apikey. "'";	
	else if (!empty($this->state))
          $wclause = " WHERE state = '". $this->state . "'";

	$query = $query. $wclause;
    	$stmt = $this->conn->prepare($query);
    	$stmt->execute();
	return $stmt; 
  }

   function validate($data) {
     if (!empty($data->apikey)) {
	$this->apikey = $data->apikey;
    	$stmt = $this->querry();
	$rows = $stmt->fetchAll();
	if(count($rows) > 0)
	   return true;
      }
	return false;
  }
	 

}
