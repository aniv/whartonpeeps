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

$user_id = $facebook->getUser();

if ($user_id) {
    try {
        # Fetch the viewer's basic information
        $basic = $facebook->api('/me');
    } catch (FacebookApiException $e) {
        # If the call fails we check if we still have a user. The user will be
        # cleared if the error is because of an invalid accesstoken
        if (!$facebook->getUser()) {
            header('Location: ' . AppInfo::getUrl($_SERVER['REQUEST_URI']));
            exit();
        }
    }
}

// $facebook->getLogoutUrl(array('next'=>'http://localhost/Dev/...'));
// $facebook->destroySession();
// echo 'Destroyed session <br/>';

?>

<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
    <head>
		<script type="text/javascript" src="javascript/bootstrap.min.js"></script>
        <link rel="stylesheet" href="stylesheets/bootstrap.min.css"  type="text/css" />
	</head>
<body>
    <div id="fb-root"></div>
	<div class="container">
		<div class="hero-unit" style="position:absolute;top:50%">
			<h1>WhartonPeeps</h1>
			<p>Map your WG'14 peeps</p>
			<?php if(!isset($basic)) { ?>
		    	<div class="fb-login-button" data-scope="user_groups" width="500px"></div>
				<fb:login-button size="xlarge" onlogin="Log.info('onlogin callback')">Log In</fb:login-button>
			<?php 
				} 
				else { 
					// Wharton = 169174513170821
					// Test = 330277880384395
				    $groupsW = $facebook->api(array(
				        'method' => 'fql.query',
				        'query' => 'SELECT uid, gid FROM group_member WHERE gid = 169174513170821 AND uid=me()'
				    ));

				    $groupsT = $facebook->api(array(
				        'method' => 'fql.query',
				        'query' => 'SELECT uid, gid FROM group_member WHERE gid = 330277880384395 AND uid=me()'
				    ));

					d($groupsW);
					d($groupsT);

					if (isset($groups['data']['uid'], $groups['data']['gid']))
					{
						echo '<div>';
						echo 'User is in group <br/>';
						echo '</div>';
					}

					echo '<div><a href="' . $facebook->getLogoutUrl() . '">Log-out URL</a></div>';
				}
				?>
		</div>
	</div>


    <script type="text/javascript">
    window.fbAsyncInit = function() {
        FB.init({
            appId     : '<?php echo $app_id; ?>', // App ID
            channelUrl: '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
            status    : true, // check login status
            cookie    : true, // enable cookies to allow the server to access the session
            xfbml     : true  // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
            // We want to reload the page now so PHP can read the cookie that the
            // Javascript SDK sat. But we don't want to use
            // window.location.reload() because if this is in a canvas there was a
            // post made to this page and a reload will trigger a message to the
            // user asking if they want to send data again.
            window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
    };

    // Load the SDK Asynchronously
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    </script>

</body>
</html>