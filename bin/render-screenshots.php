<?php
/**
 * Write design/screenshot-*.html from the plugin's real banner markup and
 * stylesheet, over a stand-in page, for bin/build-assets.sh to capture.
 * Development only - not shipped.
 *
 *   php bin/render-screenshots.php
 */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
$root = dirname( __DIR__ );
require $root . '/tests/bootstrap.php';
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
if ( ! function_exists( 'wpautop' ) ) { function wpautop( $t ) { return '<p>' . trim( (string) $t ) . "</p>\n"; } }
require $root . '/includes/class-digitizer-cookie-consent-settings.php';
require $root . '/includes/class-digitizer-cookie-consent-frontend.php';
$GLOBALS['dpt_stub_is_admin'] = false;
$GLOBALS['dpt_stub_no_user']  = true;

$shots = array(
	'screenshot-he.html'    => array( 'he', 'rtl', array( 'position' => 'bottom-left', 'policy_url' => 'https://example.test/privacy/', 'policy_inline' => '1' ), false ),
	'screenshot-en.html'    => array( 'en', 'ltr', array( 'position' => 'bottom', 'policy_url' => 'https://example.test/privacy/' ), false ),
	'screenshot-prefs.html' => array( 'he', 'rtl', array( 'position' => 'center', 'overlay_enabled' => '1', 'policy_url' => 'https://example.test/privacy/' ), true ),
);
$css_file = (string) file_get_contents( $root . '/assets/css/frontend.css' );
foreach ( $shots as $file => $shot ) {
	list( $lang, $dir, $saved, $prefs ) = $shot;
	$GLOBALS['dpt_stub_options'][ Digitizer_Cookie_Consent_Settings::OPTION ] = $saved;
	$o  = Digitizer_Cookie_Consent_Settings::all();
	$fe = new Digitizer_Cookie_Consent_Frontend();
	$m  = new ReflectionMethod( 'Digitizer_Cookie_Consent_Frontend', 'render_banner_markup' );
	if ( PHP_VERSION_ID < 80100 ) { $m->setAccessible( true ); }
	ob_start();
	$m->invoke( $fe, $o, Digitizer_Cookie_Consent_Settings::get_texts( $lang ), $lang, $dir );
	$html = str_replace( ' dpt-cb-hidden', '', (string) ob_get_clean() );
	if ( $prefs ) {
		$html = str_replace( array( 'class="dpt-cb-main-view"', 'class="dpt-cb-settings-view" style="display:none;"' ), array( 'class="dpt-cb-main-view" style="display:none;"', 'class="dpt-cb-settings-view"' ), $html );
	}
	$title = 'he' === $lang ? 'סטודיו לעיצוב ובניית אתרים' : 'A design and web studio';
	$page  = '<!doctype html><html lang="' . $lang . '" dir="' . $dir . '"><head><meta charset="utf-8"><style>'
		. 'body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif;background:#f8fafc;color:#0f172a}'
		. '.hero{padding:120px 80px;background:linear-gradient(135deg,#eef2ff,#f8fafc)}.hero h1{font-size:54px;margin:0 0 16px}.hero p{font-size:20px;color:#475569;max-width:640px}'
		. '.cards{display:flex;gap:24px;padding:0 80px}.card{flex:1;height:180px;border-radius:16px;background:#e2e8f0}'
		. $css_file . $fe->design_css( $o ) . '</style></head><body>'
		. '<div class="hero"><h1>' . $title . '</h1><p>' . ( 'he' === $lang ? 'עמוד לדוגמה מאחורי הבאנר.' : 'A sample page behind the banner.' ) . '</p></div><div class="cards"><div class="card"></div><div class="card"></div><div class="card"></div></div>'
		. $html . '</body></html>';
	file_put_contents( $root . '/design/' . $file, $page );
	echo "design/$file\n";
}
