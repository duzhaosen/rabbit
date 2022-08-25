<?php

namespace Gonghui\Queue;

class RabbitProvider extends RabbitProviderForLumen
{
    public function boot()
    {
        $configPath = __DIR__ . '/../config/rabbit.php';
        $this->publishes([$configPath => $this->getConfigPath()], 'config');
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('rabbit.php');
    }
}
