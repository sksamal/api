<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
 
// database connection will be here

// include database and object files
include_once 'config/connectdb.php';
include_once 'config/httpcodes.php';
include_once 'objects/wstation.php';
 
// instantiate database and wstation object
$database = new WeatherDB();
$db = $database->getConnection();
 
// initialize object
$wstation = new wstation($db);

// get posted data
$data = urldecode(file_get_contents("php://input"));
$post_data = split("=",$data);
$data = json_decode($post_data[1]);
//error_log("post_data=".urldecode(file_get_contents("php://input")),0);	
//error_log("json_data=".$post_data[1],0);
//error_log("json_decoded=".$data->sid,0);
// does query contain anything 
if(
    !empty($data->sid) || 
    !empty($data->sname) 
){

  if (!empty($data->sid))
    $wstation->idAWDN = $data->sid;
  if (!empty($data->sname))
    $wstation->stnName = $data->sname;

  $stmt = $wstation->querry();
   
} 

else {

	// get all weather stations 
	$stmt = $wstation->read();
}


$num = $stmt->rowCount();
 
// check if more than 0 record found
if($num>0){
 
    // wstations array
    $wstations_arr=array();
    $wstations_arr["records"]=array();
 
    // retrieve our table contents
    // fetch() is faster than fetchAll()
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // extract row
        // this will make $row['name'] to
        // just $name only
        extract($row);
 
        $wstation_item=array(
            "idAWDN" => $idAWDN,
            "stnName" => $stnName,
            "stnLat" => $stnLat,
            "stnLong" => $stnLong,
            "stnStartDate" => $stnStartDate,
            "stnEndDate" => $stnEndDate,
            "stnElev" => $stnElev,
            "stnStatus" => $stnStatus,
            "stnState" => $stnState,
            "stnDataSource" => $stnDataSource
        );
 
        array_push($wstations_arr["records"], $wstation_item);
    }
 
    // set response code - 200 OK
    http_response_code(200);
 
    // show wstations data in json format
    echo json_encode($wstations_arr);
}

else{
 
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user no weather stations found
    echo json_encode(
        array("message" => "No weather stations found.")
    );

}

 
