<?php

# Provides access to app specific values such as your app id and app secret.
# Defined in 'AppInfo.php'
require_once 'AppInfo.php';
require_once 'kint/Kint.class.php';

# Stop making excess function calls
$app_id = AppInfo::appID();
$app_url = AppInfo::getUrl();

# Enforce https on production
if (substr($app_url, 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
    header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

# This provides access to helper functions defined in 'utils.php'
require_once 'utils.php';
require_once 'sdk/src/facebook.php';

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

    # This fetches some things that you like . 'limit=*" only returns * values.
    # To see the format of the data you are retrieving, use the "Graph API
    # Explorer" which is at https://developers.facebook.com/tools/explorer/
    $likes = idx($facebook->api('/me/likes?limit=4'), 'data', array());

    # This fetches 4 of your friends.
    $friends = idx($facebook->api('/me/friends?limit=4'), 'data', array());

    # And this returns 16 of your photos.
    $photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

    # Here is an example of a FQL call that fetches all of your friends that are
    # using this app
    $app_using_friends = $facebook->api(array(
        'method' => 'fql.query',
        'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
    ));

	d($app_using_friends);
}

# Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());
$app_name = he(idx($app_info, 'name', ''));

$he_user_id = he($user_id);

?>

<html>
<body>
    <div id="fb-root"></div>
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