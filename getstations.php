<?php

if( ! ini_get('date.timezone') )
{
    date_default_timezone_set('UTC');
}
include_once("searchStationNgetClimatedata.php");
//ini_set('display_errors', 1);

//added by TS 09-05-23
//Connecting to database
require_once('wconfig.php');
//Connect to mysql server
$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
if(!$link)
{
	die('Failed to connect to server: ' . mysql_error());
}
//Select database
$db = mysql_select_db(DB_DATABASE);
if(!$db)
{
	die("Unable to select database");
}

if(isset($_POST['yearval_input'])) {
	$yearval_input = $_POST['yearval_input'];
	}
else	{
	$yearval_input = date("Y") - 1;
	}

if(isset($_POST['fieldCoordinates'])) {
	$platlng = $_POST['fieldCoordinates'];
     }
else {
	$platlng = "40.5538, -99.0279";
     }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />-->
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="soywater_c.css" rel="stylesheet" type="text/css" />
<link href="soywater_d.css" rel="stylesheet" type="text/css" />
<title>Search Map | SoyWater</title>

<!--4-13-14  -->
<style>
      html, body, #map {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
      
    </style>
<!--4-13-14  -->


<h2>Select your field</h2>
<!--<a href="member-redirect.php">My Profile</a> | --><!--<a href="logout.php">Logout</a>-->

<!--<script  src="//maps.googleapis.com/maps/api/js?&v=3key=AIzaSyBWsVM5IzZjIHrMZJZqWsHgkD7F31cKRHs&sensor=false" type="text/javascript"> -->
<script  src="//maps.googleapis.com/maps/api/js?key=AIzaSyDF-i2JSCp8th6Bje2dflMkJseIZlRV_Vs&sensor=false" type="text/javascript">
  </script>
<script src="imageshowscript.js"></script>

<table width="698" border="0" cellpadding="1" align="center">
  <tr>
    <td style="text-align:left">Step 1. Click <a href="javascript:void(0);" onclick="PopupCenter('helpmenu.html', 'help',600,400);"><b><span class="highlight">HERE</span></b></a> for tips on how to search for your field using below Search Button.</td>
  </tr>
  <tr>
    <td style="text-align:left">Step 2. Using those tips, identify a map-view that shows your field in the center of the picture.</td>
  </tr>
  <tr>
    <td style="text-align:left">Step 3. When you then click on your field, its GPS coordinates and year will show in below boxes.</td>
  </tr>
  <tr>
    <td style="text-align:left">Step 4. Click on 'Search for Nearest Station' button below the map to go to the next page.</td>
  </tr>
</table><br>
<!--  4-13-14-->
<input type="textbox" id="address" value=""/>
<input type="button" value="Search" onclick="codeAddress()">
<br>
<div align="center"><div id="map" style="position:relative; top:10px; width:650px; height:400px"></div></div>

<!--  4-13-14
<b>Closest to: <b>
<div id="closestAddress"></div>
-->
 <!-- <button codeAddress()="search();">Search</button> 4-13-14-->
<!--JT: Testing Coordinates Again! 4-14-10
<fieldset style="width:400px">
<legend style="color:#F00">
IGNORE THIS BOX FOR NOW, Programmers' testing only!
</legend>
 <table width="700" border="0" cellpadding="1" align="center">
  <tr>
    <td width="220px" align="center"><b>Type your Field location here:</b></td>
    <td width="260px" align="left"> <form action="#" onsubmit="showAddress(this.address.value); return false">
        <input type="text" size="40" name="address" value="" />
    </td>
 	<td align="left">
    <input type="submit" value="Search the Map"/></form>
    </td>
  </tr>
</table>
</fieldset>-->
<br>
<div align="center">
<div id="novel">
   <br>
       <table style="align:center;" cellpadding="5">
         <form id="form1" name="form1" method="post" action="getstations.php">
           <tr>
           		<td width='350'>
           			<span style='font-weight: bold;'>Orange Marker GPS: </span>&nbsp;
           			<input type="text" name= "fieldCoordinates" id="click" onfocus="this.select();" style="width:150px;" value= "<?php echo $platlng ?>">
				</td>
<!--			   <td width="150"><span style='font-weight: bold;'>Year:</span>&nbsp;
               	<input type = "text" name="yearval_input" size ="5" maxlength ="10" value="<?php echo $yearval_input ?>"/></td> -->
             </tr>
             <tr>
             	<td colspan="2" style='text-align:center;'><input type="submit" name="search" value = "Search for Nearest Station"/></td>
             </tr>
			</table>
			<br>
</div>

