# Graph Report - saas-boilerplate  (2026-09-05)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 1201 nodes · 2377 edges · 138 communities (60 shown, 28 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 7 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- AppServiceProvider.php
- devDependencies
- BillingPeriod
- scripts
- UsesUuidPrimaryKey
- CentralUser
- useListingFilters
- compilerOptions
- Card.vue
- Controller
- Illuminate\Database\Eloquent\Factories\Factory
- Illuminate\Database\Eloquent\Relations\BelongsTo
- Badge.vue
- CentralLayout.vue
- dependencies
- Module
- Role
- Illuminate\Database\Seeder
- Inertia\Response
- Feature
- Illuminate\Http\Request
- Illuminate\Http\RedirectResponse
- Central/composer.json
- General/composer.json
- Action
- bootstrap/app.php
- NewPasswordController.php
- SubscriptionModuleChanged
- Tenant
- TenantEntitlements
- Permission
- Plan
- Illuminate\Foundation\Http\FormRequest
- NewPasswordRequest
- AppearanceToggle.vue
- GeneralLayout.vue
- ApplyScheduledSubscriptionChanges.php
- require-dev
- Staff/Index.vue
- global.d.ts
- EnsureTenantHasModule.php
- CacheTenancyBootstrapper
- require
- composer.json
- LoginRequest
- devDependencies
- LimitType
- Illuminate\Database\Migrations\Migration
- Illuminate\Validation\Rule
- devDependencies
- TenantModulePermissionSyncer
- config
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- EventServiceProvider
- FeatureController
- CentralAuthShell.vue
- extra
- Nwidart\Modules\Support\ModuleServiceProvider
- Pest.php
- CentralLoginRequest
- Central/package.json
- General/package.json
- psr-4
- logging.php
- StoreFeatureRequest
- Plans/Index.vue
- StoreModuleRequest
- StorePlanRequest
- StoreRoleRequest
- UpdateLimitTypeRequest
- UpdateModuleRequest
- UpdatePlanRequest
- UpdateStaffRolesRequest
- StoreRoleRequest
- UpdateRoleRequest
- UpdateUserRolesRequest
- PlanLimitPivot.php
- keywords
- modules.php
- eslint.config.js
- console.php
- laravel-boost
- laravel-vite-plugin
- Application
- tailwindcss
- @vitejs/plugin-vue
- vue-shims.d.ts

## God Nodes (most connected - your core abstractions)
1. `Module` - 55 edges
2. `Tenant` - 52 edges
3. `Role` - 45 edges
4. `Controller` - 45 edges
5. `Plan` - 44 edges
6. `CentralUser` - 43 edges
7. `BillingPeriod` - 36 edges
8. `UsesUuidPrimaryKey` - 34 edges
9. `Permission` - 25 edges
10. `Feature` - 21 edges

## Surprising Connections (you probably didn't know these)
- `ModuleController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/ModuleController.php → app/Http/Controllers/Controller.php
- `RoleController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/RoleController.php → app/Http/Controllers/Controller.php
- `RoleController` --inherits--> `Controller`  [EXTRACTED]
  Modules/General/app/Http/Controllers/RoleController.php → app/Http/Controllers/Controller.php
- `StaffController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/StaffController.php → app/Http/Controllers/Controller.php
- `TenantController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/TenantController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (138 total, 28 thin omitted)

### Community 0 - "AppServiceProvider.php"
Cohesion: 0.05
Nodes (25): AppServiceProvider, TenancyServiceProvider, Illuminate\Auth\Events\Registered, Illuminate\Auth\Events\Verified, Illuminate\Auth\Listeners\SendEmailVerificationNotification, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Auth\Notifications\VerifyEmail, Illuminate\Contracts\Auth\CanResetPassword (+17 more)

### Community 1 - "devDependencies"
Cohesion: 0.04
Nodes (48): eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-vue, @laravel/vite-plugin-wayfinder, lightningcss-linux-x64-gnu (+40 more)

### Community 2 - "BillingPeriod"
Cohesion: 0.10
Nodes (12): BillingPeriod, SubscriptionChangeStatus, SubscriptionChangeType, SubscriptionStatus, Subscription, TenantPlanSubscriber, Carbon\CarbonImmutable, SubscriptionChangeFactory (+4 more)

