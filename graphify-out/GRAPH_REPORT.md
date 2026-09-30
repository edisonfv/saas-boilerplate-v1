# Graph Report - signature-project  (2026-09-27)

## Corpus Check
- 639 files · ~169,564 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 3589 nodes · 8117 edges · 266 communities (167 shown, 35 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 50 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `c6b66147`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- RouteServiceProvider
- devDependencies
- TenantPlanSubscriber
- scripts
- Illuminate\Database\Eloquent\Relations\BelongsTo
- CentralUser
- Badge.vue
- compilerOptions
- ui.ts
- Illuminate\Http\RedirectResponse
- Illuminate\Database\Eloquent\Factories\Factory
- AGENTS.md
- Tenants/Show.vue
- bootstrap/app.php
- dependencies
- Module
- Illuminate\Contracts\Queue\ShouldQueue
- BillingPeriod
- Icon.vue
- Inertia v3 Features
- Feature
- User
- Central/composer.json
- General/composer.json
- CLAUDE.md
- StoreTenantRequest
- Central/Support/Tickets/Show.vue
- SubscriptionModuleChanged
- Tenant
- TenantEntitlements
- Action
- Plan
- RegisterSignaturePaymentRequest
- PasswordResetLinkRequest
- useAppearance.ts
- AppShell.vue
- SubscriptionChangeStatus
- require-dev
- Inertia v3 Features
- global.d.ts
- Symfony\Component\HttpFoundation\Response
- tenancy.php
- require
- composer.json
- CentralLoginRequest
- devDependencies
- LimitType
- Illuminate\Database\Migrations\Migration
- Central/Dashboard.vue
- devDependencies
- Role
- config
- Illuminate\Database\Schema\Blueprint
- signatures.ts
- Illuminate\Foundation\Support\Providers\EventServiceProvider
- UsesUuidPrimaryKey
- SupportTicketManager.php
- extra
- Nwidart\Modules\Support\ModuleServiceProvider
- Pest.php
- SignatureRequest
- Gestión de planes, módulos y permisos (multi-tenant)
- Quick Reference
- psr-4
- logging.php
- SupportTicket
- Quick Reference
- Pest Testing 4
- SignatureAccount
- Pest Testing 4
- DocumentCapture.vue
- cn
- Carbon\CarbonImmutable
- Illuminate\Support\Facades\DB
- Illuminate\Database\Eloquent\Relations\HasMany
- Schedule/Index.vue
- TicketPriority
- CatalogSeeder.php
- SignatureApplicationForm.vue
- modules.php
- Illuminate\Support\Facades\Schema
- SupportAppointment
- SignaturePaymentStatus
- console.php
- SignatureProduct
- StoreTenantRequest.php
- SignatureProviderRequest
- Illuminate\Database\Eloquent\Model
- ScheduleController
- eslint.config.js
- Tailwind CSS Development
- laravel-boost
- Tailwind CSS Development
- Application
- Architecture Best Practices
- Security Best Practices
- vue-shims.d.ts
- AppServiceProvider.php
- Attachment
- AccountController
- SignatureInvitation
- Architecture Best Practices
- Security Best Practices
- Queue & Job Best Practices
- Queue & Job Best Practices
- package.json
- Advanced Query Patterns
- Database Performance Best Practices
- Events & Notifications Best Practices
- Wayfinder Development
- SignatureProvider
- AttachmentStore
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
- Spatie\Enum\Laravel\Enum
- Collection Best Practices
- HTTP Client Best Practices
- Mail Best Practices
- Routing & Controllers Best Practices
- Conventions & Style
- Validation & Forms Best Practices
- SignatureApplicantType
- Configuration Best Practices
- Configuration Best Practices
- TicketBillingMode
- SignatureStorefront
- Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza
- SignatureValidity
- SignatureDocumentKind
- Illuminate\Foundation\Http\FormRequest
- RetailPriceFloor
- StorefrontMedia
- devDependencies
- devDependencies
- SignaturePaymentMethod
- Signatures/composer.json
- Support/composer.json
- BannerPhotosCard.vue
- Money
- Illuminate\Support\Facades\Route
- WeeklyHoursEditor.vue
- Sales/Index.vue
- SignatureRequestController
- SignatureSettlementRequest
- TicketController
- TenantStatus
- TicketAuthorType
- StorefrontSeo
- UanatacaSignatureProvider
- SupportCalendar
- TicketRatingForm.vue
- Illuminate\Database\Eloquent\Relations\HasOne
- Closure
- PublicTicketController
- TimeField.vue
- .disabled
- SupportLinks
- ci:check
- setup
- EditableListCard.vue
- UanatacaPayloadMapper
- SeoCard.vue
- useSidebar.ts
- HandleInertiaRequests.php
- test
- 2026_09_27_173253_add_margins_to_signature_sales.php
- .storeBlackout
- RouteServiceProvider
- RouteServiceProvider
- HeroSlider.vue
- createTechnician
- migrations/2026_09_26_214346_use_morph_aliases_for_permission_models.php
- VerifyEmailController.php
- ProviderWebhookController.php
- format
- days
- tailwind-merge
- prettier-plugin-tailwindcss
- WeeklyWindow

## God Nodes (most connected - your core abstractions)
1. `Tenant` - 145 edges
2. `SupportTicket` - 121 edges
3. `Controller` - 85 edges
4. `UsesUuidPrimaryKey` - 82 edges
5. `SignatureRequest` - 80 edges
6. `CentralUser` - 78 edges
7. `SignatureProduct` - 73 edges
8. `Module` - 62 edges
9. `Plan` - 55 edges
10. `SupportActor` - 55 edges

## Surprising Connections (you probably didn't know these)
- `createRoleTestTenant()` --calls--> `Tenant`  [EXTRACTED]
  tests/Feature/Tenant/RoleCrudTest.php → app/Models/Tenant.php
- `createUserTestTenant()` --calls--> `Tenant`  [EXTRACTED]
  tests/Feature/Tenant/UserCrudTest.php → app/Models/Tenant.php
- `AttachmentController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/AttachmentController.php → app/Http/Controllers/Controller.php
- `RegisteredUserController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/Auth/RegisteredUserController.php → app/Http/Controllers/Controller.php
- `VerifyEmailController` --inherits--> `Controller`  [EXTRACTED]
  Modules/Central/app/Http/Controllers/Auth/VerifyEmailController.php → app/Http/Controllers/Controller.php

## Import Cycles
- None detected.

## Communities (266 total, 35 thin omitted)

### Community 0 - "RouteServiceProvider"
Cohesion: 0.25
Nodes (3): Illuminate\Foundation\Support\Providers\RouteServiceProvider, RouteServiceProvider, RouteServiceProvider

### Community 1 - "devDependencies"
Cohesion: 0.07
Nodes (27): eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-vue, @laravel/vite-plugin-wayfinder, devDependencies (+19 more)

### Community 2 - "TenantPlanSubscriber"
Cohesion: 0.16
Nodes (7): SubscriptionStatus, Subscription, CarbonImmutable, TenantPlanSubscriber, SubscriptionFactory, SubscriptionModuleFactory, Illuminate\Support\Facades\Cache

### Community 3 - "scripts"
Cohesion: 0.11
Nodes (18): scripts, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-update-cmd, pre-package-uninstall, types:check (+10 more)

### Community 4 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.08
Nodes (6): SignatureRequestDocument, SignatureRequestEvent, SubscriptionChange, SubscriptionModule, SupportRating, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 5 - "CentralUser"
Cohesion: 0.09
Nodes (13): CentralUser, CentralAclSeeder, Illuminate\Support\Facades\Hash, RegisteredUserController, StaffController, RegisterCentralUserRequest, featureManager(), limitTypeManager() (+5 more)

### Community 6 - "Badge.vue"
Cohesion: 0.06
Nodes (41): BadgeTone, props, toneClasses, PaginationLink, useListingFilters(), reload(), toggleSort(), FeatureRow (+33 more)

### Community 7 - "compilerOptions"
Cohesion: 0.07
Nodes (28): DOM, DOM.Iterable, ESNext, resources/js/**/*.d.ts, resources/js/**/*.ts, resources/js/**/*.tsx, resources/js/**/*.vue, vite/client (+20 more)

### Community 8 - "ui.ts"
Cohesion: 0.04
Nodes (49): copied, { dateTime }, method, pendingReceipt, props, registering, rejecting, emit (+41 more)

### Community 9 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.07
Nodes (23): Controller, HomeController, Illuminate\Auth\Events\PasswordReset, Illuminate\Http\RedirectResponse, Illuminate\Http\Request, Illuminate\Pagination\LengthAwarePaginator, Illuminate\Support\Facades\Password, Illuminate\Validation\ValidationException (+15 more)

### Community 10 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.08
Nodes (13): CentralUserFactory, FeatureFactory, LimitTypeFactory, ModuleFactory, static, ModulePriceFactory, PlanFactory, PlanPriceFactory (+5 more)

### Community 11 - "AGENTS.md"
Cohesion: 0.06
Nodes (33): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Enums (+25 more)

### Community 12 - "Tenants/Show.vue"
Cohesion: 0.13
Nodes (16): { can }, props, tabs, TenantHeaderData, usePermissions(), can(), BadgeTone, subscriptionStatusTone() (+8 more)

### Community 13 - "bootstrap/app.php"
Cohesion: 0.14
Nodes (10): HandleAppearance, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets, Illuminate\Support\Facades\View, Spatie\Permission\Middleware\PermissionMiddleware, Spatie\Permission\Middleware\RoleMiddleware (+2 more)

### Community 14 - "dependencies"
Cohesion: 0.07
Nodes (27): clsx, concurrently, date-fns, @inertiajs/vite, @inertiajs/vue3, dependencies, clsx, concurrently (+19 more)

### Community 15 - "Module"
Cohesion: 0.09
Nodes (12): Module, DatabaseSeeder, GeneralModuleSeeder, TenantDatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Seeder, ModuleController, CentralDatabaseSeeder (+4 more)

### Community 16 - "Illuminate\Contracts\Queue\ShouldQueue"
Cohesion: 0.06
Nodes (24): InvitationSent, MailMessage, PaymentLinkSent, MailMessage, PaymentReviewed, MailMessage, AppointmentBooked, MailMessage (+16 more)

### Community 17 - "BillingPeriod"
Cohesion: 0.17
Nodes (6): BillingPeriod, ModuleSource, SubscriptionChangeType, CatalogSeeder, Illuminate\Database\QueryException, Symfony\Component\HttpKernel\Exception\HttpException

### Community 18 - "Icon.vue"
Cohesion: 0.06
Nodes (31): attrs, paths, props, sections, CentralSignatureProduct, props, props, props (+23 more)

### Community 19 - "Inertia v3 Features"
Cohesion: 0.07
Nodes (27): Basic Link Component, Basic Usage, Client-Side Navigation, Common Pitfalls, Deferred Props, Documentation, Form Component (Recommended), Form Component Reset Props (+19 more)

### Community 21 - "User"
Cohesion: 0.12
Nodes (11): User, AttachmentPolicy, SupportTicketPolicy, Illuminate\Auth\Access\Response, Illuminate\Foundation\Auth\User, DashboardController, UserController, createRoleTestOwner() (+3 more)

### Community 22 - "Central/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 23 - "General/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 24 - "CLAUDE.md"
Cohesion: 0.07
Nodes (29): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Enums (+21 more)

### Community 26 - "Central/Support/Tickets/Show.vue"
Cohesion: 0.06
Nodes (44): { date, time }, props, RequesterTicket, { dateTime }, appointmentStatusTone(), BadgeTone, formatBytes(), formatMinutes() (+36 more)

### Community 27 - "SubscriptionModuleChanged"
Cohesion: 0.21
Nodes (6): ModuleActivationAction, SubscriptionModuleChanged, SyncTenantModulePermissionsOnActivation, Illuminate\Contracts\Events\ShouldDispatchAfterCommit, Illuminate\Foundation\Events\Dispatchable, Illuminate\Queue\SerializesModels

### Community 28 - "Tenant"
Cohesion: 0.14
Nodes (10): Tenant, Illuminate\Support\Facades\File, Illuminate\Support\Facades\Vite, Stancl\Tenancy\Contracts\TenantWithDatabase, Stancl\Tenancy\Database\Concerns\HasDatabase, Stancl\Tenancy\Database\Concerns\HasDomains, Stancl\Tenancy\Database\Models\Tenant, signedPath() (+2 more)

### Community 29 - "TenantEntitlements"
Cohesion: 0.11
Nodes (6): SeoController, InvalidateTenantEntitlementsCache, SupportSla, TenantEntitlements, TenantLimitGuard, Illuminate\Http\Response

### Community 30 - "Action"
Cohesion: 0.17
Nodes (5): Action, ModulePermissionFactory, CentralPermissions, SignaturesPermissions, SupportPermissions

### Community 31 - "Plan"
Cohesion: 0.16
Nodes (3): Plan, Illuminate\Database\Eloquent\Relations\BelongsToMany, PlanController

### Community 34 - "useAppearance.ts"
Cohesion: 0.42
Nodes (9): Appearance, getStoredAppearance(), handleSystemThemeChange(), initializeTheme(), mediaQuery(), setCookie(), updateTheme(), useAppearance() (+1 more)

### Community 35 - "AppShell.vue"
Cohesion: 0.08
Nodes (26): activeItem, activeSection, authUser, breadcrumbs, closeMenus(), collapseLabel, currentPath, handleKeydown() (+18 more)

### Community 36 - "SubscriptionChangeStatus"
Cohesion: 0.12
Nodes (11): ApplyScheduledSubscriptionChanges, CloseResolvedSupportTickets, ExpireLapsedSubscriptions, ReplaySignatureWebhooks, SyncCentralPermissions, SyncTenantPermissions, SubscriptionChangeStatus, SubscriptionChangeFactory (+3 more)

### Community 37 - "require-dev"
Cohesion: 0.17
Nodes (12): require-dev, fakerphp/faker, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail (+4 more)

### Community 38 - "Inertia v3 Features"
Cohesion: 0.07
Nodes (27): Basic Link Component, Basic Usage, Client-Side Navigation, Common Pitfalls, Deferred Props, Documentation, Form Component (Recommended), Form Component Reset Props (+19 more)

### Community 39 - "global.d.ts"
Cohesion: 0.20
Nodes (9): Auth, User, ComponentCustomProperties, ImportMeta, ImportMetaEnv, InertiaConfig, @inertiajs/core, vite/client (+1 more)

### Community 40 - "Symfony\Component\HttpFoundation\Response"
Cohesion: 0.19
Nodes (4): EnsureTenantHasFeature, EnsureTenantHasModule, PreventAccessFromTenantDomains, Symfony\Component\HttpFoundation\Response

### Community 41 - "tenancy.php"
Cohesion: 0.14
Nodes (14): CacheTenancyBootstrapper, CacheManager, Illuminate\Cache\CacheManager, Illuminate\Contracts\Foundation\Application, Spatie\Permission\PermissionRegistrar, Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper, Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper, Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper (+6 more)

### Community 42 - "require"
Cohesion: 0.18
Nodes (11): require, inertiajs/inertia-laravel, laravel/framework, laravel/tinker, laravel/wayfinder, nwidart/laravel-modules, php, spatie/laravel-enum (+3 more)

### Community 43 - "composer.json"
Cohesion: 0.15
Nodes (12): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, Tests\\ (+4 more)

### Community 44 - "CentralLoginRequest"
Cohesion: 0.18
Nodes (5): Illuminate\Auth\Events\Lockout, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\RateLimiter, CentralLoginRequest, LoginRequest

### Community 45 - "devDependencies"
Cohesion: 0.12
Nodes (16): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, laravel-vite-plugin (+8 more)

### Community 47 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.20
Nodes (3): CreateTenantsTable, CreateDomainsTable, Illuminate\Database\Migrations\Migration

### Community 48 - "Central/Dashboard.vue"
Cohesion: 0.07
Nodes (28): appName, { appearance, updateAppearance }, options, markSizes, wordSizes, dismiss(), iconClasses, icons (+20 more)

### Community 49 - "devDependencies"
Cohesion: 0.12
Nodes (16): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, laravel-vite-plugin (+8 more)

### Community 50 - "Role"
Cohesion: 0.10
Nodes (10): ModulePermission, Permission, Role, CentralPermissionSyncer, TenantModulePermissionSyncer, RoleController, RoleController, Spatie\Permission\DefaultTeamResolver (+2 more)

### Community 51 - "config"
Cohesion: 0.25
Nodes (8): pestphp/pest-plugin, php-http/discovery, wikimedia/composer-merge-plugin, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 53 - "signatures.ts"
Cohesion: 0.06
Nodes (37): prepaidProducts, props, usedPercent, BadgeTone, BankAccount, ledgerEntryTone(), money(), paymentReviewTone() (+29 more)

### Community 54 - "Illuminate\Foundation\Support\Providers\EventServiceProvider"
Cohesion: 0.15
Nodes (5): Illuminate\Foundation\Support\Providers\EventServiceProvider, EventServiceProvider, EventServiceProvider, EventServiceProvider, EventServiceProvider

### Community 55 - "UsesUuidPrimaryKey"
Cohesion: 0.15
Nodes (11): UsesUuidPrimaryKey, Domain, ModulePrice, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Concerns\HasUuids, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Notifications\Notifiable (+3 more)

### Community 56 - "SupportTicketManager.php"
Cohesion: 0.17
Nodes (7): Illuminate\Database\ClassMorphViolationException, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Notification, Illuminate\Support\Facades\Storage, Illuminate\Support\Facades\URL, ticketWithFiles(), bannerPhoto()

### Community 57 - "extra"
Cohesion: 0.33
Nodes (6): extra, laravel, merge-plugin, dont-discover, include, Modules/*/composer.json

### Community 58 - "Nwidart\Modules\Support\ModuleServiceProvider"
Cohesion: 0.33
Nodes (6): Illuminate\Console\Scheduling\Schedule, CentralServiceProvider, GeneralServiceProvider, SignaturesServiceProvider, SupportServiceProvider, Nwidart\Modules\Support\ModuleServiceProvider

### Community 59 - "Pest.php"
Cohesion: 0.08
Nodes (18): SignatureAffiliationMode, SignatureRequestSource, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, ConfigureSignatureAccountRequest, applyOnStorefront(), receiptUpload(), UploadedFile (+10 more)

### Community 60 - "SignatureRequest"
Cohesion: 0.09
Nodes (13): SignatureRequestStatus, SignatureRequest, ProviderStatusUpdate, CarbonImmutable, self, SignatureIssuance, SignatureRequestManager, Illuminate\Http\Client\Request (+5 more)

### Community 61 - "Gestión de planes, módulos y permisos (multi-tenant)"
Cohesion: 0.07
Nodes (27): 10. Flujo de siembra de permisos hacia el tenant, 1. Catálogo central, 2. Addons = módulos existentes marcados como vendibles sueltos, 3. Estado comercial del tenant (central), 4. ACL del tenant (base de datos del tenant), 5. ACL central (personal de la plataforma), 6. Autorización en runtime: doble gate, 7. Enums vs catálogo dinámico (+19 more)

### Community 62 - "Quick Reference"
Cohesion: 0.08
Nodes (23): 10. Routing & Controllers → `rules/routing.md`, 11. HTTP Client → `rules/http-client.md`, 12. Events, Notifications & Mail → `rules/events-notifications.md`, `rules/mail.md`, 13. Error Handling → `rules/error-handling.md`, 14. Task Scheduling → `rules/scheduling.md`, 15. Architecture → `rules/architecture.md`, 16. Migrations → `rules/migrations.md`, 17. Collections → `rules/collections.md` (+15 more)

### Community 63 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 64 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 65 - "SupportTicket"
Cohesion: 0.15
Nodes (6): TicketEventType, TicketStatus, SupportTicket, SupportActor, SupportTicketManager, TicketActionController

### Community 66 - "Quick Reference"
Cohesion: 0.08
Nodes (23): 10. Routing & Controllers → `rules/routing.md`, 11. HTTP Client → `rules/http-client.md`, 12. Events, Notifications & Mail → `rules/events-notifications.md`, `rules/mail.md`, 13. Error Handling → `rules/error-handling.md`, 14. Task Scheduling → `rules/scheduling.md`, 15. Architecture → `rules/architecture.md`, 16. Migrations → `rules/migrations.md`, 17. Collections → `rules/collections.md` (+15 more)

### Community 67 - "Pest Testing 4"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 68 - "SignatureAccount"
Cohesion: 0.11
Nodes (9): SignatureLedgerEntryType, self, SignatureQuotaExceeded, SignatureAccount, SignatureBalance, SignatureLedgerEntry, SignaturePackage, SignatureWallet (+1 more)

### Community 69 - "Pest Testing 4"
Cohesion: 0.11
Nodes (17): Architecture Testing, Assertions, Basic Test Structure, Basic Usage, Browser Test Example, Common Pitfalls, Creating Tests, Datasets (+9 more)

### Community 70 - "DocumentCapture.vue"
Cohesion: 0.07
Nodes (35): cameraIndex, cameras, close(), confirm(), emit, error, onKeydown(), props (+27 more)

### Community 71 - "cn"
Cohesion: 0.40
Nodes (4): classes, classes, open, cn()

### Community 72 - "Carbon\CarbonImmutable"
Cohesion: 0.11
Nodes (12): SupportAttendanceType, SupportBlackout, SupportBusinessHour, self, SupportSetting, CarbonImmutable, SupportAvailability, SupportLimits (+4 more)

### Community 73 - "Illuminate\Support\Facades\DB"
Cohesion: 0.31
Nodes (6): down(), rename(), up(), Illuminate\Support\Facades\DB, Spatie\QueryBuilder\AllowedFilter, Spatie\QueryBuilder\QueryBuilder

### Community 75 - "Schedule/Index.vue"
Cohesion: 0.06
Nodes (29): activeTypes, AttendanceType, blackoutDatesError, blackoutForm, { dateTime, time, dayKey }, durationPresets, editing, editTechnician() (+21 more)

### Community 76 - "TicketPriority"
Cohesion: 0.14
Nodes (10): TicketCategory, TicketChannel, TicketPriority, OpenTicketData, static, SupportTicketFactory, TicketController, StoreSupportTicketRequest (+2 more)

### Community 77 - "CatalogSeeder.php"
Cohesion: 0.28
Nodes (4): PlanLimitPivot, PlanPrice, SubscriptionLimitPivot, Illuminate\Database\Eloquent\Relations\Pivot

### Community 78 - "SignatureApplicationForm.vue"
Cohesion: 0.07
Nodes (29): ApplicationFields, currentStepIsValid(), defaults, documentSlots, fields, form, goTo(), isCedula (+21 more)

### Community 86 - "SupportAppointment"
Cohesion: 0.14
Nodes (5): AppointmentStatus, SupportAppointment, SupportBookingManager, DomainException, AppointmentController

### Community 89 - "SignaturePaymentStatus"
Cohesion: 0.13
Nodes (5): SignaturePaymentReview, SignaturePaymentStatus, SignaturePayment, SignaturePaymentManager, SignaturePaymentController

### Community 92 - "console.php"
Cohesion: 0.50
Nodes (3): Illuminate\Foundation\Inspiring, Illuminate\Support\Facades\Artisan, Illuminate\Support\Facades\Schedule

### Community 96 - "SignatureProduct"
Cohesion: 0.11
Nodes (4): SignatureProduct, SignaturePresenter, ProductController, SaveSignatureProductRequest

### Community 97 - "StoreTenantRequest.php"
Cohesion: 0.17
Nodes (4): Illuminate\Validation\Rules\Password, Illuminate\Validation\Validator, NewPasswordRequest, UpdatePasswordRequest

### Community 98 - "SignatureProviderRequest"
Cohesion: 0.13
Nodes (6): SignatureProviderRequest, CarbonImmutable, SignatureSalesReport, Illuminate\Support\Collection, SalesController, SignatureSalesReportRequest

### Community 100 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.13
Nodes (7): SignatureWebhookEvent, SupportTechnicianShift, SupportTicketEvent, SupportTimeEntry, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Model, Stancl\Tenancy\Database\Concerns\CentralConnection

### Community 103 - "ScheduleController"
Cohesion: 0.09
Nodes (5): ScheduleController, SaveAttendanceTypeRequest, SaveTechnicianRequest, UpdateBusinessHoursRequest, UpdateSupportSettingsRequest

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

### Community 115 - "AppServiceProvider.php"
Cohesion: 0.08
Nodes (19): AppServiceProvider, TenancyServiceProvider, Illuminate\Auth\Events\Registered, Illuminate\Auth\Events\Verified, Illuminate\Auth\Listeners\SendEmailVerificationNotification, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Auth\Notifications\VerifyEmail, Illuminate\Contracts\Auth\CanResetPassword (+11 more)

### Community 131 - "Attachment"
Cohesion: 0.13
Nodes (7): Attachment, HasAttachments, Attachable, SupportTicketMessage, Illuminate\Database\Eloquent\Relations\MorphMany, Illuminate\Database\Eloquent\Relations\MorphTo, Illuminate\Support\Facades\Gate

### Community 132 - "AccountController"
Cohesion: 0.14
Nodes (5): TenantPresenter, AccountController, AdjustSignatureUnitsRequest, RecordSignaturePaymentRequest, SellSignaturePackageRequest

### Community 133 - "SignatureInvitation"
Cohesion: 0.14
Nodes (5): SignatureInvitation, SignatureInvitationManager, SignatureLinks, SignatureInvitationController, StoreStorefrontSignatureRequest

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

### Community 147 - "SignatureProvider"
Cohesion: 0.11
Nodes (8): TenantLimitExceeded, SignatureProvider, SignatureProviderException, SignatureStatusSynchronizer, Illuminate\Http\Client\ConnectionException, Illuminate\Support\Arr, RuntimeException, Throwable

### Community 148 - "AttachmentStore"
Cohesion: 0.12
Nodes (6): AttachmentStore, AttachmentController, ReplySupportTicketRequest, ReplyAsRequesterRequest, StorePublicTicketRequest, Symfony\Component\HttpFoundation\StreamedResponse

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

### Community 174 - "Spatie\Enum\Laravel\Enum"
Cohesion: 0.11
Nodes (3): CarbonImmutable, SalesPeriod, Spatie\Enum\Laravel\Enum

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

### Community 181 - "SignatureApplicantType"
Cohesion: 0.19
Nodes (4): Gender, IdentityDocumentType, SignatureApplicantType, SignatureApplication

### Community 182 - "Configuration Best Practices"
Cohesion: 0.33
Nodes (5): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets

### Community 183 - "Configuration Best Practices"
Cohesion: 0.33
Nodes (5): Configuration Best Practices, `env()` Only in Config Files, Use `App::environment()` for Environment Checks, Use Constants and Language Files, Use Encrypted Env or External Secrets

### Community 184 - "TicketBillingMode"
Cohesion: 0.17
Nodes (7): TicketBillingMode, TicketBillingStatus, CarbonImmutable, SupportUsage, Illuminate\Notifications\AnonymousNotifiable, UpdateTicketBillingRequest, openTicketFor()

### Community 185 - "SignatureStorefront"
Cohesion: 0.20
Nodes (3): self, SignatureStorefront, StorefrontController

### Community 186 - "Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza"
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: Determinar debilidades para gestionar tenants empresariales y comercializar productos de software como firma electronica, ISO, privacidad y ensenanza, Source Nodes

### Community 187 - "SignatureValidity"
Cohesion: 0.18
Nodes (5): SignatureContainer, SignatureValidity, static, SignatureProductFactory, SignaturesModuleSeeder

### Community 189 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.03
Nodes (21): Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rule, ProfileUpdateRequest, StoreSignaturePackageRequest, StoreFeatureRequest, StoreLimitTypeRequest, StoreModuleRequest, StorePlanRequest (+13 more)

### Community 190 - "RetailPriceFloor"
Cohesion: 0.14
Nodes (6): RetailPriceFloor, Illuminate\Contracts\Validation\ValidationRule, Illuminate\Translation\PotentiallyTranslatedString, StoreSignatureRequestRequest, UpdateSignatureRequestRequest, UpdateStorefrontRequest

### Community 192 - "StorefrontMedia"
Cohesion: 0.16
Nodes (3): StorefrontMedia, StorefrontSettingsController, UploadStorefrontImageRequest

### Community 194 - "devDependencies"
Cohesion: 0.12
Nodes (16): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, laravel-vite-plugin (+8 more)

### Community 195 - "devDependencies"
Cohesion: 0.12
Nodes (16): devDependencies, axios, laravel-vite-plugin, postcss, sass, vite, axios, laravel-vite-plugin (+8 more)

### Community 196 - "SignaturePaymentMethod"
Cohesion: 0.15
Nodes (3): SignaturePaymentMethod, ReportPaymentReceiptRequest, StoreSignatureInvitationRequest

### Community 197 - "Signatures/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 198 - "Support/composer.json"
Cohesion: 0.12
Nodes (15): authors, autoload, autoload-dev, psr-4, psr-4, description, extra, laravel (+7 more)

### Community 199 - "BannerPhotosCard.vue"
Cohesion: 0.14
Nodes (10): fileInput, localError, onPicked(), preparing, preview, props, Slide, slides (+2 more)

### Community 201 - "Illuminate\Support\Facades\Route"
Cohesion: 0.24
Nodes (4): InitializeTenancyOnTenantDomains, Illuminate\Support\Facades\Route, Stancl\Tenancy\Middleware\InitializeTenancyByDomain, Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains

### Community 202 - "WeeklyHoursEditor.vue"
Cohesion: 0.22
Nodes (8): addRange(), copyToOpenDays(), isOpen(), model, rangesOf(), templates, toggleDay(), weekdays

### Community 203 - "Sales/Index.vue"
Cohesion: 0.18
Nodes (11): alertsToShow, apply(), exportUrl, Filters, form, hovered, idleAlerts, maxUnits (+3 more)

### Community 205 - "SignatureSettlementRequest"
Cohesion: 0.23
Nodes (3): SignatureSettlementController, CarbonImmutable, SignatureSettlementRequest

### Community 210 - "UanatacaSignatureProvider"
Cohesion: 0.27
Nodes (3): CarbonImmutable, UanatacaSignatureProvider, Illuminate\Http\Client\PendingRequest

### Community 212 - "TicketRatingForm.vue"
Cohesion: 0.20
Nodes (8): hovered, labels, stars, wasResolved, form, justRated, page, props

### Community 216 - "TimeField.vue"
Cohesion: 0.25
Nodes (7): attrs, isMounted, groupedSlots, model, pad(), time, blackoutRange()

### Community 217 - ".disabled"
Cohesion: 0.32
Nodes (3): TenantAccessBlockReason, EnsureTenantIsActive, Response

### Community 219 - "ci:check"
Cohesion: 0.25
Nodes (8): ci:check, dev, Composer\\Config::disableProcessTimeout, npm run format:check, npm run lint:check, npm run types:check, npx concurrently -c \"#93c5fd,#c4b5fd,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"npm run dev\" --names='server,queue,vite, @test

### Community 220 - "setup"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 221 - "EditableListCard.vue"
Cohesion: 0.25
Nodes (3): props, Row, rows

### Community 223 - "SeoCard.vue"
Cohesion: 0.29
Nodes (6): description, displayUrl, previewDescription, previewTitle, props, title

### Community 224 - "useSidebar.ts"
Cohesion: 0.33
Nodes (5): isCollapsed, isRestored, readStoredValue(), useSidebar(), restore()

### Community 226 - "test"
Cohesion: 0.40
Nodes (5): test, @lint:check, @php artisan config:clear --ansi, @php artisan test, @types:check

### Community 227 - "2026_09_27_173253_add_margins_to_signature_sales.php"
Cohesion: 0.60
Nodes (3): averagePackagePrice(), backfillUnitPrices(), up()

### Community 231 - "HeroSlider.vue"
Cohesion: 0.40
Nodes (3): current, paused, props

### Community 232 - "createTechnician"
Cohesion: 0.40
Nodes (5): createTechnician(), hoursChanged, saveTechnician(), toHoursModel(), toShifts()

### Community 233 - "migrations/2026_09_26_214346_use_morph_aliases_for_permission_models.php"
Cohesion: 0.83
Nodes (3): down(), rename(), up()

### Community 236 - "format"
Cohesion: 0.50
Nodes (4): format(), goToWeek(), onWeekPicked(), submitBlackout()

### Community 237 - "days"
Cohesion: 0.50
Nodes (4): addDays(), days, formatDay(), weekTitle

## Knowledge Gaps
- **1022 isolated node(s):** `php`, `name`, `description`, `authors`, `providers` (+1017 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 1596 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **35 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Tenant` connect `Tenant` to `TenantPlanSubscriber`, `AccountController`, `CentralUser`, `Illuminate\Http\RedirectResponse`, `Illuminate\Database\Eloquent\Factories\Factory`, `BillingPeriod`, `SignatureProvider`, `User`, `SubscriptionModuleChanged`, `TenantEntitlements`, `SubscriptionChangeStatus`, `Symfony\Component\HttpFoundation\Response`, `tenancy.php`, `Role`, `SupportTicketManager.php`, `TicketBillingMode`, `Pest.php`, `SignatureRequest`, `SignatureDocumentKind`, `SupportTicket`, `SignatureAccount`, `Money`, `TicketPriority`, `SignatureRequestController`, `SignatureSettlementRequest`, `TenantStatus`, `TicketController`, `Illuminate\Database\Eloquent\Relations\HasOne`, `SignaturePaymentStatus`, `.disabled`, `HandleInertiaRequests.php`, `SignatureProviderRequest`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **Why does `UsesUuidPrimaryKey` connect `UsesUuidPrimaryKey` to `TenantPlanSubscriber`, `Attachment`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `CentralUser`, `SignatureInvitation`, `Module`, `Feature`, `User`, `Plan`, `LimitType`, `Role`, `SignatureRequest`, `SupportTicket`, `SignatureAccount`, `Carbon\CarbonImmutable`, `Illuminate\Database\Eloquent\Relations\HasMany`, `CatalogSeeder.php`, `Illuminate\Database\Eloquent\Relations\HasOne`, `SupportAppointment`, `SignaturePaymentStatus`, `SignatureProduct`, `SignatureProviderRequest`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Why does `CentralUser` connect `CentralUser` to `Attachment`, `AccountController`, `Illuminate\Http\RedirectResponse`, `Illuminate\Database\Eloquent\Factories\Factory`, `User`, `Role`, `UsesUuidPrimaryKey`, `SupportTicketManager.php`, `Pest.php`, `SupportTicket`, `SignatureAccount`, `Carbon\CarbonImmutable`, `Illuminate\Support\Facades\DB`, `TicketPriority`, `TicketAuthorType`, `Illuminate\Database\Eloquent\Relations\HasOne`, `SupportAppointment`, `HandleInertiaRequests.php`, `ScheduleController`, `migrations/2026_09_26_214346_use_morph_aliases_for_permission_models.php`, `AppServiceProvider.php`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **What connects `php`, `name`, `description` to the rest of the system?**
  _1022 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `devDependencies` be split into smaller, more focused modules?**
  _Cohesion score 0.07407407407407407 - nodes in this community are weakly interconnected._
- **Should `scripts` be split into smaller, more focused modules?**
  _Cohesion score 0.1111111111111111 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Database\Eloquent\Relations\BelongsTo` be split into smaller, more focused modules?**
  _Cohesion score 0.08275862068965517 - nodes in this community are weakly interconnected._