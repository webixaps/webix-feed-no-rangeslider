<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://webixaps.com/
 * @since             3.1.35
 * @package           Webix_Feed
 *
 * @wordpress-plugin
 * Plugin Name:       Webix Feed without rangeslider
 * Plugin URI:        https://github.com/webixaps/webix-feed-no-rangeslider
 * Description:       The New Version of Webix comparison feed makes it easy to create your own comparison sites while always keeping your data updated. Currently we have feeds on the Danish, Swedish, Norwegian and Finnish markets across multiple sectors. With auto update from GitHub.
 * Version:           3.1.38
 * Author:            Webix Aps
 * Author URI:        https://webixaps.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       webix-feed
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/webixaps/webix-feed-no-rangeslider
 * GitHub Branch:   master
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * MIGRATIONSVAGT: Hvis det GAMLE "Webix Feed"-plugin (mappen webix-feed) stadig
 * er aktivt, er dets globale funktioner og klasser allerede indlæst — at loade
 * dette plugin oveni ville give "Cannot redeclare"-fatals. Vi bailer derfor
 * stille med en admin-advarsel. Deaktivér det gamle plugin, så tager dette over.
 */
if ( defined( 'WEBIX_FEED_VERSION' ) || function_exists( 'run_webix_feed' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p><strong>Webix Feed (no rangeslider):</strong> det gamle "Webix Feed"-plugin er stadig aktivt. Deaktivér det under Plugins — kun ét af dem må være aktivt.</p></div>';
	} );
	return;
}

/**
 * Currently plugin version.
 * Start at version 3.1.33 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WEBIX_FEED_VERSION', '3.1.38' );
define( 'WEBIX_DIRECTORY', 'webix-feed-no-rangeslider/');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-webix-feed-activator.php
 */
function activate_webix_feed() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-webix-feed-activator.php';
	Webix_Feed_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-webix-feed-deactivator.php
 */
function deactivate_webix_feed() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-webix-feed-deactivator.php';
	Webix_Feed_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_webix_feed' );
