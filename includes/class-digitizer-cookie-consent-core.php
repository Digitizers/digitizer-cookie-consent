<?php
/**
 * Boot and lifecycle: what happens when the plugin loads, is activated,
 * is deactivated.
 *
 * @package Digitizer_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Digitizer_Cookie_Consent_Core {

	/** The option Digitizer Pro Tools' Cookie Banner module keeps its settings in. */
	const DPT_OPTION = 'dpt_cookie_banner';

	public static function boot() {
		// A site that got the files without the activation hook - a network
		// sub-site, a must-use install - still needs its option, and is owed
		// the same import an activated site gets.
		if ( ! is_array( get_option( Digitizer_Cookie_Consent_Settings::OPTION ) ) ) {
			self::seed_site();
		}

		new Digitizer_Cookie_Consent_Frontend();
		if ( is_admin() && class_exists( 'Digitizer_Cookie_Consent_Admin' ) ) {
			new Digitizer_Cookie_Consent_Admin();
		}

		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
	}

	/**
	 * The Hebrew catalog ships inside the plugin. WordPress 6.8 and later read
	 * the Domain Path header and find it on their own; earlier versions have
	 * to be told where it is.
	 */
	public static function load_textdomain() {
		if ( version_compare( get_bloginfo( 'version' ), '6.8', '>=' ) ) {
			return;
		}
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Needed on WordPress 5.7-6.7 for the bundled catalog; 6.8+ returns above.
		load_plugin_textdomain( 'digitizer-cookie-consent', false, dirname( plugin_basename( DIGITIZER_COOKIE_CONSENT_FILE ) ) . '/languages' );
	}

	/**
	 * WordPress runs this once, in the site the request came from, even when
	 * the plugin is switched on for a whole network - so a network activation
	 * visits every site itself.
	 *
	 * @param bool $network_wide Whether the plugin is being activated for the network.
	 */
	public static function activate( $network_wide = false ) {
		self::on_each_site(
			$network_wide,
			function () {
				Digitizer_Cookie_Consent_Core::seed_site();
				// Pages cached before activation carry no banner and no script
				// blocking; until they expire, visitors would get the trackers
				// without being asked.
				Digitizer_Cookie_Consent_Settings::purge_page_caches();
			}
		);
	}

	/** This site's option: what it chose in Digitizer Pro Tools if it came from there, the defaults otherwise. */
	public static function seed_site() {
		self::import_from_dpt();
		Digitizer_Cookie_Consent_Settings::install_defaults();
	}

	/**
	 * A site moving here from Digitizer Pro Tools keeps everything it chose
	 * there - including the consent version, so no visitor is asked again.
	 * Once, one way, and only when this plugin has no settings of its own;
	 * the module's option is left as it is.
	 *
	 * @return bool Whether settings were imported.
	 */
	public static function import_from_dpt() {
		if ( is_array( get_option( Digitizer_Cookie_Consent_Settings::OPTION ) ) ) {
			return false;
		}
		$theirs = get_option( self::DPT_OPTION );
		if ( ! is_array( $theirs ) ) {
			return false;
		}
		$clean = Digitizer_Cookie_Consent_Settings::sanitize_import( $theirs );
		// Those settings were somebody's decision already; no need to ask again.
		$clean['saved'] = '1';
		return (bool) add_option( Digitizer_Cookie_Consent_Settings::OPTION, $clean );
	}

	/**
	 * Pages cached with the banner are thrown away - in every site when the
	 * plugin is switched off for a network.
	 *
	 * @param bool $network_wide Whether the plugin is being deactivated for the network.
	 */
	public static function deactivate( $network_wide = false ) {
		self::on_each_site(
			$network_wide,
			function () {
				Digitizer_Cookie_Consent_Settings::purge_page_caches();
			}
		);
	}

	/**
	 * Run something in this site, or in every site of the network.
	 *
	 * @param bool     $network_wide Whether the whole network is meant.
	 * @param callable $task         What to run in each site.
	 */
	private static function on_each_site( $network_wide, $task ) {
		if ( ! $network_wide || ! is_multisite() ) {
			$task();
			return;
		}
		// get_sites() stops at 100 unless the cap is lifted.
		$sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);
		foreach ( $sites as $site_id ) {
			switch_to_blog( (int) $site_id );
			$task();
			restore_current_blog();
		}
	}
}
