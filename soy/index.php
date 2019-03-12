<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
 
// database connection will be here

// include database and object files
include_once '../config/connectdb.php';
include_once '../config/httpcodes.php';
include_once '../objects/memberapi.php';
include_once '../../public_html/controllers/soy/soysimulationexec.php';
 
// instantiate database and wstation object
$database = new WeatherDB();
$db = $database->getConnection();
$database = new CSWaterDB();
$cdb = $database->getConnection();
 
// initialize object
$auth = new memberapi($cdb);

// get posted data
$data = urldecode(file_get_contents("php://input"));
$post_data = split("=",$data);
$data = json_decode($post_data[1]);
//error_log("post_data=".urldecode(file_get_contents("php://input")),0);	
//error_log("json_data=".$post_data[1],0);
//error_log("json_decoded=".$data->apikey,0);
// does query contain anything 

 error_log("user authenticated=".$auth->validate($data));
if(!$auth->validate($data)) { 

    http_response_code(200);
    echo json_encode(
        array("result" => "Invalid or no api key provided.")
    );
    return;
} 

// Field
if( empty($data->flat) ||   empty($data->flon))
 {
    http_response_code(200);
    echo json_encode(
        array("result" => "Invalid or no field lat/lon provided.")
    );
    return;
 }
else
 {
    $flat = $data->flat;
    $flon = $data->flon;
 }

// Crop
if ( empty($data->cpdate) ) 
 {
    http_response_code(200);
    echo json_encode(
        array("result" => "Invalid planting date provided.")
    );
    return;
 }
else
 {
    $cpdate = $data->cpdate;
 }

if( !empty($data->cmgroup))
    $cmgroup = $data->cmgroup;
else
    $cmgroup = 3;

// Soil data
if( !empty($data->srdepth))
    $srdepth = $data->srdepth;
else
    $srdepth = 50;

if( !empty($data->sawater))
    $sawater = $data->sawater;
else
    $sawater = 75;

if( !empty($data->satexture))
    $satexture = $data->satexture;
else
    $satexture = 16; // Slit loam

if( !empty($data->ssdepth))
    $ssdepth = $data->ssdepth;
else
    $ssdepth = 1.18;

if( !empty($data->swdepletion))
    $swdepletion = $data->swdepletion;
else
    $swdepletion = 35;

if( !empty($data->sresult))
    $sresult = $data->sresult;
else
    $sresult = 'aggr';

$irrigation_record = array();
$custom_rainfall = array();

$myfile = fopen("/home/cornwater/weai/public_html/api/temp/input.txt", "w");
fwrite($myfile,"rooting_depth=".$srdepth."\n");
fwrite($myfile,"mgval=".$cmgroup."\n");
fwrite($myfile,"soil_type=".$satexture."\n");
fwrite($myfile,"initial_water=".$sawater."\n");
fwrite($myfile,"seed_depth=".$ssdepth."\n");
fwrite($myfile,"soil_water_depletion=".$swdepletion."\n");
fwrite($myfile,"lat=".$flat."\n");
fwrite($myfile,"lng=".$flon."\n");
fwrite($myfile,"irrigation_record=".json_encode($irrigation_record)."\n");
fwrite($myfile,"custom_rainfall=".json_encode($custom_rainfall)."\n");
fwrite($myfile,"planting_date=".$cpdate."\n");
fclose($myfile);



// Call simulation code
$soySim =new SimSoy();
$soySim->simSoyData($srdepth,$cmgroup,$satexture,$sawater,$ssdepth,$swdepletion,$flat,$flon,$irrigation_record,$custom_rainfall,$cpdate);

