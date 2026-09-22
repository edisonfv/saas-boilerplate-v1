# Graph Report - saas-boilerplate  (2026-09-06)

## Corpus Check
- 349 files · ~83,972 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1817 nodes · 2783 edges · 187 communities (170 shown, 17 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 88 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- RouteServiceProvider
- devDependencies
- Subscription
- scripts
- UsesUuidPrimaryKey.php
- CentralUser
- Badge.vue
- compilerOptions
- CentralLayout.vue
- Illuminate\Http\RedirectResponse
- Illuminate\Database\Eloquent\Factories\Factory
- AGENTS.md
- Tenants/Index.vue
- Profile/Edit.vue
- dependencies
- Illuminate\Database\Seeder
- Role
- Plan.php
- Card.vue
- Inertia v3 Features
- Feature
- CentralUser.php
- Central/composer.json
- General/composer.json
- CLAUDE.md
- TenantController.php
- ProfileController.php
- SubscriptionModuleChanged
- Tenant
- TenantEntitlements
- Module
- Plan
- BillingPeriod
- Illuminate\Foundation\Http\FormRequest
- AppearanceToggle.vue
- GeneralLayout.vue
- TenantModulePermissionSyncer
- require-dev
- Inertia v3 Features
- global.d.ts
- Illuminate\Http\Request
- CacheTenancyBootstrapper
- require
- composer.json
- LoginRequest
- devDependencies
- LimitType
- Illuminate\Database\Migrations\Migration
- AppServiceProvider.php
- devDependencies
- config
- EventServiceProvider
- Domain.php
- extra
- Nwidart\Modules\Support\ModuleServiceProvider
- Pest.php
- CentralLoginRequest
- Gestión de planes, módulos y permisos (multi-tenant)
- Quick Reference
- psr-4
- Quick Reference
- Pest Testing 4
- Pest Testing 4
- TenantStatus
- Inertia\Response
- PlanLimitPivot.php
- keywords
- eslint.config.js
- Tailwind CSS Development
- laravel-boost
- Tailwind CSS Development
- artisan
- Architecture Best Practices
- Security Best Practices
- vue-shims.d.ts
- TenancyServiceProvider
- Architecture Best Practices
- Security Best Practices
- Queue & Job Best Practices
- Queue & Job Best Practices
- package.json
- Advanced Query Patterns
- Database Performance Best Practices
- Events & Notifications Best Practices
- Wayfinder Development
- TenantLimitGuard
- Advanced Query Patterns
- Database Performance Best Practices
- Events & Notifications Best Practices
- Wayfinder Development
- Caching Best Practices
- Eloquent Best Practices
- Migration Best Practices
- Caching Best Practices
- Eloquent Best Practices
- Migration Best Practices
- scripts
- Blade & Views Best Practices
- Error Handling Best Practices
- Task Scheduling Best Practices
- Testing Best Practices
- Blade & Views Best Practices
- Error Handling Best Practices
- Task Scheduling Best Practices
- Testing Best Practices
- Collection Best Practices
- HTTP Client Best Practices
- Mail Best Practices
- Routing & Controllers Best Practices
- Conventions & Style
- Validation & Forms Best Practices
- Collection Best Practices
- HTTP Client Best Practices
- Mail Best Practices
- Routing & Controllers Best Practices
- Conventions & Style
- Validation & Forms Best Practices
- Tenants/Create.vue
- Configuration Best Practices
- Configuration Best Practices
- Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza
- RegisteredUserController.php
- eslint-import-resolver-typescript
- @vue/eslint-config-typescript

## God Nodes (most connected - your core abstractions)
1. `Tenant` - 49 edges
2. `Controller` - 45 edges
3. `Module` - 34 edges
4. `Plan` - 31 edges
5. `CentralUser` - 29 edges
6. `Role` - 27 edges
7. `TenantEntitlements` - 26 edges
8. `Feature` - 21 edges
9. `LimitType` - 20 edges
10. `Quick Reference` - 20 edges

## Surprising Connections (you probably didn't know these)
- `createRoleTestTenant()` --calls--> `Tenant`  [INFERRED]
  tests/Feature/Tenant/RoleCrudTest.php → app/Models/Tenant.php
- `createUserTestTenant()` --calls--> `Tenant`  [INFERRED]
  tests/Feature/Tenant/UserCrudTest.php → app/Models/Tenant.php
- `createTenantWithDomain()` --calls--> `Tenant`  [INFERRED]
  tests/Feature/Tenant/AuthenticationTest.php → app/Models/Tenant.php
- `RegisteredUserController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/Auth/RegisteredUserController.php → app/Http/Controllers/Controller.php
- `FeatureController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/FeatureController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (187 total, 17 thin omitted)

### Community 0 - "RouteServiceProvider"
Cohesion: 0.21
Nodes (3): Illuminate\Foundation\Support\Providers\RouteServiceProvider, RouteServiceProvider, RouteServiceProvider

### Community 1 - "devDependencies"
Cohesion: 0.08
Nodes (25): eslint, eslint-config-prettier, @eslint/js, eslint-plugin-import, eslint-plugin-vue, @laravel/vite-plugin-wayfinder, devDependencies, eslint (+17 more)

### Community 2 - "Subscription"
Cohesion: 0.15
Nodes (8): ModuleSource, SubscriptionChangeStatus, SubscriptionChangeType, Subscription, Carbon\CarbonInterface, CarbonImmutable, SubscriptionChangeFactory, Spatie\Enum\Laravel\Enum

### Community 3 - "scripts"
Cohesion: 0.05
Nodes (39): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+31 more)

