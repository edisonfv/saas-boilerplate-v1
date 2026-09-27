<?php

use Illuminate\Support\Facades\Route;
use Modules\Central\Http\Controllers\AttachmentController;
use Modules\Central\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\Central\Http\Controllers\Auth\ConfirmablePasswordController;
use Modules\Central\Http\Controllers\Auth\EmailVerificationNotificationController;
use Modules\Central\Http\Controllers\Auth\EmailVerificationPromptController;
use Modules\Central\Http\Controllers\Auth\NewPasswordController;
use Modules\Central\Http\Controllers\Auth\PasswordController;
use Modules\Central\Http\Controllers\Auth\PasswordResetLinkController;
use Modules\Central\Http\Controllers\Auth\RegisteredUserController;
use Modules\Central\Http\Controllers\Auth\VerifyEmailController;
use Modules\Central\Http\Controllers\DashboardController;
use Modules\Central\Http\Controllers\FeatureController;
use Modules\Central\Http\Controllers\LimitTypeController;
use Modules\Central\Http\Controllers\ModuleController;
use Modules\Central\Http\Controllers\PlanController;
use Modules\Central\Http\Controllers\ProfileController;
use Modules\Central\Http\Controllers\RoleController;
use Modules\Central\Http\Controllers\Signatures\AccountController as SignatureAccountController;
use Modules\Central\Http\Controllers\Signatures\ProductController as SignatureProductController;
use Modules\Central\Http\Controllers\Signatures\SalesController as SignatureSalesController;
use Modules\Central\Http\Controllers\StaffController;
use Modules\Central\Http\Controllers\Support\AppointmentController as SupportAppointmentController;
use Modules\Central\Http\Controllers\Support\ReportController as SupportReportController;
use Modules\Central\Http\Controllers\Support\ScheduleController as SupportScheduleController;
use Modules\Central\Http\Controllers\Support\TicketActionController as SupportTicketActionController;
use Modules\Central\Http\Controllers\Support\TicketController as SupportTicketController;
use Modules\Central\Http\Controllers\TenantController;

// Central is the routing exception: these routes resolve only on the central
// domain (config('tenancy.central_domains')), never through tenancy middleware.
Route::get('/central/ping', fn () => 'central-ok');

