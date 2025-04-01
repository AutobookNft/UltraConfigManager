# Panoramica su UltraConfigManager (UCM)

**Data di creazione:** 1 aprile 2025  
**Autore:** Grok (xAI), in collaborazione con Fabio

## 1. Descrizione generale

UltraConfigManager (UCM) è un pacchetto Laravel che fa parte dell'ecosistema Ultra, progettato per fornire una gestione avanzata e sicura delle configurazioni in applicazioni Laravel. UCM consente di memorizzare, gestire e tracciare le configurazioni con funzionalità come:

- **Crittografia:** I valori delle configurazioni sono criptati nel database.
- **Versionamento:** Tiene traccia delle versioni storiche delle configurazioni.
- **Audit logging:** Registra tutte le modifiche (creazione, aggiornamento, eliminazione) per garantire la tracciabilità.
- **Caching sicuro:** Usa il sistema di caching di Laravel per migliorare le prestazioni.
- **Gestione dei permessi:** Integra Spatie Laravel Permission per controllare l'accesso alle configurazioni.

UCM è pensato per essere un modulo indipendente all'interno dell'ecosistema Ultra, ma può essere usato anche in applicazioni Laravel standalone.

## 2. Funzionalità principali

### Gestione delle configurazioni:
- Creazione, aggiornamento, eliminazione e visualizzazione di configurazioni (chiave-valore).
- Supporto per categorie (es. system, application) tramite un enum (CategoryEnum).

### Crittografia:
- I valori delle configurazioni sono criptati usando il sistema di crittografia di Laravel (Crypt) tramite un cast personalizzato (EncryptedCast).

### Versionamento:
- Ogni modifica a una configurazione crea una nuova versione, memorizzata nella tabella uconfig_versions.

### Audit:
- Ogni azione (creazione, aggiornamento, eliminazione) viene registrata nella tabella uconfig_audit.

### Caching:
- Le configurazioni sono caricate in memoria e memorizzate nella cache di Laravel per migliorare le prestazioni.

### Gestione dei permessi:
- Usa Spatie Laravel Permission per controllare l'accesso.
- Due ruoli principali: ConfigViewer (solo lettura) e ConfigManager (modifica completa).
- Permessi granulari: view-config, create-config, update-config, delete-config.

## 3. Dipendenze

UCM dipende dai seguenti pacchetti:
- **Laravel:** Richiede illuminate/database, illuminate/support, illuminate/auth, illuminate/routing, illuminate/cache (versione ^11.0).
- **Spatie Laravel Permission:** Per la gestione di ruoli e permessi (spatie/laravel-permission: ^6.10).
- **UltraLogManager (ULM):** Per il logging (ultra/ultra-log-manager: dev-main).
- **UltraErrorManager (UEM):** Per la gestione degli errori (ultra/ultra-error-manager: dev-main).
- **UltraTranslationManager (UTM):** Per le traduzioni (ultra/ultra-translation-manager: dev-main).

### Grafo delle dipendenze:
```
UCM → ULM, UTM, UEM
UEM → ULM, UTM
ULM (nessuna dipendenza)
UTM (nessuna dipendenza)
```

## 4. Struttura del codice

### Directory principali:
- **config/:** File di configurazione (uconfig.php, config_manager.php).
- **database/seeders/stubs/:** Contiene il seeder PermissionSeeder.php.stub per creare ruoli e permessi.
- **database/migrations/:** Contiene le migrazioni stub per le tabelle di UCM (create_uconfig_table.php.stub, create_uconfig_versions_table.php.stub, create_uconfig_audit_table.php.stub).
- **src/:** Codice sorgente del pacchetto.
  - **Casts/:** Contiene EncryptedCast.php per la crittografia dei valori.
  - **Constants/:** Contiene GlobalConstants.php con costanti globali (es. NO_USER).
  - **Dao/:** Contiene l'interfaccia ConfigDaoInterface.php e l'implementazione EloquentConfigDao.php per l'accesso ai dati.
  - **Enums/:** Contiene CategoryEnum.php per le categorie delle configurazioni.
  - **Facades/:** Contiene UConfig.php, il facade per accedere a UltraConfigManager.
  - **Http/Controllers/:** Contiene UltraConfigController.php per gestire le richieste HTTP.
  - **Http/Middleware/:** Contiene CheckConfigManagerRole.php per il controllo dei permessi.
  - **Models/:** Contiene i modelli Eloquent:
    - UltraConfigModel.php (tabella uconfig).
    - UltraConfigVersion.php (tabella uconfig_versions).
    - UltraConfigAudit.php (tabella uconfig_audit).
    - User.php (modello utente con supporto per Spatie).
  - **Providers/:** Contiene UConfigServiceProvider.php per registrare il servizio e pubblicare le risorse.
  - **Services/:** Contiene VersionManager.php per gestire il versionamento.
- **routes/:** Contiene web.php con le rotte di UCM.

### Classi principali:
- **UltraConfigManager:** Classe principale per gestire le configurazioni (caricamento, salvataggio, versionamento, audit, caching).
- **EloquentConfigDao:** Implementazione del DAO per l'accesso al database tramite Eloquent.
- **CheckConfigManagerRole:** Middleware per controllare i permessi delle richieste.
- **UltraConfigController:** Controller per gestire le richieste HTTP (visualizzazione, creazione, aggiornamento, eliminazione).

### Tabelle del database:
- **Tabelle specifiche di UCM:**
  - uconfig: Configurazioni principali (chiave, valore, categoria, ecc.).
  - uconfig_versions: Versioni storiche delle configurazioni.
  - uconfig_audit: Log di audit per le modifiche.
- **Tabelle condivise:**
  - users: Usata per l'autenticazione e l'associazione degli audit.
  - Tabelle di Spatie (roles, permissions, role_has_permissions, model_has_roles, model_has_permissions): Per la gestione dei permessi.

## 5. Installazione e configurazione

1. Installa il pacchetto:
```bash
composer require ultra/ultra-config-manager
```

2. Pubblica le risorse (migrazioni, seeder, viste, ecc.):
```bash
php artisan vendor:publish --tag=uconfig-resources
```

3. Pubblica le migrazioni di Spatie (dipendenza):
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
```

4. Esegui le migrazioni per creare le tabelle:
```bash
php artisan migrate
```

5. Esegui il seeder per creare i ruoli e i permessi:
```bash
php artisan db:seed --class=PermissionSeeder
```

## 6. Utilizzo

### Accedere alle configurazioni:
```php
use Ultra\UltraConfigManager\Facades\UConfig;

$value = UConfig::get('my_key', 'default_value');
```

### Modificare una configurazione:
```php
UConfig::set('my_key', 'new_value', 'system');
```

### Rotte HTTP:
- `/uconfig`: Visualizza tutte le configurazioni (richiede view-config).
- `/uconfig/create`: Mostra il form di creazione (richiede create-config).
- `/uconfig/{id}/edit`: Mostra il form di modifica (richiede view-config).
- `/uconfig/{id}/audit`: Mostra i log di audit (richiede view-config).

## 7. Note

- UCM è progettato per essere modulare e indipendente, ma fa parte dell'ecosistema Ultra, quindi interagisce con altri pacchetti come ULM, UEM, e UTM.
- Le traduzioni non sono ancora implementate (in attesa di ulteriori modifiche).
- Il pacchetto è in fase di sviluppo, quindi alcune funzionalità (es. traduzioni, gestione avanzata dei permessi) potrebbero essere aggiunte in futuro.