### Community 4 - "UsesUuidPrimaryKey.php"
Cohesion: 0.15
Nodes (8): ModulePermission, ModulePrice, PlanPrice, SubscriptionChange, SubscriptionModule, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 5 - "CentralUser"
Cohesion: 0.15
Nodes (9): CentralUser, Illuminate\Foundation\Auth\User, featureManager(), limitTypeManager(), moduleManager(), planManager(), roleManager(), staffManager() (+1 more)

### Community 6 - "Badge.vue"
Cohesion: 0.08
Nodes (32): BadgeTone, classes, props, toneClasses, PaginationLink, useListingFilters(), cn(), FeatureRow (+24 more)

### Community 7 - "compilerOptions"
Cohesion: 0.07
Nodes (28): DOM, DOM.Iterable, ESNext, resources/js/**/*.d.ts, resources/js/**/*.ts, resources/js/**/*.tsx, resources/js/**/*.vue, vite/client (+20 more)

### Community 8 - "CentralLayout.vue"
Cohesion: 0.09
Nodes (17): allNavigationSections, authUser, collapseLabel, currentPath, isMobileNavigationOpen, isSidebarCollapsed, isUserMenuOpen, NavigationItem (+9 more)

### Community 9 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.10
Nodes (14): Controller, Illuminate\Foundation\Auth\EmailVerificationRequest, Illuminate\Http\RedirectResponse, AuthenticatedSessionController, ConfirmablePasswordController, EmailVerificationNotificationController, EmailVerificationPromptController, NewPasswordController (+6 more)

### Community 10 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.09
Nodes (12): CentralUserFactory, FeatureFactory, LimitTypeFactory, ModuleFactory, static, ModulePriceFactory, PlanFactory, PlanPriceFactory (+4 more)

### Community 11 - "AGENTS.md"
Cohesion: 0.06
Nodes (31): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Enums (+23 more)

### Community 12 - "Tenants/Index.vue"
Cohesion: 0.14
Nodes (13): BadgeTone, subscriptionStatusTone(), props, { search, toggleSort, sortIndicator, reload }, status, TenantRow, Entitlements, isActive (+5 more)

### Community 14 - "dependencies"
Cohesion: 0.08
Nodes (25): clsx, concurrently, @inertiajs/vite, @inertiajs/vue3, dependencies, clsx, concurrently, @inertiajs/vite (+17 more)

### Community 15 - "Illuminate\Database\Seeder"
Cohesion: 0.16
Nodes (9): CentralAclSeeder, DatabaseSeeder, GeneralModuleSeeder, TenantDatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, CentralDatabaseSeeder, GeneralPermissions (+1 more)

### Community 16 - "Role"
Cohesion: 0.07
Nodes (10): Permission, Role, RoleController, StoreRoleRequest, UpdateRoleRequest, RoleController, StoreRoleRequest, UpdateRoleRequest (+2 more)

### Community 18 - "Card.vue"
Cohesion: 0.12
Nodes (10): paths, props, props, props, props, props, PermissionGroup, PermissionGroup (+2 more)

