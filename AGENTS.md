<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

## Project-Specific Conventions

### Laravel first (check the framework before building anything)

Laravel already ships most of the infrastructure a SaaS needs. **Before writing custom code for a cross-cutting concern, search the version-specific docs (`search-docs` tool) and use the framework feature.** Only build something custom when Laravel (or an already-installed package) genuinely doesn't cover it, and say why in the class docblock.

- **Polymorphism for shared entities.** Anything that can belong to several models (attachments, comments, notes, tags, activity/audit, likes, addresses…) is a polymorphic relation (`morphTo` / `morphMany` / `morphToMany`, `uuidMorphs()` in migrations), exposed through a reusable `Has…` trait in `App\Models\Concerns` plus a contract in `App\Models\Contracts` — never a per-feature table like `ticket_attachments`.
    - **Attachments are already polymorphic:** use `App\Models\Attachment` (central `attachments` table) by adding `implements App\Models\Contracts\Attachable` + `use App\Models\Concerns\HasAttachments` to the model, storing files with `App\Services\Attachments\AttachmentStore`, and mapping the model to its guarding entity in `App\Policies\AttachmentPolicy::guardedEntity()`.
    - **The morph map is enforced** (`Relation::enforceMorphMap()` in `AppServiceProvider::configureMorphMap()`): every model that takes part in a polymorphic relation — including models that receive Spatie roles/permissions — must be registered there with a snake_case alias, or Eloquent throws `ClassMorphViolationException`. Never store class names in `*_type` columns; when adding an alias for a model that already has rows, ship a migration that converts the stored values (central and `database/migrations/tenant/` when tenant tables are involved).
- **Authorization = Policies + Gates.** Per-record access checks live in a Policy (`php artisan make:policy`, auto-discovered) and are applied on routes with `->can('ability', 'param')` or `Gate::authorize()`, not with ad-hoc `abort_unless()` helpers in controllers. Use `Response::denyAsNotFound()` when existence must not leak (cross-tenant ids). Spatie permissions answer "may use this feature"; policies answer "may touch *this* record". Mirror a policy's rule for listings with a local scope (e.g. `SupportTicket::visibleTo()`).
- **Use the built-in building blocks** instead of re-implementing them: Form Requests (validation + `authorize`), route model binding with `scopeBindings()` for nested resources, Eloquent casts and local scopes, Notifications (mail/database, `ShouldQueue`), queued jobs, events/listeners, the scheduler (`routes/console.php`), `Storage` disks, signed/temporary URLs (`URL::temporarySignedRoute`, `signed` middleware), rate limiting (`throttle`), `Str`/`Number`/`Arr` helpers, and model factories/states in tests.
- **Generate with Artisan** (`make:model`, `make:policy`, `make:migration`, `make:request`, `make:notification`, `make:test --pest`…) so files follow Laravel's structure, then adapt them to the project's conventions.

### Model IDs

- Domain models in both the central app and tenant app use UUID primary keys. New first-party Eloquent models should use `uuid('id')->primary()` in migrations and the shared `App\Models\Concerns\UsesUuidPrimaryKey` trait on the model.
- Foreign keys that point to UUID-backed models must use `foreignUuid(...)` (or an explicit UUID column + FK) and request validation must validate those IDs as `uuid`, not `integer`.
- Spatie Permission is UUID-backed in this project: use `App\Models\Role` and `App\Models\Permission`, not `Spatie\Permission\Models\Role` / `Permission`, and keep permission pivots UUID-compatible.
- Exception: `App\Models\Tenant` keeps its primary key as the human-readable tenant identifier used for subdomains, e.g. `acme` or `acme-prod`. Do not convert `tenants.id`, `domains.tenant_id`, or `subscriptions.tenant_id` to UUID. Validate tenant identifiers with the existing lowercase slug/subdomain pattern.
- Laravel infrastructure tables such as jobs, cache, sessions, and password reset tokens may keep Laravel's default key conventions unless the application has a specific domain reason to change them.

### Enums

- Use `spatie/laravel-enum` (`Spatie\Enum\Laravel\Enum`) for all enum-like values in this project (statuses, types, plan tiers, module keys, etc.), not plain native PHP backed enums, class constants, or raw strings.
- Never compare an enum-backed value against a raw string literal (e.g. `$order->status === 'active'`). Always compare against the enum case/instance using the enum's own equality method (e.g. `$order->status->equals(OrderStatus::Active())`).
- This applies to validation too: validate status/type-like fields against the enum class (e.g. its values or a dedicated rule), never with a hardcoded `in:active,inactive` string list.
- Enum-backed properties, method parameters, and return types must be typed as the enum class, never `string`.
- Eloquent attributes backed by an enum must use a cast to the enum class so they are never read back as a raw string.
- Every enum must define a `labels()` override (`Spatie\Enum\Enum::labels()`) with the human-readable text for the frontend. Never compare against `->label` — comparisons and business logic always use `->value`/`->equals()`. `Enum::jsonSerialize()` only emits `->value`, so when a prop/resource needs the label for display (e.g. a `<select>` of billing periods), expose it explicitly (e.g. `'billing_period' => $model->billing_period->value, 'billing_period_label' => $model->billing_period->label`, or `EnumClass::toArray()` for a full value→label list of options).

