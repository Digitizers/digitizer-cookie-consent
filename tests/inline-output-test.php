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
exit( dpt_test_summary() > 0 ? 1 : 0 );
