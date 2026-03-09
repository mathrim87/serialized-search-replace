<?php
/**
 * Plugin Update Checker - Aggiornamenti da GitHub
 *
 * Configura Plugin Update Checker per verificare le Release su GitHub
 * e mostrare gli aggiornamenti nella schermata Plugin di WordPress.
 *
 * Richiede la libreria PUC in salus/plugin-update-checker/
 * Scarica da: https://github.com/YahnisElsts/plugin-update-checker/releases
 *
 * Per repo privato: definisci GITHUB_TOKEN in wp-config.php (condiviso tra i plugin)
 *
 * @package Serialized_Search_Replace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSR_Update_Checker {

	/**
	 * Inizializza il controllo aggiornamenti.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! is_admin() || defined( 'SSR_DISABLE_UPDATE_CHECK' ) ) {
			return;
		}

		$puc_path = SSR_PLUGIN_DIR . 'salus/plugin-update-checker/load-v5p6.php';
		if ( ! file_exists( $puc_path ) ) {
			return;
		}

		require $puc_path;

		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/mathrim87/serialized-search-replace',
			SSR_PLUGIN_FILE,
			'serialized-search-replace'
		);

		// Usa le Release di GitHub (ZIP allegato dal workflow auto-release)
		$checker->getVcsApi()->enableReleaseAssets();

		// Per repo privato: definisci GITHUB_TOKEN in wp-config.php (condiviso tra i plugin)
		if ( defined( 'GITHUB_TOKEN' ) && GITHUB_TOKEN ) {
			$checker->setAuthentication( GITHUB_TOKEN );
		}
	}
}
