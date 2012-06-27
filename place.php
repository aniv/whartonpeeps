<?php

	require_once 'db/fetch.php';
	require_once 'AppInfo.php';
	require_once 'kint/Kint.class.php';
	require_once 'utils.php';
	require_once 'sdk/src/facebook.php';

	header('content-type: text/html;charset=UTF-8');

	$placeHash = $_GET['hash'];
	$placeDetails = array("place_short"=>"1800 chesnut", "place_long"=>"1800 chesnut philly", "people"=>array(1201997), "lat_lng"=>0); //1223131
//	$placeDetails = GetExpandedUsersForPlace($placeHash);
	
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
		
	        $fql = json_encode(array("query1" => "SELECT id, name, url, pic from profile where id in (" . implode(", ", $people) . ")",
						"query2" => "SELECT uid, affiliations from user where uid in (SELECT id FROM #query1)" ));
			echo $fql;
			
			$peopleData = $facebook->api(array('method'=>'fql.query','queries'=>$fql));

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
		<script type="text/javascript" src="javascript/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="javascript/bootstrap.min.js"></script>
        <link rel="stylesheet" href="stylesheets/bootstrap.min.css"  type="text/css" />
	</head>
	
	<body>
		<div class="container">
			<div class="row" style="margin-top:12px">
				<div class="span1">
					<img src="images/Map2.png" width=60 height=60/>
				</div>
				<div class="span7" style="margin-top:10px">
					<h2>WhartonPeeps @ <?php echo $place_short; ?></h2>
				</div>
			</div>
			<div class="row">
				<div class="span9">
					<table class="table">
						<?php
						
						echo $peopleData;
						echo var_export($peopleData);
						
						foreach ($peopleData as $pd)
						{
							var_dump($pd);
							echo "<tr><td><img src='".$pd['pic']."'></td>";
							echo "<td><a href='".$pd['url']."'>".$pd['name']."</a><br/>Networks: </td>";
							echo "</tr>";
						}
						
						?>
					</table>
				</div>
			</div>
		</div>
		
	</body>
</html>
