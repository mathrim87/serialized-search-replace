<?php
/**
 * Salus Admin Menu Helper
 *
 * Classe riutilizzabile per registrare sottomenu sotto il menu "Salus" nella dashboard WordPress.
 * Se il menu Salus non esiste, viene creato automaticamente.
 *
 * Utilizzo in un nuovo plugin:
 *   add_action('admin_menu', function() {
 *       Salus_Admin_Menu::register_submenu(
 *           __('Titolo Pagina', 'mio-plugin'),
 *           __('Titolo Menu', 'mio-plugin'),
 *           'manage_options',
 *           'mio-plugin-slug',
 *           array($this, 'render_callback'),
 *           'mio-plugin'  // text domain per le traduzioni del menu Salus (opzionale)
 *       );
 *   }, 99);
 *
 * @package Salus
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Salus_Admin_Menu')) {

    class Salus_Admin_Menu {

        /**
         * Flag per evitare di registrare l'hook di ordinamento più volte.
         *
         * @var bool
         */
        private static $sort_hook_added = false;

        /**
         * Registra un sottomenu sotto il menu Salus.
         *
         * @param string   $page_title   Titolo della pagina (tag title).
         * @param string   $menu_title   Testo visibile nel menu.
         * @param string   $capability   Capability richiesta (es. 'manage_options').
         * @param string   $menu_slug    Slug univoco del sottomenu.
         * @param callable $callback     Funzione/callback per il rendering della pagina.
         * @param string   $text_domain  Text domain per le traduzioni del menu Salus (default: 'salus').
         * @return void
         */
        public static function register_submenu($page_title, $menu_title, $capability, $menu_slug, $callback, $text_domain = 'salus') {
            $salus_menu_slug = 'salus';

            if (!self::$sort_hook_added) {
                add_action('admin_menu', array(__CLASS__, 'sort_submenu'), 999);
                self::$sort_hook_added = true;
            }

            if (self::menu_exists($salus_menu_slug)) {
                add_submenu_page(
                    $salus_menu_slug,
                    $page_title,
                    $menu_title,
                    $capability,
                    $menu_slug,
                    $callback
                );
                return;
            }

            // Crea il menu Salus e aggiungi il sottomenu
            add_menu_page(
                __('Salus', $text_domain),
                __('Salus', $text_domain),
                $capability,
                $salus_menu_slug,
                $callback,
                'dashicons-heart',
                30
            );

            add_submenu_page(
                $salus_menu_slug,
                $page_title,
                $menu_title,
                $capability,
                $menu_slug,
                $callback
            );

            remove_submenu_page($salus_menu_slug, $salus_menu_slug);
        }

        /**
         * Verifica se il menu Salus esiste già.
         *
         * @param string $slug Slug del menu (default: 'salus').
         * @return bool
         */
        public static function menu_exists($slug = 'salus') {
            global $menu;

            if (!is_array($menu)) {
                return false;
            }

            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === $slug) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Ordina le voci del menu Salus alfabeticamente.
         * Eseguito con priorità 999, dopo che tutti i plugin hanno registrato i sottomenu.
         *
         * @return void
         */
        public static function sort_submenu() {
            global $submenu;
            if (isset($submenu['salus']) && is_array($submenu['salus'])) {
                usort($submenu['salus'], function ($a, $b) {
                    return strcasecmp($a[0], $b[0]);
                });
            }
        }
    }
}