### Community 19 - "Inertia v3 Features"
Cohesion: 0.07
Nodes (27): Basic Link Component, Basic Usage, Client-Side Navigation, Common Pitfalls, Deferred Props, Documentation, Form Component (Recommended), Form Component Reset Props (+19 more)

### Community 20 - "Feature"
Cohesion: 0.14
Nodes (4): Feature, FeatureController, StoreFeatureRequest, UpdateFeatureRequest

### Community 21 - "CentralUser.php"
Cohesion: 0.18
Nodes (7): User, Illuminate\Notifications\Notifiable, Spatie\Permission\Traits\HasRoles, createRoleTestOwner(), createRoleTestTenant(), createUserTestOwner(), createUserTestTenant()

### Community 22 - "Central/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 23 - "General/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 24 - "CLAUDE.md"
Cohesion: 0.07
Nodes (27): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Enums (+19 more)

### Community 27 - "SubscriptionModuleChanged"
Cohesion: 0.43
Nodes (5): ModuleActivationAction, SubscriptionModuleChanged, Illuminate\Contracts\Events\ShouldDispatchAfterCommit, Illuminate\Foundation\Events\Dispatchable, Illuminate\Queue\SerializesModels

### Community 28 - "Tenant"
Cohesion: 0.21
Nodes (7): Tenant, Illuminate\Database\Eloquent\Relations\HasOne, Stancl\Tenancy\Contracts\TenantWithDatabase, Stancl\Tenancy\Database\Concerns\HasDatabase, Stancl\Tenancy\Database\Concerns\HasDomains, Stancl\Tenancy\Database\Models\Tenant, createTenantWithDomain()

### Community 29 - "TenantEntitlements"
Cohesion: 0.19
Nodes (5): EnsureTenantHasFeature, EnsureTenantHasModule, InvalidateTenantEntitlementsCache, TenantEntitlements, Illuminate\Support\Collection

### Community 30 - "Module"
Cohesion: 0.11
Nodes (7): Action, Module, ModulePermissionFactory, Illuminate\Database\Eloquent\Relations\HasMany, ModuleController, StoreModuleRequest, UpdateModuleRequest

### Community 31 - "Plan"
Cohesion: 0.20
Nodes (3): Plan, Illuminate\Database\Eloquent\Relations\BelongsToMany, PlanController

### Community 32 - "BillingPeriod"
Cohesion: 0.24
Nodes (4): BillingPeriod, SubscriptionStatus, SubscriptionFactory, CatalogSeeder

### Community 33 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.13
Nodes (5): Illuminate\Foundation\Http\FormRequest, NewPasswordRequest, PasswordResetLinkRequest, StorePlanRequest, UpdatePlanRequest

### Community 34 - "AppearanceToggle.vue"
Cohesion: 0.32
Nodes (9): { appearance, updateAppearance }, options, Appearance, getStoredAppearance(), handleSystemThemeChange(), initializeTheme(), mediaQuery(), updateTheme() (+1 more)

### Community 35 - "GeneralLayout.vue"
Cohesion: 0.15
Nodes (9): authUser, canManageRoles, canManageUsers, page, permissions, PermissionGroup, PermissionGroup, props (+1 more)

### Community 36 - "TenantModulePermissionSyncer"
Cohesion: 0.12
Nodes (9): ApplyScheduledSubscriptionChanges, SyncCentralPermissions, SyncTenantPermissions, SyncTenantModulePermissionsOnActivation, CentralPermissionSyncer, TenantModulePermissionSyncer, Illuminate\Console\Command, Illuminate\Contracts\Queue\ShouldQueue (+1 more)

### Community 37 - "require-dev"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 38 - "Inertia v3 Features"
Cohesion: 0.07
Nodes (27): Basic Link Component, Basic Usage, Client-Side Navigation, Common Pitfalls, Deferred Props, Documentation, Form Component (Recommended), Form Component Reset Props (+19 more)

### Community 39 - "global.d.ts"
Cohesion: 0.20
Nodes (9): Auth, User, ComponentCustomProperties, ImportMeta, ImportMetaEnv, InertiaConfig, @inertiajs/core, vite/client (+1 more)

### Community 40 - "Illuminate\Http\Request"
Cohesion: 0.19
Nodes (9): EnsureTenantIsActive, HandleAppearance, HandleInertiaRequests, Closure, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request, Inertia\Middleware (+1 more)

