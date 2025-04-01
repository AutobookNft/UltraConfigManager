# UConfig

UConfig è una libreria PHP moderna e flessibile per la gestione delle configurazioni, che permette di caricare e gestire configurazioni sia da file che da database in modo semplice ed efficiente.

## Caratteristiche Principali

- 🔄 Caricamento configurazioni da file PHP e database
- 🔒 Gestione sicura delle variabili d'ambiente
- 📝 Sistema di logging integrato
- 🛠 Facile integrazione con Laravel tramite Service Provider
- 🎯 Design Pattern Singleton per la connessione al database
- ⚡ Performance ottimizzate con caricamento lazy

## Requisiti

- PHP >= 8.1
- PDO Extension
- Composer

## Installazione

1. Installa il pacchetto tramite Composer:

``` bash
composer require ultra/ultra-config-manager

Pubblica le risorse di UCM (migrazioni, seeder, viste, ecc.):
bash

php artisan vendor:publish --tag=uconfig-resources

UCM dipende da Spatie Laravel Permission per la gestione dei permessi. Assicurati di aver installato e configurato Spatie:
bash

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"
php artisan migrate

Esegui le migrazioni di UCM per creare le tabelle specifiche (uconfig, uconfig_versions, uconfig_audit):
bash

php artisan migrate

Esegui il seeder per creare i ruoli e i permessi di UCM:
bash

php artisan db:seed --class=PermissionSeeder

#### **3.2. Correggere `UConfigServiceProvider.php`**

**Problema**:
- Uso di `FacadesUConfig` invece di `UConfig`.
- Import mancante per `UConfig`.

**Azione**:
- Correggiamo il riferimento a `FacadesUConfig` e aggiungiamo l’import.

**`src/Providers/UConfigServiceProvider.php` (attuale)**:

``` php


