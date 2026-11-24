<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Therapist;
use App\Models\User;
use App\Policies\BranchScopedPolicy;
use App\View\Composers\CustomerLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {

        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('booking-store', fn (Request $request) => [
            Limit::perMinute(3)->by('minute:'.$request->ip()),
            Limit::perDay(20)->by('day:'.$request->ip()),
        ]);
        RateLimiter::for('booking-lookup', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        View::composer('components.layouts.customer', CustomerLayoutComposer::class);

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        Gate::policy(Booking::class, BranchScopedPolicy::class);
        Gate::policy(Therapist::class, BranchScopedPolicy::class);
        Gate::define('super-admin', fn (User $user): bool => $user->isSuperAdmin());
    }
}
