<?php
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

?>

<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
    <head>
		<title>WhartonPeeps</title>
		<script type="text/javascript" src="javascript/bootstrap.min.js"></script>
        <link rel="stylesheet" href="stylesheets/bootstrap.min.css"  type="text/css" />

		<script type="text/javascript">

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-32846016-1']);
		  _gaq.push(['_trackPageview']);

		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
	</head>
<body>
    <div id="fb-root"></div>
	<div class="container">
		<div class="hero-unit">
			<div class="row">
				<div class="span1">
					<img src="images/Map2.png" width=60 height=60/>
				</div>
				<div class="span9">
					<h1>WhartonPeeps</h1>
					<p>Map your WG'14 peeps</p>
					<br/>

				</div>
			</div>

			<div class="row">
				<div class="span10">
					<?php
					$user_id = $facebook->getUser();

					if ($user_id) {
					    try {
					        # Fetch the viewer's basic information
					        $basic = $facebook->api('/me');
							d($basic);
					
					    } catch (FacebookApiException $e) {
					        if (!$facebook->getUser()) {
					            header('Location: ' . AppInfo::getUrl($_SERVER['REQUEST_URI']));
					            exit();
					        }
					    }
					}
					?>
				</div>
			</div>

			<div class="row">
				<div class="span2 offset1">
					<a href="about.php">About</a> | <a href="privacy.php">Privacy</a>
				</div>
			</div>
		</div>
	</div>
</body>
</html>