<?php

	if(isset($_POST['fieldCoordinates'])) {
	    
		$lat_lng = explode(',',$_POST['fieldCoordinates']);
			echo "
			    <table cellspacing='0' cellpadding='0' width='850' border='0' align='center'>
				<tbody>
				   <tr>
					 <td bgcolor='#000000'>
					   <table cellspacing='1' cellpadding='4' width='850' border='0'>
					   <tbody>
						  <tr>
						  <th bgcolor='#99ccff' width=5% align='center'>
						  Select One <br>
						  </th>

						  <th bgcolor='#99ccff' width=13% align='center'>
						   Weather <br> Stn Name
						  </th>
						  <th bgcolor='#99ccff' width=13% align='center'>
						   Distance <br> 						   
						  </th>

						  <th bgcolor='#99ccff' width=14% align='center'>
						   Latitude 
						  </th>
						  <th bgcolor='#99ccff' width=8% align='center'>
						   Longitude <br>
						  </th>
						  <th bgcolor='#99ccff' width=15% align='center'>
						   Elevation <br>
						   </th>
						  </th>
						  <th bgcolor='#99ccff' width=15% align='center'>
						   State <br>
						   </th>
						  </tr>
		
		";
	$stations = searchStation($lat_lng[0], $lat_lng[1]);
	if (sizeof($stations) > 0)
	{
		foreach($stations as $station)
		{
				echo "
					  <tr>
					   <td bgcolor='#ffffff' width=5% align='center'>
						 <input type = \"Radio\" Name =\"field_to_select\" value= \"$i\" $checked_status>
						 <!--<input type = \"hidden\" Name =\"awdn_id\" value= \"". $station['awdn_id']. "_". $yearval_input . ".wth\"> -->
						 <input type = \"hidden\" Name =\"awdn_id\" value= \"". ucfirst(strtolower($station['station_name'])). ",". $station['station_state'] . ".wth\">
					   </td>

					   <td bgcolor='#ffffff' width=13% align='left'>".
						 $station['station_name'].
					   "</td>
					   <td bgcolor='#ffffff' width=13% align='left'>".
						 $station['distance'].
					   "</td>

					   <td bgcolor='#ffffff' width=14% align='left'>".
						 $station['latitude'].
					   "</td>
					   <td bgcolor='#ffffff' width=8% align='center'>".
						 $station['longitude'].
					   "</td>
					   <td bgcolor='#ffffff' width=15% align='left'>".
						 $station['elevation'].
					   "</td>
					   <td bgcolor='#ffffff' width=15% align='left'>".
						 $station['station_state'].
					   "</td>
					  </tr>
					 ";
        	}
     }
     echo "
          		         </tbody>
				         </TABLE>
				         </td>
				       </tr>
		<tr><td colspan='7'>&nbsp;</td></tr>
		<tr>
             	<td colspan='7' bgcolor='#ffffff' style='text-align:center;'><input type='submit' name='select' value = 'Continue'/></td>
		</tr>
				      </tbody>
				     </table>
				</form>
			     ";
  } ?>
</div>

    <script type="text/javascript">
	
// 4-13-14
var geocoder = new google.maps.Geocoder();
//var geocoder;
var map;
var marker;
var latLng;
var markersArray = [];
//4-13-14

/*
var marker = new google.maps.Marker({
    position: new google.maps.LatLng(40.549929, -99.023209),
    draggable: true,
    map: map
});
*/

