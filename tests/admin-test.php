<?php
require_once __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
$GLOBALS['dpt_stub_pages'] = array();
function add_options_page( $title, $menu, $cap, $slug, $cb ) { $GLOBALS['dpt_stub_pages'][] = compact( 'title', 'menu', 'cap', 'slug' ); return 'settings_page_' . $slug; }
if ( ! function_exists( 'home_url' ) ) { function home_url( $p = '' ) { return 'https://example.test' . $p; } }
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args ); }
}
if ( ! function_exists( 'wp_nonce_url' ) ) { function wp_nonce_url( $url, $action ) { return add_query_arg( array( '_wpnonce' => 'n-' . $action ), $url ); } }
if ( ! function_exists( 'wp_verify_nonce' ) ) { function wp_verify_nonce( $n, $a ) { return ! empty( $GLOBALS['dpt_stub_nonce_ok'] ); } }
if ( ! function_exists( 'wp_enqueue_media' ) ) { function wp_enqueue_media() { $GLOBALS['dpt_stub_media'] = true; } }
$GLOBALS['dpt_stub_screen'] = null;
if ( ! function_exists( 'get_current_screen' ) ) { function get_current_screen() { return $GLOBALS['dpt_stub_screen']; } }
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-admin.php';

$GLOBALS['dpt_stub_no_user'] = false;
$GLOBALS['dpt_stub_filters'] = array();
$admin = new Digitizer_Cookie_Consent_Admin();
dpt_test_ok( dpt_stub_has_filter( 'admin_menu' ) && dpt_stub_has_filter( 'admin_enqueue_scripts' ) && dpt_stub_has_filter( 'plugin_action_links_digitizer-cookie-consent/digitizer-cookie-consent.php' ), 'the screen, its assets and the row link are wired' );

/* Settings -> Cookie Consent. */
$admin->register_menu();
dpt_test_eq( $GLOBALS['dpt_stub_pages'][0]['slug'] ?? null, 'digitizer-cookie-consent', 'an options page under Settings, at the plugin slug' );
dpt_test_eq( $GLOBALS['dpt_stub_pages'][0]['cap'] ?? null, 'manage_options', 'for those who manage options' );
dpt_test_eq( $admin->screen_hook(), 'settings_page_digitizer-cookie-consent', 'the screen hook is kept for gating assets' );
$links = $admin->row_links( array() );
dpt_test_ok( false !== strpos( implode( '', $links ), 'options-general.php?page=digitizer-cookie-consent' ), 'a Settings link in the plugin row' );

/* Assets only on the plugin's own screen. */
$GLOBALS['dpt_stub_scripts'] = array(); $GLOBALS['dpt_stub_media'] = false;
$admin->enqueue_assets( 'index.php' );
dpt_test_ok( ! isset( $GLOBALS['dpt_stub_scripts']['digitizer-cookie-consent-admin'] ), 'not on other screens' );
$admin->enqueue_assets( 'settings_page_digitizer-cookie-consent' );
dpt_test_ok( isset( $GLOBALS['dpt_stub_scripts']['digitizer-cookie-consent-admin'] ) && in_array( 'wp-color-picker', $GLOBALS['dpt_stub_scripts']['digitizer-cookie-consent-admin'], true ) && $GLOBALS['dpt_stub_media'], 'on its screen: admin.js after the color picker, and the media library' );
$js  = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/admin.js' );
dpt_test_ok( false !== strpos( $js, "'.dpt-color'" ) && false !== strpos( $js, 'wpColorPicker' ), 'admin.js starts the color pickers' );
dpt_test_ok( false !== strpos( $js, 'wp.media' ) && false !== strpos( $js, 'bg_image_url' ), 'and the background image picker' );
$css = (string) file_get_contents( dirname( __DIR__ ) . '/assets/css/admin.css' );
foreach ( array( '.dpt-layout', '.dpt-panel', '.dpt-form', '.dpt-actions', '.dpt-image-picker', '.dpt-lang-manager', '.dpt-sidebar', '.dpt-switch', '.dpt-preview-box' ) as $sel ) {
	dpt_test_ok( false !== strpos( $css, $sel ), "admin.css carries $sel" );
}

/* The first-save notice: for managers, on Plugins and Dashboard, until saved. */
$notice = function () use ( $admin ) { ob_start(); $admin->first_save_notice(); return (string) ob_get_clean(); };
$GLOBALS['dpt_stub_options'] = array( 'digitizer_cookie_consent' => array( 'saved' => '0' ) );
$GLOBALS['dpt_stub_screen'] = (object) array( 'id' => 'plugins' );
dpt_test_ok( false !== strpos( $notice(), 'options-general.php?page=digitizer-cookie-consent' ), 'not yet saved: the Plugins screen says so, with a link' );
$GLOBALS['dpt_stub_screen'] = (object) array( 'id' => 'edit-post' );
dpt_test_eq( $notice(), '', 'not on other screens' );
$GLOBALS['dpt_stub_screen'] = (object) array( 'id' => 'dashboard' );
$GLOBALS['dpt_stub_no_user'] = true;
dpt_test_eq( $notice(), '', 'not to a user who cannot manage options' );
$GLOBALS['dpt_stub_no_user'] = false;
$GLOBALS['dpt_stub_options']['digitizer_cookie_consent']['saved'] = '1';
dpt_test_eq( $notice(), '', 'gone once saved' );

/* The admin's own page check. */
$_GET['page'] = 'digitizer-cookie-consent';
dpt_test_eq( Digitizer_Cookie_Consent_Admin::current_page(), 'digitizer-cookie-consent', 'the screen check works without Digitizer Pro Tools' );
ob_start(); $admin->maybe_show_notices(); ob_end_clean();
dpt_test_ok( true, 'maybe_show_notices() runs on its screen without a fatal' );
unset( $_GET['page'] );

/* The preview needs its nonce. */
dpt_test_ok( false !== strpos( Digitizer_Cookie_Consent_Admin::preview_url(), '_wpnonce=' ), 'the preview link carries a nonce' );
dpt_test_ok( false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php' ), "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_GET['_wpnonce'] ) ), 'digitizer_cc_preview' )" ), 'and the front end checks it before forcing the banner open' );

/* The credit switch is on the screen. */
$o = Digitizer_Cookie_Consent_Settings::all();
$m = new ReflectionMethod( 'Digitizer_Cookie_Consent_Admin', 'tab_general' );
if ( PHP_VERSION_ID < 80100 ) { $m->setAccessible( true ); }
ob_start(); $m->invoke( $admin, $o ); $general = (string) ob_get_clean();
dpt_test_ok( false !== strpos( $general, 'name="dpt_cb[credit]"' ), 'the General tab has the credit switch' );

exit( dpt_test_summary() > 0 ? 1 : 0 );
