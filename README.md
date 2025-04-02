
# UltraConfigManager

## 📖 What is it?

**UltraConfigManager (UCM)** is a versioned, auditable and secure configuration system for Laravel.  
It treats configuration not as passive .env or static files — but as **live, vital data** that must be validated, tracked and protected.

Designed for high-responsibility systems where a misconfigured setting might have real-world consequences.

---

## 🎯 Objectives

- Protect critical configuration data from silent overrides
- Provide versioning, rollback and full audit trail
- Allow flexible role/permission control (Spatie or fallback)
- Avoid hardcoded values — categories, defaults, roles are centralized
- Improve developer awareness and traceability

---

## 🧠 Architecture

- `UltraConfigManager`: the service logic
- `ConfigDaoInterface`: contract for persistence
- `EloquentConfigDao`: implementation based on Eloquent ORM
- `UltraConfigController`: UI + CRUD + audit integration
- `CategoryEnum`: classification and translation
- `GlobalConstants`: central point for shared values
- `CheckConfigManagerRole`: middleware (Spatie-aware or fallback)

---

## ⚙️ How It Works

1. Configs are created via controller or facade (`UConfig::set(...)`)
2. Each update triggers:
   - a new version saved (`UltraConfigVersion`)
   - an audit log (`UltraConfigAudit`)
3. You can retrieve by key or ID
4. Enum-based category validation and translation

---

## 🔐 Permissions

- If `config('uconfig.use_spatie_permissions') === true`, it uses `hasPermissionTo()`
- Else, it checks a fallback `role` field on user
- Middleware: `uconfig.check_role:view-config` or `create-config`, etc.

---

## 🚀 Installation

```bash
composer require ultra/ultra-config-manager
php artisan vendor:publish --tag=uconfig-resources
```

If aliases are not auto-discovered, add to `config/app.php`:

```php
'UConfig' => UltraProject\UConfig\Facades\UConfig::class,
```

---

## ⚙️ Configuration File (`config/uconfig.php`)

- `use_spatie_permissions`: whether to use Spatie's permission system

---

## 📦 Resource Publishing

When you run:

```bash
php artisan vendor:publish --tag=uconfig-resources
```

You publish:

- Migrations:
  - `create_uconfig_table`
  - `create_uconfig_versions_table`
  - `create_uconfig_audit_table`
- Views to `resources/views/vendor/uconfig`
- Translations
- `uconfig.php` config file
- Optional: `aliases.php` to bootstrap folder

## 🔁 Route Autoloading in Laravel 11+

In Laravel 11, custom route files like `routes/uconfig.php` are **not loaded automatically** unless explicitly declared in `bootstrap/app.php`.

**UltraConfigManager handles this internally**.  
Once the file is published, it is automatically loaded thanks to the following logic inside the service provider:

```php
$this->app->booted(function () {
    $router = $this->app->make(\Illuminate\Routing\Router::class);
    if (file_exists(base_path('routes/uconfig.php'))) {
        $router->group([], base_path('routes/uconfig.php'));
    }
});
```

✅ No need to manually touch `bootstrap/app.php`  
✅ The file is optional and safely ignored if not present  
✅ Can be edited after publishing to customize route behavior

---

## 🧪 Testing & Error Simulation

Simulate test conditions in development via:

```php
TestingConditions::enable('UCM_NOT_FOUND');
TestingConditions::enable('UCM_DUPLICATE_KEY');
```

All DAO methods return handled responses using `UltraError::handle(...)`.

---

## 🌍 Translation

Category labels use enum cases with a fallback for `None`.

```php
CategoryEnum::translatedOptions();
// => ['system' => 'System', ...]
```

Translations are under: `resources/lang/vendor/uconfig`.

---

## ⛳ Credits & Philosophy

This package was born from the idea that **configuration is not metadata — it is operational infrastructure**.  
Inspired by real-world cases where misconfigured systems led to physical failures.

Generated on: 2025-04-01
