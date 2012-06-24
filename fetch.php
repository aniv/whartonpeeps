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
		$m = new Mongo("mongodb://wpeep:wpeep@flame.mongohq.com:27092/phpfogffc59520_9d6d_012f_df4e_7efd45a4c57b");
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
	
	function GetPlacesInBox($ne_lat, $ne_lng, $sw_lat, $sw_lng, $callback)
	{
		// db.places.find({"loc" : {"$within" : {"$box" : box}}})
		
		$db = GetDb();
		$ne_lat = floatval($ne_lat);
		$ne_lng = floatval($ne_lng);
		$sw_lat = floatval($sw_lat);
		$sw_lng = floatval($sw_lng);
		
		$queryStr = array("lat_lng"=>array('$within'=>array('$box'=>array(array($sw_lat, $sw_lng),array($ne_lat, $ne_lng)))));
		// $queryStr2 = array("lat_lng"=>array('$near'=>array($sw_lat, $sw_lng)));
		// error_log("Query: db.Places.find({'lat_lng':{ \$within :{ \$box : [[".$sw_lat.",".$sw_lng."],[".$ne_lat.",".$ne_lng."]] } }})");
		// error_log("Query: db.Places.find({'lat_lng':{ \$near :[".$sw_lat.",".$sw_lng."]} })");
		// error_log(var_export($queryStr2,true));
		
		$c = $db->Places->find($queryStr);
		// error_log("query returns ".$c->count()." results");
		
		
		$markers = array();
		foreach($c as $doc)
			array_push($markers, array("place_short"=>$doc['place_short'], "place_long"=>$doc['place_long'], "lat_lng"=>$doc['lat_lng'], "place_hash"=>$doc['place_hash'],"people"=>$doc['people']));
			
		if (isset($callback))
			echo $callback . '('. json_encode($markers) .')';
		else
			echo json_encode($markers);
	}
	
	function GetPlaceForAddress($fullAdd, $lat, $lng, $callback)
	{
		$db = GetDb();
		$lat = floatval($lat);
		$lng = floatval($lng);
		
		$queryStr = array("place_long"=>$fullAdd, "lat_lng"=>array($lat,$lng));
		
		$c = $db->Places->find($queryStr);
		
		$markers = array();
		foreach($c as $doc)
			array_push($markers, array("place_short"=>$doc['place_short'], "place_long"=>$doc['place_long'], "lat_lng"=>$doc['lat_lng'], "place_hash"=>$doc['place_hash'],"people"=>$doc['people']));

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
	
	function GetTopUsersForPlace($placeId)
	{
		
	}
	
	function GetExpandedUsersForPlace($placeId)
	{
		
	}

	
	$action = $_GET['action'];
	$ne_lat = $_GET['ne_lat'];
	$ne_lng = $_GET['ne_lng'];
	$sw_lat = $_GET['sw_lat'];
	$sw_lng = $_GET['sw_lng'];
	
	$fullAddress = $_GET['fullAddress'];
	$lat = $_GET['lat'];
	$lng = $_GET['lng'];
	
	$callback = $_GET['callback'];
	
	switch($action)
	{
		case "refreshMarkers":
			if (isset($ne_lat, $ne_lng, $sw_lat, $sw_lng))
				GetPlacesInBox($ne_lat, $ne_lng, $sw_lat, $sw_lng, $callback);
			else
				echo -1;
			break;
		case "markerForAddress":
			if (isset($fullAddress, $lat, $lng))
				GetPlaceForAddress($fullAddress, $lat, $lng, $callback);
			else
				echo -1;
			break;
		case "getIP":
			GetClientIpAddress($callback);
			break;
	}
?>