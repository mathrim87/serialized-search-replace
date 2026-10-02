# Serialized Search & Replace

Plugin WordPress per cercare e sostituire testo all'interno di dati **serializzati** in tabelle `*meta` e `*options`, con anteprima prima della scrittura.

**Versione attuale:** 1.1.8

## Caratteristiche

- Ricerca e sostituzione su dati serializzati (array PHP in `postmeta`, `usermeta`, `termmeta`, `options`, ecc.)
- Supporto multi-tabella con rilevamento automatico della struttura
- Filtro opzionale per `meta_key`
- Esempi regex integrati nell'interfaccia admin
- Anteprima dei record trovati prima di applicare le modifiche
- Modalità regex PCRE o ricerca letterale
- Report delle sostituzioni effettuate
- Aggiornamenti da GitHub Release (Plugin Update Checker)

## Requisiti

- WordPress 5.0+
- PHP 7.4+ (consigliato 8.x)
- Permessi `manage_options` (solo amministratori)

## Struttura del plugin

```
serialized-search-replace/
├── serialized-search-replace.php   # Bootstrap e logica principale
├── assets/
│   ├── mitoff-ssr-admin.js         # Interfaccia admin (AJAX)
│   └── mitoff-ssr-admin.css        # Stili admin
├── salus/
│   ├── salus-admin-menu.php        # Menu condiviso Salus
│   ├── class-ssr-update-checker.php
│   ├── salus-puc-manual-check.php
│   └── plugin-update-checker/      # Libreria PUC (aggiornamenti GitHub)
├── CHANGELOG.md
└── README.md
```

## Installazione

### Da Release GitHub (consigliato)

1. Scarica lo ZIP dalla [Release](https://github.com/mathrim87/serialized-search-replace/releases) più recente
2. In **Plugin → Aggiungi nuovo → Carica plugin**, installa e attiva
3. Gli aggiornamenti successivi compaiono in **Plugin** se il sito può raggiungere GitHub

### Copia manuale

1. Copia la cartella `serialized-search-replace` in `wp-content/plugins/`
2. Attiva il plugin da **Plugin → Plugin installati**

## Utilizzo

1. Vai in **Salus → Search & Replace** (il menu **Salus** viene creato automaticamente se assente)
2. Scegli un esempio dalla sezione integrata (opzionale)
3. Seleziona la **tabella** database (default: `postmeta`)
4. Seleziona una **meta_key** (obbligatoria). Sulle tabelle options seleziona una **option_name**
5. Inserisci il **pattern di ricerca** (testo o regex, senza delimitatori `/`)
6. Inserisci il **testo sostitutivo** (può essere vuoto)
7. Clicca **Cerca** e verifica l'anteprima
8. Conferma con **Procedi con la sostituzione** solo dopo aver controllato i risultati

## Esempi di pattern

| Caso | Cerca | Sostituisci |
|------|-------|-------------|
| Tag `<br />` malformati | `(?<!<)br /(?!>)` | `<br />` |
| HTTP → HTTPS | `http://(?!.*https://)` | `https://` |
| Sostituzione dominio | `vecchio-dominio.com` | `nuovo-dominio.com` |
| Rimuovere `style` inline | `style="[^"]*"` | _(vuoto)_ |

## Sicurezza

Il plugin è pensato solo per admin con `manage_options`:

- Nonce CSRF su tutte le richieste AJAX
- Whitelist tabelle (`*meta`, `*options`) con verifica su `information_schema`
- Query SQL con `$wpdb->prepare()` e `$wpdb->esc_like()`
- Deserializzazione con `allowed_classes => false` (nessuna istanziazione di oggetti PHP)
- Filtro obbligatorio su `meta_key` o `option_name`, così la query usa l'indice e non scorre l'intera tabella
- Valori oltre 512 KB esclusi prima di `unserialize`; walk ricorsivo limitato a 32 livelli
- Righe serializzate come oggetti (`O:`) escluse dalla query
- Validazione regex: sintassi, rifiuto dei quantificatori annidati, probe anti-ReDoS
- Limiti PCRE (`backtrack_limit`, `recursion_limit`) verificati via `ini_set`; se non applicabili la modalità regex è disattivata
- Testo oltre 100 KB o superamento del backtrack restituiti come errore, non come «zero risultati»
- Elaborazione batch lato server (200 righe per richiesta AJAX)

> **Nota:** è uno strumento di manutenzione database. Un admin compromesso o un uso improprio possono danneggiare i dati del sito. Backup obbligatorio.

## Limitazioni note

- Opera solo su valori **serializzati** come array o stringa (prefissi `a:` e `s:`). Le righe oggetto (`O:`) e i valori oltre 512 KB sono esclusi
- `meta_key` o `option_name` è obbligatoria: non è possibile cercare su tutta la tabella
- Un pattern con quantificatori annidati, come `(a+)+`, viene rifiutato
- La paginazione batch è attiva lato server; l'interfaccia JS elabora ancora una richiesta per operazione (estensione multi-batch in roadmap)

## Risoluzione problemi

**Il plugin non compare nel menu**  
Verifica permessi amministratore, plugin attivo e assenza di errori PHP nei log.

**Nessun risultato con regex complessa**  
Verifica la chiave selezionata e semplifica il pattern.

**Errore «Seleziona una meta_key»**  
La scansione parte solo dopo aver scelto una meta_key o, sulle options, una option_name.

**Errore su quantificatori annidati o limite PCRE**  
Il pattern è stato rifiutato come possibile ReDoS. Usa un pattern più semplice oppure la ricerca letterale.

**Valori oltre 512 KB ignorati**  
Quei record restano invariati. Vanno corretti con un altro strumento se superano il tetto.

**Regex non applicata**  
Controlla che «Usa espressione regolare» sia attivo e che il pattern **non** includa i delimitatori `/`.

## Changelog

Vedi [CHANGELOG.md](CHANGELOG.md).

## Casi d'uso

- Migrazione dominio o passaggio HTTP → HTTPS in meta serializzate
- Correzione HTML malformato in campi builder / ACF / WooCommerce
- Pulizia attributi inline o testo duplicato in opzioni serializzate

## Licenza e supporto

Codice distribuito via GitHub ([mathrim87/serialized-search-replace](https://github.com/mathrim87/serialized-search-replace)).  
Per segnalazioni e richieste, apri una issue sul repository.

---

**Fai sempre un backup completo del database prima di eseguire sostituzioni.**