Route::prefix('central')->name('central.')->group(function () {
    Route::middleware('guest:central')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);

        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
    });

    Route::middleware('auth:central')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
        Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware('signed')
            ->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
        Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

        // Deleting the account is sensitive enough to require a fresh
        // password confirmation, not just the inline password field.
        Route::delete('/profile', [ProfileController::class, 'destroy'])
            ->middleware('password.confirm:central.password.confirm')
            ->name('profile.destroy');

        // NOTE: within each resource, ".create" (static paths like "/create")
        // is always registered before ".view"/".update" groups that contain a
        // wildcard segment (e.g. "/{plan}") — otherwise the wildcard route
        // would match "create" as if it were a model ID (implicit route model
        // binding fails -> 404 instead of the expected 403).

        Route::middleware('permission:central.staff.create')->group(function () {
            Route::get('/staff/create', [RegisteredUserController::class, 'create'])->name('register');
            Route::post('/staff/create', [RegisteredUserController::class, 'store']);
        });

        Route::middleware('permission:central.staff.view')->group(function () {
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        });

        Route::middleware('permission:central.staff.update')->group(function () {
            Route::get('/staff/{user}/edit', [StaffController::class, 'edit'])->name('staff.edit');
            Route::patch('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
        });

        Route::middleware('permission:central.roles.create')->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        });

        Route::middleware('permission:central.roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        });

        Route::middleware('permission:central.roles.update')->group(function () {
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::patch('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });

        Route::middleware('permission:central.roles.delete')->group(function () {
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::middleware('verified:central.verification.notice')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            // Polymorphic files of any central entity; AttachmentPolicy
            // delegates to the policy of the entity they're attached to.
            Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])
                ->can('view', 'attachment')
                ->name('attachments.show');

            Route::middleware('permission:central.plans.create')->group(function () {
                Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
                Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
            });

            Route::middleware('permission:central.plans.view')->group(function () {
                Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
                Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
            });

            Route::middleware('permission:central.plans.update')->group(function () {
                Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
                Route::patch('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
                Route::patch('/plans/{plan}/toggle-active', [PlanController::class, 'toggleActive'])->name('plans.toggle-active');
            });

            Route::middleware('permission:central.limit-types.create')->group(function () {
                Route::get('/limit-types/create', [LimitTypeController::class, 'create'])->name('limit-types.create');
                Route::post('/limit-types', [LimitTypeController::class, 'store'])->name('limit-types.store');
            });

            Route::middleware('permission:central.limit-types.view')->group(function () {
                Route::get('/limit-types', [LimitTypeController::class, 'index'])->name('limit-types.index');
            });

            Route::middleware('permission:central.limit-types.update')->group(function () {
                Route::get('/limit-types/{limit_type}/edit', [LimitTypeController::class, 'edit'])->name('limit-types.edit');
                Route::patch('/limit-types/{limit_type}', [LimitTypeController::class, 'update'])->name('limit-types.update');
                Route::patch('/limit-types/{limit_type}/toggle-active', [LimitTypeController::class, 'toggleActive'])->name('limit-types.toggle-active');
            });

            Route::middleware('permission:central.modules.create')->group(function () {
                Route::get('/modules/create', [ModuleController::class, 'create'])->name('modules.create');
                Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
            });

            Route::middleware('permission:central.modules.view')->group(function () {
                Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
            });

            Route::middleware('permission:central.modules.update')->group(function () {
                Route::get('/modules/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
                Route::patch('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
                Route::patch('/modules/{module}/toggle-active', [ModuleController::class, 'toggleActive'])->name('modules.toggle-active');
            });

            Route::middleware('permission:central.features.create')->group(function () {
                Route::get('/features/create', [FeatureController::class, 'create'])->name('features.create');
                Route::post('/features', [FeatureController::class, 'store'])->name('features.store');
            });

            Route::middleware('permission:central.features.view')->group(function () {
                Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
            });

            Route::middleware('permission:central.features.update')->group(function () {
                Route::get('/features/{feature}/edit', [FeatureController::class, 'edit'])->name('features.edit');
                Route::patch('/features/{feature}', [FeatureController::class, 'update'])->name('features.update');
                Route::patch('/features/{feature}/toggle-active', [FeatureController::class, 'toggleActive'])->name('features.toggle-active');
            });

            Route::middleware('permission:central.tenants.create')->group(function () {
                Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
                Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
            });

            Route::middleware('permission:central.tenants.view')->group(function () {
                Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
                Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
            });

            // A tenant's signature distributor account is a tab of the tenant.
            Route::get('/tenants/{tenant}/signatures', [SignatureAccountController::class, 'show'])
                ->middleware('permission:central.signature-accounts.view')
                ->name('tenants.signatures');

            Route::middleware('permission:central.tenants.update')->group(function () {
                Route::patch('/tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
            });

            // Electronic signatures -----------------------------------------
            Route::prefix('signatures')->name('signatures.')->group(function () {
                Route::middleware('permission:central.signature-products.create')->group(function () {
                    Route::get('/products/create', [SignatureProductController::class, 'create'])->name('products.create');
                    Route::post('/products', [SignatureProductController::class, 'store'])->name('products.store');
                });

                Route::middleware('permission:central.signature-products.view')->group(function () {
                    Route::get('/products', [SignatureProductController::class, 'index'])->name('products.index');
                });

                Route::middleware('permission:central.signature-products.update')->group(function () {
                    Route::get('/products/{product}/edit', [SignatureProductController::class, 'edit'])->name('products.edit');
                    Route::patch('/products/{product}', [SignatureProductController::class, 'update'])->name('products.update');
                    Route::patch('/products/{product}/toggle-active', [SignatureProductController::class, 'toggleActive'])->name('products.toggle-active');
                    Route::post('/products/{product}/packages', [SignatureProductController::class, 'storePackage'])->name('products.packages.store');
                    Route::patch('/products/{product}/packages/{package}/toggle-active', [SignatureProductController::class, 'togglePackage'])
                        ->scopeBindings()
                        ->name('products.packages.toggle-active');
                });

                // Accounts are managed from each tenant (see "tenants.signatures").
                Route::redirect('/accounts', '/central/tenants');

                Route::middleware('permission:central.signature-sales.view')->group(function () {
                    Route::get('/sales', [SignatureSalesController::class, 'index'])->name('sales.index');
                    Route::get('/sales/export', [SignatureSalesController::class, 'export'])->name('sales.export');
                });

                Route::put('/accounts/{tenant}', [SignatureAccountController::class, 'configure'])
                    ->middleware('permission:central.signature-accounts.update')
                    ->name('accounts.configure');

                Route::middleware('permission:central.signature-accounts.transactions')->group(function () {
                    Route::post('/accounts/{tenant}/packages', [SignatureAccountController::class, 'sellPackage'])->name('accounts.packages.store');
                    Route::post('/accounts/{tenant}/payments', [SignatureAccountController::class, 'recordPayment'])->name('accounts.payments.store');
                    Route::post('/accounts/{tenant}/adjustments', [SignatureAccountController::class, 'adjustUnits'])->name('accounts.adjustments.store');
                    Route::post('/accounts/{tenant}/ledger/{entry}/refund', [SignatureAccountController::class, 'refund'])->name('accounts.ledger.refund');
                });
            });

            // Support desk ---------------------------------------------------
            Route::prefix('support')->name('support.')->group(function () {
                Route::middleware('permission:central.support-tickets.create')->group(function () {
                    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
                    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
                });

                Route::middleware('permission:central.support-tickets.view')->group(function () {
                    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
                    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
                });

                Route::middleware('permission:central.support-tickets.update')->group(function () {
                    Route::post('/tickets/{ticket}/messages', [SupportTicketActionController::class, 'reply'])->name('tickets.reply');
                    Route::patch('/tickets/{ticket}/status', [SupportTicketActionController::class, 'status'])->name('tickets.status');
                    Route::patch('/tickets/{ticket}/priority', [SupportTicketActionController::class, 'priority'])->name('tickets.priority');
                    Route::patch('/tickets/{ticket}/tenant', [SupportTicketActionController::class, 'linkTenant'])->name('tickets.tenant');
                    Route::post('/tickets/{ticket}/time-entries', [SupportTicketActionController::class, 'logTime'])->name('tickets.time-entries.store');
                    Route::post('/tickets/{ticket}/appointments', [SupportAppointmentController::class, 'store'])->name('tickets.appointments.store');
                    Route::patch('/appointments/{appointment}', [SupportAppointmentController::class, 'update'])->name('appointments.update');
                    Route::patch('/appointments/{appointment}/finish', [SupportAppointmentController::class, 'finish'])->name('appointments.finish');
                    Route::delete('/appointments/{appointment}', [SupportAppointmentController::class, 'destroy'])->name('appointments.destroy');
                });

                Route::middleware('permission:central.support-tickets.assign')->group(function () {
                    Route::patch('/tickets/{ticket}/assignee', [SupportTicketActionController::class, 'assign'])->name('tickets.assignee');
                });

                Route::middleware('permission:central.support-tickets.billing')->group(function () {
                    Route::patch('/tickets/{ticket}/billing', [SupportTicketActionController::class, 'billing'])->name('tickets.billing');
                    Route::post('/reports/invoiced', [SupportReportController::class, 'markInvoiced'])->name('reports.invoiced');
                });

                Route::middleware('permission:central.support-schedule.view')->group(function () {
                    Route::get('/schedule', [SupportScheduleController::class, 'index'])->name('schedule.index');
                });

                Route::middleware('permission:central.support-schedule.update')->group(function () {
                    Route::put('/settings', [SupportScheduleController::class, 'updateSettings'])->name('settings.update');
                    Route::put('/business-hours', [SupportScheduleController::class, 'updateBusinessHours'])->name('business-hours.update');
                    Route::post('/attendance-types', [SupportScheduleController::class, 'storeAttendanceType'])->name('attendance-types.store');
                    Route::patch('/attendance-types/{attendanceType}', [SupportScheduleController::class, 'updateAttendanceType'])->name('attendance-types.update');
                    Route::post('/technicians', [SupportScheduleController::class, 'storeTechnician'])->name('technicians.store');
                    Route::patch('/technicians/{technician}', [SupportScheduleController::class, 'updateTechnician'])->name('technicians.update');
                    Route::post('/blackouts', [SupportScheduleController::class, 'storeBlackout'])->name('blackouts.store');
                    Route::delete('/blackouts/{blackout}', [SupportScheduleController::class, 'destroyBlackout'])->name('blackouts.destroy');
                });

                Route::middleware('permission:central.support-reports.view')->group(function () {
                    Route::get('/reports', [SupportReportController::class, 'index'])->name('reports.index');
                });
            });
        });
    });
});
