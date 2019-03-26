<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
 
// database connection will be here

// include database and object files
include_once '../config/connectdb.php';
include_once '../config/httpcodes.php';
include_once '../objects/memberapi.php';
include_once '../objects/wstation.php';
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
error_log("json_data=".$post_data[1],0);
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

if( !empty($data->crmaturity))
 {
    $crmaturity = $data->crmaturity;
    if ($crmaturity < 90 || $crmaturity > 125)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid relative maturity provided(should be between 90 and 125 days).")
    	);
    	return;
      }
 }
else
    $crmaturity = 90;

if( !empty($data->cppopulation))
 {
    $cppopulation = $data->cppopulation;
    if ($cppopulation < 20 || $cppopulation > 50)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid plant population, should be between 20 and 50 in (1000/acre).")
    	);
    	return;
      }
 }
else
    $cppopulation = 20;

// Soil data
if( !empty($data->srdepth))
 {
    $srdepth = $data->srdepth;
    if ($srdepth < 20 || $srdepth > 60)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid soil rooting depth(should be between 20 and 60).")
    	);
    	return;
      }
 }
else
    $srdepth = 35;

if( !empty($data->ssresidues))
 {
    $ssresidues = $data->ssresidues;
    if ($ssresidues < 0 || $ssresidues > 100)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid soil residue,(should be between 0 and 100).")
    	);
    	return;
      }
 }
else
    $ssresidues = 75;

if( !empty($data->tsdensity))
 {
    $tsdensity = $data->tsdensity;
    if ($tsdensity < 1.2 || $tsdensity > 1.5)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid top soil bulk density(should be between 1.2 and 1.5).")
    	);
    	return;
      }
 }
else
    $tsdensity = 1.2;


if( !empty($data->tsmoisture))
  {
    $tsmoisture = $data->tsmoisture;
    if ($tsmoisture < 0 || $tsmoisture > 100)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid top soil moisture percentage(should be between 0 and 100).")
    	);
    	return;
      }
    // convert it to 1-4 for batch simulator
    $tsmoisture = ceil((125 - $tsmoisture)/25);
 }
else
    $tsmoisture = 3;

if( !empty($data->ssmoisture)) 
 {
    $ssmoisture = $data->ssmoisture;
    if ($ssmoisture < 0 || $ssmoisture > 100)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid sub soil moisture percentage(should be between 0 and 100).")
    	);
    	return;
      }
    // convert it to 1-4 for batch simulator
    $ssmoisture = ceil((125 - $ssmoisture)/25);
 }
else
    $ssmoisture = 3;

// needs to be fixed
if( !empty($data->tstexture))
 {
    $tstexture = $data->tstexture;
    if ($tstexture < 0 || $tstexture > 9)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid top soil texture(should be between 0 and 9).")
    	);
    	return;
      }
 }
else
    $tstexture = 16;

//needs to be fixed
if( !empty($data->sstexture))
 {
    $sstexture = $data->sstexture;
    if ($sstexture < 0 || $sstexture > 9)	
      {
    	http_response_code(200);
    	echo json_encode(
        	array("result" => "Invalid sub soil texture(should be between 0 and 9).")
    	);
    	return;
      }
 }
else
    $sstexture = 16;

if( !empty($data->sresult))
    $sresult = $data->sresult;
else
    $sresult = 'aggr';

# Fill the irrigation amounts/dates
$irrigation_record = "";
$custom_rainfall = array();
if ( !empty($data->irrdata))
  { 
     foreach ( $data->irrdata as $irr ) {
	  if ( !empty($irr->date) &&  !empty($irr->amount) ) {
	  	$date1 = date_create($irr->date);
		if($date1 == false) {
    			echo json_encode(array("result" => "Invalid date :".$irr->date));
			return;
		 }
		$irri_date=explode("/",$irr->date);
		if($irri_date[2]==date('Y')){
		$irrigation_record=$irrigation_record."\r\n".ltrim($irri_date[0],'0')." ".ltrim($irri_date[1],'0')." ".$irr->amount;
		}
	    } 
	}	
  }

$myfile = fopen("/home/cornwater/weai/api/temp/corn_input.txt", "w");
fwrite($myfile,"lat=".$flat."\n");
fwrite($myfile,"lng=".$flon."\n");
fwrite($myfile,"planting_date=".$cpdate."\n");
fwrite($myfile,"relative_maturity=".$crmaturity."\n");
fwrite($myfile,"plant_population=".$cppopulation."\n");
fwrite($myfile,"soil_rooting_depth=".$srdepth."\n");
fwrite($myfile,"soil_residues_coverage=".$ssresidues."\n");
fwrite($myfile,"topsoil_bulk_density=".$tsdensity."\n");
fwrite($myfile,"topsoil_moisture=".$tsmoisture."\n");
fwrite($myfile,"subsoil_moisture=".$ssmoisture."\n");
fwrite($myfile,"topsoil_texture=".$tstexture."\n");
fwrite($myfile,"subsoil_texture=".$sstexture."\n");
fwrite($myfile,"sresult=".$sresult."\n");
fwrite($myfile,"irrigation_record=".json_encode($irrigation_record)."\n");
fwrite($myfile,"custom_rainfall=".json_encode($custom_rainfall)."\n");
//fclose($myfile);

// some more variables
// planting date/planting month
$planting_date_array=explode("/",$cpdate);
$plant_month=ltrim($planting_date_array[0], '0');
$plant_day=ltrim($planting_date_array[1], '0');

// special fieldid,field_name, userid
$ufid = 9999;
$field_name = "api_special_".$ufid;
$user = 9999;

// get nearest station id
$wstation = new wstation($db);
$stmt = $wstation->queryByField($flat, $flon);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // this will make $row['name'] to
        // just $name only
        extract($row);
 } 
// $idAWDN retrieved
// generate weather file and note its path
// this only works if $ufid and $idAWDN is set
include_once("/home/cornwater/weai/awdnweatherToText.php");
$path="weather_files/".$idAWDN."_".$currentyear."_".$ufid.".wth";
// store it in $path

$end="0 0       0";
$content = $field_name."\r\n".$path."\r\n".$plant_month." ".$plant_day."\r\n".$crmaturity."\r\n".$cppopulation."\r\n".$ssresidues."\r\n".$tstexture." ".$sstexture."\r\n".$tsdensity."\r\n".$tsmoisture."\r\n".$ssmoisture."\r\n".$srdepth;
$content=$content.$irrigation_record."\r\n".$end;
fwrite($myfile,"awdn_id=".$idAWDN."\n");
fwrite($myfile,"plant_month=".$plant_month."\n");
fwrite($myfile,"plant_day=".$plant_day."\n");
fwrite($myfile,"\n\ncontent=\n".$content."\n");
fclose($myfile);

// dump input and run simulation
file_put_contents("/home/soywater2/CornWaterV5Linux32bit/".$user.".inp",$content,LOCK_EX);
file_put_contents("/home/soywater2/CornWaterV5Linux32bit/userlist.txt",$user."/");
chmod("/home/soywater2/CornWaterV5Linux32bit/".$user.".out", 0766);
unlink("/home/soywater2/CornWaterV5Linux32bit/".$user.".out");
shell_exec("cd /&& cd /home/soywater2/CornWaterV5Linux32bit&& ./CornWater");

// need to read output from $user.out
exit;



$myfile = fopen("/home/cornwater/weai/api/temp/output.txt","w");
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

 
