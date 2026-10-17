<?php
/**
 * Regenerate the plugin's code from Digitizer Pro Tools' Cookie Banner
 * module. The standalone is a renamed copy; this is the rename, so a module
 * change is mirrored by re-running it and reviewing the diff.
 *
 *   php bin/port-from-dpt.php [path-to-digitizer-pro-tools]
 *
 * Names the spec keeps (the consent cookie, #dpt-cookie-settings,
 * .dpt-cb-open, dpt:consent, the dpt-cb-* HTML ids and classes) are not
 * touched. Development only - not shipped.
 */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
$src  = rtrim( $argv[1] ?? dirname( __DIR__, 2 ) . '/digitizer-pro-tools', '/' );
$root = dirname( __DIR__ );
$map  = array(
	'modules/cookie-banner/class-dpt-cb-settings.php' => 'includes/class-digitizer-cookie-consent-settings.php',
	'modules/cookie-banner/class-dpt-cb-frontend.php' => 'includes/class-digitizer-cookie-consent-frontend.php',
	'modules/cookie-banner/class-dpt-cb-admin.php'    => 'includes/class-digitizer-cookie-consent-admin.php',
	'modules/cookie-banner/assets/css/frontend.css'   => 'assets/css/frontend.css',
	'modules/cookie-banner/assets/js/frontend.js'     => 'assets/js/frontend.js',
	'tests/cb-design-test.php'                        => 'tests/design-test.php',
	'tests/cb-consent-signals-test.php'               => 'tests/consent-signals-test.php',
	'tests/cb-cookie-cleanup-test.php'                => 'tests/cookie-cleanup-test.php',
);
// Order matters: specific before general.
$rename = array(
	"dirname( __DIR__ ) . '/modules/cookie-banner/class-dpt-cb-settings.php'" => "dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-settings.php'",
	"dirname( __DIR__ ) . '/modules/cookie-banner/class-dpt-cb-frontend.php'" => "dirname( __DIR__ ) . '/includes/class-digitizer-cookie-consent-frontend.php'",
	"dirname( __DIR__ ) . '/modules/cookie-banner/assets/js/frontend.js'"     => "dirname( __DIR__ ) . '/assets/js/frontend.js'",
	"dirname( __DIR__ ) . '/modules/cookie-banner/assets/css/frontend.css'"   => "dirname( __DIR__ ) . '/assets/css/frontend.css'",
	"DPT_URL . 'modules/cookie-banner/assets/'" => "DIGITIZER_COOKIE_CONSENT_URL . 'assets/'",
	// WP Rocket's file exclusion is a literal path, not DPT_URL: without
	// this the standalone's script is not excluded and can be deferred
	// behind scripts that read the WP Consent API.
	"'digitizer-pro-tools/modules/cookie-banner/assets/js/frontend.js'" => "'digitizer-cookie-consent/assets/js/frontend.js'",
	'DPT_CB_Settings'           => 'Digitizer_Cookie_Consent_Settings',
	'DPT_CB_Frontend'           => 'Digitizer_Cookie_Consent_Frontend',
	'DPT_CB_Admin'              => 'Digitizer_Cookie_Consent_Admin',
	"'dpt_cookie_banner'"       => "'digitizer_cookie_consent'",
	'DPT_CB_CONFIG'             => 'DigitizerCookieConsentConfig',
	// strtr() matches the longest key first, so the bare forms below also
	// cover window.DPTCB, '[DPTCB]' log text, the 'DPTCB' optimizer
	// exclusion marker, and the tests' dptCbPurge assertions.
	'DPTCB'                     => 'DigitizerCookieConsent',
	'dptCbPurge'                => 'digitizerCookieConsentPurge',
	'dpt_cb_open_selector'      => 'digitizer_cookie_consent_open_selector',
	'dpt_cb_rtl_langs'          => 'digitizer_cookie_consent_rtl_langs',
	'dpt_cb_purge_caches'       => 'digitizer_cookie_consent_purge_caches',
	'dpt_cb_preview'            => 'digitizer_cc_preview',
	'dpt_debug'                 => 'digitizer_cc_debug',
	// Bare, so the admin_post_* hooks, the forms' value="..." and the nonce
	// actions all move together - a form posting to an old action would
	// reach no handler.
	'dpt_cb_save'               => 'digitizer_cc_save',
	'dpt_cb_add_lang'           => 'digitizer_cc_add_lang',
	'dpt_cb_remove_lang'        => 'digitizer_cc_remove_lang',
	// A Digitizer Pro Tools core helper; the plugin carries its own (Task 5).
	'dpt_current_admin_page()'  => 'self::current_page()',
	"'dpt-cb-frontend'"         => "'digitizer-cookie-consent'",
	'DPT_VERSION'               => 'DIGITIZER_COOKIE_CONSENT_VERSION',
	// The debug panel's visible titles, so a support dump names the plugin.
	'DPT Cookie Banner Debug'      => 'Digitizer Cookie Consent Debug',
	'DPT COOKIE BANNER DEBUG DUMP' => 'DIGITIZER COOKIE CONSENT DEBUG DUMP',
	"'digitizer-pro-tools'"     => "'digitizer-cookie-consent'",
	'@package Digitizer_Pro_Tools' => '@package Digitizer_Cookie_Consent',
);
foreach ( $map as $from => $to ) {
	$code = file_get_contents( "$src/$from" );
	if ( false === $code ) { fwrite( STDERR, "missing $src/$from\n" ); exit( 1 ); }
	$code = strtr( $code, $rename );
	// Every DPT_ identifier the spec renames must be gone; DPT_BASENAME is
	// replaced in Task 4, DPT_ in comments is allowed.
	foreach ( array( 'DPT_CB_', 'DPTCB', 'dptCbPurge', "'dpt_cookie_banner'", 'DPT_URL', 'DPT_VERSION', 'dpt_current_admin_page', 'dpt_cb_save', 'dpt_cb_add_lang', 'dpt_cb_remove_lang', 'modules/cookie-banner' ) as $left ) {
		if ( false !== strpos( $code, $left ) ) { fwrite( STDERR, "$to still contains $left\n" ); exit( 1 ); }
	}
	@mkdir( dirname( "$root/$to" ), 0777, true );
	file_put_contents( "$root/$to", $code );
	echo "$from -> $to\n";
}