### Listings, filters and pagination

- All Inertia index listings use `spatie/laravel-query-builder` (`QueryBuilder::for(Model::class)`) instead of `Model::get()` — this is the standard for every listing query in the project, not just the ones already built.
- Pattern: `->allowedFilters(AllowedFilter::partial('search', 'column'), AllowedFilter::exact('flag'), ...)` (variadic args, not an array) + `->allowedSorts(...)` + `->defaultSort(...)` + `->paginate(15)->withQueryString()->through(fn ($model) => [...])`, passing the paginator straight through as the Inertia prop. Relationship-based filters (e.g. filtering tenants by subscription status) use `AllowedFilter::callback($name, fn (Builder $query, $value) => $query->whereHas(...))`.
- The Inertia paginator prop serializes flat (`{ data, links, from, to, total, current_page, ... }`), not nested under `meta` — that nested shape is JSON:API Eloquent Resources only.
- Frontend: `resources/js/composables/useListingFilters.ts` drives the search box (debounced) and sortable column headers via `router.get(..., { only: [...] })`; `resources/js/components/Pagination.vue` renders the `links` array. Any page needing an extra filter beyond search/sort (e.g. a status `<select>`) passes an `extraParams` callback into `useListingFilters` so it's merged into the same request instead of round-tripping separately.
- Any StatCard/aggregate shown alongside a paginated table must be computed via a dedicated whole-table query (a `stats` prop from the controller), never via client-side iteration over the paginated page — that only reflects the current page.

### Seeders

- Every seeder must be idempotent: running it again (e.g. `php artisan db:seed --class=...` on an existing central or tenant database, after adding a permission, module or catalog item) must never duplicate rows, fail on unique constraints, or overwrite data the users changed.
- Match records on their natural/unique key with `firstOrCreate()` (create once, keep later user edits), `updateOrCreate()` (only for platform-owned definitions that must track the code, e.g. permission labels), or `upsert()` for bulk rows; guard optional example data with `exists()`/`doesntExist()`. Never use bare `create()`/`insert()` in a seeder, and never truncate or delete existing rows.
- Attach relations with `syncWithoutDetaching()` (or `firstOrCreate()` on the pivot), never `attach()`, so re-running doesn't duplicate pivot rows.
- Cover it with a test that runs the seeder twice and asserts the row counts don't change.

### Graphify

- If `graphify-out/graph.json` exists, before answering questions about the code use `graphify query "<pregunta>"`.
- For relationships use `graphify path "<A>" "<B>"`.
- For concepts use `graphify explain "<concepto>"`.
- After modifying code, run `graphify update .`.

## Project Summary

This is a multi-tenant SaaS boilerplate (Laravel 13 + Inertia/Vue 3) split into two completely independent domains:

- **Multi-tenancy** (`stancl/tenancy`): a central app (no tenant context) plus per-tenant apps, each with its own database, provisioned synchronously on `Tenant::create()` (migrations + seeding via the `TenantCreated` event pipeline). `Modules/Central` (nwidart/laravel-modules) is the routing/controller home for everything central; tenant-side code lives in the main `app/` tree under the `web` guard.
- **Commercial catalog** (central-only): Plan, Module, Feature, LimitType — full CRUD, soft-toggle `is_active` instead of deletion. Plans bundle Modules/Features/LimitTypes with per-billing-period prices (`PlanPrice`, `BillingPeriod` enum). Subscribing a tenant to a plan (`TenantPlanSubscriber`) creates a `Subscription` + `SubscriptionModule` rows and fires `SubscriptionModuleChanged`, which auto-syncs the tenant's ACL permissions.
- **Dual, independent ACL** (`spatie/laravel-permission` installed twice): `central` guard (`CentralUser`, central DB, `platform_*` tables) and `web` guard (`User`, per-tenant DB) never share tables, guards, or semantics — central and tenant authorization are fully separate systems by design.
- **Central authentication**: login, admin-gated registration (`central.staff.manage`), password reset, email verification, password confirmation, profile (with password-confirmed account deletion).
- **Light/dark theme**: class-based (`.dark` on `<html>`), driven by `useAppearance.ts` + a blade bootstrap script + a Tailwind v4 `@custom-variant dark (&:where(.dark, .dark *));` in `resources/css/app.css` (Tailwind v4 defaults `dark:` to the OS media query otherwise).
- **Listings/filters/pagination**: see "Listings, filters and pagination" above — all central catalog/tenant index pages use `spatie/laravel-query-builder` with real search, sortable columns, and 15-per-page pagination, backed by `Pagination.vue` and `useListingFilters.ts`.
