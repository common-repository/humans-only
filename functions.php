<?php
/*
    Plugin Name: Humans Only
    Plugin URI: http://www.little-apps.org/blog/2013/06/stop-spammers-entering-wordpress-site/
    Description: Redirects user to enter CAPTCHA
    Version: 1.1
    Author: Little Apps
    Author URI: http://www.little-apps.com/
    License: GPL2
*/

global $wpdb;
if (!isset($wpdb)) die('Cannot be loaded directly.');

session_start();

if (!function_exists('check_if_spider')) {
	function ho_check_if_spider() {
		if (get_option("humansonly-allow-spiders") == "on") {
			// Add as many spiders you want in this array
			$spiders = array('Googlebot', 'Yammybot', 'Openbot', 'Yahoo', 'Slurp', 'msnbot', 'ia_archiver', 'Lycos', 'Scooter', 'AltaVista', 'Teoma', 'Gigabot', 'Googlebot-Mobile');
			
			// Loop through each spider and check if it appears in
			// the User Agent
			foreach ($spiders as $spider) {
				if (stripos($_SERVER['HTTP_USER_AGENT'], $spider) !== false) {
					$_SESSION['is_valid'] = true;
					return true;
				}
			}
		}
		
		return false;
	}
}

if (!function_exists('check_cookie')) {
	function ho_check_cookie() {
		if (isset($_COOKIE['VALIDHUMAN'])) {
			// Get salt
			$salt = get_option('humansonly-salt');
			$ip_address = $_SERVER['REMOTE_ADDR'];
		
			if ($_COOKIE['VALIDHUMAN'] == md5($salt.$ip_address)) {
				$_SESSION['is_valid'] = true;
				return true;
			} else {
				// Remove cookie as its invalid
				setcookie("VALIDHUMAN", "", time()-3600);
			}
		}
		
		return false;
	}
}

function humansonly_activate() {
	// Delete setting if it already exists
	delete_option('humansonly-salt');

	// Generate salt for cookies
	$salt = md5(uniqid() . ( defined('AUTH_SALT') ? AUTH_SALT : microtime() ));
	
	add_option('humansonly-salt', $salt);
	
	add_option('humansonly-allow-spiders', 'on');
	add_option('humansonly-recaptcha-publickey', '');
	add_option('humansonly-recaptcha-privatekey', '');
}

register_activation_hook( __FILE__, 'humansonly_activate' );

function humansonly_deactivate() {
	// Remove settings
	delete_option('humansonly-salt');
	delete_option('humansonly-allow-spiders');
	delete_option('humansonly-recaptcha-publickey');
	delete_option('humansonly-recaptcha-privatekey');
}

register_deactivation_hook(__FILE__, 'humansonly_deactivate' );

function humansonly_menu() {
	add_options_page('Humans Only', 'Humans Only Settings', 'manage_options', 'humansonly-admin', 'ho_create_admin_page');
}

function humansonly_register_settings() {
	register_setting('humansonly_group', 'humansonly-allow-spiders');
	register_setting('humansonly_group', 'humansonly-recaptcha-publickey');
	register_setting('humansonly_group', 'humansonly-recaptcha-privatekey');
}

if ( is_admin() ){ // admin actions
	add_action( 'admin_menu', 'humansonly_menu' );
	add_action( 'admin_init', 'humansonly_register_settings' );
}

function ho_create_admin_page() {
?>
	<div class="wrap">
	    <?php screen_icon(); ?>
	    <h2>Humans Only Settings</h2>			
	    <form method="post" action="options.php">
	        <?php
				// This prints out all hidden setting fields
				settings_fields('humansonly_group');	
				do_settings_sections('humansonly-admin');
			?>
			<table class="form-table">
				<tr valign="top"> 
					<th scope="row"><label for="humansonly-allow-spiders">Allow Spiders?</label></th> 
					<td><input type="checkbox" name="humansonly-allow-spiders" id="humansonly-allow-spiders" <?php checked( 'on', get_option( 'humansonly-allow-spiders' ) ); ?> /></td> 
				</tr> 
				<tr valign="top"> 
					<th scope="row"><label for="humansonly-recaptcha-publickey">reCaptcha Public Key</label></th> 
					<td><input type="text" name="humansonly-recaptcha-publickey" id="humansonly-recaptcha-publickey" value="<?php echo get_option('humansonly-recaptcha-publickey'); ?>" style="width: 250px" /></td> 
				</tr> 
				<tr valign="top"> 
					<th scope="row"><label for="humansonly-recaptcha-privatekey">reCaptcha Private Key</label></th> 
					<td><input type="text" name="humansonly-recaptcha-privatekey" id="humansonly-recaptcha-privatekey" value="<?php echo get_option('humansonly-recaptcha-privatekey'); ?>" style="width: 250px" /></td> 
				</tr> 
				<tr valign="top"> 
					<th scope="row"></th>
					<td>Sign up for a free <a target="_new" href="https://www.google.com/recaptcha/admin/create">Google reCaptcha</a> account.</td> 
				</tr>
			</table>
	        <?php submit_button(); ?>
	    </form>
	</div>
<?php
}

function ho_template_redirect() {
	if (!isset($_SESSION['is_valid']))
		$_SESSION['is_valid'] = false;
		
	// Comment the line below if you don't want to allow spiders
	ho_check_if_spider();
	
	// Check if user is valid
	ho_check_cookie();
		
	if ($_SESSION['is_valid'] == false) {
		global $wpdb;
	
		$_SESSION['current_url'] = ( ( isset($_SERVER["HTTPS"]) ) && $_SERVER["HTTPS"] == "on" ? "https://" : "http://" ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		ob_start();
		
		include dirname(__FILE__).'/index.php';
		
		ob_end_flush();
		
		die();
		
		//$redirect_url = plugins_url( 'index.php' , __FILE__ );
		
		//wp_redirect( $redirect_url );
	}
}

add_action( 'template_redirect', 'ho_template_redirect' );