### Community 3 - "scripts"
Cohesion: 0.05
Nodes (39): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+31 more)

### Community 4 - "UsesUuidPrimaryKey"
Cohesion: 0.11
Nodes (13): UsesUuidPrimaryKey, Domain, ModulePrice, PlanPrice, LimitTypeFactory, PlanFactory, PlanPriceFactory, Illuminate\Database\Eloquent\Attributes\Fillable (+5 more)

### Community 5 - "CentralUser"
Cohesion: 0.11
Nodes (15): CentralUser, User, CentralAclSeeder, Illuminate\Contracts\Auth\MustVerifyEmail, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Spatie\Permission\Traits\HasRoles (+7 more)

### Community 6 - "useListingFilters"
Cohesion: 0.10
Nodes (22): PaginationLink, useListingFilters(), reload(), toggleSort(), FeatureRow, props, { search, toggleSort, sortIndicator }, LimitTypeRow (+14 more)

### Community 7 - "compilerOptions"
Cohesion: 0.07
Nodes (28): DOM, DOM.Iterable, ESNext, resources/js/**/*.d.ts, resources/js/**/*.ts, resources/js/**/*.tsx, resources/js/**/*.vue, vite/client (+20 more)

### Community 8 - "Card.vue"
Cohesion: 0.11
Nodes (12): paths, props, props, props, props, props, PermissionGroup, PermissionGroup (+4 more)

### Community 9 - "Controller"
Cohesion: 0.13
Nodes (9): Controller, Illuminate\Foundation\Auth\EmailVerificationRequest, Illuminate\Support\Facades\Hash, EmailVerificationNotificationController, EmailVerificationPromptController, PasswordController, RegisteredUserController, VerifyEmailController (+1 more)

### Community 10 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.11
Nodes (9): CentralUserFactory, ModuleFactory, static, ModulePriceFactory, static, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str (+1 more)

### Community 11 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.13
Nodes (6): ModuleSource, SubscriptionChange, SubscriptionModule, CarbonImmutable, SubscriptionModuleFactory, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 12 - "Badge.vue"
Cohesion: 0.13
Nodes (15): BadgeTone, classes, props, toneClasses, BadgeTone, subscriptionStatusTone(), cn(), props (+7 more)

### Community 13 - "CentralLayout.vue"
Cohesion: 0.11
Nodes (14): allNavigationSections, authUser, can(), currentPath, isMobileNavigationOpen, isSidebarCollapsed, isUserMenuOpen, NavigationSection (+6 more)

### Community 14 - "dependencies"
Cohesion: 0.11
Nodes (19): clsx, concurrently, @inertiajs/vite, @inertiajs/vue3, dependencies, clsx, concurrently, @inertiajs/vite (+11 more)

### Community 15 - "Module"
Cohesion: 0.21
Nodes (3): Module, Illuminate\Database\Eloquent\Relations\HasMany, ModuleController

### Community 16 - "Role"
Cohesion: 0.19
Nodes (4): Role, RoleController, RoleController, Spatie\Permission\Models\Role

### Community 17 - "Illuminate\Database\Seeder"
Cohesion: 0.19
Nodes (7): CatalogSeeder, DatabaseSeeder, TenantDatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, CentralDatabaseSeeder, GeneralDatabaseSeeder

### Community 18 - "Inertia\Response"
Cohesion: 0.15
Nodes (5): Inertia\Response, StaffController, TenantController, DashboardController, UserController

### Community 19 - "Feature"
Cohesion: 0.14
Nodes (4): Feature, FeatureFactory, Illuminate\Database\Eloquent\Relations\BelongsToMany, Illuminate\Database\QueryException

### Community 20 - "Illuminate\Http\Request"
Cohesion: 0.35
Nodes (7): Illuminate\Database\Eloquent\Builder, Illuminate\Http\Request, Illuminate\Support\Facades\DB, Inertia\Inertia, Spatie\QueryBuilder\AllowedFilter, Spatie\QueryBuilder\QueryBuilder, Throwable

### Community 21 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.19
Nodes (5): Illuminate\Http\RedirectResponse, Illuminate\Support\Facades\Auth, AuthenticatedSessionController, ProfileController, AuthenticatedSessionController

