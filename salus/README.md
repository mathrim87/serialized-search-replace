# Cartella Salus – Helper e aggiornamenti

Questa cartella contiene i file helper condivisi tra i plugin, tra cui il sistema di **aggiornamenti automatici** tramite Plugin Update Checker (PUC) e GitHub.

---

## Aggiornamenti automatici (PUC)

I plugin che usano questa struttura possono ricevere aggiornamenti direttamente dalla schermata **Plugin** di WordPress, come i plugin da WordPress.org. Il flusso si basa su:

- **Plugin Update Checker (PUC)**: libreria che controlla le Release su GitHub
- **GitHub Actions**: workflow che crea automaticamente una Release con tag e ZIP al push
- **Token GitHub**: necessario per accedere ai repository privati

---

## Come generare un token su GitHub

### Opzione A: Token classico (classic)

1. Vai su GitHub → **Settings** (profilo) → **Developer settings** → **Personal access tokens**
2. Clicca **Tokens (classic)** → **Generate new token (classic)**
3. **Note**: es. `WordPress plugin updates`
4. **Expiration**: 90 giorni, 1 anno, o No expiration
5. **Scopes**: seleziona **repo** (accesso completo ai repository privati)
6. Clicca **Generate token** e copia il token (es. `ghp_xxxxxxxxxxxx`)

### Opzione B: Token fine-grained (più sicuro)

1. Vai su GitHub → **Settings** → **Developer settings** → **Personal access tokens**
2. Clicca **Fine-grained tokens** → **Generate new token**
3. **Token name**: es. `WordPress plugin updates`
4. **Expiration**: 90 giorni o No expiration
5. **Repository access**: **Only select repositories** → seleziona i repo dei plugin
6. **Permissions** → **Repository permissions**:
   - **Contents**: **Read-only** (per leggere le Release)
7. Clicca **Generate token** e copia il token

---

## Configurazione in WordPress

Per i repository privati, aggiungi il token in `wp-config.php` (prima di `/* That's all, stop editing! */`):

```php
/** Token GitHub per aggiornamenti plugin privati (condiviso tra i plugin) */
define( 'GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
```

Sostituisci `ghp_xxx...` con il token reale. Il token è condiviso e può essere usato da tutti i plugin che usano PUC.

---

## Come funziona il flusso di aggiornamento

### 1. Sviluppo

Quando modifichi la versione nel file principale del plugin (es. `Version: 1.4.4`) e fai push su `main`:

1. **GitHub Actions** si attiva (workflow `auto-release.yml`)
2. Legge la versione dall’header `Version:` del file principale
3. Crea il tag `v1.4.4` (se non esiste)
4. Crea una **Release** su GitHub con quel tag
5. Genera lo ZIP del plugin (es. `custom-post-views-counter-1.4.4.zip`) e lo allega alla Release

### 2. Sul sito WordPress

1. **PUC** controlla periodicamente le Release su GitHub (circa ogni 12 ore)
2. Confronta la versione remota con quella installata
3. Se c’è una versione più recente, mostra **Aggiornamento disponibile** in Plugin
4. L’utente clicca **Aggiorna ora** → WordPress scarica lo ZIP dalla Release e aggiorna il plugin

### 3. Schema riassuntivo

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Sviluppatore  │     │   GitHub Actions │     │   WordPress     │
└────────┬────────┘     └────────┬─────────┘     └────────┬────────┘
         │                       │                        │
         │  Push su main         │                        │
         │  (Version bump)      │                        │
         │─────────────────────>│                        │
         │                       │  Crea Release          │
         │                       │  + ZIP allegato        │
         │                       │                        │
         │                       │<───────────────────────│
         │                       │  PUC controlla         │
         │                       │  Release (con token)  │
         │                       │                        │
         │                       │  Mostra "Aggiorna"    │
         │                       │  → Download ZIP       │
         │                       │  → Sostituisce file    │
```

---

## Disabilitare il controllo in sviluppo

Per disattivare il controllo aggiornamenti (es. in ambiente locale):

```php
define( 'CPVC_DISABLE_UPDATE_CHECK', true );
```

---

## Requisiti del workflow

- **Repository**: `mathrim87/.github` con workflow riutilizzabile `wp-plugin-auto-release.yml`
- **Variabile**: `ENABLE_AUTO_RELEASE = true` nelle impostazioni del repo (Settings → Actions → Variables)
- **Header**: il file principale del plugin deve avere `Version: X.Y.Z` nel formato corretto
