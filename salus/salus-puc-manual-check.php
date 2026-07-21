<?php
/**
 * Salus PUC helper: link "Update now" nel notice dopo "Check for updates".
 *
 * Uso (dopo buildUpdateChecker):
 *   require_once __DIR__ . '/salus-puc-manual-check.php';
 *   Salus_Puc_Manual_Check::add_update_now_link( $slug, $plugin_file );
 *
 * @package Salus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Salus_Puc_Manual_Check' ) ) {

	class Salus_Puc_Manual_Check {

		/**
		 * Aggiunge un link "Update now" al messaggio PUC quando c'è un aggiornamento.
		 *
		 * @param string $slug        Slug PUC (terzo argomento di buildUpdateChecker).
		 * @param string $plugin_file Percorso assoluto al file principale del plugin.
		 * @return void
		 */
		public static function add_update_now_link( $slug, $plugin_file ) {
			$slug        = (string) $slug;
			$plugin_file = (string) $plugin_file;

			if ( '' === $slug || '' === $plugin_file ) {
				return;
			}

			add_filter(
				'puc_manual_check_message-' . $slug,
				static function ( $message, $status ) use ( $plugin_file ) {
					if ( 'update_available' !== $status ) {
						return $message;
					}

					$basename = plugin_basename( $plugin_file );
					$url      = wp_nonce_url(
						self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $basename ) ),
						'upgrade-plugin_' . $basename
					);

					return $message . sprintf(
						' <a href="%s">%s</a>',
						esc_url( $url ),
						esc_html__( 'Update now' )
					);
				},
				10,
				2
			);
		}
	}
}
