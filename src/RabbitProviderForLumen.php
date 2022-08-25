<?php

namespace Gonghui\Queue;


use Illuminate\Support\ServiceProvider;

class RabbitProviderForLumen extends ServiceProvider
{

    protected $defer = true;

    protected $providers = ['rabbit.consumer', 'rabbit.publisher'];

    protected $rabbitConf = [];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/rabbit.php', 'rabbit');
        $this->app->singleton('rabbit.publisher', function ($app) {
            return new Publisher($app['log'], $app->config['rabbit']);
        });
        $this->app->singleton('rabbit.consumer', function ($app) {
            return new Consumer($app['log'], $app->config['rabbit']);
        });
        $this->setServiceProvides();
    }

    public function provides()
    {
        return $this->providers;
    }

    protected function setPublisherServices($abstractPublisher, $serviceKey)
    {
        $this->app->singleton($abstractPublisher, function ($app) use ($serviceKey) {
            return new Publisher($app['log'], $this->rabbitConf[$serviceKey]);
        });
    }

    protected function setConsumerServices($abstractConsumer, $serviceKey)
    {
        $this->app->singleton($abstractConsumer, function ($app) use ($serviceKey) {
            return new Consumer($app['log'], $this->rabbitConf[$serviceKey]);
        });
    }

    protected function setServiceProvides()
    {
        $this->rabbitConf = $this->app['config']->get('rabbit.services', []);
        $servicesProvides = [];
        foreach ($this->rabbitConf as $serviceKey => $valueConf) {
            $abstractPublisher = 'rabbit.' . $serviceKey . '.publisher';
            $abstractConsumer  = 'rabbit.' . $serviceKey . '.consumer';
            $this->setPublisherServices($abstractPublisher,$serviceKey);
            $this->setConsumerServices($abstractConsumer, $serviceKey);
            $servicesProvides[] = $abstractPublisher;
            $servicesProvides[] = $abstractConsumer;
        }
        $this->providers = array_merge($this->providers, $servicesProvides);
    }
}
