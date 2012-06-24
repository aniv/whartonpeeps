<?php

	function GetDb()
	{
		if (getenv("DB_MODE") == "PROD")
			return GetProdDb();
		else
			return GetDevDb();
	}

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
	
	function AddPlaceAndUser($placeShort, $placeLong, $placeLat, $placeLng, $userId, $ip)
	{
		$db = GetDb();
		$result = $db->Places->insert(array("place_short"=>$placeShort, 
											"place_long"=>$placeLong,
											"lat_lng"=> array($placeLat,$placeLng), 
											"place_hash"=>sha1($place_long . $placeLat . $placeLng),
											"people"=> array($userId), 
											"created_date"=>new MongoDate(), 
											"created_by"=>$userId, 
											"created_ip"=>$ip,
											"schema_version"=>"1.0" ));
		return $result;						
		
	}

	function AddUserToPlace($placeId, $userId, $ip)
	{
		
	}

	function AddPlace($placeShort, $placeLong, $placeLat, $placeLng, $ip)
	{
		$db = GetDb();
		$result = $db->Places->insert(array("place_short"=>$placeShort, 
											"place_long"=>$placeLong,
											"lat_lng"=> array($placeLat,$placeLng), 
											"place_hash"=>sha1($place_long . $placeLat . $placeLng),
											"people"=> array($userId), 
											"created_date"=>new MongoDate(), 
											"created_by"=>null, 
											"created_ip"=>$ip,
											"schema_version"=>"1.0" ));
		return $result;						
	}
	
	
	
	$action = $_POST['action'];
	$placeShort = $_POST['shortAddress'];
	$placeLong = $_POST['fullAddress'];
	$placeLat = floatval($_POST['lat']);
	$placeLng = floatval($_POST['lng']);
	$placeId = intval($_POST['placeId']);
	$userId = intval($_POST['fbUserId']);
	$ip = $_POST['ip'];
	
	switch($action)
	{
		case "newPlaceAndUser":
			if (isset($placeShort, $placeLong, $placeLat, $placeLng, $userId, $ip))
				echo AddPlaceAndUser($placeShort, $placeLong, $placeLat, $placeLng, $userId, $ip);
			else
				echo -1;
			break;
		case "addUserToPlace":
			if (isset($placeId, $userId, $ip))
				echo AddUserToPlace($placeId, $userId, $ip);
			else
				echo -1;
			break;
		case "addPlace":
			if(isset($placeShort, $placeLong, $placeLat, $placeLng, $ip))
				echo AddPlace($placeShort, $placeLong, $placeLat, $placeLng, $ip);
			else
				echo -1;
			break;
	}
?>