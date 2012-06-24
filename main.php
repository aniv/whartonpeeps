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

	$user_id = $facebook->getUser();
	if ($user_id) {
	    try {
	        # Fetch the viewer's basic information
	        $basic = $facebook->api('/me?fields=id,name,picture,link');
	    } catch (FacebookApiException $e) {
	        # If the call fails we check if we still have a user. The user will be
	        # cleared if the error is because of an invalid accesstoken
	        if (!$facebook->getUser()) {
	            header('Location: ' . AppInfo::getUrl($_SERVER['REQUEST_URI']));
	            exit();
	        }
	    }

		$profile_id = $basic['id'];
		$profile_name = $basic['name'];
		$profile_link = $basic['link'];
		$profile_photo_link = "https://graph.facebook.com/".$profile_id."/picture?type=square&return_ssl_resources=1";
	
		echo "<!-- ". $profile_id .",". $profile_name .",". $profile_link .",". $profile_photo_link . "--> ";
	}
	else
	{
		echo "<!-- No Facebook data available -->";
	}	
?>

<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
    <head>
		<title>WhartonPeeps</title>
		<script type="text/javascript" src="javascript/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="javascript/murmurhash-3.min.js"></script>		
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&sensor=true"></script>
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
			<b>Start here!</b> Search for your address in the box:
		</div>
		<form method="post" class="form-inline span6" id="addressForm" style="margin-top:4px">
			<input type="text" class="span5" placeholder="Enter your address here" id="addressBox">
			<button type="submit" class="btn btn-primary"><i class="icon-map-marker icon-white"></i> Find it!</button>
			<img src="images/ajax-loader.gif" id="spinner" height="28px" style="padding-left:25px">
		</form>
		<div class="span2">
			<a class="btn btn-inverse <?php if (!$user_id) echo "disabled"; ?>" style="margin-top:4px" title="<?php if (!$user_id) echo "Facebook API Unavailable"; ?>" href="<?php if ($user_id) $facebook->getLogoutUrl(); ?>">Log-out</a>
		</div>
	</div>
	
	<div class="row-fluid">
		<div id="fullScreenMap" class="span12"></div>
		<script type="text/javascript">
	
			window.map = null;
			window.viewMarkers = {};
			window.modelMarkers = {};
			window.clientIP = null;
			
			function refreshMarkers()
			{
				console.log("RefreshMarkers() called");
				currentBounds = window.map.map.getBounds();

				rm = $.ajax({
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
					console.log("Received fresh markers");
					// console.log("Server response: " + $.serializeArray(msg));
					refreshMarkersHandler(msg);
				});
			}
			
			function refreshMarkersHandler(markersInCurrentBounds)
			{
				window.modelMarkers = {}; // reset model markers
				dirtyViewMarkers = $.map(window.viewMarkers, function(vm,i){ if (vm.dirty) return [[vm.position.lat(), vm.position.lng(), i]];} );
				
				for(i in markersInCurrentBounds)
				{
					mm = markersInCurrentBounds[i];
					
					// rebuild the view just for the model markers (view markers are left as is)
					window.map.addMarker({
						lat: mm.lat_lng[0],
						lng: mm.lat_lng[1],
						title: mm.place_short,
						infoWindow : {
							content: existingMarkerInfoWindowMarkup(mm.place_short, mm.place_long, mm.people, i, "")
						}
					});

					// rebuild the model markers
					// over time, modelMarkers will hold all markers that were ever requested from db
					markerNum = murmurhash3_32_gc(mm.place_long,Math.floor(Math.random()*1e6)).toString();
					window.modelMarkers[markerNum] = mm;
					// window.modelMarkers.push(mm);
					mm.markerNum = markerNum;
					
					// compare against view markers to see if dirty flag needs updating
					for(j in dirtyViewMarkers)
						//if (dirtyViewMarkers[j][0] == mm.lat_lng[0] && dirtyViewMarkers[j][1] == mm.lat_lng[1])
						if (dirtyViewMarkers[j][2] == mm.markerNum)
						{
							k = dirtyViewMarkers[j][2];  // we stored in the index in the tuple
							window.viewMarkers[k].dirty = false;  // kind redundant huh?
							delete window.viewMarkers[k];
						}
				}
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
						ip: window.clientIP,
						fbUserId: null
					},
				}).done(function(msg){
					console.log("Data sent to server");
					console.log("Server response: " + msg);
				});
			}
			
			function getMarkerForAddress(fullAdd, shortAdd, latlng)
			{
				$.ajax({
					type: "GET",
					url: "fetch.php",
					data: {
						action: "markerForAddress",
						fullAddress: fullAdd,
						lat: latlng.lat(),
						lng: latlng.lng()
					}
				}).done(function(msg){
					getMarkerForAddressHandler(msg, fullAdd, shortAdd, latlng);
				});
			}
			
			function getMarkerForAddressHandler(dbMarkers, fullAddress, shortAddress, latlng)
			{
				// Triggered when Marker found in DB
				// there are two options at this point
				//   option 1. marker is not in current view - we'll need to add it in
				//   option 2. marker is in current view - we need to surface it visually
				// we start by searching for it in modelMarkers and the map's markers
				if (dbMarkers.length > 0)
				{
					dbMarker = dbMarkers[0];
					
					// find it: look up it up the window.modelMarkers first, and then in the window.map.markers
					mmIndex = null;
					//$.inArray(dbMarker.place_long, $.map(window.modelMarkers, function(v,i) { return v.place_long; }) );
					for (i in window.modelMarkers)
					{
						if (window.modelMarkers[i].place_long == dbMarker.place_long)
						{
							mmIndex = i;
							break;
						}
					}
					
					mkCoords = $.map(window.map.markers, function(v,i) { return [[v.position.lat(), v.position.lng()]]; });
					mkIndex = -1;
					for(k in mkCoords)
					{
						if (window.modelMarkers[mmIndex].lat_lng[0] == mkCoords[k][0] && 
							window.modelMarkers[mmIndex].lat_lng[1] == mkCoords[k][1])
						{
							mkIndex = k;
							break;
						}
					}
					
					// option 1. marker is in view somewhere
					if (mkIndex > 0)
					{
						m = window.map.markers[mkIndex];
						google.maps.event.trigger(m, 'click');  // Trigger auto pop-up
					}
					// option 2. marker is not in view, so we add it in
					else
					{
						markerNum = murmurhash3_32_gc(fullAddress,Math.floor(Math.random()*1e6)).toString();
						m = window.map.addMarker({
							lat: latlng.lat(),
							lng: latlng.lng(),
							title: dbMarker.place_short,
							infoWindow: {
								content: existingMarkerInfoWindowMarkup(dbMarker.place_short, dbMarker.place_long, dbMarker.people, markerNum, " ")
							}
						});
						window.viewMarkers[markerNum] = m;  // add to view markers; may or may not be destroyed via prompt
						google.maps.event.trigger(m, 'click');  // Trigger auto pop-up
					}
					$("#infoBox").html("<b>Great news!</b> Wharton peeps at that address");
					$("#infoBox").addClass("alert-success").removeClass("alert-info");
					
				}
				// marker not found in db
				else
				{
					// option 1. marker needs to be added to view
					$("#infoBox").html("<b>Cool!</b> That's a new address for WhartonPeeps");
					$("#infoBox").addClass("alert-success").removeClass("alert-info");

					markerNum = murmurhash3_32_gc(fullAddress,Math.floor(Math.random()*1e6)).toString();
				
					m = window.map.addMarker({
						lat: latlng.lat(),
						lng: latlng.lng(),
						infoWindow: {
							content: newMarkerInfoWindowMarkup(latlng, fullAddress, shortAddress, markerNum)
						}
					});

					window.viewMarkers[markerNum] = m;  // add to view markers; may or may not be destroyed via prompt
					google.maps.event.trigger(m, 'click');  // Trigger auto pop-up
				}
				
				// TODO: do we even need this?
				// window.map.setCenter(latlng.lat(), latlng.lng());
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
		
			function existingMarkerInfoWindowMarkup(shortAddress, fullAddress, people, markerNum, extra)
			{
				var peopleList = "";
				for(p in people)
					peopleList += p;
					
				return "<div>" +
					   "Wharton peeps at \"" + shortAddress + "\" <br/>" +
					   peopleList + 
					   "<input type='hidden' id='markerNum' name='markerNum' value='"+ markerNum +"'/>" +
					   "</div>";
			}
	
			$(document).ready(function(){
				
				// Main map config
				window.map = new GMaps({
					div: '#fullScreenMap',
					lat: 39.949457,
					lng: -75.171998,
					zoom: 16,
					height: ($(window).height()-46-12-25)+'px',
					idle: refreshMarkers
				});
				
				// Client IP address
				rm = $.ajax({
					type: "GET",
					url: "fetch.php",
					data: { 
						action: "getIP"
					},
				}).done(function(msg){
					window.clientIP = msg;
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
							if (status == "OK")  // geocoding successful
							{   
								var latlng = results[0].geometry.location;
								var fullAddress = results[0].formatted_address;
								var shortAddress = results[0].formatted_address.split(',')[0];

								// check to see if in db
								getMarkerForAddress(fullAddress, shortAddress, latlng);
							}
						}
					});
				});
				
				$('#spinner').ajaxStart(function(){
					$(this).show();
				}).ajaxStop(function(){
					$(this).hide();
				});
				
				// Marker confirmation - Yes handler
				$('#fullScreenMap').on('click','#yesMarker',function(e){
					markerNum = $('#markerNum').val().toString();
					console.log("yes clicked: " + markerNum);
					
					// Persist to db
					fullAdd = $('#fullAddress').val();
					shortAdd = $('#shortAddress').val();
					lat = $('#lat').val();
					lng = $('#lng').val();
					addLocationToDB(fullAdd, shortAdd, lat, lng);
					
					// Dismiss info window
					marker = window.viewMarkers[markerNum];
					window.viewMarkers[markerNum].infoWindow.content = existingMarkerInfoWindowMarkup(shortAdd, fullAdd, [0], markerNum, " dirty ");
					window.viewMarkers[markerNum].infoWindow.close();
					window.viewMarkers[markerNum].dirty = true;  // set dirty flag
					
					// Remove from view (will be added back in during refreshMarkers())
					// marker.setMap(null);

					// Pop it out of the viewMarkers
					// window.viewMarkers.splice(markerNum,1);
					
					// Force a refresh
					refreshMarkers();
				});

				// Marker confirmation - No handler
				$('#fullScreenMap').on('click','#noMarker',function(e){
					markerNum = $('#markerNum').val().toString();
					console.log("no clicked: " + markerNum);
					
					marker = window.viewMarkers[markerNum];  // get marker
					marker.setMap(null);		  // remove from view
					delete window.viewMarkers[markerNum];  // remove from client model
				});
			});
		</script>
	</div>
	</div>
</body>
</html>