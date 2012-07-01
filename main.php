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
	
			// Wharton = 169174513170821
			// Test = 330277880384395
		    $groupsW = $facebook->api(array(
		        'method' => 'fql.query',
		        'query' => 'SELECT uid, gid FROM group_member WHERE gid = 169174513170821 AND uid=me()'
		    ));

		    $groupsT = $facebook->api(array(
		        'method' => 'fql.query',
		        'query' => 'SELECT uid, gid FROM group_member WHERE gid = 330277880384395 AND uid=me()'
		    ));

			// Neither in test nor wharton fb groups
			if (!isset($groupsW['data']['uid']) and !isset($groupsT['data']['gid']))
			{
				d($groupsW);
				echo !isset($groupsW['data']['uid']);
				d($groupsT);
				echo !isset($groupsT['data']['gid']);
				
	            // header('Location: ' . AppInfo::getUrl('/unauthorized.php'));
	            // exit();
			}

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
	
		// echo "<!-- ". $profile_id .",". $profile_name .",". $profile_link .",". $profile_photo_link . "--> ";
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
		<style>
		#infoWindow {
			width: 250px;
			height: 120px;
			padding: 5px;
			overflow: hidden;
		}
		
		#infoWindow img {
			width: 25px;
			height: 25px;
			padding: 2px;
		}
		
		.infoWindowButtons {
			margin: 5px;
			margin-left: 2.5px;
		}
		</style>

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
		<form method="post" class="form-search span6" id="addressForm" style="margin-top:4px">
			<input type="text" class="input-xxlarge" placeholder="Enter your address here" id="addressBox">
			<button type="submit" class="btn btn-primary"><i class="icon-map-marker icon-white"></i> Find it!</button>
			<img src="images/ajax-loader.gif" id="spinner" height="28px" style="padding-left:25px">
		</form>
		<div class="span2">
			<a class="btn btn-inverse <?php if (!$user_id) echo "disabled"; ?>" style="margin-top:4px" alt="<?php if (!$user_id) echo "Facebook API Unavailable"; else echo "Logout"; ?>" href="<?php if ($user_id) echo $facebook->getLogoutUrl(array("next"=>"https://".$_SERVER['HTTP_HOST'])); ?>">Log-out</a>
		</div>
	</div>
	
	<div class="row-fluid">
		<div id="fullScreenMap" class="span12"></div>
		<script type="text/javascript">
	
			window.map = null;
			window.viewMarkers = {};
			window.modelMarkers = {};
			window.clientIP = null;
			window.infoWindowOpen = null;
			
			function refreshMarkers()
			{
				console.log("RefreshMarkers() called");
				currentBounds = window.map.map.getBounds();

				$.ajax({
					type: "GET",
					url: "db/fetch.php",
					data: { 
						action: "refreshMarkers",
						ne_lat: currentBounds.getNorthEast().lat(), 
						ne_lng: currentBounds.getNorthEast().lng(), 
						sw_lat: currentBounds.getSouthWest().lat(), 
						sw_lng: currentBounds.getSouthWest().lng(),
						fbUserId: ('<?php echo $profile_id; ?>' == '' ? 0 : '<?php echo $profile_id; ?>')
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
				
				window.map.removeMarkers();
				
				for(i in markersInCurrentBounds)
				{
					mm = markersInCurrentBounds[i];
					
					// rebuild the view just for the model markers (view markers are left as is)
					mvm = window.map.addMarker({
						lat: mm.lat_lng[0],
						lng: mm.lat_lng[1],
						title: mm.place_short,
						infoWindow : {
							content: existingMarkerInfoWindowMarkup(mm.place_short, mm.place_long, mm.people, mm.user_at_place, i, mm.place_hash)
						}
					});
					
					google.maps.event.addListener(mvm.infoWindow, 'domready', function() {
						getFacebookPreviews(mm.people, i);
			        });

					google.maps.event.addListener(mvm.infoWindow, 'closeclick', function() {
						window.infoWindowOpen = null;
					});

					google.maps.event.addListener(mvm, 'click', function(e){
					    console.log("clicked on a marker");
						console.log(e);
						e.cancelBubble = true;
						e.stop();
						
						window.infoWindowOpen = mvm;
					});
					
					// rebuild the model markers
					// over time, modelMarkers will hold all markers that were ever requested from db
					markerNum = murmurhash3_32_gc(mm.place_long,Math.floor(Math.random()*1e6)).toString();
					window.modelMarkers[markerNum] = mm;
					// window.modelMarkers.push(mm);
					mm.markerNum = markerNum;
					
					// Process the dirty view markers
					for(j in dirtyViewMarkers)
					{
						// 1. compare against view markers to see if dirty flag needs updating
						if (dirtyViewMarkers[j][2] == mm.markerNum)
						{
							k = dirtyViewMarkers[j][2];  // we stored the viewMarker index in the tuple
							window.viewMarkers[k].dirty = false;  // kind redundant huh?
							delete window.viewMarkers[k];
						}
					}
				}				
					
				// We have now processed all markers in window.viewMarkers, anything left is still dirty (might be cleaned out in the next refresh)
				// Till then, we need to keep it around in the view.
				// We do that by removing all markers from the map (done on line 3 in refreshMarkersHandler), but drawing back all the view markers
				window.map.addMarkers(window.viewMarkers);
			}

			function addLocationToDB(fullAdd, shortAdd, lat, lng)
			{
				addLocationAndUserToDB(fullAdd, shortAdd, lat, lng, null);				
			}
			
			function addLocationAndUserToDB(fullAdd, shortAdd, lat, lng, fb)
			{
				data = {};
				data.action = "newPlaceAndUser";
				data.fullAddress = fullAdd;
				data.shortAddress = shortAdd;
				data.lat = lat; 
				data.lng = lng;
				data.ip = window.clientIP;
				data.fbUserId = fb;
				// data.fbUserName = (fb ? fb.user_name : fb);
				// data.fbProfileUrl = (fb ? fb.profile_url : fb);
				// data.fbProfilePhotoUrl = (fb ? fb.profile_photo_url : fb);
								
				$.ajax({
					type: "POST",
					url: "db/save.php",
					data: data
				}).done(function(msg){
					console.log("Data sent to server");
					console.log("Server response: " + msg);
				});
			}
			
			function getMarkerForAddress(fullAdd, shortAdd, latlng)
			{
				$.ajax({
					type: "GET",
					url: "db/fetch.php",
					data: {
						action: "markerForAddress",
						fullAddress: fullAdd,
						lat: latlng.lat(),
						lng: latlng.lng(),
						fbUserId: ('<?php echo $profile_id; ?>' == '' ? 100 : '<?php echo $profile_id; ?>')
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
						window.infoWindowOpen = m;
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
								content: existingMarkerInfoWindowMarkup(dbMarker.place_short, dbMarker.place_long, dbMarker.people, dbMarker.user_at_place, markerNum, dbMarker.place_hash)
							}
						});
						window.viewMarkers[markerNum] = m;  // add to view markers; may or may not be destroyed via prompt
						google.maps.event.trigger(m, 'click');  // Trigger auto pop-up
						window.infoWindowOpen = m;
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
					window.infoWindowOpen = m;
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
		
			function existingMarkerInfoWindowMarkup(shortAddress, fullAddress, people, userIsHere, markerNum, hash)
			{
				var peopleImageList = "";
				for(p in people)
				{
					fbUserId = people[p];
					peopleImageList += "<a id='p"+fbUserId+"' href='#' alt='#'><img src=\'https://graph.facebook.com/"+fbUserId+"/picture?type=square&return_ssl_resources=1\'></a>";
				}

				markup = "<div id='infoWindow' style='height:"+ ((Math.ceil(people.length / 6)+2)*30) +"px'>" +
					   "Wharton peeps at " + shortAddress + ": <br/>" +
					   peopleImageList + " <a href='place.php?hash=" + hash + "'>more..</a><br/>";
					
				if (userIsHere)
					markup += "<button class='btn btn-mini btn-success infoWindowButtons disabled' type='submit' id='addUser'>You're here</button>";  					
				else
					markup += "<button class='btn btn-mini btn-success infoWindowButtons' type='submit' id='addUser'>Add Yourself Here</button>";
				
				markup += "<input type='hidden' id='markerNum' name='markerNum' value='"+ markerNum +"'/>" +
					   "<input type='hidden' id='hash' name='hash' value='"+ hash +"'/>" +
					   "</div>";
					
				return markup;
			}
			
			function getFacebookPreviews(fbList, markerNum)
			{
				$.ajax({
					type: "GET",
					url: "db/fb.php",
					data: { 
						action: "getFacebookPreviews",
						fbList: fbList
					},
				}).done(function(previews){
					for(p in previews)
					{
						if (previews[p].profile_id == null)  // local debug
							previews[p].profile_id = 0;

						$("#p"+previews[p].profile_id).ready(function(){
							$(this).attr('href', previews[p].profile_url);
							$(this).attr('alt', previews[p].profile_name);
						});
					}
				});
			}
			
			
			/**** 

				Begin on document ready 

			*****/
			
	
			$(document).ready(function(){
				
				// console.log("Setting map height to " + ($(window).height()-46-12-25)+'px');
				$("#fullScreenMap").height($(window).height()-46-12-25);
				
				// Main map config
				window.map = new GMaps({
					div: '#fullScreenMap',
					lat: 39.949457,
					lng: -75.171998,
					zoom: 16,
					height: ($(window).height()-46-12-25)+'px',
					idle: function(){ console.log("idle"); if (window.infoWindowOpen == null) refreshMarkers(); },
					dragend: function() { if (window.infoWindowOpen != null) { window.infoWindowOpen.infoWindow.close(); refreshMarkers(); } }
				});
				
				// Client IP address
				$.ajax({
					type: "GET",
					url: "db/fetch.php",
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
				
				// AJAX spinners
				$('#spinner').ajaxStart(function(){
					$(this).show();
				}).ajaxStop(function(){
					$(this).hide();
				});
				
				// InfoWindow height adjuster
				// for (mm in window.map.markers)
				// 	google.maps.event.addListener(window.map.markers[mm].infoWindow, 'domready', function() {
				// 	  var height = $('#infoWindow').parent().parent().parent().height();
				// 	  height = 320;
				// 	  $('#infoWindow').parent().parent().parent().height(height);
				// 	});
				
				// Marker (New Location) confirmation - Yes handler
				$('#fullScreenMap').on('click','#yesMarker',function(e){
					markerNum = $('#markerNum').val().toString();
					console.log("yes clicked: " + markerNum);
					
					// Persist to db
					fullAdd = $('#fullAddress').val();
					shortAdd = $('#shortAddress').val();
					lat = $('#lat').val();
					lng = $('#lng').val();
					<?php
						if (!$user_id)
							echo "fb = null";
						else
							echo "fb = $profile_id";
					?>
					
					//addLocationToDB(fullAdd, shortAdd, lat, lng);
					addLocationAndUserToDB(fullAdd, shortAdd, lat, lng, fb);
					
					// Dismiss info window
					marker = window.viewMarkers[markerNum];
					window.viewMarkers[markerNum].infoWindow.content = existingMarkerInfoWindowMarkup(shortAdd, fullAdd, [fb], true, markerNum, 0);
					window.viewMarkers[markerNum].infoWindow.close();
					window.viewMarkers[markerNum].dirty = true;  // set dirty flag
					
					// Remove from view (will be added back in during refreshMarkers())
					// marker.setMap(null);

					// Pop it out of the viewMarkers
					// window.viewMarkers.splice(markerNum,1);
					
					// Force a refresh
					refreshMarkers();
				});

				// Marker (New Location) confirmation - No handler
				$('#fullScreenMap').on('click','#noMarker',function(e){
					markerNum = $('#markerNum').val().toString();
					console.log("no clicked: " + markerNum);
					
					marker = window.viewMarkers[markerNum];  // get marker
					marker.setMap(null);		  // remove from view
					delete window.viewMarkers[markerNum];  // remove from client model
				});
				
				// InfoWindow Add User to Location - button handler
				$('#fullScreenMap').on('click','#addUser',function(e){
					placeHash = $('#hash').val().toString();
					markerNume = $('#markerNum').val().toString();
					
					if (!$(this).hasClass('disabled'))
					{
						console.log("add user to placeHash: " + placeHash);
					
						$.ajax({
							type: "POST",
							url: "db/save.php",
							data: {
								action: "addUserToPlace",
								placeHash: placeHash,
								//TODO: remove this debug eventually
								fbUserId: ('<?php echo $profile_id; ?>' == '' ? 0 : '<?php echo $profile_id; ?>'),
								ip: window.clientIP
							}
						}).done(function(msg){
							$("#addUser").addClass("disabled");
							$("#addUser").text("You're here");
						});
					}
				});
				
				
			});
		</script>
	</div>
	</div>
</body>
</html>