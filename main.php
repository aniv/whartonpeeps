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
		<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=places&sensor=true"></script>
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
		<div id="infoBox" class="span3 alert alert-info" style="padding-right:14px">
			Start by searching for your address in the box:
		</div>
		<form method="post" class="form-inline span5" id="addressForm" style="margin-top:4px">
			<input type="text" class="span6" placeholder="Your address" id="addressBox">
			<button type="submit" class="btn btn-primary"><i class="icon-map-marker icon-white"></i> Find it!</button>
		</form>
	</div>
	
	<div class="row-fluid">
		<div id="fullScreenMap" class="span12"></div>
		<script type="text/javascript">
	
			window.map = null;
			window.viewMarkers = [];
			window.modelMarkers = [];
			// var r;
	
			// function clickHandler(e)
			// {
			// 	console.log("clicked");
			// }
			// 
			// function rightClickHandler(e)
			// {
			// 	console.log("right clicked");
			// 	console.log("latitude: " + e.latLng.lat() + " and long: " + e.latLng.lng());
			// 	
			// 	overlayIndex = overlays.length;
			// 
			// 	o = map.drawOverlay({
			// 		lat: e.latLng.lat(),
			// 		lng: e.latLng.lng(),
			// 		content: "<div>New place here? " + overlayIndex + "</div>"
			// 	});
			// 	
			// 	overlays.push(o);
			// }
			
			function refreshMarkers()
			{
				console.log("refresh markers called");
				currentBounds = window.map.map.getBounds();

				$.ajax({
					type: "GET",
					url: "fetch.php",
					data: { 
						action: "refreshMarkers",
						ne_lat: currentBounds.getNorthEast().lat(), 
						ne_lng: currentBounds.getNorthEast().lng(), 
						sw_lat: currentBounds.getSouthWest().lat(), 
						sw_lng: currentBounds.getSouthWest().lng()
					},
				}).done(function(msg){
					console.log("Requesting fresh markers");
					console.log("Server response: " + msg);
					markers = $.parseJSON(msg);
					
					window.modelMarkers = []; // reset model markers
					
					for(i in markers)
					{
						marker = markers[i];
						
						// rebuild the view just for the model markers (view markers are left as is)
						window.map.addMarker({
							lat: marker.lat_lng[0],
							lng: marker.lat_lng[1],
							title: marker.place_short,
							//click: markerClickHandler,
							infoWindow : {
								content: exisitingMarkerInfoWindowMarkup(marker.place_short, marker.place_long, marker.people)
							}
						});

						// rebuild the model markers
						window.modelMarkers.push(marker);
					}
				});
			}
			
			function addLocationToDB(fullAdd, shortAdd, lat, lng)
			{
				$.ajax({
					type: "POST",
					url: "save.php",
					data: { 
						action: "newPlaceAndUser",
						fullAddress: fullAdd, 
						shortAddress: shortAdd, 
						lat: lat, 
						lng: lng,
						fbUserId: null
					},
				}).done(function(msg){
					console.log("Data sent to server");
					console.log("Server response: " + msg);
				});
			}
			
			function newMarkerInfoWindowMarkup(latlng, fullAddress, shortAddress, markerNum)
			{
				return "<div>" +
					   "Congrats! You're the first Wharton peep to list at <br/>\"" + fullAddress + "\" <br/> Add as a new place? ["+markerNum+"]<br/>" +
					   "<button class='btn btn-small btn-success' type='submit' id='yesMarker'>Yes</button>" +
					   "<button class='btn btn-small' type='submit' id='noMarker'>No</button>" +
					   "<input type='hidden' id='markerNum' name='markerNum' value='"+ markerNum +"'/>" +
					   "<input type='hidden' id='fullAddress' name='fullAddress' value='"+ fullAddress +"'/>" +
					   "<input type='hidden' id='shortAddress' name='shortAddress' value='"+ shortAddress +"'/>" +
					   "<input type='hidden' id='lat' name='lat' value='"+ latlng.lat() +"'/>" +
					   "<input type='hidden' id='lng' name='lng' value='"+ latlng.lng() +"'/>" +
					   "</div>";
			}
		
			function exisitingMarkerInfoWindowMarkup(shortAddress, fullAddress, people)
			{
				var peopleList = "";
				for(p in people)
					peopleList += p;
					
				return "<div>" +
					   "Wharton peeps at \"" + shortAddress + "\" <br/>" +
					   peopleList +
					   "</div>";
			}
			
			// function addMarker(lat, long, title, markerClickHandler, infoWindowContent)
			// {
			// 	map.addMarker({
			// 		lat: lat,
			// 		lng: long,
			// 		title: title,
			// 		click: markerClickHandler,
			// 		infoWindow : {
			// 			content: infoWindowContent
			// 		}
			// 	});
			// }
	
			$(document).ready(function(){
				
				// Main map config
				window.map = new GMaps({
					div: '#fullScreenMap',
					lat: 39.949457,
					lng: -75.171998,
					zoom: 16,
					height: ($(window).height()-46)+'px',
					idle: refreshMarkers
				});
				
				// Address autocomplete config
				var input = document.getElementById('addressBox');
				var options = {
				  bounds: window.map.map.getBounds(),
				  types: ['geocode']
				};
				autocomplete = new google.maps.places.Autocomplete(input, options);
				autocomplete.bindTo('bounds', window.map.map);
				autocomplete.setBounds(window.map.map.getBounds());
				
				// Address submit handler
				$('#addressForm').submit(function(e){
					e.preventDefault();
					GMaps.geocode({
						address: $('#addressBox').val().trim(),  // ($('#addressBox').val() == "" ? : $('#addressBox').val().trim()),
						callback: function(results, status) {
							if (status == "OK") {
								// console.log(results);
								
								var latlng = results[0].geometry.location;
								var fullAddress = results[0].formatted_address;
								var shortAddress = results[0].formatted_address.split(',')[0];
								window.map.setCenter(latlng.lat(), latlng.lng());
								
								markerNum = window.viewMarkers.length;
								
								m = window.map.addMarker({
									lat: latlng.lat(),
									lng: latlng.lng(),
									infoWindow: {
										content: newMarkerInfoWindowMarkup(latlng, fullAddress, shortAddress, markerNum)
									}
								});

								window.viewMarkers.push(m);  // add to view markers; may or may not be destroyed via prompt
								google.maps.event.trigger(m, 'click');  // Trigger auto pop-up
							}
						}
					});
				});
				
				// Address default value
				$('#address').val('2110 Spruce St philly pa');
				
				// Marker confirmation - Yes handler
				$('#fullScreenMap').on('click','#yesMarker',function(e){
					markerNum = $('#markerNum').val();
					console.log("yes clicked: " + markerNum);
					
					// persist to db
					fullAdd = $('#fullAddress').val();
					shortAdd = $('#shortAddress').val();
					lat = $('#lat').val();
					lng = $('#lng').val();
					addLocationToDB(fullAdd, shortAdd, lat, lng);
					
					// Pop it out of the viewMarkers
					window.viewMarkers.splice(markerNum,1);
					
					// Force a refresh to get it into the modelMarkers
					refreshMarkers();
					
					// marker = window.markers[markerNum];  // get marker
					marker.infoWindow.close();    // already added to client model, just dimiss the infoWindow
				});

				// Marker confirmation - No handler
				$('#fullScreenMap').on('click','#noMarker',function(e){
					markerNum = $('#markerNum').val();
					console.log("no clicked: " + markerNum);
					
					marker = window.viewMarkers[markerNum];  // get marker
					window.viewMarkers.splice(markerNum,1);  // remove from client model
					marker.setMap(null);		  // remove from view
				});
			});
		</script>
	</div>
	</div>
</body>
</html>