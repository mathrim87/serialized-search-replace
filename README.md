# Serialized Search & Replace

Plugin WordPress per cercare e sostituire testo all'interno di dati **serializzati** in tabelle `*meta` e `*options`, con anteprima prima della scrittura.

**Versione attuale:** 1.1.7

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
4. Filtra per **meta_key** se la tabella lo supporta (consigliato con pattern regex complessi)
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
- Validazione sintassi regex e limiti PCRE (`backtrack_limit`, `recursion_limit`)
- Blocco scan SQL troppo ampia: pattern regex generici richiedono una `meta_key` o un filtro LIKE utilizzabile
- Elaborazione batch lato server (200 righe per richiesta AJAX) per limitare il carico su tabelle grandi

> **Nota:** è uno strumento di manutenzione database. Un admin compromesso o un uso improprio possono danneggiare i dati del sito. Backup obbligatorio.

## Limitazioni note

- Opera solo su valori **serializzati** (prefissi `a:`, `s:`, `O:` nel campo); stringhe plain non serializzate non vengono processate
- I dati oggetto (`O:...`) vengono ignorati in lettura per sicurezza
- Con pattern regex molto astratti, seleziona una `meta_key` per restringere la scansione SQL
- La paginazione batch è attiva lato server; l'interfaccia JS elabora ancora una richiesta per operazione (estensione multi-batch in roadmap)

## Risoluzione problemi

**Il plugin non compare nel menu**  
Verifica permessi amministratore, plugin attivo e assenza di errori PHP nei log.

**Nessun risultato con regex complessa**  
Prova a selezionare una `meta_key` specifica o semplifica il pattern.

**Errore «Pattern regex troppo generico»**  
Il filtro SQL non può restringere la ricerca: aggiungi una `meta_key` o usa un pattern con testo letterale riconoscibile.

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
