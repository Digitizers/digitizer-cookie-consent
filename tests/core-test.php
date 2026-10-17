<?php
require_once __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
// Paragraphs on blank lines, as core's wpautop() makes them.
if ( ! function_exists( 'wpautop' ) ) { function wpautop( $t ) { $t = trim( (string) $t ); return '' === $t ? '' : '<p>' . $t . "</p>\n"; } }
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-core.php';

/* Import from Digitizer Pro Tools: once, one way, consent version included. */
$GLOBALS['dpt_stub_options'] = array( 'dpt_cookie_banner' => array( 'consent_version' => '7', 'cleanup_analytics' => '_ga', 'btn_stretch' => '1' ) );
Digitizer_Cookie_Consent_Core::activate( false );
$o = get_option( 'digitizer_cookie_consent' );
dpt_test_eq( $o['consent_version'], '7', 'the consent version comes across - a new one would ask every visitor again' );
dpt_test_eq( array( $o['cleanup_analytics'], $o['btn_stretch'], $o['saved'] ), array( '_ga', '1', '1' ), 'settings come across, and count as saved' );
// Once: with the plugin's option in place, a changed source is not imported again.
$GLOBALS['dpt_stub_options']['dpt_cookie_banner']['consent_version'] = '8';
Digitizer_Cookie_Consent_Core::activate( false );
dpt_test_eq( get_option( 'digitizer_cookie_consent' )['consent_version'], '7', 'once: a second activation does not import again' );

// A language the site added in the module comes across with its texts.
$GLOBALS['dpt_stub_options'] = array( 'dpt_cookie_banner' => array( 'languages' => array( 'he', 'en', 'fr' ), 'default_lang' => 'fr', 'texts' => array( 'fr' => array( 'title' => 'Nous utilisons des cookies' ) ) ) );
Digitizer_Cookie_Consent_Core::activate( false );
$o = get_option( 'digitizer_cookie_consent' );
dpt_test_eq( array( $o['languages'], $o['default_lang'], $o['texts']['fr']['title'] ), array( array( 'he', 'en', 'fr' ), 'fr', 'Nous utilisons des cookies' ), 'an added language, its default status and its texts survive the import' );

// Stored snippets survive an activation by a user without unfiltered_html.
$GLOBALS['dpt_stub_no_user'] = true; // current_user_can() answers false for everything, unfiltered_html included
$GLOBALS['dpt_stub_options'] = array( 'dpt_cookie_banner' => array( 'scripts_analytics' => '<script>gtag("config","G-X");</script>' ) );
Digitizer_Cookie_Consent_Core::activate( false );
dpt_test_eq( get_option( 'digitizer_cookie_consent' )['scripts_analytics'], '<script>gtag("config","G-X");</script>', 'the site\'s tracking snippet comes across intact, whoever activates' );
$GLOBALS['dpt_stub_no_user'] = false;
// The whole option, not a subset: a reset switch, category or lifetime would change tracking or re-ask visitors.
$GLOBALS['dpt_stub_options'] = array( 'dpt_cookie_banner' => array( 'block_scripts' => '0', 'cat_marketing_enabled' => '0', 'consent_mode' => '1', 'consent_days' => '365', 'bg_color' => '#123456', 'policy_url' => 'https://example.test/p/' ) );
Digitizer_Cookie_Consent_Core::activate( false );
$o = get_option( 'digitizer_cookie_consent' );
dpt_test_eq( array( $o['block_scripts'], $o['cat_marketing_enabled'], $o['consent_mode'], $o['consent_days'], $o['bg_color'], $o['policy_url'] ), array( '0', '0', '1', '365', '#123456', 'https://example.test/p/' ), 'every setting comes across: switches, categories, Consent Mode, lifetime, design, policy link' );
dpt_test_ok( isset( $GLOBALS['dpt_stub_options']['dpt_cookie_banner'] ), 'the module\'s option is left alone' );

/* Text read from the database is not slashed: a backslash in it survives. */
$GLOBALS['dpt_stub_options'] = array( 'dpt_cookie_banner' => array( 'texts' => array( 'en' => array( 'message' => 'Files in C:\\docs' ) ) ) );
Digitizer_Cookie_Consent_Core::activate( false );
dpt_test_eq( get_option( 'digitizer_cookie_consent' )['texts']['en']['message'], 'Files in C:\\docs', 'a backslash in a stored text comes across - the import does not unslash database values' );

/* A fresh site: defaults, not saved. */
$GLOBALS['dpt_stub_options'] = array();
Digitizer_Cookie_Consent_Core::activate( false );
$o = get_option( 'digitizer_cookie_consent' );
dpt_test_eq( array( $o['enabled'], $o['saved'], $o['credit'] ), array( '1', '0', '0' ), 'on, not yet saved, no credit' );

/* The credit: absent by default, one line when on. */
$GLOBALS['dpt_stub_is_admin'] = false; $GLOBALS['dpt_stub_no_user'] = true;
$f = new Digitizer_Cookie_Consent_Frontend();
$m = new ReflectionMethod( 'Digitizer_Cookie_Consent_Frontend', 'render_banner_markup' );
if ( PHP_VERSION_ID < 80100 ) { $m->setAccessible( true ); }
ob_start(); $m->invoke( $f, Digitizer_Cookie_Consent_Settings::all(), Digitizer_Cookie_Consent_Settings::get_texts( 'en' ), 'en', 'ltr' ); $html = (string) ob_get_clean();
dpt_test_ok( false === strpos( $html, 'digitizer.studio' ), 'no credit unless switched on' );
$GLOBALS['dpt_stub_options']['digitizer_cookie_consent']['credit'] = '1';
ob_start(); $m->invoke( $f, Digitizer_Cookie_Consent_Settings::all(), Digitizer_Cookie_Consent_Settings::get_texts( 'en' ), 'en', 'ltr' ); $html = (string) ob_get_clean();
dpt_test_ok( 1 === preg_match( '#<p class="dpt-cb-credit"><a href="https://www\.digitizer\.studio" target="_blank" rel="noopener">Cookie consent by Digitizer</a></p>#', $html ), 'switched on: one line under the buttons' );

/* The module's hooks still reach site code written against them. */
add_filter( 'dpt_cb_open_selector', function ( $sel ) { return $sel . ', .legacy-open'; } );
dpt_test_ok( '.legacy-open' === substr( Digitizer_Cookie_Consent_Frontend::open_selector(), -12 ), 'a callback on dpt_cb_open_selector still changes what reopens the banner' );
add_filter( 'dpt_cb_rtl_langs', function ( $langs ) { $langs[] = 'ckb'; return $langs; } );
dpt_test_eq( $f->lang_dir( 'ckb' ), 'rtl', 'and one on dpt_cb_rtl_langs still adds an RTL language' );
$GLOBALS['dpt_stub_filters'] = array();

/* Uninstall removes the option. */
$GLOBALS['dpt_stub_options'] = array( 'digitizer_cookie_consent' => array( 'enabled' => '1' ) );
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { define( 'WP_UNINSTALL_PLUGIN', true ); }
include dirname( __DIR__ ) . '/uninstall.php';
dpt_test_ok( ! isset( $GLOBALS['dpt_stub_options']['digitizer_cookie_consent'] ), 'uninstall deletes the option' );

exit( dpt_test_summary() > 0 ? 1 : 0 );
