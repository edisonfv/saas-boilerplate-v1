# Gestión de planes, módulos y permisos (multi-tenant)

Este documento define cómo se comercializan y autorizan las capacidades del SaaS: qué vive en el dominio **central**, qué vive en cada **tenant**, y cómo se combinan planes, módulos, features, límites y permisos.

Estado: **diseño**, aún no implementado. Sirve de referencia para las migraciones/modelos que se creen después.

## Principio rector

Separar cuatro preguntas distintas, porque cada una cambia por razones distintas y a velocidades distintas:

1. **¿Qué vendemos?** → catálogo central (plan, módulo, feature, límite, addon).
2. **¿Qué contrató este tenant?** → estado comercial del tenant (subscription, addons, entitlements efectivos).
3. **¿Qué puede hacer este usuario dentro de lo contratado?** → ACL del tenant (roles y permisos, propiedad exclusiva del tenant).
4. **¿Quién administra la plataforma misma (planes, módulos, tenants, facturación)?** → ACL central (roles y permisos del personal de la plataforma, totalmente independiente del punto 3).

Que un plan incluya un permiso **no** significa que todos los usuarios del tenant lo tengan. El plan habilita la capacidad; el rol del usuario decide si la ejerce.

**Los permisos centrales y los permisos de cada tenant son sistemas independientes que no se mezclan.** Un permiso central (ej. `central.tenants.impersonate`) no dice nada sobre lo que puede hacer un usuario dentro de un tenant, y viceversa. Comparten base de datos (la central), pero no tablas, no guard, y no semántica.

## 1. Catálogo central

Vive únicamente en la base de datos central. Es el "menú" de lo que se puede vender.

```
modules        (id, slug, name, is_active, sellable_as_addon)
features       (id, module_id nullable, slug, name, is_active)
limit_types    (id, key, name, unit)
module_permissions (id, module_id, slug)      -- catálogo/metadata, NO la tabla real de Spatie del ACL central

plans          (id, slug, name, is_active, trial_days)
plan_prices    (id, plan_id, billing_period, price, currency, is_active)
plan_module    (plan_id, module_id)
plan_feature   (plan_id, feature_id)
plan_limit     (plan_id, limit_type_id, value)

module_prices  (id, module_id, billing_period, price, currency, is_active)   -- solo si sellable_as_addon = true
```

Notas:

- **`features.module_id` es nullable**: una feature puede ser transversal (`api_access`, no pertenece a ningún módulo) o específica de un módulo (`crm.advanced_reports`).
- **`limit_types` es un catálogo, no columnas fijas** (`users`, `storage_gb` como campos de `plans`). Un límite nuevo (ej. `api_calls_per_month`) es una fila + seed, no una migración.
- **`module_permissions` (catálogo) es solo blueprint**, no autorización real: cada módulo declara qué permisos ofrece; las filas reales de autorización (Spatie) viven en la BD de cada tenant, y este catálogo es la fuente para sembrarlas. Dado que central y tenant son bases de datos físicamente separadas (despliegue por BD individual), no hay colisión entre este catálogo y las tablas de un tenant. La única colisión real era **dentro de la propia BD central**, entre este catálogo y la tabla `permissions` real que usa el ACL central (sección 5) — se resuelve renombrando **nuestro** catálogo (`module_permissions`, no `permissions`), dejando `spatie/laravel-permission` con sus nombres de tabla 100% por defecto. Preferible a personalizar la config de Spatie: usamos la librería tal cual viene, y adaptamos nuestro propio nombre.
- **`plans.trial_days`**: días de prueba por defecto al contratar el plan (`null` = sin trial). Se copia a `subscriptions.trial_ends_at` al momento de contratar, no se recalcula después (si cambia el plan, no afecta trials ya otorgados).
- **`plan_prices` separa el precio de la definición del plan**: un plan puede tener precio mensual, trimestral y anual simultáneamente. `billing_period` es un enum cerrado (`Monthly`, `Quarterly`, `Annual`) → `spatie/laravel-enum`.
- **`modules.sellable_as_addon`**: marca que el módulo, además de venir empaquetado en planes vía `plan_module`, puede contratarse suelto por un tenant que no lo tiene en su plan. Si es `true`, necesita fila(s) en `module_prices`.