### Community 41 - "CacheTenancyBootstrapper"
Cohesion: 0.31
Nodes (5): CacheTenancyBootstrapper, Illuminate\Cache\CacheManager, Illuminate\Contracts\Foundation\Application, Stancl\Tenancy\Contracts\TenancyBootstrapper, Stancl\Tenancy\Contracts\Tenant

### Community 42 - "require"
Cohesion: 0.18
Nodes (11): require, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, laravel/wayfinder, nwidart/laravel-modules, php, spatie/laravel-enum (+3 more)

### Community 43 - "composer.json"
Cohesion: 0.20
Nodes (9): autoload-dev, psr-4, description, license, minimum-stability, name, Tests\\, $schema (+1 more)

### Community 45 - "devDependencies"
Cohesion: 0.12
Nodes (16): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, laravel-vite-plugin (+8 more)

### Community 46 - "LimitType"
Cohesion: 0.15
Nodes (4): LimitType, LimitTypeController, StoreLimitTypeRequest, UpdateLimitTypeRequest

### Community 47 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.28
Nodes (3): CreateTenantsTable, CreateDomainsTable, Illuminate\Database\Migrations\Migration

### Community 49 - "devDependencies"
Cohesion: 0.12
Nodes (16): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, laravel-vite-plugin (+8 more)

### Community 51 - "config"
Cohesion: 0.25
Nodes (8): pestphp/pest-plugin, php-http/discovery, wikimedia/composer-merge-plugin, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 54 - "EventServiceProvider"
Cohesion: 0.29
Nodes (3): Illuminate\Foundation\Support\Providers\EventServiceProvider, EventServiceProvider, EventServiceProvider

