<?php
require_once __DIR__ . '/bootstrap.php';
$main = (string) file_get_contents( dirname( __DIR__ ) . '/digitizer-cookie-consent.php' );
dpt_test_ok( false !== strpos( $main, 'Text Domain:       digitizer-cookie-consent' ), 'the text domain is the slug' );
dpt_test_ok( false !== strpos( $main, 'Requires at least: 5.7' ), 'WordPress 5.7, for wp_print_inline_script_tag()' );
preg_match( '/\* Version:\s+(\S+)/', $main, $hv );
preg_match( "/define\( 'DIGITIZER_COOKIE_CONSENT_VERSION', '([^']+)' \);/", $main, $dv );
preg_match( '/^Stable tag: (\S+)$/m', (string) file_get_contents( dirname( __DIR__ ) . '/readme.txt' ), $sv );
dpt_test_ok( isset( $hv[1], $dv[1], $sv[1] ) && $hv[1] === $dv[1] && $dv[1] === $sv[1], 'the header, the constant and the readme\'s stable tag name one version' );
foreach ( array( '.github/workflows/deploy.yml', '.github/workflows/assets.yml' ) as $f ) {
	dpt_test_ok( false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/' . $f ), 'SLUG: digitizer-cookie-consent' ), "$f deploys this slug" );
}
$js = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/frontend.js' );
foreach ( array( "'dpt_consent'", "'dpt_consent_v1'", "'dpt:consent'" ) as $kept ) {
	dpt_test_ok( false !== strpos( $js, $kept ), "kept for sites moving from Digitizer Pro Tools: $kept" );
}
dpt_test_ok( false === strpos( $js, 'DPTCB' ), 'no window.DPTCB alias' );
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php';
$fe = new Digitizer_Cookie_Consent_Frontend();
dpt_test_ok( in_array( 'digitizer-cookie-consent/assets/js/frontend.js', $fe->rocket_exclude_files( array() ), true ), 'WP Rocket excludes the plugin\'s own script path, not the module\'s' );
exit( dpt_test_summary() > 0 ? 1 : 0 );
