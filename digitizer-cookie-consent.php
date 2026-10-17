<?php
/**
 * Plugin Name:       Digitizer Cookie Consent
 * Plugin URI:        https://github.com/Digitizers/digitizer-cookie-consent
 * Description:       A cookie consent banner for Hebrew and multilingual sites: categories, scripts blocked until consent, Google Consent Mode v2, the WP Consent API, and the cookies of a refused category deleted. Nothing is sent anywhere.
 * Version:           1.0.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            Digitizer
 * Author URI:        https://www.digitizer.studio
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       digitizer-cookie-consent
 * Domain Path:       /languages
 *
 * @package Digitizer_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIGITIZER_COOKIE_CONSENT_VERSION', '1.0.0' );
define( 'DIGITIZER_COOKIE_CONSENT_FILE', __FILE__ );
define( 'DIGITIZER_COOKIE_CONSENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIGITIZER_COOKIE_CONSENT_URL', plugin_dir_url( __FILE__ ) );

require_once DIGITIZER_COOKIE_CONSENT_PATH . 'includes/class-digitizer-cookie-consent-settings.php';
require_once DIGITIZER_COOKIE_CONSENT_PATH . 'includes/class-digitizer-cookie-consent-frontend.php';
if ( is_admin() ) {
	require_once DIGITIZER_COOKIE_CONSENT_PATH . 'includes/class-digitizer-cookie-consent-admin.php';
}
