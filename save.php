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
	
	function AddPlaceAndUser($placeShort, $placeLong, $placeLat, $placeLng, $userId)
	{
		$db = GetDevDb();
		$result = $db->Places->insert(array("place_short"=>$placeShort, 
											"place_long"=>$placeLong,
											"lat_lng"=> array($placeLat,$placeLng), 
											"place_hash"=>sha1($place_long . $placeLat . $placeLng),
											"people"=> array($userId), 
											"created_date"=>new MongoDate(), 
											"created_by"=>$userId, 
											"created_ip"=>$_SERVER['REMOTE_ADDR']) );
		return $result;						
		
	}

	function AddUserToPlace($placeId, $userId)
	{
		
	}

	function AddPlace($placeShort, $placeLong, $placeLat, $placeLng)
	{
		$db = GetDevDb();
		$result = $db->Places->insert(array("place_short"=>$placeShort, 
											"place_long"=>$placeLong,
											"lat_lng"=> array($placeLat,$placeLng), 
											"place_hash"=>sha1($place_long . $placeLat . $placeLng),
											"people"=> array($userId), 
											"created_date"=>new MongoDate(), 
											"created_by"=>null, 
											"created_ip"=>$_SERVER['REMOTE_ADDR']) );
		return $result;						
	}
	
	
	
	$action = $_POST['action'];
	$placeShort = $_POST['shortAddress'];
	$placeLong = $_POST['fullAddress'];
	$placeLat = floatval($_POST['lat']);
	$placeLng = floatval($_POST['lng']);
	$placeId = intval($_POST['placeId']);
	$userId = intval($_POST['fbUserId']);
	
	switch($action)
	{
		case "newPlaceAndUser":
			if (isset($placeShort, $placeLong, $placeLat, $placeLng, $userId))
				echo AddPlaceAndUser($placeShort, $placeLong, $placeLat, $placeLng, $userId);
			else
				echo -1;
			break;
		case "addUserToPlace":
			if (isset($placeId, $userId))
				echo AddUserToPlace($placeId, $userId);
			else
				echo -1;
			break;
		case "addPlace":
			if(isset($placeShort, $placeLong, $placeLat, $placeLng))
				echo AddPlace($placeShort, $placeLong, $placeLat, $placeLng);
			else
				echo -1;
			break;
	}
?>