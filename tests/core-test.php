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

/* A deliberate 900px / 95% box survives activation running the defaults again. */
$GLOBALS['dpt_stub_options'] = array( 'digitizer_cookie_consent' => array_merge( Digitizer_Cookie_Consent_Settings::defaults(), array( 'width' => '900', 'max_width_pct' => '95', 'saved' => '1' ) ) );
Digitizer_Cookie_Consent_Core::activate( false );
$o = get_option( 'digitizer_cookie_consent' );
dpt_test_eq( array( $o['width'], $o['max_width_pct'] ), array( '900', '95' ), 'no Digitizer Pro Tools migration resets a saved width: the plugin has no DPT database version to read' );

/* Activation empties full-page caches: pages cached before it carry no banner and no script blocking. */
$GLOBALS['dpt_test_purges'] = 0;
if ( ! function_exists( 'rocket_clean_domain' ) ) { function rocket_clean_domain() { $GLOBALS['dpt_test_purges']++; } }
$GLOBALS['dpt_stub_options'] = array();
Digitizer_Cookie_Consent_Core::activate( false );
dpt_test_eq( $GLOBALS['dpt_test_purges'], 1, 'a fresh activation purges the page cache once' );

/* So does the seeding boot() does for a site the activation hook never reached. */
$GLOBALS['dpt_test_purges'] = 0;
$GLOBALS['dpt_stub_options'] = array();
Digitizer_Cookie_Consent_Core::boot();
dpt_test_eq( $GLOBALS['dpt_test_purges'], 1, 'boot() seeding a site without the option purges the page cache once' );
$GLOBALS['dpt_test_purges'] = 0;
Digitizer_Cookie_Consent_Core::boot();
dpt_test_eq( $GLOBALS['dpt_test_purges'], 0, 'and an already seeded site is not purged on every load' );

/* Blank or nonsense consent timing falls back to the defaults the precheck and frontend.js agree on. */
$c = Digitizer_Cookie_Consent_Settings::sanitize( array( 'consent_version' => '', 'consent_days' => '' ), Digitizer_Cookie_Consent_Settings::defaults() );
dpt_test_eq( array( $c['consent_version'], $c['consent_days'] ), array( '1', '180' ), 'blank version and days become 1 and 180' );
$c = Digitizer_Cookie_Consent_Settings::sanitize( array( 'consent_version' => '0', 'consent_days' => 'abc' ), Digitizer_Cookie_Consent_Settings::defaults() );
dpt_test_eq( array( $c['consent_version'], $c['consent_days'] ), array( '1', '180' ), 'zero and non-numbers too' );
$c = Digitizer_Cookie_Consent_Settings::sanitize( array( 'consent_version' => '4', 'consent_days' => '9999' ), Digitizer_Cookie_Consent_Settings::defaults() );
dpt_test_eq( array( $c['consent_version'], $c['consent_days'] ), array( '4', '730' ), 'a real version is kept; days are capped at the form\'s 730' );

/* Every number on the settings form falls back to its default when blank, and stays within the form's bounds. */
$c = Digitizer_Cookie_Consent_Settings::sanitize( array( 'width' => '', 'max_width_pct' => '500', 'width_mobile' => '50', 'overlay_opacity' => '', 'close_opacity' => '2', 'show_delay' => '-3', 'width_corner' => 'abc' ), Digitizer_Cookie_Consent_Settings::defaults() );
dpt_test_eq(
	array( $c['width'], $c['max_width_pct'], $c['width_mobile'], $c['overlay_opacity'], $c['close_opacity'], $c['show_delay'], $c['width_corner'] ),
	array( '700', '100', '200', '0.5', '1', '0', '380' ),
	'blank or nonsense -> default; out of range -> the nearest bound'
);
$c = Digitizer_Cookie_Consent_Settings::sanitize( array( 'width' => '640', 'overlay_opacity' => '0.35' ), Digitizer_Cookie_Consent_Settings::defaults() );
dpt_test_eq( array( $c['width'], $c['overlay_opacity'] ), array( '640', '0.35' ), 'a value in range is kept as entered' );

/* The banner does not trap focus or make the page inert, so it does not claim to be modal. */
ob_start(); $m->invoke( $f, Digitizer_Cookie_Consent_Settings::all(), Digitizer_Cookie_Consent_Settings::get_texts( 'en' ), 'en', 'ltr' ); $html = (string) ob_get_clean();
dpt_test_ok( false !== strpos( $html, 'role="dialog"' ) && false === strpos( $html, 'aria-modal' ), 'a non-modal dialog' );

/* Each category switch is named by its category, for screen readers. */
$GLOBALS['dpt_stub_options'] = array( 'digitizer_cookie_consent' => array_merge( Digitizer_Cookie_Consent_Settings::defaults(), array( 'cat_functional_enabled' => '1', 'cat_analytics_enabled' => '1', 'cat_marketing_enabled' => '1' ) ) );
ob_start(); $m->invoke( $f, Digitizer_Cookie_Consent_Settings::all(), Digitizer_Cookie_Consent_Settings::get_texts( 'en' ), 'en', 'ltr' ); $html = (string) ob_get_clean();
foreach ( array( 'functional', 'analytics', 'marketing' ) as $cat ) {
	$ok = preg_match( '#<input type="checkbox" data-dpt-cb-cat="' . $cat . '" aria-labelledby="([a-z0-9-]+)"#', $html, $hit )
		&& false !== strpos( $html, 'id="' . $hit[1] . '"' );
	dpt_test_ok( (bool) $ok, "the $cat switch is labelled by its category name" );
}

/* Uninstall removes the option. */
$GLOBALS['dpt_stub_options'] = array( 'digitizer_cookie_consent' => array( 'enabled' => '1' ) );
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { define( 'WP_UNINSTALL_PLUGIN', true ); }
include dirname( __DIR__ ) . '/uninstall.php';
dpt_test_ok( ! isset( $GLOBALS['dpt_stub_options']['digitizer_cookie_consent'] ), 'uninstall deletes the option' );

exit( dpt_test_summary() > 0 ? 1 : 0 );
