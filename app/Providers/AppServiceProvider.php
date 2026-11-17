<?php

namespace App\Providers;

use App\Models\User;
use App\Prism\Providers\PixelForgeProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Prism\Prism\PrismManager;

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
        Gate::define('admin', fn (User $user): bool => $user->is_admin);

        $manager = $this->app->make(PrismManager::class);
        $manager->extend('pixelforge', function ($app, array $config): PixelForgeProvider {
            $apiKey = $config['api_key'] ?? '';
            $url = $config['url'] ?? 'https://pixelforge.promptedit.dev/v1';
            $pollInterval = filter_var($config['poll_interval'] ?? 2, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0],
            ]);

            if (! is_string($apiKey) || ! is_string($url) || $pollInterval === false) {
                throw new InvalidArgumentException('PixelForge provider configuration is invalid.');
            }

            return new PixelForgeProvider(
                apiKey: $apiKey,
                url: $url,
                pollInterval: $pollInterval,
            );
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::transliterate(
                Str::lower($request->string('email')->trim()->toString()).'|'.$request->ip(),
            ));
        });
    }
}
