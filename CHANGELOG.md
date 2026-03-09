# Changelog

Tutte le modifiche significative a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [1.1.3] - 2025-03-09

### Aggiunto
- **Update Checker**: aggiornamenti da GitHub tramite `salus/class-ssr-update-checker.php`
- **Versione in admin**: badge con numero versione accanto al titolo nella pagina admin
- **Update URI**: nell'header del plugin per collegamento al repository GitHub

### Modificato
- **salus-admin-menu.php**: spostato da `includes/` alla cartella `salus/`
- **Costanti**: aggiunte `SSR_PLUGIN_FILE` e `SSR_PLUGIN_DIR`

## [1.1.2] - 2025-03-09

### Modificato
- Aggiornamenti minori

## [1.1.1] - 2026-03-09

### Aggiunto
- **CHANGELOG.md**: nuovo file per tracciare le modifiche del plugin

### Modificato
- **Menu admin**: spostato da Strumenti al menu Salus tramite `Salus_Admin_Menu::register_submenu()`
- **Asset**: CSS e JS spostati nella cartella `assets/`
- **Versioning**: introdotta costante `SSR_VERSION` per il versioning degli asset
