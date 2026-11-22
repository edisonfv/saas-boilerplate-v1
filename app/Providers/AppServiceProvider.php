<?php

namespace App\Providers;

use App\Models\CentralUser;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Signatures\Contracts\SignatureProvider;
use App\Services\Signatures\Uanataca\UanatacaSignatureProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The certification authority behind the Signatures module; swap the
        // adapter here to change (or fake) the provider.
        $this->app->bind(SignatureProvider::class, UanatacaSignatureProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configureAuthNotifications();
    }

    /**
     * Polymorphic "*_type" columns store these stable aliases instead of
     * class names, so renaming or moving a model never breaks stored rows.
     * Enforced: morphing a model that isn't listed here throws, so every new
     * polymorphic participant (e.g. a model using HasAttachments, or anything
     * assigned Spatie roles) must be registered below.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'central_user' => CentralUser::class,
            'user' => User::class,
            'support_ticket' => SupportTicket::class,
            'support_ticket_message' => SupportTicketMessage::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Point Laravel's stock password-reset/email-verification notifications
     * at central's own named routes (`central.password.reset`,
     * `central.verification.verify`) instead of their hardcoded defaults
     * (`password.reset`, `verification.verify`), which don't exist here.
     *
     * Reuses Laravel's notification classes unmodified — only the URL
     * generation is overridden. Tenant doesn't have these routes yet, so
     * this callback only branches for CentralUser for now.
     */
    protected function configureAuthNotifications(): void
    {
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        ResetPassword::createUrlUsing(function (CanResetPassword $notifiable, string $token): string {
            $routeName = $notifiable instanceof CentralUser ? 'central.password.reset' : 'password.reset';

            return route($routeName, ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);
        });

        VerifyEmail::createUrlUsing(function (Model&MustVerifyEmail $notifiable): string {
            $routeName = $notifiable instanceof CentralUser ? 'central.verification.verify' : 'verification.verify';

            return URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
            );
        });
    }
}