$myfile = fopen("/home/cornwater/weai/public_html/api/temp/output.txt","w");
fwrite($myfile,"rpheno=".json_encode($soySim->getRPhenology())."\n");
fwrite($myfile,"vpheno=".json_encode($soySim->getVPhenology())."\n");
fwrite($myfile,"date=".json_encode($soySim->getDate())."\n");
fwrite($myfile,"water_deficit=".json_encode($soySim->getWaterDeficit())."\n");
fwrite($myfile,"stress=".json_encode($soySim->getStress())."\n");
fwrite($myfile,"threshold=".json_encode($soySim->getThreshold())."\n");
fwrite($myfile,"rain=".json_encode($soySim->getRain())."\n");
fwrite($myfile,"recordedrain=".json_encode($soySim->getRecordedRain())."\n");
fwrite($myfile,"irrigation=".json_encode($soySim->getIrrigation())."\n");
fwrite($myfile,"year_in=".json_encode($soySim->getYear())."\n");
fwrite($myfile,"total_rain=".json_encode($soySim->getTotalRain())."\n");
fwrite($myfile,"total_irrigation=".json_encode($soySim->getTotalIrrigation())."\n");
fwrite($myfile,"initial_available_water=".json_encode($soySim->getInitialAvailableWater())."\n");
fwrite($myfile,"total_water_deficit=".json_encode($soySim->getTotalWaterDeficit())."\n");
fwrite($myfile,"current_available_water=".json_encode($soySim->getCurrentAvailableWater())."\n");
fclose($myfile);

if($sresult == 'cstage') {
   $crpstg_arr = array();
   $crpstg_arr["field_latitude"]=$data->flat;
   $crpstg_arr["field_longitude"]=$data->flon;
   $crpstg_arr["planting_date"]=$data->cpdate;
   $dates = $soySim->getDate();
   $r_phenology=$soySim->getRPhenology();
   $v_phenology=$soySim->getVPhenology();
   $n = sizeof($dates);
   $crpstg_arr["count"]=$n;
   $crpstg_arr["records"]=array();
   for($i=0;$i<$n;$i+=1) {
        $crpstg = array();
	$crpstg["date"]=$dates[$i];
   	$crpstg["r_phenology"]=$r_phenology[$i];
   	$crpstg["v_phenology"]=$v_phenology[$i];
        array_push($crpstg_arr["records"], $crpstg);
   }
   echo json_encode(array("records"=>$crpstg_arr));
    http_response_code(200);
}
else if($sresult == 'awater') {
    $awater_arr = array();
    $awater_arr["field_latitude"]=$data->flat;
    $awater_arr["field_longitude"]=$data->flon;
    $awater_arr["planting_date"]=$data->cpdate;
    $dates = $soySim->getDate();
    $threshold = $soySim->getThreshold();
    $rain = $soySim->getRain();
    $irrigation = $soySim->getIrrigation();
    $water_deficit =$soySim->getWaterDeficit();
    $n = sizeof($dates);
    $awater_arr["count"]=$n;
    $awater_arr["records"]=array();

    for($i=0;$i<$n;$i+=1) {
    	$awater = array();
    	$awater["date"] =$dates[$i];
    	$awater["threshold"]=$threshold[$i];
    	$awater["rain"]=$rain[$i];
    	$awater["irrigation"]=$irrigation[$i];
    	$awater["water_deficit"]=$water_deficit[$i];
        array_push($awater_arr["records"], $awater);
    }
    echo json_encode(array("records"=>$awater_arr));
    http_response_code(200);
}

else if($sresult == 'wstress') {
    $wstress_arr = array();
    $wstress_arr["field_latitude"]=$data->flat;
    $wstress_arr["field_longitude"]=$data->flon;
    $wstress_arr["planting_date"]=$data->cpdate;
    $dates = $soySim->getDate();
    $stress = $soySim->getStress();
    $n = sizeof($dates);
    $wstress_arr["count"]=$n;
    $wstress_arr["records"]=array();

    for($i=0;$i<$n;$i+=1) {
    	$wstress = array();
    	$wstress["date"] =$dates[$i];
    	$wstress["wstress"]=$stress[$i];
        array_push($wstress_arr["records"], $wstress);
    }
    echo json_encode(array("records"=>$wstress_arr));
    http_response_code(200);
}

else if($sresult == 'aggr') {

    $aggr_arr=array();
    $aggr = array();
    $aggr["initial_available_water"]=$soySim->getInitialAvailableWater();
    $aggr["total_rain"]=$soySim->getTotalRain();
    $aggr["total_irrigation"]=$soySim->getTotalIrrigation();
    $aggr["total_water_deficit"]=$soySim->getTotalWaterDeficit();
    $aggr["current_available_water"]=$soySim->getCurrentAvailableWater();
    $aggr_arr["aggregate_results"]=$aggr;
    echo json_encode($aggr_arr);
}
 
else{
 
    // set response code - 404 Not found
    http_response_code(404);
 
    // tell the user no weather stations found
    echo json_encode(
        array("result" => "An error occured.")
    );

}

 
