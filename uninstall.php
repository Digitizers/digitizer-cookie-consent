<?php
/**
 * Remove everything Digitizer Cookie Consent stored: one option. On a
 * network the plugin can have run on any subset of sites, so every site is
 * visited - and only this plugin's own data is touched in each. Visitors'
 * consent cookies live in their browsers; nothing here can reach them.
 *
 * @package Digitizer_Cookie_Consent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Forget this site's settings.
 *
 * A closure rather than a named function: an uninstall file is included, and
 * a name declared at the top level of an included file cannot be included
 * twice - which the tests do.
 *
 * @var callable $digitizer_cookie_consent_uninstall_site
 */
$digitizer_cookie_consent_uninstall_site = function () {
	delete_option( 'digitizer_cookie_consent' );
};

if ( is_multisite() ) {
	// get_sites() stops at 100 unless the cap is lifted.
	$digitizer_cookie_consent_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $digitizer_cookie_consent_sites as $digitizer_cookie_consent_site_id ) {
		switch_to_blog( (int) $digitizer_cookie_consent_site_id );
		$digitizer_cookie_consent_uninstall_site();
		restore_current_blog();
	}
} else {
	$digitizer_cookie_consent_uninstall_site();
}