### Ejemplo

```
Plan: Starter
- trial_days: 14
- Prices: monthly=$29, annual=$290 (2 meses gratis)
- Modules: CRM, Projects
- Features: basic_reports
- Limits: users=5, storage_gb=10

Plan: Pro
- trial_days: 14
- Prices: monthly=$79, annual=$790
- Modules: CRM, Projects, Inventory, Reports
- Features: api_access, advanced_reports, exports
- Limits: users=50, storage_gb=100

Módulo: Inventory
- sellable_as_addon: true
- Prices: monthly=$15, annual=$150
  -> un tenant en plan Starter puede agregarlo sin cambiar de plan
```

```
CRM declara:
- tenant.crm.contacts.view
- tenant.crm.contacts.create
- tenant.crm.contacts.update
- tenant.crm.contacts.delete
- tenant.crm.pipeline.manage
```

### Cómo se genera el catálogo `module_permissions` (enums + seeder, sin escribirlo a mano)

En vez de hardcodear cada fila del catálogo, se genera con dos enums de código + un seeder que recorre los módulos registrados:

- **`Action`** (enum cerrado: `Create`, `View`, `Update`, `Delete`, `Manage`, `Export`...) → `spatie/laravel-enum`, porque es un conjunto que solo cambia por decisión de código.
- **`SpecialPermission`** (enum cerrado: permisos que no siguen el patrón `módulo.acción`, ej. `crm.pipeline.manage`) → también `spatie/laravel-enum`.
- **El módulo NO se mete en un enum.** El módulo ya tiene una fuente de verdad: el catálogo `modules` (sección 1) / el registro de nwidart. Meterlo también en un enum crearía un tercer lugar donde vive "qué módulos existen" (carpeta nwidart + tabla `modules` + enum), y con la cantidad de módulos que se planea tener a futuro eso desincroniza fácil. El seeder itera los módulos ya registrados, no un enum paralelo.
- **Sin cross product ciego** (`todos los módulos × todas las acciones`): no todos los módulos necesitan las mismas acciones (ej. `reports` no se borra, `crm` tiene `pipeline.manage` que no aplica a nadie más). Cada módulo declara **qué subconjunto** de `Action` usa, más sus `SpecialPermission` propios, en una clase que vive junto al módulo:

```php
// Modules/Empresas/app/Permissions/EmpresasPermissions.php
final class EmpresasPermissions
{
    public static function actions(): array
    {
        return [Action::Create, Action::View, Action::Update]; // sin Delete, por decisión de negocio
    }

    public static function special(): array
    {
        return [];
    }
}
```

El seeder central recorre los módulos registrados, llama a `actions()`/`special()` de cada uno y arma el slug `{modulo}.{accion}` (más los especiales) — determinístico, sin typos, sin permisos que nadie va a usar, y la declaración vive junto al módulo (coherente con nwidart, donde cada módulo es autocontenido).

## 2. Addons = módulos existentes marcados como vendibles sueltos

No hace falta una entidad `addon` genérica ni una tabla polimórfica: un addon **es** un `module` ya existente con `sellable_as_addon = true` y su propio precio en `module_prices` (sección 1). Así, cualquier módulo del catálogo puede venderse de dos formas sin duplicar nada:

- **Empaquetado**: incluido en un plan vía `plan_module`.
- **Suelto**: contratado individualmente por un tenant que no lo tiene en su plan, vía `module_prices` + una fila en `subscription_modules` con `source = addon` (sección 3).

```
Plan Starter incluye: CRM, Projects
Tenant contrata además el módulo Inventory como addon (sellable_as_addon = true)
  -> subscription_modules: (CRM, source=plan), (Projects, source=plan), (Inventory, source=addon)
```

