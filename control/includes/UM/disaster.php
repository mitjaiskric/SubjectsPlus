<?php 

$subsubcat = "";
$subcat = "";
$page_title = "View/Export Contact Information";
include("../includes/header.php");













exit;
$_POST["address"] = urlencode("6700 Southwest 74th Avenue Miami, FL 33143");
$endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=" . $_POST["address"] . "&sensor=false";
      $address = curl_get($endpoint);
      print $address;
      var_dump($address);
exit;


$our_spots = "";
  $q = "SELECT CONCAT( street_address, ' ', city, ' ', state, ' ', zip) as full_address
  , home_phone, cell_phone,
  emergency_contact_name, emergency_contact_relation,emergency_contact_phone, supervisor_id, staff_id, CONCAT( fname, ' ', lname ) AS fullname, email 
  FROM staff 
 
  WHERE active = 1";
  //print $q . "<br /><br />";

  $r = MYSQL_QUERY($q);


  $row_count = mysql_num_rows($r);

  while ($myrow = mysql_fetch_array($r, MYSQL_NUM)) {
    $our_spots .= "\"" .  $myrow[0] . "\",";
    
  }
//$our_spots = '"15636 Southwest 85th Terrace Miami, FL 33193","9157 Southwest 72nd Avenue, Apartment S-8 Miami, FL 33156"';
?>
<!DOCTYPE html>
<html>

  
  <head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<title>Google Maps JavaScript API v3 Example: Geocoding Simple</title>

<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script type="text/javascript">
    var geocoder;
  var map;
  var query = "Toledo";
  var queryArray = [<?php print $our_spots; ?>];
  
  function initialize() {
    geocoder = new google.maps.Geocoder();
    var myOptions = {
      zoom: 12,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map"), myOptions);
    //codeAddress();
        for (var i = 0; i < queryArray.length; i++) {
    //alert(myStringArray[i]);
    //Do something
    codeAddress(queryArray[i]);
    <?php usleep(2000000); ?>
    }
  }

  function codeAddress(address) {
    //var address = query;
    geocoder.geocode( { 'address': address}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        map.setCenter(results[0].geometry.location);
        var marker = new google.maps.Marker({
            map: map, 
            position: results[0].geometry.location
        });
      } else {
        alert("Geocode was not successful for the following reason: " + status);
      }
    });
  }


</script>
</head>
<body onload="initialize()">
  <div>
    <input id="address" type="textbox" value="Sydney, NSW">
    <input type="button" value="Geocode" onclick="codeAddress()">
  </div>
<div id="map_canvas" style="height:90%;top:30px"></div>
<div id="map" style="width: 800px; height: 800px; border: 1px solid #333;"></div>
</body>
</html>

<?php 

include("../includes/footer.php");
?>