<?php

	require_once 'db/fetch.php';
	require_once 'AppInfo.php';
	require_once 'kint/Kint.class.php';
	require_once 'utils.php';
	require_once 'sdk/src/facebook.php';

	header('content-type: text/html;charset=UTF-8');

	$placeHash = $_GET['hash'];
	$placeDetails = GetExpandedUsersForPlace($placeHash);
	
	$place_short = $placeDetails['place_short'];
	$place_long = $placeDetails['place_long'];
	$people = $placeDetails['people'];
	$lat_lng = $placeDetails['lat_lng'];
	
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
		
	        $fql = 'SELECT name, url, pic from profile where uid in (' . implode(", ", $people) . ')';
			echo $fql;
			
	        $ret_obj = $facebook->api(array(
	                                   'method' => 'fql.query',
	                                   'query' => $fql,
	                                 ));

	        echo '<pre>Name: ' . $ret_obj[0]['name'] . '</pre>';

	    } catch (FacebookApiException $e) {
	        # If the call fails we check if we still have a user. The user will be
	        # cleared if the error is because of an invalid accesstoken
	        if (!$facebook->getUser()) {
	            header('Location: ' . AppInfo::getUrl($_SERVER['REQUEST_URI']));
	            exit();
	        }
	    }
	}
	
?>

<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
    <head>
		<title>WhartonPeeps | <?php echo $place_short; ?></title>
	</head>
	
	<body>
		
	</body>
</html>