Esto evita crear un plan nuevo por cada combinación comercial ("Starter + Inventory") — es simplemente otra fila en `subscription_modules`.

### Fuera de alcance por ahora: incrementos de límite

Un upsell tipo "25 usuarios extra" no es un módulo, así que no encaja en `sellable_as_addon`. Si se necesita más adelante, es una tabla aparte y simple, no una extensión del modelo de addons:

```
subscription_limit_overrides (subscription_id, limit_type_id, extra_value, starts_at, ends_at)
```

No se incluye en esta iteración salvo que se confirme que hace falta ya.

## 3. Estado comercial del tenant (central)

```
subscriptions        (id, tenant_id, plan_id, billing_period, status, trial_ends_at,
                       current_period_start, current_period_end, cancel_at_period_end)

subscription_changes (id, subscription_id, from_plan_id, to_plan_id, type, effective_at, applied_at, status)

subscription_modules (id, subscription_id, module_id, source, starts_at, ends_at)
```

- **`subscriptions` = estado actual** del tenant (una fila viva por tenant, no un histórico). `status` es un enum cerrado (`Trialing`, `Active`, `PastDue`, `Cancelled`, `Expired`) → `spatie/laravel-enum`. `billing_period` es el que el tenant eligió al contratar (`Monthly`/`Quarterly`/`Annual`), usado para resolver el precio en `plan_prices`.
- **`trial_ends_at`** se calcula una sola vez al crear la suscripción (`now()->addDays($plan->trial_days)`) y queda fijo; cambiar `plans.trial_days` después no afecta trials ya otorgados.
- **`subscription_changes` = histórico + cambios programados** (upgrades/downgrades). Regla de negocio: un **upgrade** aplica de inmediato (`effective_at = now()`); un **downgrade** se agenda para el fin del periodo actual (`effective_at = current_period_end`), para no penalizar lo ya pagado. Un job periódico (`ApplyScheduledSubscriptionChanges`) aplica los cambios cuyo `effective_at` ya pasó: actualiza `subscriptions.plan_id`, resincroniza `subscription_modules` y marca `applied_at`.
- **`subscription_modules` reemplaza el cálculo implícito** "módulos habilitados = `plan_module` del plan actual". Ahora es explícito y con procedencia (`source`: `plan` o `addon`), lo que permite mezclar módulos del plan con módulos comprados como addon (sección 2), cada uno con su propia vigencia. Al cambiar de plan o (des)activar un addon, un listener resincroniza esta tabla: agrega filas `source=plan` para los módulos del nuevo plan, cierra (`ends_at`) las que ya no aplican, y no toca las `source=addon` salvo que el addon mismo se cancele.

### Entitlements efectivos (derivados, no editados a mano)

```
tenant_entitlements (cacheado, invalidado por evento SubscriptionUpdated | SubscriptionModuleChanged)
  enabled_modules: union(subscription_modules activos, independiente del origen)
  enabled_features: plan_feature del plan actual
  effective_limits: plan_limit del plan actual
```

Se recalculan a partir de `subscriptions` + `subscription_modules` mediante un listener cuando cambian; se cachean en Redis con key prefijada por tenant (evitar fugas de cache entre tenants). Nunca son la fuente de verdad, son una proyección — así no se recalcula la matriz completa en cada request.

## 4. ACL del tenant (base de datos del tenant)

- Tablas de `spatie/laravel-permission` (`roles`, `permissions`, `model_has_roles`, `role_has_permissions`) viven en la BD de **cada tenant**, no en central — sin personalizar nombres, tal como se usa en central.
- `App\Models\User` (con su tabla `users`) **es el usuario de un tenant**, no de central — su migración vive en `database/migrations/tenant/`. El personal de la plataforma usa `App\Models\CentralUser` (sección 5), completamente aparte.
- Cuando se activa un módulo para un tenant (alta, upgrade o addon), un listener siembra en su BD los permisos correspondientes desde el catálogo central `permissions` — detalle completo del pipeline en la sección 10.
- En un downgrade **no se borran permisos**: solo se desactiva el módulo/feature a nivel de entitlement. Si el tenant vuelve a subir de plan, los roles ya asignados siguen íntegros.
- Los roles y su composición de permisos son responsabilidad exclusiva de cada tenant (ej. `tenant.admin` recibe `tenant.crm.contacts.delete`, `tenant.member` no).

