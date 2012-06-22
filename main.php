<?php
# Provides access to app specific values such as your app id and app secret.
# Defined in 'AppInfo.php'
require_once 'AppInfo.php';
require_once 'kint/Kint.class.php';
require_once 'utils.php';
require_once 'sdk/src/facebook.php';

# Stop making excess function calls
$app_id = AppInfo::appID();
$app_url = AppInfo::getUrl();

# Enforce https on production
if (substr($app_url, 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
    header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

$facebook = new Facebook(array(
    'appId'  => $app_id,
    'secret' => AppInfo::appSecret(),
));

?>

<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
    <head>
		<title>WhartonPeeps</title>
		<script type="text/javascript" src="javascript/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=true"></script>
		<script type="text/javascript" src="javascript/bootstrap.min.js"></script>
		<script type="text/javascript" src="javascript/gmaps.js"></script>
        <link rel="stylesheet" href="stylesheets/bootstrap.min.css"  type="text/css" />

		<script type="text/javascript">

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-32846016-1']);
		  _gaq.push(['_trackPageview']);

		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
	</head>
<body>
	<div class="container-fluid">
	<div class="row-fluid" style="margin-top:12px;margin-bottom:-6px">
		<form method="post" class="form-inline span5" id="addressForm">
			<input type="text" class="span6" placeholder="Your address" id="address">
			<button type="submit" class="btn btn-primary"><i class="icon-map-marker icon-white"></i> Find it!</button>
		</form>
		<div id="infoBox" class="span5 alert alert-info">
			Start by searching for your address
		</div>
	</div>
	
	<div class="row-fluid">
		<div id="fullScreenMap" class="span12"></div>
		<script type="text/javascript">
	
			var map;
			var markers = [];
	
			function clickHandler(e)
			{
				console.log("clicked");
			}
			
			function rightClickHandler(e)
			{
				console.log("right clicked");
				console.log("latitude: " + e.latLng.lat() + " and long: " + e.latLng.lng());
				
				overlayIndex = overlays.length;

				o = map.drawOverlay({
					lat: e.latLng.lat(),
					lng: e.latLng.lng(),
					content: "<div>New place here? " + overlayIndex + "</div>"
				});
				
				overlays.push(o);
			}
			
			// TODO:
			function addLocationToDB(latlng, address)
			{
				return true;
			}
			
			// TODO: This will have to be modified depending on whether or not a place already exists in the DB
			function addMarkerConfirmationInfoWinMarkup(latlng, address, markerNum)
			{
				return "<div>" +
					   "Congrats! You're the first Wharton peep to list at <br/>\"" + address + "\" <br/> Add as a new place? ["+markerNum+"]" +
					   "<br/><button class='btn btn-small btn-success' type='submit' id='yesMarker'>Yes</button>" +
					   "<button class='btn btn-small' type='submit' id='noMarker'>No</button>" +
					   "<input type='hidden' id='markerNum' name='markerNum' value='"+ markerNum +"'/>"
					   "</div>";
			}
		
			function addMarker(lat, long, title, markerClickHandler, infoWindowContent)
			{
				map.addMarker({
					lat: lat,
					lng: long,
					title: title,
					click: markerClickHandler,
					infoWindow : {
						content: infoWindowContent
					}
				});
			}
	
			$(document).ready(function(){
				
				map = new GMaps({
					div: '#fullScreenMap',
					lat: 39.949457,
					lng: -75.171998,
					height: ($(window).height()-46)+'px',
					click: clickHandler
					//rightclick: rightClickHandler
				});
				
				$('#addressForm').submit(function(e){
					e.preventDefault();
					GMaps.geocode({
						address: $('#address').val().trim(),
						callback: function(results, status) {
							if (status == "OK") {
								console.log(results);
								var latlng = results[0].geometry.location;
								var cleanAddress = results[0].formatted_address;
								map.setCenter(latlng.lat(), latlng.lng());
								
								//
								// TODO: Query place database and see if it already exists
								//

								markerNum = markers.length;
								
								m = map.addMarker({
									lat: latlng.lat(),
									lng: latlng.lng(),
									infoWindow: {
										content: addMarkerConfirmationInfoWinMarkup(latlng, cleanAddress, markerNum)
									}
								});

								markers.push(m);  // add to 'model'

								// Trigger auto pop-up
								google.maps.event.trigger(m, 'click');
							}
						}
					})
				});
				
				$('#address').val('2110 Spruce St philly pa');
				
				$('#fullScreenMap').on('click','#yesMarker',function(e){
					markerNum = $('#markerNum').val();
					console.log("yes clicked: " + markerNum);
					
					marker = markers[markerNum];  // get marker
					marker.infoWindow.close();    // already added to model, just dimiss the infoWindow
				});

				$('#fullScreenMap').on('click','#noMarker',function(e){
					markerNum = $('#markerNum').val();
					console.log("no clicked: " + markerNum);
					
					marker = markers[markerNum];  // get marker
					markers.splice(markerNum,1);  // remove from 'model'
					marker.setMap(null);		  // remove from view
				});
			});
		</script>
	</div>
	</div>
</body>
</html>