### Community 57 - "extra"
Cohesion: 0.33
Nodes (6): extra, laravel, merge-plugin, dont-discover, include, Modules/*/composer.json

### Community 58 - "Nwidart\Modules\Support\ModuleServiceProvider"
Cohesion: 0.60
Nodes (3): CentralServiceProvider, GeneralServiceProvider, Nwidart\Modules\Support\ModuleServiceProvider

### Community 61 - "Gestión de planes, módulos y permisos (multi-tenant)"
Cohesion: 0.07
Nodes (27): 10. Flujo de siembra de permisos hacia el tenant, 1. Catálogo central, 2. Addons = módulos existentes marcados como vendibles sueltos, 3. Estado comercial del tenant (central), 4. ACL del tenant (base de datos del tenant), 5. ACL central (personal de la plataforma), 6. Autorización en runtime: doble gate, 7. Enums vs catálogo dinámico (+19 more)

### Community 62 - "Quick Reference"
Cohesion: 0.08
Nodes (23): 10. Routing & Controllers → `rules/routing.md`, 11. HTTP Client → `rules/http-client.md`, 12. Events, Notifications & Mail → `rules/events-notifications.md`, `rules/mail.md`, 13. Error Handling → `rules/error-handling.md`, 14. Task Scheduling → `rules/scheduling.md`, 15. Architecture → `rules/architecture.md`, 16. Migrations → `rules/migrations.md`, 17. Collections → `rules/collections.md` (+15 more)

### Community 63 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 66 - "Quick Reference"
Cohesion: 0.08
Nodes (23): 10. Routing & Controllers → `rules/routing.md`, 11. HTTP Client → `rules/http-client.md`, 12. Events, Notifications & Mail → `rules/events-notifications.md`, `rules/mail.md`, 13. Error Handling → `rules/error-handling.md`, 14. Task Scheduling → `rules/scheduling.md`, 15. Architecture → `rules/architecture.md`, 16. Migrations → `rules/migrations.md`, 17. Collections → `rules/collections.md` (+15 more)

### Community 67 - "Pest Testing 4"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 69 - "Pest Testing 4"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 71 - "TenantStatus"
Cohesion: 0.21
Nodes (3): TenantStatus, TenantPlanSubscriber, TenantProvisioner

### Community 73 - "Inertia\Response"
Cohesion: 0.12
Nodes (5): Inertia\Response, StaffController, UpdateStaffRolesRequest, UserController, UpdateUserRolesRequest

### Community 78 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 105 - "Tailwind CSS Development"
Cohesion: 0.14
Nodes (13): Basic Usage, Common Patterns, Common Pitfalls, CSS-First Configuration, Dark Mode, Documentation, Flexbox Layout, Grid Layout (+5 more)

### Community 107 - "Tailwind CSS Development"
Cohesion: 0.14
Nodes (13): Basic Usage, Common Patterns, Common Pitfalls, CSS-First Configuration, Dark Mode, Documentation, Flexbox Layout, Grid Layout (+5 more)

### Community 109 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 110 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 115 - "TenancyServiceProvider"
Cohesion: 0.18
Nodes (4): AppServiceProvider, TenancyServiceProvider, Illuminate\Support\ServiceProvider, Stancl\Tenancy\Middleware

### Community 138 - "Architecture Best Practices"
Cohesion: 0.17
Nodes (11): Architecture Best Practices, Code to Interfaces, Convention Over Configuration, Default Sort by Descending, Single-Purpose Action Classes, Use Atomic Locks for Race Conditions, Use `Concurrency::run()` for Parallel Execution, Use `Context` for Request-Scoped Data (+3 more)

### Community 139 - "Security Best Practices"
Cohesion: 0.17
Nodes (11): Audit Dependencies, Authorize Every Action, CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Keep Secrets Out of Code, Mass Assignment Protection, Prevent SQL Injection (+3 more)

### Community 140 - "Queue & Job Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 141 - "Queue & Job Best Practices"
Cohesion: 0.18
Nodes (10): Always Implement `failed()`, Batch Related Jobs, Implement `ShouldBeUnique`, Queue & Job Best Practices, Rate Limit External API Calls in Jobs, `retryUntil()` Needs `$tries = 0`, Set `retry_after` Greater Than `timeout`, Use Exponential Backoff (+2 more)

### Community 142 - "package.json"
Cohesion: 0.18
Nodes (10): lightningcss-linux-x64-gnu, optionalDependencies, lightningcss-linux-x64-gnu, @rollup/rollup-linux-x64-gnu, @tailwindcss/oxide-linux-x64-gnu, private, $schema, type (+2 more)

### Community 143 - "Advanced Query Patterns"
Cohesion: 0.20
Nodes (9): Advanced Query Patterns, Create Dynamic Relationships via Subquery FK, Prefer `whereIn` + Subquery Over `whereHas`, Sometimes Two Simple Queries Beat One Complex Query, Use `addSelect()` Subqueries for Single Values from Has-Many, Use Compound Indexes Matching `orderBy` Column Order, Use Conditional Aggregates Instead of Multiple Count Queries, Use Correlated Subqueries for Has-Many Ordering (+1 more)

### Community 144 - "Database Performance Best Practices"
Cohesion: 0.20
Nodes (9): Add Database Indexes, Always Eager Load Relationships, Chunk Large Datasets, Database Performance Best Practices, No Queries in Blade Templates, Prevent Lazy Loading in Development, Select Only Needed Columns, Use `cursor()` for Memory-Efficient Iteration (+1 more)

### Community 145 - "Events & Notifications Best Practices"
Cohesion: 0.20
Nodes (9): Always Queue Notifications, Events & Notifications Best Practices, Implement `HasLocalePreference` on Notifiable Models, Rely on Event Discovery, Route Notification Channels to Dedicated Queues, Run `event:cache` in Production Deploy, Use `afterCommit()` on Notifications in Transactions, Use On-Demand Notifications for Non-User Recipients (+1 more)

### Community 146 - "Wayfinder Development"
Cohesion: 0.20
Nodes (9): Common Methods, Common Pitfalls, Documentation, Generate Routes, Import Patterns, Quick Reference, Verification, Wayfinder Development (+1 more)

### Community 147 - "TenantLimitGuard"
Cohesion: 0.22
Nodes (3): TenantLimitExceeded, TenantLimitGuard, RuntimeException

### Community 149 - "Advanced Query Patterns"
Cohesion: 0.20
Nodes (9): Advanced Query Patterns, Create Dynamic Relationships via Subquery FK, Prefer `whereIn` + Subquery Over `whereHas`, Sometimes Two Simple Queries Beat One Complex Query, Use `addSelect()` Subqueries for Single Values from Has-Many, Use Compound Indexes Matching `orderBy` Column Order, Use Conditional Aggregates Instead of Multiple Count Queries, Use Correlated Subqueries for Has-Many Ordering (+1 more)

### Community 150 - "Database Performance Best Practices"
Cohesion: 0.20
Nodes (9): Add Database Indexes, Always Eager Load Relationships, Chunk Large Datasets, Database Performance Best Practices, No Queries in Blade Templates, Prevent Lazy Loading in Development, Select Only Needed Columns, Use `cursor()` for Memory-Efficient Iteration (+1 more)

### Community 151 - "Events & Notifications Best Practices"
Cohesion: 0.20
Nodes (9): Always Queue Notifications, Events & Notifications Best Practices, Implement `HasLocalePreference` on Notifiable Models, Rely on Event Discovery, Route Notification Channels to Dedicated Queues, Run `event:cache` in Production Deploy, Use `afterCommit()` on Notifications in Transactions, Use On-Demand Notifications for Non-User Recipients (+1 more)

### Community 152 - "Wayfinder Development"
Cohesion: 0.20
Nodes (9): Common Methods, Common Pitfalls, Documentation, Generate Routes, Import Patterns, Quick Reference, Verification, Wayfinder Development (+1 more)

### Community 153 - "Caching Best Practices"
Cohesion: 0.22
Nodes (8): Caching Best Practices, Configure Failover Cache Stores in Production, Use `Cache::add()` for Atomic Conditional Writes, Use `Cache::flexible()` for Stale-While-Revalidate, Use `Cache::memo()` to Avoid Redundant Hits Within a Request, Use `Cache::remember()` Instead of Manual Get/Put, Use Cache Tags to Invalidate Related Groups, Use `once()` for Per-Request Memoization

### Community 154 - "Eloquent Best Practices"
Cohesion: 0.22
Nodes (8): Apply Global Scopes Sparingly, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Define Attribute Casts, Eloquent Best Practices, Use Correct Relationship Types, Use Local Scopes for Reusable Queries, Use `whereBelongsTo()` for Relationship Queries

### Community 155 - "Migration Best Practices"
Cohesion: 0.22
Nodes (8): Add Indexes in the Migration, Generate Migrations with Artisan, Keep Migrations Focused, Migration Best Practices, Mirror Defaults in Model `$attributes`, Never Modify Deployed Migrations, Use `constrained()` for Foreign Keys, Write Reversible `down()` Methods by Default

### Community 156 - "Caching Best Practices"
Cohesion: 0.22
Nodes (8): Caching Best Practices, Configure Failover Cache Stores in Production, Use `Cache::add()` for Atomic Conditional Writes, Use `Cache::flexible()` for Stale-While-Revalidate, Use `Cache::memo()` to Avoid Redundant Hits Within a Request, Use `Cache::remember()` Instead of Manual Get/Put, Use Cache Tags to Invalidate Related Groups, Use `once()` for Per-Request Memoization

### Community 157 - "Eloquent Best Practices"
Cohesion: 0.22
Nodes (8): Apply Global Scopes Sparingly, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Define Attribute Casts, Eloquent Best Practices, Use Correct Relationship Types, Use Local Scopes for Reusable Queries, Use `whereBelongsTo()` for Relationship Queries

### Community 158 - "Migration Best Practices"
Cohesion: 0.22
Nodes (8): Add Indexes in the Migration, Generate Migrations with Artisan, Keep Migrations Focused, Migration Best Practices, Mirror Defaults in Model `$attributes`, Never Modify Deployed Migrations, Use `constrained()` for Foreign Keys, Write Reversible `down()` Methods by Default

### Community 159 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, build, build:ssr, dev, format, format:check, lint, lint:check (+1 more)

### Community 160 - "Blade & Views Best Practices"
Cohesion: 0.25
Nodes (7): Blade & Views Best Practices, Prefer Blade Components Over `@include`, Use `$attributes->merge()` in Component Templates, Use `@aware` for Deeply Nested Component Props, Use Blade Fragments for Partial Re-Renders (htmx/Turbo), Use `@pushOnce` for Per-Component Scripts, Use View Composers for Shared View Data

### Community 161 - "Error Handling Best Practices"
Cohesion: 0.25
Nodes (7): Add Context to Exception Classes, Enable `dontReportDuplicates()`, Error Handling Best Practices, Exception Reporting and Rendering, Force JSON Error Rendering for API Routes, Throttle High-Volume Exceptions, Use `ShouldntReport` for Exceptions That Should Never Log

### Community 162 - "Task Scheduling Best Practices"
Cohesion: 0.25
Nodes (7): Task Scheduling Best Practices, Use `environments()` to Restrict Tasks, Use `onOneServer()` on Multi-Server Deployments, Use `runInBackground()` for Concurrent Long Tasks, Use Schedule Groups for Shared Configuration, Use `takeUntilTimeout()` for Time-Bounded Processing, Use `withoutOverlapping()` on Variable-Duration Tasks

### Community 163 - "Testing Best Practices"
Cohesion: 0.25
Nodes (7): Call `Event::fake()` After Factory Setup, Testing Best Practices, Use `Exceptions::fake()` to Assert Exception Reporting, Use Factory States and Sequences, Use `LazilyRefreshDatabase` Over `RefreshDatabase`, Use Model Assertions Over Raw Database Assertions, Use `recycle()` to Share Relationship Instances Across Factories

### Community 164 - "Blade & Views Best Practices"
Cohesion: 0.25
Nodes (7): Blade & Views Best Practices, Prefer Blade Components Over `@include`, Use `$attributes->merge()` in Component Templates, Use `@aware` for Deeply Nested Component Props, Use Blade Fragments for Partial Re-Renders (htmx/Turbo), Use `@pushOnce` for Per-Component Scripts, Use View Composers for Shared View Data

### Community 165 - "Error Handling Best Practices"
Cohesion: 0.25
Nodes (7): Add Context to Exception Classes, Enable `dontReportDuplicates()`, Error Handling Best Practices, Exception Reporting and Rendering, Force JSON Error Rendering for API Routes, Throttle High-Volume Exceptions, Use `ShouldntReport` for Exceptions That Should Never Log

### Community 166 - "Task Scheduling Best Practices"
Cohesion: 0.25
Nodes (7): Task Scheduling Best Practices, Use `environments()` to Restrict Tasks, Use `onOneServer()` on Multi-Server Deployments, Use `runInBackground()` for Concurrent Long Tasks, Use Schedule Groups for Shared Configuration, Use `takeUntilTimeout()` for Time-Bounded Processing, Use `withoutOverlapping()` on Variable-Duration Tasks

### Community 167 - "Testing Best Practices"
Cohesion: 0.25
Nodes (7): Call `Event::fake()` After Factory Setup, Testing Best Practices, Use `Exceptions::fake()` to Assert Exception Reporting, Use Factory States and Sequences, Use `LazilyRefreshDatabase` Over `RefreshDatabase`, Use Model Assertions Over Raw Database Assertions, Use `recycle()` to Share Relationship Instances Across Factories

### Community 168 - "Collection Best Practices"
Cohesion: 0.29
Nodes (6): Choose `cursor()` vs. `lazy()` Correctly, Collection Best Practices, Use `#[CollectedBy]` for Custom Collection Classes, Use Higher-Order Messages for Simple Operations, Use `lazyById()` When Updating Records While Iterating, Use `toQuery()` for Bulk Operations on Collections

### Community 169 - "HTTP Client Best Practices"
Cohesion: 0.29
Nodes (6): Always Set Explicit Timeouts, Fake HTTP Calls in Tests, Handle Errors Explicitly, HTTP Client Best Practices, Use Request Pooling for Concurrent Requests, Use Retry with Backoff for External APIs

### Community 170 - "Mail Best Practices"
Cohesion: 0.29
Nodes (6): Implement `ShouldQueue` on the Mailable Class, Mail Best Practices, Separate Content Tests from Sending Tests, Use `afterCommit()` on Mailables Inside Transactions, Use `assertQueued()` Not `assertSent()` for Queued Mailables, Use Markdown Mailables for Transactional Emails

### Community 171 - "Routing & Controllers Best Practices"
Cohesion: 0.29
Nodes (6): Keep Controllers Thin, Routing & Controllers Best Practices, Type-Hint Form Requests, Use Implicit Route Model Binding, Use Resource Controllers, Use Scoped Bindings for Nested Resources

### Community 172 - "Conventions & Style"
Cohesion: 0.29
Nodes (6): Conventions & Style, Follow Laravel Naming Conventions, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers

### Community 173 - "Validation & Forms Best Practices"
Cohesion: 0.29
Nodes (6): Always Use `validated()`, Array vs. String Notation for Rules, Use Form Request Classes, Use `Rule::when()` for Conditional Validation, Use the `after()` Method for Custom Validation, Validation & Forms Best Practices

### Community 175 - "Collection Best Practices"
Cohesion: 0.29
Nodes (6): Choose `cursor()` vs. `lazy()` Correctly, Collection Best Practices, Use `#[CollectedBy]` for Custom Collection Classes, Use Higher-Order Messages for Simple Operations, Use `lazyById()` When Updating Records While Iterating, Use `toQuery()` for Bulk Operations on Collections

### Community 176 - "HTTP Client Best Practices"
Cohesion: 0.29
Nodes (6): Always Set Explicit Timeouts, Fake HTTP Calls in Tests, Handle Errors Explicitly, HTTP Client Best Practices, Use Request Pooling for Concurrent Requests, Use Retry with Backoff for External APIs

### Community 177 - "Mail Best Practices"
Cohesion: 0.29
Nodes (6): Implement `ShouldQueue` on the Mailable Class, Mail Best Practices, Separate Content Tests from Sending Tests, Use `afterCommit()` on Mailables Inside Transactions, Use `assertQueued()` Not `assertSent()` for Queued Mailables, Use Markdown Mailables for Transactional Emails

### Community 178 - "Routing & Controllers Best Practices"
Cohesion: 0.29
Nodes (6): Keep Controllers Thin, Routing & Controllers Best Practices, Type-Hint Form Requests, Use Implicit Route Model Binding, Use Resource Controllers, Use Scoped Bindings for Nested Resources

### Community 179 - "Conventions & Style"
Cohesion: 0.29
Nodes (6): Conventions & Style, Follow Laravel Naming Conventions, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers

### Community 180 - "Validation & Forms Best Practices"
Cohesion: 0.29
Nodes (6): Always Use `validated()`, Array vs. String Notation for Rules, Use Form Request Classes, Use `Rule::when()` for Conditional Validation, Use the `after()` Method for Custom Validation, Validation & Forms Best Practices

### Community 181 - "Tenants/Create.vue"
Cohesion: 0.22
Nodes (7): availableBillingPeriods, PlanOption, props, selectedBillingPeriod, selectedPlan, selectedPlanId, tenantId

### Community 182 - "Configuration Best Practices"
Cohesion: 0.33
Nodes (5): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets

### Community 183 - "Configuration Best Practices"
Cohesion: 0.33
Nodes (5): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets

### Community 186 - "Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza"
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza, Source Nodes

## Knowledge Gaps
- **745 isolated node(s):** `php`, `name`, `description`, `authors`, `providers` (+740 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **17 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Tenant` connect `Tenant` to `BillingPeriod`, `Subscription`, `TenantModulePermissionSyncer`, `TenantStatus`, `Illuminate\Http\Request`, `Role`, `Plan.php`, `Module.php`, `TenantLimitGuard`, `CentralUser.php`, `TenantController.php`, `SubscriptionModuleChanged`, `TenantEntitlements`?**
  _High betweenness centrality (0.010) - this node is a cross-community bridge._
- **Why does `Module` connect `Module` to `BillingPeriod`, `Subscription`, `TenantModulePermissionSyncer`, `UsesUuidPrimaryKey.php`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Database\Seeder`, `Role`, `Module.php`, `Feature`, `SubscriptionModuleChanged`, `Plan`?**
  _High betweenness centrality (0.009) - this node is a cross-community bridge._
- **Why does `Controller` connect `Illuminate\Http\RedirectResponse` to `Illuminate\Foundation\Http\FormRequest`, `Inertia\Response`, `LoginRequest`, `LimitType`, `Role`, `Feature`, `TenantController.php`, `ProfileController.php`, `RegisteredUserController.php`, `Module`, `Plan`?**
  _High betweenness centrality (0.006) - this node is a cross-community bridge._
- **Are the 10 inferred relationships involving `Tenant` (e.g. with `.handle()` and `.handle()`) actually correct?**
  _`Tenant` has 10 INFERRED edges - model-reasoned connections that need verification._
- **What connects `php`, `name`, `description` to the rest of the system?**
  _745 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `devDependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.08 - nodes in this community are weakly interconnected._
- **Should `Subscription` be split into smaller, more focused modules?**
  _Cohesion score 0.14761904761904762 - nodes in this community are weakly interconnected._