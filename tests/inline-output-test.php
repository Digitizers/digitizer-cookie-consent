<?php
require_once __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
if ( ! function_exists( 'get_locale' ) ) { function get_locale() { return 'he_IL'; } }
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php';
$GLOBALS['dpt_stub_is_admin'] = false;
$GLOBALS['dpt_stub_no_user']  = true;

$GLOBALS['dpt_stub_filters'] = array();
$f = new Digitizer_Cookie_Consent_Frontend();
dpt_test_ok( dpt_stub_has_filter( 'wp_head' ), 'the precheck is hooked to wp_head' );
ob_start(); $f->inject_inline_consent_check(); $head = (string) ob_get_clean();
dpt_test_ok( 1 === preg_match( '#<script id="digitizer-cookie-consent-precheck" data-no-defer data-cfasync="false">#', $head ), 'the precheck goes through wp_print_inline_script_tag(), keeping the attributes that stop WP Rocket and Cloudflare deferring it' );
dpt_test_ok( false === strpos( $head, '<style' ), 'no raw <style> in the head' );
dpt_test_ok( isset( $GLOBALS['dpt_stub_inline_styles']['digitizer-cookie-consent-precheck'] ) && false !== strpos( implode( '', $GLOBALS['dpt_stub_inline_styles']['digitizer-cookie-consent-precheck'] ), 'html[data-dpt-cb-resolved="1"] #dpt-cb-banner' ), 'the CSS that hides the banner for a visitor who answered is an inline style on its own handle' );

$src = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php' );
dpt_test_ok( 0 === preg_match_all( '/^\s*<(script|style)[ >]/m', $src ), 'no raw <script> or <style> tag left in the front-end class' );
dpt_test_ok( false === strpos( $src, 'onclick=' ), 'the floating button has no inline onclick; frontend.js binds it' );

// The design CSS rides on the front-end stylesheet.
$GLOBALS['dpt_stub_inline_styles'] = array();
$f->enqueue();
dpt_test_ok( isset( $GLOBALS['dpt_stub_inline_styles']['digitizer-cookie-consent'] ) && false !== strpos( implode( '', $GLOBALS['dpt_stub_inline_styles']['digitizer-cookie-consent'] ), '#dpt-cb-banner .dpt-cb-box' ), 'the design CSS is an inline style on the plugin\'s stylesheet' );

/* The consent cookie's domain: the parent domain by default, host-only or explicit through a filter. */
$cfg = function () use ( $f ) { $f->enqueue(); return $GLOBALS['dpt_stub_localized']['digitizer-cookie-consent'][1]; };
dpt_test_ok( array_key_exists( 'cookieDomain', $cfg() ) && null === $cfg()['cookieDomain'], 'by default frontend.js picks the parent domain itself (cookieDomain null)' );
add_filter( 'digitizer_cookie_consent_cookie_domain', function () { return ''; } );
dpt_test_eq( $cfg()['cookieDomain'], '', 'the filter returning \'\' makes the cookie host-only' );
$GLOBALS['dpt_stub_filters'] = array();
add_filter( 'digitizer_cookie_consent_cookie_domain', function () { return ' .Example.com '; } );
dpt_test_eq( $cfg()['cookieDomain'], '.example.com', 'an explicit domain is trimmed and lower-cased' );
$GLOBALS['dpt_stub_filters'] = array();
add_filter( 'digitizer_cookie_consent_cookie_domain', function () { return '.example.com"><x'; } );
dpt_test_eq( $cfg()['cookieDomain'], null, 'anything that is not a domain name falls back to the default' );
$GLOBALS['dpt_stub_filters'] = array();

$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );
$js   = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/frontend.js' );
if ( '' !== $node && preg_match( '/(    function getCookieDomain\(\) \{.*?\n    \})/s', $js, $fn ) ) {
	$run = function ( $domain ) use ( $node, $fn ) {
		$file = tempnam( sys_get_temp_dir(), 'dcc' ) . '.js';
		file_put_contents( $file, 'var location={hostname:"shop.example.co.il"};var config={cookieDomain:' . json_encode( $domain ) . '};' . $fn[1] . 'process.stdout.write(JSON.stringify(getCookieDomain()));' );
		$out = json_decode( (string) shell_exec( escapeshellarg( $node ) . ' ' . escapeshellarg( $file ) ) );
		unlink( $file );
		return $out;
	};
	dpt_test_eq( $run( null ), '.example.co.il', 'frontend.js: no filter, the parent domain' );
	dpt_test_eq( $run( '' ), '', 'frontend.js: host-only when the filter says so' );
	dpt_test_eq( $run( '.example.co.il' ), '.example.co.il', 'frontend.js: an explicit domain is used as given' );
} else {
	dpt_test_ok( false, 'getCookieDomain() found and node available' );
}

/* Switching where the cookie lives leaves no same-named copy behind to win over the new choice. */
if ( '' !== $node && preg_match( '/(    function getCookieDomain\(\) \{.*?\n    function getCookie\(name\) \{.*?\n    \})/s', $js, $fns ) ) {
	$jar_run = function ( $domain, $jar ) use ( $node, $fns ) {
		$file = tempnam( sys_get_temp_dir(), 'dcc' ) . '.js';
		$harness = 'var jar=' . json_encode( $jar ) . ';function log(){}
var location={hostname:"shop.example.co.il",protocol:"https:"};var config={cookieDomain:' . json_encode( $domain ) . '};
var document={get cookie(){return jar.map(function(c){return c[0]+"="+c[2];}).join("; ");},
set cookie(s){var p=s.split(";").map(function(x){return x.trim();});var n=p[0].split("=")[0],v=p[0].slice(n.length+1),d="",exp=false;
p.slice(1).forEach(function(x){var kv=x.split("=");var k=kv[0].toLowerCase();if(k==="domain")d=kv[1].charAt(0)==="."?kv[1]:"."+kv[1];if(k==="expires"&&new Date(kv[1])<new Date())exp=true;if(k==="max-age"&&kv[1]==="0")exp=true;});
if(d&&!("."+location.hostname).endsWith(d))return;if(d===".co.il")return;
jar=jar.filter(function(c){return !(c[0]===n&&c[1]===d);});if(!exp)jar.push([n,d,v]);}};
' . $fns[1] . '
setCookie("dpt_consent","new",180);process.stdout.write(JSON.stringify(jar.map(function(c){return c[0]+"@"+(c[1]||"host")+"="+c[2];}).sort()));';
		file_put_contents( $file, $harness );
		$out = json_decode( (string) shell_exec( escapeshellarg( $node ) . ' ' . escapeshellarg( $file ) ), true );
		unlink( $file );
		return $out;
	};
	dpt_test_eq( $jar_run( '', array( array( 'dpt_consent', '.example.co.il', 'old' ) ) ), array( 'dpt_consent@host=new' ), 'host-only: the parent-domain copy from before is expired' );
	dpt_test_eq( $jar_run( null, array( array( 'dpt_consent', '', 'old' ) ) ), array( 'dpt_consent@.example.co.il=new' ), 'parent domain: a host-only copy from before is expired' );
	dpt_test_eq( $jar_run( null, array( array( 'theme', '.example.co.il', 'x' ) ) ), array( 'dpt_consent@.example.co.il=new', 'theme@.example.co.il=x' ), 'other cookies are untouched' );
} else {
	dpt_test_ok( false, 'setCookie() found and node available' );
}
exit( dpt_test_summary() > 0 ? 1 : 0 );
