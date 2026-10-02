# Changelog

Tutte le modifiche significative a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [1.1.8] - 2026-10-02

### Corretto
- **Scansione SQL**: `meta_key` obbligatoria su postmeta, usermeta e termmeta; `option_name` obbligatoria sulle tabelle options. Senza filtro la richiesta è rifiutata
- **Valori grandi**: le righe oltre 512 KB non vengono deserializzate (`CHAR_LENGTH` in SQL e controllo in PHP)
- **Profondità**: il walk ricorsivo si ferma a 32 livelli
- **Oggetti serializzati**: le righe con prefisso `O:` non entrano più nella query
- **ReDoS**: la modalità regex è rifiutata se `ini_set` non applica i limiti PCRE; quantificatori annidati e probe di backtrack bloccano i pattern pericolosi
- **Errori PCRE**: superamento di backtrack o recursion limit restituito come errore, insieme alle stringhe oltre 100 KB

## [1.1.7] - 2026-09-01

### Corretto
- **Deserializzazione sicura**: `unserialize()` con `allowed_classes => false` per evitare POP/object injection su dati serializzati
- **Scansione SQL**: paginazione batch (200 righe per richiesta) su ricerca e sostituzione AJAX per ridurre rischio DoS su tabelle meta/options grandi
- **Pattern regex generici**: blocco della scansione SQL quando il pattern regex non produce un filtro LIKE utilizzabile e non è selezionata una `meta_key`
- **ReDoS**: validazione sintassi regex prima dell’uso e limiti `pcre.backtrack_limit` / `pcre.recursion_limit` su `preg_match_all` e `preg_replace`

### Migliorato
- **Query SQL**: clausole LIKE e filtri costruiti con `$wpdb->prepare()` e `$wpdb->esc_like()`
- **Parametri AJAX**: normalizzazione centralizzata (`parse_request_params`) con `offset` e `batch_size`

## [1.1.6] - 2026-05-22

### Modificato
- versione plugin nell'intestazione admin allineata allo standard Salus (`salus-plugin-version` con stile inline)
- rimossi stili CSS obsoleti della classe `.ssr-version`

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
