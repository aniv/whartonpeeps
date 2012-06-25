<?php

	header('content-type: application/json; charset=utf-8'); // obviates the need for $.parseJSON on the client side..
	error_reporting (E_ALL ^ E_NOTICE);

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

	function GetFacebookPreviews($fbList)
	{
		for($fbList as $i=>$fbId)
		{
			$fql = "SELECT id, name, url, pic FROM profile";
			$res = $facebook->api(array('method'=>'fql.query','query'=>$fql));
			
			$result = array("profile_id"=>$res[0]['id'], "profile_name"=>$res[0]['name'], "profile_link"=>$res[0]['url'], "profile_photo_link"=>$res[0]['pic']);

			if (isset($callback))
				echo $callback . '('. json_encode($result) .')';
			else
				echo json_encode($result);
		}
	}

	// $user_id = $facebook->getUser();
	// 
	// if ($user_id) {
	//     try {
	// 
	//     } catch (FacebookApiException $e) {
	//         # If the call fails we check if we still have a user. The user will be
	//         # cleared if the error is because of an invalid accesstoken
	//         if (!$facebook->getUser()) {
	//             header('Location: ' . AppInfo::getUrl($_SERVER['REQUEST_URI']));
	//             exit();
	//         }
	//     }
	// 
	// }
	// else
	// {
	// 	echo "<!-- No Facebook data available -->";
	// }	
	
	$action = $_POST['action'];
	$fbList = $_POST['fbList'];
	$callback = $_POST['callback'];
	
	switch($action)
	{
		case "getFacebookPreviews":
			if (isset($fbList))
				GetFacebookPreviews($fbList);
			break;
	}
	
?>