## 5. ACL central (personal de la plataforma)

Aparte de todo lo anterior, la plataforma misma necesita autorización: alguien tiene que poder crear planes, activar/desactivar módulos del catálogo, ver o impersonar tenants, gestionar precios, etc. Esto **no** son tenant users y **no** deben mezclarse con el catálogo `module_permissions` (blueprint) de la sección 1.

- El dominio central corre su propia instancia de `spatie/laravel-permission`, **con sus tablas y config 100% por defecto** (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`) — no se personaliza la librería; el catálogo de la sección 1 es el que se llama distinto (`module_permissions`) precisamente para dejar el nombre `permissions` libre para Spatie.
- Usa un guard propio (`central`, ver `App\Models\CentralUser`) para que no haya ambigüedad sobre a qué modelo/autenticación pertenece cada permiso. Spatie resuelve el guard automáticamente mirando qué guard de `config/auth.php` tiene como `provider.model` la clase del usuario — no hace falta configuración adicional para esto.
- Requiere decidir un modelo autenticable propio para el personal de la plataforma (ej. `CentralUser`/`Admin`), distinto del `User` que se autentica dentro de un tenant. Es una decisión pendiente para la fase de implementación, no de este documento.
- Ejemplos de permisos centrales: `central.plans.manage`, `central.modules.manage`, `central.tenants.view`, `central.tenants.impersonate`, `central.subscriptions.manage`, `central.billing.view`.
- Ejemplos de roles centrales: `super-admin` (todo), `support` (ver tenants, impersonar), `billing` (planes/precios/suscripciones), `sales` (alta de tenants/trials).

**Regla de independencia:** ningún permiso central otorga capacidades dentro de un tenant, y ningún permiso de un tenant otorga capacidades sobre la plataforma. Un `super-admin` central que necesite actuar "dentro" de un tenant (soporte, debugging) lo hace vía impersonación explícita (`central.tenants.impersonate`), no porque el ACL central y el del tenant compartan tabla.

## 6. Autorización en runtime: doble gate

```php
EnsureTenantHasModule:crm            // o EnsureTenantHasFeature:crm.advanced_reports
can:tenant.crm.contacts.create
```

1. **Entitlement** (¿el tenant tiene acceso a esta capacidad por su plan/addons?) — barato, cacheado, se resuelve contra `tenant_entitlements`.
2. **ACL** (¿el usuario tiene permiso para ejecutar esta acción?) — Spatie normal, sin cambios respecto al patrón habitual de Laravel.

Los **limits** son una tercera dimensión, distinta de autorización: se validan en el punto de acción (ej. `CreateUserAction` verifica `effective_limits.users` contra el conteo actual de usuarios del tenant), típicamente con un middleware/regla dedicada (`EnsureLimitNotExceeded:users`) en los endpoints de creación, no como un permiso booleano.

## 7. Enums vs catálogo dinámico

No todo debe forzarse a `spatie/laravel-enum`:

- **Sí usar enum** (conjunto cerrado, definido en código): `SubscriptionStatus`, `AddonGrantType`, `BillingPeriod`, `Action` y `SpecialPermission` (ver sección 1 — son el vocabulario de acciones/permisos especiales, no cambian sin tocar código).
- **No usar enum** (catálogo dinámico en BD, un admin puede crear filas nuevas sin deploy, o cuya fuente de verdad ya es otra): `module.slug`, `feature.slug`, `limit_type.key`. Estos se validan con reglas tipo `exists:modules,slug`, no con un enum cerrado en código. En particular, **no dupliques el módulo en un enum** solo para poder iterarlo en un seeder — itera sobre el catálogo `modules` o el registro de nwidart, que ya es la fuente de verdad.

## 8. Resumen de niveles

```
Central catalog
  - Plan (+ trial_days)
  - PlanPrice (billing_period, price, currency)
  - Module (+ sellable_as_addon)
  - ModulePrice (billing_period, price, currency)
  - Feature
  - ModulePermission (catálogo/blueprint, tabla `module_permissions` — no es la tabla real de Spatie)
  - LimitType

Tenant commercial state (central)
  - Subscription (estado actual: plan, billing_period, status, trial_ends_at, periodo)
  - SubscriptionChange (histórico + cambios programados: upgrade/downgrade)
  - SubscriptionModule (módulos activos y su procedencia: plan | addon)
  - Entitlements efectivos (cache/derivado)

Central ACL (BD central, independiente de todo lo anterior)
  - Role (Spatie, guard `central`, tablas por defecto)
  - Permission (Spatie, guard `central`, tablas por defecto)
  - Asignación rol-usuario de plataforma

Tenant ACL (BD del tenant, independiente del ACL central)
  - Role
  - Permission (Spatie, sembrado desde el catálogo central)
  - Asignación rol-usuario
```

## 9. Integración con nwidart/laravel-modules

Cada módulo de negocio (CRM, Inventory, Projects, ...) se implementa como un módulo de `nwidart/laravel-modules`. El **Central Panel también es un módulo nwidart**, pero es la excepción de comportamiento: todos los demás módulos futuros son de tenant.

### Central: la excepción, construida a mano

- Se crea una sola vez (`php artisan module:make Central`) con el comportamiento por defecto de nwidart (rutas normales, sin tenancy).
- Su `RouteServiceProvider` se edita a mano para que sus rutas resuelvan **solo en el dominio central** — sin pasar por `InitializeTenancyByDomain` de stancl/tenancy (y opcionalmente con `PreventAccessFromTenantDomains` para bloquear explícitamente el acceso desde subdominios de tenant).
- Al ser un único módulo que no se repite, no necesita generalizarse en los stubs.

### Todos los demás módulos: el stub por defecto debe ser "de tenant"

Como la mayoría de los módulos futuros son para tenants, conviene invertir el default de nwidart en vez de recablear tenancy módulo por módulo. nwidart permite publicar/sobreescribir sus stubs vía `config/modules.php`:

```php
'stubs' => [
    'enabled' => true,
    'path' => base_path('stubs/modules'),      // copia editable de los stubs originales
    'files' => [
        'routes/web' => 'routes/tenant.php',   // el archivo generado se llama tenant.php, no web.php
        // ...
    ],
],
```

Pasos:

1. Copiar los stubs de `vendor/nwidart/laravel-modules/src/Commands/stubs` a `stubs/modules` (o la ruta elegida) y activarlos en `config/modules.php`.
2. Editar el stub de rutas para que `module:make` genere `routes/tenant.php` en vez de `routes/web.php` — el nombre del archivo ya comunica que es tenant-only.
3. Editar el stub de `RouteServiceProvider` para que registre ese archivo dentro del middleware de tenancy de stancl (`InitializeTenancyByDomain`, `PreventAccessFromCentralDomains`, o el alias que se defina, ej. `tenant`), en vez de `web` a secas.

Con esto, `php artisan module:make Inventory` genera de entrada un módulo listo para tenant — sin tener que recordar cablear tenancy cada vez que se crea uno nuevo.

### Nota relacionada (para cuando se diseñen las migraciones)

El mismo problema se repite con `database/migrations`: nwidart también permite configurar la ruta/generador de migraciones por módulo, y stancl/tenancy espera las migraciones de tenant en una ruta distinta a las centrales. Se resuelve con el mismo mecanismo de stubs/paths — queda anotado en "Próximos pasos".

### Pendiente de verificar al implementar

Los nombres exactos de los stubs (`routes/web.stub`, `route-provider.stub`, etc.) pueden variar según la versión instalada de `nwidart/laravel-modules`; se confirman revisando `vendor/nwidart/laravel-modules/src/Commands/stubs` una vez se instale el paquete.

## 10. Flujo de siembra de permisos hacia el tenant

Objetivo: cuando cambia lo que un tenant tiene contratado (alta, upgrade, downgrade, addon), su BD debe reflejar los permisos correspondientes sin recalcular todo a mano y sin perder asignaciones existentes.

### Disparadores

- Alta de tenant (elige un plan por primera vez).
- Upgrade de plan (agrega módulos).
- Compra de un módulo como addon.
- Downgrade / cancelación de addon → **no** dispara siembra, solo actualiza el entitlement (sección 3); los permisos ya sembrados no se tocan.
- Un módulo existente agrega un permiso nuevo en una versión futura → esto no es un cambio de suscripción, así que no dispara el listener normal; necesita una resincronización aparte (ver más abajo).

### Pipeline (alta / upgrade / addon)

1. Se actualiza `subscription_modules` (nueva fila `source=plan` o `source=addon`) → evento `SubscriptionModuleChanged($tenant, $module, action: activated)` (el mismo evento que ya invalida `tenant_entitlements` en la sección 3, con la acción distinguiendo activación de baja).
2. Un listener en cola (queued listener), tenant-aware:
   a. Resuelve — **desde la conexión central**, antes de cambiar de contexto — los slugs de `module_permissions` que declara el módulo (sección 1, generados por `{Modulo}Permissions::actions()/special()`).
   b. Cambia al contexto de BD del tenant (`$tenant->run(fn () => ...)` de stancl/tenancy).
   c. `Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web'])` por cada slug — idempotente, seguro ante reintentos de cola.
   d. Adjunta automáticamente los permisos nuevos al rol de sistema `owner` del tenant (el dueño de la cuenta recibe de inmediato todo lo que su plan permite). Los demás roles (`member`, roles personalizados) **no** se tocan — su composición de permisos es decisión exclusiva del tenant.
3. El mismo evento invalida/recalcula el cache de `tenant_entitlements` (sección 3), para que el gate de entitlement vea el módulo activo de inmediato.

### Downgrade / cancelación de addon

No dispara siembra. Solo se actualiza `subscription_modules`/entitlements (`action: deactivated`) — permisos y asignaciones de rol quedan intactos, listos por si el tenant vuelve a subir de plan.

### Alta de un tenant nuevo (mismo mecanismo, no uno aparte)

Provisionar un tenant = correr sus migraciones (crea `roles`/`permissions` vacíos vía Spatie) + sembrar los roles base del sistema (`owner`, `member`) + disparar el mismo pipeline de arriba para cada módulo incluido en el plan elegido. No es un camino especial: es "activar todos los módulos del plan inicial" con el mismo listener que cualquier upgrade posterior.

### Resincronización cuando un módulo agrega un permiso nuevo

No lo dispara ningún evento de suscripción (el tenant no cambió nada contratado). Se resuelve con un comando explícito, reutilizando el paso 2 (a-d) del pipeline:

```
php artisan tenants:sync-permissions {tenant?} [--all]
```

Sirve tanto para un tenant puntual (soporte) como para un backfill masivo tras desplegar una nueva versión de un módulo que agregó permisos.

### Por qué no una transacción única central + tenant

Central (registro de la suscripción) y tenant (siembra de permisos) son conexiones de BD distintas — no se pueden envolver en una sola transacción atómica. El cambio en `subscriptions`/`subscription_modules` es la fuente de verdad y se confirma primero; la siembra es un job en cola, idempotente y reintentable, así que un fallo puntual no deja el sistema en un estado irrecuperable — se puede volver a correr, incluso manualmente con `tenants:sync-permissions`, sin duplicar nada.

## Próximos pasos

- [x] Diseñar migraciones y modelos para el catálogo central (`Plan`, `PlanPrice`, `Module`, `ModulePrice`, `Feature`, `LimitType`, `Permission`) — implementado con `spatie/laravel-enum` (`BillingPeriod`, `Action`), factories y `CatalogSeeder` de ejemplo.
- [x] Decidir el modelo autenticable del personal de la plataforma (`App\Models\CentralUser`) y el guard (`central`) para el ACL central, usando `spatie/laravel-permission` con sus tablas por defecto (el catálogo se llama `module_permissions`, no `permissions`, para no chocar).
- [x] Definir el set inicial de roles/permisos centrales (`super-admin`, `support`, `billing`, `sales`) y sembrarlos con `CentralAclSeeder`, sin relación con el catálogo `module_permissions` de módulos.
- [ ] Crear el enum `SpecialPermission` (`Action` ya existe), y la clase `{Modulo}Permissions` por módulo (`actions()`/`special()`) que el seeder del catálogo `module_permissions` recorre.
- [x] Diseñar migraciones para `Subscription`, `SubscriptionModule` — implementado. `SubscriptionChange` (historial + upgrade/downgrade agendado) queda pendiente para una siguiente pasada.
- [x] Definir el job `ApplyScheduledSubscriptionChanges` (aplica downgrades agendados, resincroniza `subscription_modules`) — `SubscriptionChange` implementado, `TenantPlanSubscriber::changePlan()/applyChange()` y el comando `subscriptions:apply-scheduled-changes`. Falta agregarlo al scheduler de Laravel en el despliegue real (configuración, no código).
- [x] Definir el listener/job que recalcula `tenant_entitlements` al cambiar `subscriptions`/`subscription_modules` — `App\Services\TenantEntitlements` + `App\Listeners\InvalidateTenantEntitlementsCache`, disparado por `App\Events\SubscriptionModuleChanged`.
- [x] Definir el mecanismo (tenant-aware) que siembra permisos en la BD del tenant al activarse un módulo, y adjunta los nuevos permisos al rol `owner` — implementado como `App\Services\TenantModulePermissionSyncer`, invocable manualmente por ahora (ver `tenants:sync-permissions`); falta conectarlo al evento `SubscriptionModuleChanged` cuando exista (paso 3).
- [x] Definir el seeder de roles base del tenant (`owner`, `member`) que corre al provisionar un tenant nuevo — `TenantDatabaseSeeder`, disparado automáticamente vía `Jobs\SeedDatabase` en el pipeline de `TenantCreated`.
- [x] Crear el comando `tenants:sync-permissions {tenant?} [--all] [--module=*]` para backfill cuando un módulo agrega permisos nuevos en una versión posterior.
- [x] Definir los middlewares `EnsureTenantHasModule`, `EnsureTenantHasFeature` (alias `tenant.module`/`tenant.feature`). `EnsureLimitNotExceeded` sigue pendiente — necesita un módulo de negocio real que defina cómo "contar uso actual" por límite.
- [x] Decidir estrategia de cache: no hizo falta Redis ni prefijo manual — `CACHE_STORE=database` + `CacheTenancyBootstrapper` (ya activo) hacen que `Cache::remember()` corrido en contexto de tenant ya escriba en la tabla `cache` de ese tenant, aislado automáticamente.
- [ ] Decidir si/cuándo se necesitan `subscription_limit_overrides` (upsells de cantidad, ej. "usuarios extra").
- [x] Crear el módulo `Central` con nwidart (comportamiento por defecto) y editar a mano su `RouteServiceProvider` para que no use middleware de tenancy — implementado, ruta de humo `GET /central/ping` verificada.
- [x] Publicar y personalizar los stubs de nwidart (`config/modules.php` → `stubs`) para que `module:make` genere `routes/tenant.php` cableado con el middleware de tenancy de stancl por defecto — verificado con un módulo de prueba (creado y luego eliminado).
- [ ] Resolver el mismo problema de stubs/paths para `database/migrations` (migraciones de tenant vs. centrales por módulo).
