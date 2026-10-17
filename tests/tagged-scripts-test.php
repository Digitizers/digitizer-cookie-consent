<?php
/**
 * The plugin stores and prints no code. A site tags its own scripts
 * type="text/plain" data-dpt-cb-category="..." and frontend.js turns them
 * into running scripts once that category is allowed.
 */
require_once __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
if ( ! function_exists( 'get_locale' ) ) { function get_locale() { return 'en_US'; } }
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php';
$GLOBALS['dpt_stub_is_admin'] = false;
$GLOBALS['dpt_stub_no_user']  = true;

$GLOBALS['dpt_stub_filters'] = array();
$f = new Digitizer_Cookie_Consent_Frontend();
dpt_test_ok( ! dpt_stub_has_filter( 'wp_head' ) || ! method_exists( $f, 'inject_consented_scripts' ), 'nothing prints stored snippets into the head' );
$f->enqueue();
$cfg = $GLOBALS['dpt_stub_localized']['digitizer-cookie-consent'][1];
dpt_test_ok( ! array_key_exists( 'scripts', $cfg ) && ! array_key_exists( 'blockScripts', $cfg ), 'the page config carries no code' );

$js = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/frontend.js' );
dpt_test_ok( false === strpos( $js, 'innerHTML = html' ) && false === strpos( $js, 'appendExecutable' ), 'frontend.js no longer builds scripts from stored HTML' );

$watch = substr( $js, (int) strpos( $js, 'function watchTaggedScripts' ), 900 );
dpt_test_ok( false !== strpos( $watch, 'new MutationObserver' ) && false !== strpos( $watch, 'activateTaggedScripts(readConsent())' ), 'a tagged script added later - by Tag Manager, say - is run too, under the stored choice' );
$init = substr( $js, (int) strpos( $js, 'function init()' ) );
dpt_test_ok( false !== strpos( $init, 'watchTaggedScripts();' ), 'and the page is watched from load' );

$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );
$start = strpos( $js, '    var injectedCats' );
$end   = strpos( $js, '    // Google Consent Mode v2' );
if ( '' === $node || false === $start || false === $end ) {
	dpt_test_ok( false, 'node available and the activation block found' );
	exit( dpt_test_summary() > 0 ? 1 : 0 );
}
$block = substr( $js, $start, $end - $start );

/**
 * Run the activation on a page holding these tagged scripts.
 *
 * @param array $consent The visitor's choice.
 * @param array $cats    Categories offered.
 * @return array What ran, in order, and what is still inert.
 */
$run = function ( $consent, $cats ) use ( $node, $block ) {
	$harness = <<<'JS'
function El(attrs, text) { this.attributes = []; this.parentNode = null; this.text = text || '';
  for (var k in attrs) this.attributes.push({ name: k, value: attrs[k] }); }
El.prototype.getAttribute = function (n) { for (var i = 0; i < this.attributes.length; i++) if (this.attributes[i].name === n) return this.attributes[i].value; return null; };
El.prototype.setAttribute = function (n, v) { for (var i = 0; i < this.attributes.length; i++) if (this.attributes[i].name === n) { this.attributes[i].value = v; return; } this.attributes.push({ name: n, value: v }); };
var ran = [];
var body = { children: [], replaceChild: function (n, o) { var i = this.children.indexOf(o); this.children[i] = n; n.parentNode = this; o.parentNode = null;
  ran.push((n.getAttribute('type') || 'js') + ':' + (n.getAttribute('src') || n.text) + (n.getAttribute('data-dpt-cb-category') ? ':tagged' : ''));
  if (n.getAttribute('src')) setTimeout(function () { n.onload && n.onload(); }, 5); } };
function add(attrs, text) { var e = new El(attrs, text); e.parentNode = body; body.children.push(e); return e; }
add({ type: 'text/plain', 'data-dpt-cb-category': 'analytics', src: 'https://example.test/gtag.js' });
add({ type: 'text/plain', 'data-dpt-cb-category': 'analytics' }, 'gtag("config")');
add({ type: 'text/plain', 'data-dpt-cb-category': 'marketing' }, 'fbq("init")');
add({ type: 'text/plain', 'data-dpt-cb-category': 'functional', 'data-dpt-cb-type': 'module' }, 'chat()');
add({ type: 'text/plain' }, 'not ours');
var document = {
  createElement: function () { return new El({}); },
  querySelectorAll: function (sel) { return body.children.filter(function (e) {
    return e.getAttribute('type') === 'text/plain' && e.getAttribute('data-dpt-cb-category') !== null; }); }
};
var config = { categories: __CATS__ };
function log() {}
__BLOCK__
function granted(consent, cat) { var cats = config.categories || {}; return !!(consent && consent[cat] && cats[cat]); }
activateTaggedScripts(__CONSENT__);
activateTaggedScripts(__CONSENT__); // a second call (boot, then a choice) runs nothing twice
setTimeout(function () {
  process.stdout.write(JSON.stringify({ ran: ran, inert: body.children.filter(function (e) { return e.getAttribute('type') === 'text/plain'; }).length, cats: injectedCats }));
}, 50);
JS;
	$harness = str_replace( array( '__CATS__', '__BLOCK__', '__CONSENT__' ), array( json_encode( $cats ), $block, json_encode( $consent ) ), $harness );
	$file    = tempnam( sys_get_temp_dir(), 'dcc' ) . '.js';
	file_put_contents( $file, $harness );
	$out = shell_exec( escapeshellarg( $node ) . ' ' . escapeshellarg( $file ) . ' 2>&1' );
	unlink( $file );
	$decoded = json_decode( (string) $out, true );
	return is_array( $decoded ) ? $decoded : array( 'node error' => $out );
};

$all = array( 'functional' => true, 'analytics' => true, 'marketing' => true );
$r = $run( array( 'functional' => false, 'analytics' => true, 'marketing' => false ), $all );
dpt_test_eq( $r['ran'] ?? $r, array( 'js:https://example.test/gtag.js', 'js:gtag("config")' ), 'analytics allowed: its two scripts run, in page order, the inline one after the library loads - once each' );
dpt_test_eq( $r['inert'], 3, 'marketing, functional and an untagged text/plain script stay inert' );
dpt_test_eq( array( $r['cats']['analytics'], $r['cats']['marketing'] ), array( true, false ), 'and the page remembers which category is running, for a later withdrawal' );

$r = $run( $all, array( 'functional' => true, 'analytics' => true, 'marketing' => false ) );
dpt_test_ok( ! in_array( 'js:fbq("init")', $r['ran'], true ), 'a category no longer offered runs nothing, whatever the stored choice says' );
dpt_test_ok( in_array( 'module:chat()', $r['ran'], true ), 'data-dpt-cb-type sets the running script\'s type, for modules' );

$r = $run( null, $all );
dpt_test_eq( $r['ran'], array(), 'no choice yet: nothing runs' );

exit( dpt_test_summary() > 0 ? 1 : 0 );
