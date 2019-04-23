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
//error_log("json_data=".$post_data[1],0);
//error_log("json_decoded=".$data->apikey,0);
// does query contain anything 

// error_log("user authenticated=".$auth->validate($data));
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
    $tstexture = 3;

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
    $sstexture = 3;

if( !empty($data->sresult))
    $sresult = $data->sresult;
else
    $sresult = 'aggr';

# Fill the irrigation ater_balancemounts/dates
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

// debugging
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

// Corn Simulation call
// $idAWDN retrieved
// generate weather file and note its path
// this only works if $ufid and $idAWDN is set
include_once("/home/cornwater/weai/awdnweatherToText.php");
$path="weather_files/".$idAWDN."_".$currentyear."_".$ufid.".wth";
// store it in $path

$end="0 0       0";
$content = $field_name."\r\n".$path."\r\n".$plant_month." ".$plant_day."\r\n".$crmaturity."\r\n".$cppopulation."\r\n".$ssresidues."\r\n".$tstexture." ".$sstexture."\r\n".$tsdensity."\r\n".$tsmoisture."\r\n".$ssmoisture."\r\n".$srdepth;
$content=$content.$irrigation_record."\r\n".$end;
fwrite($myfile,"ufid=".$ufid."\n");
fwrite($myfile,"field_name=".$field_name."\n");
fwrite($myfile,"user=".$user."\n");
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
//Start: Extract data from output file.
$file="/home/soywater2/CornWaterV5Linux32bit/".$user.".out";
$linecount=0;
$last_seven=0;
$handle = fopen($file, "r");
if($handle == false)  {
    echo json_encode(
        array("result" => "An error occured.")
    );
    return;
}
$phenology=array();
$date=array();
$rain=array();

$irrigation=array();
$w_1ft=array();
$w_2ft=array();
$w_blw=array();
$w_stress=array();
$total_available_water=array();
$threshold=array();
$tick_position=array();
while(!feof($handle)){
$str=fgets($handle);//Read array line
$linecount++;
$variables=explode("\t",$str);//Divide the array and read in to a variable
$array_count=count($variables);//Calculate Number of values in array
if($array_count >=12){
array_push($date,$variables[0]);
array_push($rain,$variables[3]);
array_push($irrigation,$variables[4]);
array_push($w_1ft,$variables[5]);
array_push($w_2ft,$variables[6]);
array_push($w_blw,$variables[7]);
array_push($w_stress,$variables[8]);
array_push($phenology,$variables[9]);
array_push($total_available_water,$variables[10]);
array_push($threshold,$variables[11]);
}
else if($array_count==1){
	$last_seven=$last_seven+1;
	if($last_seven==1){
	$temp=explode("\n",$variables[0]);
	$field_name=$temp[0];
	}
	else if($last_seven==2){
	$available_water=$variables[0];
	}
	else if($last_seven==3){
	$total_rainfall=$variables[0];
	}
	else if($last_seven==4){
	$total_irrigation=$variables[0];
	}
	else if($last_seven==5){
	$water_consumption=$variables[0];
	}
	else if($last_seven==6){
	$water_drain=$variables[0];
	}
	else if($last_seven==7){
	$current_water_balance=$variables[0];
	}
	else if($last_seven==8){
	$current_root_depth=$variables[0];
	}
	else if($last_seven==9){
	$maturity_status=$variables[0];
	}
}
else if($array_count==0){
	$last_seven=0;
	break;
}

}
$stressMax = max($w_stress);
$rainMax = max($rain);
$thresholdMax = max($threshold);
$irrigationMax = max($irrigation);
// Ends: Extraction from output file ends here

//debugging 
$myfile = fopen("/home/cornwater/weai/api/temp/corn_output.txt","w");
fwrite($myfile,"phenology=".json_encode($phenology)."\n");
fwrite($myfile,"date=".json_encode($date)."\n");
fwrite($myfile,"w_stress=".json_encode($w_stress)."\n");
fwrite($myfile,"w_1ft=".json_encode($w_1ft)."\n");
fwrite($myfile,"w_2ft=".json_encode($w_2ft)."\n");
fwrite($myfile,"w_blw=".json_encode($w_blw)."\n");
fwrite($myfile,"threshold=".json_encode($threshold)."\n");
fwrite($myfile,"rain=".json_encode($rain)."\n");
//fwrite($myfile,"recordedrain=".json_encode($soySim->getRecordedRain())."\n");
fwrite($myfile,"irrigation=".json_encode($irrigation)."\n");
fwrite($myfile,"total_available_water=".json_encode($available_water)."\n");
fwrite($myfile,"total_rainfall=".json_encode($total_rainfall)."\n");
fwrite($myfile,"total_irrigation=".json_encode($total_irrigation)."\n");
fwrite($myfile,"available_water=".json_encode($available_water)."\n");
fwrite($myfile,"water_consumption=".json_encode($water_consumption)."\n");
fwrite($myfile,"water_drain=".json_encode($water_drain)."\n");
fwrite($myfile,"current_water_balance=".json_encode($current_water_balance)."\n");
fclose($myfile);

// Create response based on what is requested in sresult
// cstage --> crop stage records
// awater --> available water records
// wstress --> water stress
// aggr --> aggregate info of simulation 
if($sresult == 'cstage') {
   $crpstg_arr = array();
   $crpstg_arr["field_latitude"]=$data->flat;
   $crpstg_arr["field_longitude"]=$data->flon;
   $crpstg_arr["planting_date"]=$data->cpdate;
   $n = sizeof($date);
   $crpstg_arr["count"]=$n;
   $crpstg_arr["records"]=array();
   for($i=0;$i<$n;$i+=1) {
        $crpstg = array();
	$crpstg["date"]=$date[$i];
   	$crpstg["phenology"]=$phenology[$i];
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
    $n = sizeof($date);
    $awater_arr["count"]=$n;
    $awater_arr["records"]=array();

    for($i=0;$i<$n;$i+=1) {
    	$awater = array();
    	$awater["date"] =$date[$i];
    	$awater["rain"]=$rain[$i];
    	$awater["irrigation"]=$irrigation[$i];
    	$awater["water_1ft"]=$w_1ft[$i];
    	$awater["water_2ft"]=$w_2ft[$i];
    	$awater["water_blw2ft"]=$w_blw[$i];
    	$awater["threshold"]=$threshold[$i];
    	$awater["total_available_water"]=$total_available_water[$i];
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
    $n = sizeof($date);
    $wstress_arr["count"]=$n;
    $wstress_arr["records"]=array();

    for($i=0;$i<$n;$i+=1) {
    	$wstress = array();
    	$wstress["date"] =$date[$i];
    	$wstress["wstress"]=$w_stress[$i];
        array_push($wstress_arr["records"], $wstress);
    }
    echo json_encode(array("records"=>$wstress_arr));
    http_response_code(200);
}

else if($sresult == 'aggr') {

    $aggr_arr=array();
    $aggr = array();
    $aggr["available_water"]=$available_water;
    $aggr["total_rainfall"]=$total_rainfall;
    $aggr["total_irrigation"]=$total_irrigation;
    $aggr["water_drain"]=$water_drain;
    $aggr["water_consumption"]=$water_consumption;
    $aggr["current_water_balance"]=$current_water_balance;
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

 
