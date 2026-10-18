<?php
/**
 * Escaping late (wp.org review): every value printed into the inline CSS is
 * escaped where it is printed, and the CSS stays valid after escaping.
 */
require_once __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'do_action' ) ) { function do_action( $t ) {} }
if ( ! function_exists( 'add_shortcode' ) ) { function add_shortcode( $t, $c ) {} }
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php';

$src  = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php' );
$from = strpos( $src, 'public function design_css' );
$body = substr( $src, $from, strpos( $src, 'return (string) ob_get_clean();', $from ) - $from );
preg_match_all( '/<\?php echo (?!esc_attr\(|absint\(|esc_url\()[^?]*\?>/', $body, $bare );
dpt_test_eq( $bare[0], array(), 'every echo in the CSS template is escaped where it is printed' );
dpt_test_ok( 2 === preg_match_all( '/wp_add_inline_style\(\s*\'[a-z-]+\',\s*wp_strip_all_tags\(/', $src ), 'both inline styles pass through wp_strip_all_tags()' );

$o = array_merge( Digitizer_Cookie_Consent_Settings::defaults(), array(
	'font_family'        => 'custom',
	'font_family_custom' => '"Open Sans", sans-serif',
	'bg_image_url'       => 'https://example.test/wp-content/uploads/a.jpg',
) );
$GLOBALS['dpt_stub_options'] = array( 'digitizer_cookie_consent' => $o );
$css = ( new Digitizer_Cookie_Consent_Frontend() )->design_css( Digitizer_Cookie_Consent_Settings::all() );
dpt_test_ok( false === strpos( $css, '&quot;' ) && false === strpos( $css, '&#039;' ) && false === strpos( $css, '&#039' ), 'escaping leaves no HTML entity in the CSS' );
dpt_test_ok( 1 === preg_match( '/font-family:\s*Open Sans, sans-serif !important;/', $css ), 'a quoted family name is printed unquoted, which CSS reads the same' );
dpt_test_ok( false !== strpos( $css, 'url(https://example.test/wp-content/uploads/a.jpg)' ), 'the background image URL is printed unquoted and escaped' );

$GLOBALS['dpt_stub_options']['digitizer_cookie_consent'] = array_merge( $o, array( 'font_family_custom' => '"Source Sans 3", \'Noto 2B Sans\', sans-serif', 'bg_image_url' => 'https://example.test/uploads/banner(1) x.jpg' ) );
$css = ( new Digitizer_Cookie_Consent_Frontend() )->design_css( Digitizer_Cookie_Consent_Settings::all() );
dpt_test_ok( false !== strpos( $css, 'font-family: Source Sans \\33 , Noto \\32 B Sans, sans-serif !important;' ), 'a word that starts with a digit is escaped as a CSS identifier, so the family stays valid unquoted' );
dpt_test_ok( false !== strpos( $css, 'url(https://example.test/uploads/banner%281%29%20x.jpg)' ), 'parentheses and spaces in the image URL are percent-encoded, so it stays a valid unquoted url()' );

$all = '';
foreach ( glob( dirname( __DIR__ ) . '/includes/*.php' ) as $file ) { $all .= (string) file_get_contents( $file ); }
dpt_test_eq( substr_count( $all, 'EscapeOutput.OutputNotEscaped' ), 0, 'no output is waved through with a phpcs:ignore: everything printed is escaped' );
$svg = Digitizer_Cookie_Consent_Frontend::svg_allowed_html();
dpt_test_ok( isset( $svg['svg']['viewbox'], $svg['path']['d'], $svg['line']['x1'], $svg['rect']['rx'] ), 'the icons are printed through wp_kses() with an SVG allowlist' );
dpt_test_eq( wp_kses( Digitizer_Cookie_Consent_Frontend::close_glyph( 'svg' ), $svg ), Digitizer_Cookie_Consent_Frontend::close_glyph( 'svg' ), 'which keeps the close icon whole' );

exit( dpt_test_summary() > 0 ? 1 : 0 );