### Community 22 - "Central/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 23 - "General/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 24 - "Action"
Cohesion: 0.18
Nodes (5): Action, ModulePermissionFactory, GeneralModuleSeeder, CentralPermissions, GeneralPermissions

### Community 25 - "bootstrap/app.php"
Cohesion: 0.14
Nodes (10): HandleInertiaRequests, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Inertia\Middleware, Spatie\Permission\Middleware\PermissionMiddleware, Spatie\Permission\Middleware\RoleMiddleware (+2 more)

### Community 26 - "NewPasswordController.php"
Cohesion: 0.14
Nodes (6): Illuminate\Auth\Events\PasswordReset, Illuminate\Support\Facades\Password, Illuminate\Validation\ValidationException, ConfirmablePasswordController, NewPasswordController, PasswordResetLinkController

### Community 27 - "SubscriptionModuleChanged"
Cohesion: 0.24
Nodes (6): ModuleActivationAction, SubscriptionModuleChanged, SyncTenantModulePermissionsOnActivation, Illuminate\Contracts\Queue\ShouldQueue, Illuminate\Foundation\Events\Dispatchable, Illuminate\Queue\SerializesModels

### Community 28 - "Tenant"
Cohesion: 0.22
Nodes (9): Tenant, Illuminate\Database\Eloquent\Relations\HasOne, Stancl\Tenancy\Contracts\TenantWithDatabase, Stancl\Tenancy\Database\Concerns\HasDatabase, Stancl\Tenancy\Database\Concerns\HasDomains, Stancl\Tenancy\Database\Models\Tenant, createTenantWithDomain(), createRoleTestTenant() (+1 more)

### Community 29 - "TenantEntitlements"
Cohesion: 0.24
Nodes (3): EnsureTenantHasFeature, InvalidateTenantEntitlementsCache, TenantEntitlements

### Community 30 - "Permission"
Cohesion: 0.23
Nodes (5): ModulePermission, Permission, CentralPermissionSyncer, Spatie\Permission\DefaultTeamResolver, Spatie\Permission\Models\Permission

### Community 32 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.19
Nodes (4): Illuminate\Foundation\Http\FormRequest, PasswordResetLinkRequest, StoreLimitTypeRequest, UpdateFeatureRequest

### Community 33 - "NewPasswordRequest"
Cohesion: 0.15
Nodes (4): Illuminate\Validation\Rules\Password, NewPasswordRequest, RegisterCentralUserRequest, UpdatePasswordRequest

### Community 34 - "AppearanceToggle.vue"
Cohesion: 0.31
Nodes (11): { appearance, updateAppearance }, options, Appearance, getStoredAppearance(), handleSystemThemeChange(), initializeTheme(), mediaQuery(), setCookie() (+3 more)

### Community 35 - "GeneralLayout.vue"
Cohesion: 0.15
Nodes (9): authUser, canManageRoles, canManageUsers, page, permissions, PermissionGroup, PermissionGroup, props (+1 more)

### Community 36 - "ApplyScheduledSubscriptionChanges.php"
Cohesion: 0.27
Nodes (6): ApplyScheduledSubscriptionChanges, SyncCentralPermissions, SyncTenantPermissions, Illuminate\Console\Attributes\Description, Illuminate\Console\Attributes\Signature, Illuminate\Console\Command

### Community 37 - "require-dev"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 38 - "Staff/Index.vue"
Cohesion: 0.18
Nodes (7): NavigationItem, quickLinks, PlanDetail, props, { search, toggleSort, sortIndicator }, StaffRow, IconName

### Community 39 - "global.d.ts"
Cohesion: 0.20
Nodes (9): Auth, User, ComponentCustomProperties, ImportMeta, ImportMetaEnv, InertiaConfig, @inertiajs/core, vite/client (+1 more)

### Community 40 - "EnsureTenantHasModule.php"
Cohesion: 0.29
Nodes (5): EnsureTenantHasModule, HandleAppearance, Closure, Illuminate\Support\Facades\View, Symfony\Component\HttpFoundation\Response

### Community 41 - "CacheTenancyBootstrapper"
Cohesion: 0.27
Nodes (7): CacheTenancyBootstrapper, CacheManager, Illuminate\Cache\CacheManager, Illuminate\Contracts\Foundation\Application, Illuminate\Support\Facades\Cache, Stancl\Tenancy\Contracts\TenancyBootstrapper, Stancl\Tenancy\Contracts\Tenant

