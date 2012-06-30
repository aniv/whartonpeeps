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
		
	        $fql = json_encode(array("query1" => "SELECT id, name, url, pic from profile where id in (" . implode(", ", $people) . ")",
						"query2" => "SELECT uid, affiliations from user where uid in (SELECT id FROM #query1)" ));
			$peopleDataRaw = $facebook->api(array('method'=>'fql.multiquery','queries'=>$fql));
			$profileData = null;
			$networkData = null;
			
			if ($peopleDataRaw[0]['name'] == 'query1')
			{
				$profileData = $peopleDataRaw[0]['fql_result_set'];
				$networkData = $peopleDataRaw[1]['fql_result_set'];
			}
			else  // does the order ever change?
			{
				$profileData = $peopleDataRaw[1]['fql_result_set'];
				$networkData = $peopleDataRaw[0]['fql_result_set'];				
			}

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
		<style>
		.networks {
			font-size:13px;
			color:grey;
			margin-top:10px;
		}
		</style>
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
				<div class="span7">
					<table class="table">
						<?php
						
						foreach ($profileData as $pd)
						{
							echo "<tr><td><a href='".$pd['url']."'><img src='".$pd['pic']."'></a></td>";
							echo "<td><a href='".$pd['url']."'>".$pd['name']."</a>";
							echo "<div class='networks'>Networks:<br/>";
							foreach ($networkData as $nd)
							{
								if ($nd['uid'] == $pd['id'])
									foreach ($nd['affiliations'] as $network)
									{
										if ($network['type'] == 'college')
											echo "<i class='icon-book'></i> ";
										if ($network['type'] == 'work')
											echo "<i class='icon-briefcase'></i> ";
										echo " " . $network['name'] . "<br/>";
									}
							}
							echo "</div></td>";
							echo "</tr>";
						}
						
						echo "<tr><td><a href='main.php'>&lt; Back</a></td></tr>";
						
						?>
					</table>
				</div>
			</div>
		</div>
		
	</body>
</html>
