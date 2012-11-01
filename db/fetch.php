<?php

	header('content-type: application/json; charset=utf-8'); // obviates the need for $.parseJSON on the client side..
	error_reporting (E_ALL ^ E_NOTICE);
	
	function GetDevDb()
	{
		$m = new Mongo("mongodb://localhost/wpeeps");
		$db = $m->selectDB("wpeeps");
		return $db;
	}

	function GetProdDb()
	{
		$m = new Mongo(getenv("MONGOHQ_URL"));
		$db = $m->selectDB("phpfogffc59520_9d6d_012f_df4e_7efd45a4c57b");
		return $db;
	}
	
	function GetDb()
	{
		if (getenv("DB_MODE") == "PROD")
			return GetProdDb();
		else
			return GetDevDb();
	}
	
	function GetPlacesInBox($ne_lat, $ne_lng, $sw_lat, $sw_lng, $userId, $callback)
	{
		// db.places.find({"loc" : {"$within" : {"$box" : box}}})
		
		$db = GetDb();
		$ne_lat = floatval($ne_lat);
		$ne_lng = floatval($ne_lng);
		$sw_lat = floatval($sw_lat);
		$sw_lng = floatval($sw_lng);
		$userId = intval($userId);
		
		$queryStr = array("lat_lng"=>array('$within'=>array('$box'=>array(array($sw_lat, $sw_lng),array($ne_lat, $ne_lng)))));
		// $queryStr2 = array("lat_lng"=>array('$near'=>array($sw_lat, $sw_lng)));
		// error_log("Query: db.Places.find({'lat_lng':{ \$within :{ \$box : [[".$sw_lat.",".$sw_lng."],[".$ne_lat.",".$ne_lng."]] } }})");
		// error_log("Query: db.Places.find({'lat_lng':{ \$near :[".$sw_lat.",".$sw_lng."]} })");
		// error_log(var_export($queryStr2,true));
		
		$c = $db->Places->find($queryStr);
		// error_log("query returns ".$c->count()." results");
		
		
		$markers = array();
		foreach($c as $doc)
		{
			$x = $doc['people'];
			$currentUserAtPlace = in_array($userId, $x);
			$y = array_slice($x, 0, 22, true);

			array_push($markers, array("place_short"=>$doc['place_short'], "place_long"=>$doc['place_long'], "lat_lng"=>$doc['lat_lng'], "place_hash"=>$doc['place_hash'],"people"=>$y, "user_at_place"=>$currentUserAtPlace));
		}
		
		if (isset($callback))
			echo $callback . '('. json_encode($markers) .')';
		else
			echo json_encode($markers);
	}
	
	function GetPlaceForAddress($fullAdd, $lat, $lng, $userId, $callback)
	{
		$db = GetDb();
		$lat = floatval($lat);
		$lng = floatval($lng);
		
		$queryStr = array("place_long"=>$fullAdd, "lat_lng"=>array($lat,$lng));
		
		$c = $db->Places->find($queryStr);
		
		$markers = array();
		foreach($c as $doc)
		{
			$x = $doc['people'];
			$currentUserAtPlace = (array_search($userId, $x) != false ? true : false);
			$y = array_slice($x, 0, 22, true);
			
			array_push($markers, array("place_short"=>$doc['place_short'], "place_long"=>$doc['place_long'], "lat_lng"=>$doc['lat_lng'], "place_hash"=>$doc['place_hash'],"people"=>$y, "user_at_place"=>$currentUserAtPlace));
		}
		
		if (isset($callback))
			echo $callback . '('. json_encode($markers) .')';
		else
			echo json_encode($markers);
	}
	
	function GetClientIpAddress($callback)
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		
		if (isset($callback))
			echo $callback . '('. $ip .')';
		else
			echo "\"". $ip . "\"";
	}
	
	function GetExpandedUsersForPlace($placeHash)
	{
		$db = GetDb();
		$queryStr = array("place_hash"=>$placeHash);
		
		$c = $db->Places->find($queryStr);
		
		$placeDetails = array();
		foreach($c as $doc)
		{
			array_push($placeDetails, array("place_short"=>$doc['place_short'], "place_long"=>$doc['place_long'], "lat_lng"=>$doc['lat_lng'], "place_hash"=>$doc['place_hash'],"people"=>$doc['people']));
		}
		
		return $placeDetails[0];
	}
	
	$action = $_GET['action'];
	$ne_lat = $_GET['ne_lat'];
	$ne_lng = $_GET['ne_lng'];
	$sw_lat = $_GET['sw_lat'];
	$sw_lng = $_GET['sw_lng'];
	
	$fullAddress = $_GET['fullAddress'];
	$lat = $_GET['lat'];
	$lng = $_GET['lng'];
	$userId = $_GET['fbUserId'];
	
	$callback = $_GET['callback'];
	
	switch($action)
	{
		case "refreshMarkers":
			if (isset($ne_lat, $ne_lng, $sw_lat, $sw_lng, $userId))
				GetPlacesInBox($ne_lat, $ne_lng, $sw_lat, $sw_lng, $userId, $callback);
			else
				echo -1;
			break;
		case "markerForAddress":
			if (isset($fullAddress, $lat, $lng, $userId))
				GetPlaceForAddress($fullAddress, $lat, $lng, $userId, $callback);
			else
				echo -1;
			break;
		case "getIP":
			GetClientIpAddress($callback);
			break;
	}
?>