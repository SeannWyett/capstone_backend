<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Models\PaperUploads;
use App\Policies\PaperUploadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        VerifyEmail::createurlUsing(function ($notifiable) {
            URL::forceRootUrl(config('app.url'));
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            $backendUrl = URL::temporarySignedRoute(
                'api.verification.verify',
                now()->addMinutes(60),
                ['id' => $id, 'hash' => $hash]
            );

            $query = parse_url($backendUrl, PHP_URL_QUERY);

            return config('app.frontend_url') . "/verify-email?id={$id}&hash={$hash}&{$query}";
        });

        Gate::policy(PaperUploads::class, PaperUploadPolicy::class);

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');
            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });
        //     return (new MailMessage)
        //         ->subject('Reset Your Password')
        //         ->line('You are receiving this email because we received a password reset request for your account.')
        //         ->action('Reset Password', config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}")
        //         ->line('If you did not request a password reset, no further action is required.');
        // });
    }
}
