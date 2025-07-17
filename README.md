# Serialized Search & Replace Plugin

Plugin WordPress avanzato per cercare e sostituire testo in dati serializzati su diverse tabelle del database in modo sicuro.

## 🚀 Caratteristiche

- ✅ **Ricerca sicura** in dati serializzati
- ✅ **Supporto multi-tabella** (postmeta, usermeta, termmeta, options, ecc.)
- ✅ **Esempi pratici integrati** con pattern regex comuni
- ✅ **Anteprima dettagliata** prima della sostituzione
- ✅ **Supporto espressioni regolari** avanzate (PCRE)
- ✅ **Report dinamico** delle modifiche per tabella
- ✅ **Interfaccia moderna** e user-friendly
- ✅ **Gestione sicura** dei dati (nonce, sanitizzazione, validazione)
- ✅ **Rilevamento automatico** struttura tabelle

## 📁 Struttura Plugin

```
serialized-search-replace/
├── serialized-search-replace.php  # File principale del plugin
├── admin.js                       # JavaScript per l'interfaccia
├── admin.css                      # Stili CSS
└── README.md                      # Questo file
```

## 🛠️ Installazione

### Metodo 1: Copia dei file

1. Crea una cartella chiamata `serialized-search-replace` in `/wp-content/plugins/`
2. Copia tutti i file del plugin nella cartella:
   - `serialized-search-replace.php`
   - `admin.js`
   - `admin.css`
3. Vai in **WordPress Admin > Plugin > Plugin installati**
4. Attiva il plugin "**Serialized Search & Replace**"

### Metodo 2: ZIP Upload

1. Crea un file ZIP con tutti i file del plugin
2. Vai in **WordPress Admin > Plugin > Aggiungi nuovo**
3. Clicca "**Carica plugin**" e seleziona il file ZIP
4. Installa e attiva il plugin

## 📖 Come usare

1. Vai in **WordPress Admin > Strumenti > Search & Replace**
2. **Scegli un esempio** dalla sezione "Esempi di utilizzo" (opzionale)
3. **Seleziona la tabella** database dal dropdown (default: `postmeta`)
4. Inserisci il **pattern di ricerca** (testo o regex)
5. Inserisci il **testo sostitutivo** (può essere vuoto per rimuovere)
6. **Abilita/disabilita** la modalità regex (default: abilitata)
7. Clicca **"🔍 Cerca"** per vedere l'anteprima
8. **Verifica attentamente** i risultati nella tabella dinamica
9. Se i risultati sono corretti, clicca **"🔄 Procedi con la sostituzione"**
10. **Controlla il report finale** delle modifiche effettuate

## 🔍 Esempi integrati nel plugin

### 🔧 Riparare tag BR malformati
- **Pattern:** `(?<!<)br /(?!>)`
- **Sostituisci:** `<br />`
- **Descrizione:** Trova "br /" che non è già formattato come `<br />`

### 🔗 Aggiornare URL HTTP a HTTPS  
- **Pattern:** `http://(?!.*https://)`
- **Sostituisci:** `https://`
- **Descrizione:** Converte URL HTTP in HTTPS evitando duplicazioni

### 📝 Sostituire testo semplice
- **Pattern:** `vecchio-dominio.com`
- **Sostituisci:** `nuovo-dominio.com`
- **Descrizione:** Sostituzione diretta senza regex

### 🎨 Rimuovere attributi style inline
- **Pattern:** `style="[^"]*"`
- **Sostituisci:** _(vuoto)_
- **Descrizione:** Rimuove tutti gli attributi style inline

## ⚠️ Avvertenze importanti

1. **BACKUP OBBLIGATORIO**: Fai sempre un backup completo del database prima di usare il plugin!
2. **Test su staging**: Testa prima su un ambiente di sviluppo
3. **Dati serializzati**: Il plugin funziona su dati serializzati in tabelle `*meta` e `*options`
4. **Amministratori**: Solo gli utenti con permessi `manage_options` possono usare il plugin
5. **Validazione tabelle**: Il plugin accetta solo tabelle sicure (meta/options pattern)

## 🔧 Funzionalità tecniche

### Sicurezza Avanzata
- ✅ Verifica dei permessi utente (`manage_options`)
- ✅ Nonce per prevenire CSRF
- ✅ Sanitizzazione rigorosa degli input
- ✅ Prepared statements per le query
- ✅ Validazione whitelist delle tabelle
- ✅ Controllo esistenza tabelle via `information_schema`

### Gestione Multi-Tabella
- ✅ Rilevamento automatico struttura tabelle
- ✅ Supporto pattern per tabelle `*meta` e `*options`
- ✅ Mappatura dinamica campi (primary key, serialized field, display fields)
- ✅ Query builder dinamico per diverse strutture
- ✅ Fallback intelligente per tabelle sconosciute

### Gestione Dati Serializzati
- ✅ Deserializzazione sicura dei dati
- ✅ Processamento ricorsivo di array multidimensionali
- ✅ Riserializzazione corretta (mantiene l'integrità dei dati)
- ✅ Conteggio accurato delle occorrenze
- ✅ Supporto regex PCRE avanzate

### Interfaccia Utente Moderna
- ✅ Grid responsive per esempi
- ✅ Tabelle dinamiche con header adattivi
- ✅ Feedback in tempo reale
- ✅ Loading indicators con animazioni
- ✅ Conferme di sicurezza
- ✅ Report dettagliati dinamici
- ✅ Auto-popolazione campi da esempi
- ✅ Scroll automatico alle sezioni

## 🐛 Risoluzione problemi

### Il plugin non appare nel menu
- Verifica che l'utente abbia permessi di amministratore
- Controlla che il plugin sia attivato
- Verifica la presenza di errori PHP nei log

### Errori durante la ricerca
- Controlla la connessione al database
- Verifica che la tabella `postmeta` esista
- Controlla i log di errore di WordPress

### Regex non funziona
- Verifica che la checkbox "Usa espressione regolare" sia selezionata
- Testa il pattern regex in un tool online prima
- Ricorda di NON includere i delimitatori `/` nel pattern

## 📝 Changelog

### Versione 2.0 (Attuale)
- ✨ **Supporto multi-tabella** (postmeta, usermeta, termmeta, options)
- ✨ **Esempi pratici integrati** con pulsanti "Usa questo esempio"
- ✨ **Rilevamento automatico** struttura tabelle
- ✨ **Interface dinamica** con tabelle responsive
- ✨ **Sicurezza migliorata** con validazione whitelist
- ✨ **Regex abilitato di default** per facilità d'uso

### Versione 1.0
- Prima release
- Ricerca e sostituzione in dati serializzati
- Supporto espressioni regolari
- Interfaccia admin base
- Report dettagliati

## 🎯 Casi d'uso comuni

### E-commerce (WooCommerce)
- Aggiornare URL immagini prodotti
- Correggere testi di prodotto malformattati
- Migrare da HTTP a HTTPS

### Migrazione siti
- Cambiare domini nei link interni
- Aggiornare percorsi file/media
- Correggere URL di sviluppo in produzione

### Pulizia codice
- Rimuovere CSS inline
- Correggere tag HTML malformati
- Standardizzare formattazione

## 🤝 Supporto

Per problemi o domande, contatta lo sviluppatore del plugin.

---

**⚠️ RICORDA: Fai sempre un backup del database prima di usare questo plugin!** 