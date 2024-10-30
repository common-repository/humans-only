<?php
if (!isset($wpdb)) die('Cannot be loaded directly.');

if (!function_exists('recaptcha_get_html'))
	require_once(dirname(__FILE__) . '/recaptchalib.php');
$publickey = get_option("humansonly-recaptcha-publickey");
$privatekey = get_option("humansonly-recaptcha-privatekey");

if ($publickey == '' || $privatekey == '')
	die('reCaptcha keys are not set.');

if (!isset($_SESSION['is_valid']))
	$_SESSION['is_valid'] = false;
		
$redirect_url = ( isset( $_SESSION['current_url'] ) ? $_SESSION['current_url'] : home_url() );

if ($_SESSION['is_valid'] == true) {
	wp_redirect( $redirect_url );
	die();
}

if ( $_SERVER['REQUEST_METHOD'] && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if ( !isset($_POST['recaptcha_challenge_field']) || !isset($_POST['recaptcha_challenge_field']) || !isset($_POST['email']) )
		$success = false;
	elseif ($_POST['email'] != $_SESSION['token']) {
		$success = false;
	} else {
		$resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		
		if (!$resp->is_valid) {
			$success = false;
		} else {
			// Get salt + ip address
			$salt = get_option('humansonly-salt');
			$ip_address = $_SERVER['REMOTE_ADDR'];
		
			// Set user as human
			setcookie("VALIDHUMAN", md5($salt.$ip_address), time() + 60 * 60 * 24 * 365 );
			$_SESSION['is_valid'] = true;
			
			// Redirect back
			wp_redirect( $redirect_url );
			die();
		}
	}
}

// Honeypot to through off automated bots
$_SESSION['token'] = md5(uniqid());
?>
<html>
	<head>
		<title>Prove Your Human</title>
		
		<meta name="robots" content="noindex,nofollow" />
		
		<link href="<?php echo plugins_url( 'style.css' , __FILE__ ); ?>" rel="stylesheet" type="text/css" />
		<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,600,700' rel='stylesheet' type='text/css' />
		<link href='http://fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css' />
		
		<script type="text/javascript">var RecaptchaOptions = { theme : 'clean' };</script>
		
		<?php if ( $success === false ) : ?>
		<script type="text/javascript">alert("Oops! You don't seem to be a human.");</script>
		<?php endif; ?>
	</head>
	<body>
		<p>You must prove your human before you can access this website</p>
		
		<form action="<?php echo $redirect_url; ?>" method="post">
			<input name="email" type="text" value="<?php echo $_SESSION['token']; ?>" id="email" />
			<?php echo recaptcha_get_html($publickey); ?>
			<input type="submit" name="submit" value="Yes, I'm Human" id="submit" />
		</form>
	</body>
</html>