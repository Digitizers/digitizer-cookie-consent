<?php
require_once __DIR__ . '/bootstrap.php';

/* Every translated string in the shipped PHP uses the plugin's own domain. */
$wrong = array();
foreach ( array_merge( glob( dirname( __DIR__ ) . '/includes/*.php' ), array( dirname( __DIR__ ) . '/digitizer-cookie-consent.php' ) ) as $file ) {
	$src = (string) file_get_contents( $file );
	if ( preg_match_all( "/\\b(__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\\(\\s*'(?:[^'\\\\]|\\\\.)*'\\s*,\\s*'([^']+)'/", $src, $m ) ) {
		foreach ( $m[2] as $domain ) {
			if ( 'digitizer-cookie-consent' !== $domain ) {
				$wrong[] = basename( $file ) . ': ' . $domain;
			}
		}
	}
}
dpt_test_eq( $wrong, array(), 'every string uses the digitizer-cookie-consent text domain' );

/* The Hebrew catalog is complete and loadable. */
$l10n = include dirname( __DIR__ ) . '/languages/digitizer-cookie-consent-he_IL.l10n.php';
dpt_test_ok( is_array( $l10n ) && isset( $l10n['messages']['Cookie consent by Digitizer'] ), 'the .l10n.php carries the new strings' );
dpt_test_eq( $l10n['messages']['Not recommended: scrolling past the banner is not an explicit choice.'] ?? null, 'לא מומלץ: גלילה מעבר לבאנר אינה בחירה מפורשת.', 'and their Hebrew' );
dpt_test_ok( is_file( dirname( __DIR__ ) . '/languages/digitizer-cookie-consent-he_IL.mo' ), 'the .mo is compiled' );

/* No legal claims in the UI. */
$ui = '';
foreach ( array_merge( glob( dirname( __DIR__ ) . '/includes/*.php' ), glob( dirname( __DIR__ ) . '/assets/*/*' ) ) as $file ) {
	$ui .= (string) file_get_contents( $file );
}
dpt_test_eq( preg_match_all( '/gdpr|complian|amendment 13|EU law/i', $ui ), 0, 'no GDPR, compliance or Amendment 13 claims in the plugin\'s code or screens' );

exit( dpt_test_summary() > 0 ? 1 : 0 );