### Community 42 - "require"
Cohesion: 0.18
Nodes (11): require, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, laravel/wayfinder, nwidart/laravel-modules, php, spatie/laravel-enum (+3 more)

### Community 43 - "composer.json"
Cohesion: 0.20
Nodes (9): autoload-dev, psr-4, description, license, minimum-stability, name, Tests\\, $schema (+1 more)

### Community 44 - "LoginRequest"
Cohesion: 0.29
Nodes (3): Illuminate\Auth\Events\Lockout, Illuminate\Support\Facades\RateLimiter, LoginRequest

### Community 45 - "devDependencies"
Cohesion: 0.20
Nodes (10): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, postcss (+2 more)

### Community 47 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.28
Nodes (3): CreateTenantsTable, CreateDomainsTable, Illuminate\Database\Migrations\Migration

### Community 48 - "Illuminate\Validation\Rule"
Cohesion: 0.22
Nodes (3): Illuminate\Validation\Rule, ProfileUpdateRequest, UpdateRoleRequest

### Community 49 - "devDependencies"
Cohesion: 0.22
Nodes (9): devDependencies, axios, postcss, sass, vite, axios, postcss, sass (+1 more)

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
Cohesion: 0.53
Nodes (4): Illuminate\Console\Scheduling\Schedule, CentralServiceProvider, GeneralServiceProvider, Nwidart\Modules\Support\ModuleServiceProvider

### Community 59 - "Pest.php"
Cohesion: 0.33
Nodes (3): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, TestCase

### Community 61 - "Central/package.json"
Cohesion: 0.33
Nodes (5): private, scripts, build, dev, type

### Community 62 - "General/package.json"
Cohesion: 0.33
Nodes (5): private, scripts, build, dev, type

### Community 63 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 64 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 66 - "Plans/Index.vue"
Cohesion: 0.40
Nodes (4): PlanPrice, PlanRow, props, { search, toggleSort, sortIndicator }

### Community 78 - "keywords"
Cohesion: 0.67
Nodes (3): keywords, framework, laravel

### Community 107 - "laravel-vite-plugin"
Cohesion: 0.67
Nodes (3): laravel-vite-plugin, laravel-vite-plugin, laravel-vite-plugin

## Knowledge Gaps
- **248 isolated node(s):** `BadgeTone`, `BadgeTone`, `TenantRow`, `Entitlements`, `Subscription` (+243 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 479 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **28 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Module` connect `Module` to `BillingPeriod`, `ApplyScheduledSubscriptionChanges.php`, `UsesUuidPrimaryKey`, `CentralUser`, `Controller`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Seeder`, `TenantModulePermissionSyncer`, `Feature`, `Illuminate\Http\Request`, `FeatureController`, `Action`, `SubscriptionModuleChanged`, `Permission`, `Plan`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Why does `Tenant` connect `Tenant` to `BillingPeriod`, `ApplyScheduledSubscriptionChanges.php`, `UsesUuidPrimaryKey`, `CentralUser`, `EnsureTenantHasModule.php`, `Controller`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `TenantModulePermissionSyncer`, `Inertia\Response`, `Illuminate\Http\Request`, `bootstrap/app.php`, `SubscriptionModuleChanged`, `TenantEntitlements`, `Permission`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **Why does `CentralUser` connect `CentralUser` to `AppServiceProvider.php`, `UsesUuidPrimaryKey`, `Controller`, `Illuminate\Database\Eloquent\Factories\Factory`, `Inertia\Response`, `Illuminate\Http\Request`, `Illuminate\Http\RedirectResponse`, `NewPasswordController.php`, `Permission`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `Tenant` (e.g. with `.share()` and `.index()`) actually correct?**
  _`Tenant` has 2 INFERRED edges - model-reasoned connections that need verification._
- **What connects `BadgeTone`, `BadgeTone`, `TenantRow` to the rest of the system?**
  _248 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `AppServiceProvider.php` be split into smaller, more focused modules?**
  _Cohesion score 0.05052790346907994 - nodes in this community are weakly interconnected._
- **Should `devDependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.04081632653061224 - nodes in this community are weakly interconnected._