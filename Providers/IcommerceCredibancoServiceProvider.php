<?php

namespace Modules\IcommerceCredibanco\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Traits\CanPublishConfiguration;
use Modules\Core\Events\BuildingSidebar;
use Modules\Core\Events\LoadingBackendTranslations;
use Modules\IcommerceCredibanco\Events\Handlers\RegisterIcommerceCredibancoSidebar;

class IcommerceCredibancoServiceProvider extends ServiceProvider
{
    use CanPublishConfiguration;
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBindings();
        $this->app['events']->listen(BuildingSidebar::class, RegisterIcommerceCredibancoSidebar::class);

        $this->app['events']->listen(LoadingBackendTranslations::class, function (LoadingBackendTranslations $event) {
            $event->load('configcredibancos', array_dot(trans('icommercecredibanco::configcredibancos'))); 
            $event->load('transactions', array_dot(trans('icommercecredibanco::transactions')));
            // append translations



        });
    }

    public function boot()
    {
        $this->publishConfig('IcommerceCredibanco', 'permissions');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

    private function registerBindings()
    {
        $this->app->bind(
            'Modules\IcommerceCredibanco\Repositories\ConfigcredibancoRepository',
            function () {
                $repository = new \Modules\IcommerceCredibanco\Repositories\Eloquent\EloquentConfigcredibancoRepository(new \Modules\IcommerceCredibanco\Entities\Configcredibanco());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\IcommerceCredibanco\Repositories\Cache\CacheConfigcredibancoDecorator($repository);
            }
        );
        $this->app->bind(
            'Modules\IcommerceCredibanco\Repositories\TransactionRepository',
            function () {
                $repository = new \Modules\IcommerceCredibanco\Repositories\Eloquent\EloquentTransactionRepository(new \Modules\IcommerceCredibanco\Entities\Transaction());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\IcommerceCredibanco\Repositories\Cache\CacheTransactionDecorator($repository);
            }
        );
// add bindings



    }
}