register_deactivation_hook( __FILE__, 'deactivate_webix_feed' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-webix-feed.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    3.1.33
 */
function run_webix_feed() {

	$plugin = new Webix_Feed();
	$plugin->run();

}

if ( ! function_exists('webix_options_menu') ) {
    add_action( 'admin_menu', 'webix_options_menu' );
    function webix_options_menu() {
        add_menu_page( 'Webix','Webix Service', 'manage_options', WEBIX_DIRECTORY.'admin/webix-feed-admin.php', '', plugins_url('/images/MT2.png', __FILE__));
    }
}

//For REDIRECTS with "/comparison/go/feed/" with $_SERVER['HTTP_REFERER'] for param #1
if(preg_match("/\bcomparison\/go\/(feed|ticker|email|popup|link|table)\/\b/", $_SERVER["REQUEST_URI"])){
  if (strpos($_SERVER["REQUEST_URI"], '/go/feed') !== false) {
    $redirectfeedId = explode('feed/',$_SERVER["REQUEST_URI"]);
    $site_details['utm_medium'] = 'feed';
  }else if (strpos($_SERVER["REQUEST_URI"], '/go/ticker') !== false) {
    $redirectfeedId = explode('ticker/',$_SERVER["REQUEST_URI"]);
    $site_details['utm_medium'] = 'ticker';
  }else if (strpos($_SERVER["REQUEST_URI"], '/go/email') !== false) {
    $redirectfeedId = explode('email/',$_SERVER["REQUEST_URI"]);
    $site_details['utm_medium'] = 'email';
  }else if (strpos($_SERVER["REQUEST_URI"], '/go/popup') !== false) {
    $redirectfeedId = explode('popup/',$_SERVER["REQUEST_URI"]);
    $site_details['utm_medium'] = 'popup';
  }else if (strpos($_SERVER["REQUEST_URI"], '/go/link') !== false) {
    $redirectfeedId = explode('link/',$_SERVER["REQUEST_URI"]);
    $site_details['utm_medium'] = 'link';
  }else if (strpos($_SERVER["REQUEST_URI"], '/go/table') !== false) {
    $redirectfeedId = explode('table/',$_SERVER["REQUEST_URI"]);
    $site_details['utm_medium'] = 'table';
  }
	$feedId = $redirectfeedId[1];
	$site_details['site_name'] = get_bloginfo( 'name' );
	$site_details['site_domain'] = get_bloginfo( 'url' );
	$site_details['site_url'] = $_SERVER['HTTP_REFERER'] ?? '';

	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$site_details['client_browser'] = get_browser_name($user_agent);

$iPod    = stripos($user_agent, 'iPod');
$iPhone  = stripos($user_agent, 'iPhone');
$iPad    = stripos($user_agent, 'iPad');
$Android = stripos($user_agent, 'Android');
$webOS   = stripos($user_agent, 'webOS');
	if( $iPod || $iPhone ){
		$device_type = "iPhone";
	}else if($iPad){
		$device_type = "iPad";
	}else if($Android){
		$device_type = "Android";
	}else if($webOS){
		$device_type = "webOS";
	}else{
		$device_type = "Unknown";
	}
	$site_details['client_platform'] = $device_type;
	// Client Device
	if ( wp_is_mobile() ) {
		$site_details['client_device'] = "Mobile";
	}else{
		$site_details['client_device'] = "Desktop";
	}

	$site_details['client_ip_address'] = get_client_ip();
	$site_details['visitor_id'] = check_and_get_visitor_id();


	$landing_page_url = get_landing_page_url($feedId, $site_details);

// Rens URL'en for skjulte tegn
$landing_page_url = trim($landing_page_url);
$landing_page_url = str_replace(array("\r", "\n"), '', $landing_page_url);

// Redirect kun hvis URL'en er gyldig
if (filter_var($landing_page_url, FILTER_VALIDATE_URL)) {
    header('Location: ' . $landing_page_url, true, 302);
    exit;
}

// Fallback hvis API returnerer noget ugyldigt
wp_die('Invalid redirect URL returned by Webix API.');

}

function get_browser_name($user_agent)
{
    if (empty($user_agent)) {
        return 'Other';
    }

    if (strpos($user_agent, 'Opera') !== false || strpos($user_agent, 'OPR/') !== false) {
        return 'Opera';
    }

    if (strpos($user_agent, 'Edg') !== false || strpos($user_agent, 'Edge') !== false) {
        return 'Edge';
    }

    if (strpos($user_agent, 'Chrome') !== false) {
        return 'Chrome';
    }

    if (strpos($user_agent, 'Safari') !== false) {
        return 'Safari';
    }

    if (strpos($user_agent, 'Firefox') !== false) {
        return 'Firefox';
    }

    if (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident/7') !== false) {
        return 'Internet Explorer';
    }

    return 'Other';
}

function get_landing_page_url($clean_url, $site_details){
//TODO:: temporary ip
//    $site_details['client_ip_address']='103.138.144.206';
	$parameters = [
		"clean_url" => $clean_url,
		"site_name" => $site_details['site_name'],
		"utm_content" => $site_details['site_url'],
		"utm_source" => $site_details['site_domain'],
		"utm_medium" => $site_details['utm_medium'],
		"client_device" => $site_details['client_device'],
		"client_browser" => $site_details['client_browser'],
		"client_platform" => $site_details['client_platform'],
		"client_ip_address" => $site_details['client_ip_address'],
		"visitor_id" => $site_details['visitor_id']
	];
    $ai_webixaps_url = 'https://ai.webixaps.com/get_campaign_landing_page';
	$response = wp_remote_get( $ai_webixaps_url.'?'.http_build_query($parameters));
	if ( is_wp_error( $response ) ) {
		return 'There be errors, yos!';
	}else{
		$body = trim( wp_remote_retrieve_body( $response ) );

// Fjern skjulte linjeskift som kan ødelægge redirect-headeren
$body = str_replace(array("\r", "\n"), '', $body);

return $body;
	}
	return false;
}

function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
/**
 * check website visitor
 * */
function check_and_get_visitor_id(){
    $visitor_id = '';
    try {
        $cookies_expire_time = 2147483647;
        if(isset($_COOKIE['webix_visitor'])) {
            $visitor_id = $_COOKIE['webix_visitor'];
            setcookie('webix_last_visited',  time(), $cookies_expire_time,'/');
        }
        else {
            $visitor_id = getUniqueUserId();
            setcookie('webix_visitor',$visitor_id , $cookies_expire_time,'/');
            setcookie('webix_last_visited',  time(), $cookies_expire_time,'/');
        }
    }
    catch (\Exception $e){
    }
    return $visitor_id;
}
function crypto_rand_secure($min, $max){
    $range = $max - $min;
    if ($range < 1) {
        return $min;
    }
    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1;
    $bits = (int) $log + 1;
    $filter = (int) (1 << $bits) - 1;
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter;
    }
    while ($rnd > $range);
    return $min + $rnd;
}

function getUniqueUserId($length=100){
    $token = "";
    $id_alphabets = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $id_alphabets.= "abcdefghijklmnopqrstuvwxyz";
    $id_alphabets.= "0123456789";
    $max = strlen($id_alphabets);
    for ($i=0; $i < $length; $i++) {
        $token .= $id_alphabets[crypto_rand_secure(0, $max-1)];
    }
    return $token;
}

run_webix_feed();
add_action( 'init', 'github_plugin_updater_test_init' );
function github_plugin_updater_test_init() {
    if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
        include_once 'webix-updater.php';
        define( 'PLUGIN_DIRECTORY',  __FILE__ );
        define( 'PROPER_FOLDER_NAME', 'webix-feed-no-rangeslider');
        define( 'PLUGIN_SLUG',  'webix-feed-no-rangeslider' );
        define( 'GITHUB_URL',  'https://api.github.com/repos/webixaps/webix-feed-no-rangeslider/releases');
        /*
         * SIKKERHED: GitHub-tokenet må ALDRIG hardcodes her (v3.1.35 og ældre
         * distribuerede et token i klartekst til alle kundesites — det skal revokes).
         * Sæt i stedet tokenet ét af to steder på det enkelte site:
         *   1) wp-config.php:  define( 'WEBIX_GITHUB_TOKEN', '...' );
         *   2) eller som option: update_option( 'webix_github_token', '...' );
         * Uden token virker updateren stadig, hvis webix-feed-repoets releases er offentlige.
         */
        if ( ! defined( 'GITHUB_TOKEN' ) ) {
            $webix_gh_token = defined( 'WEBIX_GITHUB_TOKEN' )
                ? WEBIX_GITHUB_TOKEN
                : get_option( 'webix_github_token', '' );
            define( 'GITHUB_TOKEN', is_string( $webix_gh_token ) ? $webix_gh_token : '' );
        }
        new WP_GitHub_Updater_For_Webixfeed();

    }

}
