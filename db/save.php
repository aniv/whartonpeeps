<?php

	error_reporting (E_ALL ^ E_NOTICE);
	
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
		$db = $m->selectDB("phpfogffc59520_9d6d_012f_df4e_7efd45a4c57b");
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

	function AddUserToPlace($placeHash, $userId, $ip)
	{
		$db = GetDb();

		$findCode = array("place_hash" => $placeHash);
		$changeCode = array('$addToSet' => array("people" => $userId));
		
		$result = $db->Places->update($findCode, $changeCode);
		
		return $result;
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
	$placeHash = $_POST['placeHash'];
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
			if (isset($placeHash, $userId, $ip))
				echo AddUserToPlace($placeHash, $userId, $ip);
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