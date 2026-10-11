<?php

namespace App\Providers;

use App\Services\CartService;
use App\Services\DbNotificationService;
use App\View\Composers\AdminComposer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // if ($this->app->environment('local')) {
        //     $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        //     $this->app->register(TelescopeServiceProvider::class);
        // }
        $this->app->singleton('cart', function ($app) {
            return new CartService();
        });

        $this->app->singleton('db-notification', function () {
            return new DbNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El chat nativo (Livewire) reemplaza la pantalla del paquete LaraGuppy: se quita su ruta /messenger para que no choque.
        $this->app->booted(function () {
            $router = $this->app['router'];
            $routes = new \Illuminate\Routing\RouteCollection();
            foreach ($router->getRoutes() as $route) {
                if ($route->uri() === 'messenger' && str_contains((string) $route->getActionName(), 'ChatActionsController')) {
                    continue;
                }
                $routes->add($route);
            }
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
            $router->setRoutes($routes);
        });

        // Livewire re-applies only these route middleware on /livewire/update; without it `role:admin` was skipped on actions.
        \Livewire\Livewire::addPersistentMiddleware([\Spatie\Permission\Middleware\RoleMiddleware::class]);

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
        });
        View::composer('*', AdminComposer::class);

        // Public header/footer read cached menus: any change in the menu builder refreshes them.
        foreach ([\App\Models\Menu::class, \App\Models\MenuItem::class] as $model) {
            $model::saved(fn () => \App\Services\MenuService::flush());
            $model::deleted(fn () => \App\Services\MenuService::flush());
        }
    }
}
