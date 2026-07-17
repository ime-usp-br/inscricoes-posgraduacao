<?php

namespace App\Providers;

use App\Contracts\WspixClient;
use App\Models\EmailLog;
use App\Services\WspixHttpClient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Policies\AuditPolicy;
use App\Policies\EmailLogPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use OwenIt\Auditing\Models\Audit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WspixClient::class, WspixHttpClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Força HTTPS em produção (necessário quando há proxy/nginx na frente)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        Password::defaults(function () {
            $rule = Password::min(8);

            return $rule->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Register policies for Filament
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Audit::class, AuditPolicy::class);
        Gate::policy(EmailLog::class, EmailLogPolicy::class);

        // Register observers for auditing Spatie models
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
    }
}