//4-13-14
function initialize(){
// 4-13-14

/// Code Adapted from Esa (Feb 2006 and recent email conversation 2009)
//4-6-14
//<title>Places search box</title>
//    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places">
 
// 4-6-14 new search box
//var map = new google.maps.Map(document.getElementById("map"),
// {googleBarOptions:{style: "new", onMarkersSetCallback: setMarker,suppressInitialResultSelection : true}});
//map.addControl(new GMapTypeControl(1));
//map.addControl(new GLargeMapControl());

geocoder = new google.maps.Geocoder();
//latLng = new google.maps.LatLng(40.8202, -96.7005);
//latLng = new google.maps.LatLng(40.549929, -99.023209);
latLng = new google.maps.LatLng(<?php echo $platlng ?>);
var myOptions = {
    center: latLng,
    zoom: 14,
    mapTypeId: google.maps.MapTypeId.HYBRID,
    // Add controls
    mapTypeControl: true,
    scaleControl: true,
    gestureHandling: 'greedy'
    //overviewMapControl: true,
   // overviewMapControlOptions: 
    //  opened: true
    //
};

map = new google.maps.Map(
        document.getElementById("map"), myOptions);
  


/////Marker and info window

var html = "//You can provide a set of coordinates<br>by dragging the marker or clicking your<br> mouse to the center of your field.";
html += "//<br><a href='javascript:map.zoomIn();map.zoomIn();map.zoomIn()'>See closer &lt;/a&gt;";

//var marker = new GMarker(map.getCenter(),{draggable:true});
//map.addOverlay(marker);
<!--marker.openInfoWindowHtml(html);-->


//4-13-14

  marker = new google.maps.Marker({
    position: latLng,
    draggable: true,
    map: map
});
 markersArray.push(marker);
//4-13-14

//marker = new google.maps.Marker(map.getCenter(),{draggable:true})

 // Update current position info.
  updateDisplay(latLng);
 // geocodePosition(latLng);

// initial info window
//google.maps.event.addListener(marker, "click", function() {
 // marker.openInfoWindowHtml(html);
//});

//// marker click
/*
google.maps.event.addListener(map, "click", function(a, clickPoint){
  if(clickPoint){
    marker.setPosition(clickPoint);
    updateDisplay(clickPoint);
  }
});
*/

//4-19-14 chenghcou marker click
 google.maps.event.addListener(map, 'click', function(event) {
            placeMarker(event.latLng);
		    updateDisplay(marker.getPosition());
		//	geocodePosition(marker.getPosition());
        });
		
//4-19-14



 google.maps.event.addListener(marker, 'drag', function() {
    
    updateDisplay(marker.getPosition());
  });


//// marker dragend
google.maps.event.addListener(marker, "dragend", function(){
  //placeMarker(event.latLng);
   //marker = markersArray.pop();
  
  updateDisplay(marker.getPosition());
 // geocodePosition(marker.getPosition());
  
});

/// Function for Geocoder using sets of coordinates
//Chengchou 3-23-2014 changed GClientGeocoder() to google.maps.Geocoder()
//var geocoder = new google.maps.Geocoder();
}


//4-16-14 chengchou new function for show address 

function geocodePosition(pos) {
  geocoder.geocode({
    latLng: pos
  }, function(responses) {
    if (responses && responses.length > 0) {
      updateMarkerAddress(responses[0].formatted_address);
    } else {
      updateMarkerAddress('Try again.');
    }
  });
}

//4-16-14 chengchou new function for show address 

function updateMarkerAddress(str) {
  document.getElementById('closestAddress').innerHTML = str;
}
//4-16-14 chengchou new function for show address 

// called by search complete and map 'click'
/*
function setMarker(){
  var center = map.getCenter();
  marker.setPosition(center);
  updateDisplay(center);
}
*/

// 4-19-14 Chengchou place marker
function placeMarker(location) {
  /*
  // first remove all markers if there are any
            deleteOverlays();

            marker = new google.maps.Marker({
                position: location, 
				draggable: true,
                map: map
            });

            // add marker in markers array
            markersArray.push(marker);
*/
if (marker) {
        //if marker already was created change positon
        marker.setPosition(location);
    } else {
        //create a marker
        marker = new google.maps.Marker({
            position: location,
            map: map,
            draggable: true
        });
    }
}



// Deletes all markers in the array by removing references to them
function deleteOverlays() {
            if (markersArray) {
                for (i in markersArray) {
                    markersArray[i].setMap(null);
                }
            markersArray.length = 0;
            }
}
		
//MINDEN
var defaultLocationSelected = true;

// called every time marker is moved
function updateDisplay(latLng){
  var latLngStr = latLng.lat().toFixed(6) + ', ' + latLng.lng().toFixed(6);
  var lat_long_box = document.getElementById("click");
	lat_long_box.value = latLngStr;
	
	if(!defaultLocationSelected){
		lat_long_box.style.backgroundColor = 'yellow';
	}else{
		defaultLocationSelected = false;
	}
//alert (latLngStr);
}
//var testlat = 0;
//testlat = pnt.lat();

function showAddress(address) {
  geocoder.getLatLng(
    address,
    function(point) {
      if (!point) {
        alert(address + " not found");
      } else {
        map.setCenter(point, 15);
        var marker = new google.maps.Marker(point);
        map.addOverlay(marker);
        marker.openInfoWindowHtml(address);
      }
    }
  );
}

function codeAddress() {
  var address = document.getElementById('address').value;
  geocoder.geocode( { 'address': address}, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
      map.setCenter(results[0].geometry.location);
	  placeMarker(results[0].geometry.location);
	  updateDisplay(marker.getPosition());
	 // geocodePosition(marker.getPosition());
     /*
	  marker = new google.maps.Marker({
          map: map,
		  draggable: true,
        position: results[0].geometry.location
     });
	*/
    } else {
      alert('Geocode was not successful for the following reason: ' + status);
    }
  });
}

//google.maps.event.addDomListener(window, 'load', initialize);
</script>

</head>

<body onload="initialize()">

    <div id="map"></div>
    <div id="click"></div>
</body>
</html>
