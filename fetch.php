<?php

	function GetDevDb()
	{
		$m = new Mongo("mongodb://localhost/wpeeps");
		$db = $m->selectDB("wpeeps");
		return $db;
	}

	function GetProdDb()
	{
		$m = new Mongo("mongodb://wpeep:wpeep@flame.mongohq.com:27092/phpfogffc59520_9d6d_012f_df4e_7efd45a4c57b");
		$db = $m->selectDB("phpfogc59e6360_8910_012f_cdf0_7efd45a4c57b");
		return $db;
	}
	
	function GetPlacesInBox($ne_lat, $ne_lng, $sw_lat, $sw_lng)
	{
		// db.places.find({"loc" : {"$within" : {"$box" : box}}})
		
		$db = GetDevDb();
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
			array_push($markers, array("place_short"=>$doc['place_short'], "place_long"=>$doc['place_long'], "lat_lng"=>$doc['lat_lng'], "people"=>$doc['people']));
			
		echo json_encode($markers);
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
	
	switch($action)
	{
		case "refreshMarkers":
			if (isset($ne_lat, $ne_lng, $sw_lat, $sw_lng))
				GetPlacesInBox($ne_lat, $ne_lng, $sw_lat, $sw_lng);
			else
				echo -1;
			break;
	}
?>