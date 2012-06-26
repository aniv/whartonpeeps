<?php

	header('content-type: application/json; charset=utf-8'); // obviates the need for $.parseJSON on the client side..
	error_reporting (E_ALL ^ E_NOTICE);

	# Provides access to app specific values such as your app id and app secret.
	# Defined in 'AppInfo.php'
	require_once '../AppInfo.php';
	require_once '../kint/Kint.class.php';
	require_once '../utils.php';
	require_once '../sdk/src/facebook.php';

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
	    'secret' => AppInfo::appSecret()
	));

	$action = $_GET['action'];
	$fbList = $_GET['fbList'];
	$callback = $_GET['callback'];
	
	switch($action)
	{
		case "getFacebookPreviews":
			if (isset($fbList))
			{
				$fbResultSet = array();
				
				foreach($fbList as $fbUserId)
				{
					$fql = "SELECT id, name, url, pic FROM profile WHERE id = $fbUserId";
					// echo $fql;
					$res = $facebook->api(array('method'=>'fql.query','query'=>$fql));

					array_push($fbResultSet, array("profile_id"=>$res[0]['id'], "profile_name"=>$res[0]['name'], "profile_link"=>$res[0]['url'], "profile_photo_link"=>$res[0]['pic']));

				}

				if (isset($callback))
					echo $callback . '('. json_encode($fbResultSet) .')';
				else
					echo json_encode($fbResultSet);
			}
			break;
	}
	
?>