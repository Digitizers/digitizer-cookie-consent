<?php
require_once __DIR__ . '/bootstrap.php';
$main = (string) file_get_contents( dirname( __DIR__ ) . '/digitizer-cookie-consent.php' );
dpt_test_ok( false !== strpos( $main, 'Text Domain:       digitizer-cookie-consent' ), 'the text domain is the slug' );
dpt_test_ok( false !== strpos( $main, 'Requires at least: 5.7' ), 'WordPress 5.7, for wp_print_inline_script_tag()' );
dpt_test_ok( false !== strpos( $main, "define( 'DIGITIZER_COOKIE_CONSENT_VERSION', '1.0.0' );" ), 'version 1.0.0' );
foreach ( array( '.github/workflows/deploy.yml', '.github/workflows/assets.yml' ) as $f ) {
	dpt_test_ok( false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/' . $f ), 'SLUG: digitizer-cookie-consent' ), "$f deploys this slug" );
}
exit( dpt_test_summary() > 0 ? 1 : 0 );
