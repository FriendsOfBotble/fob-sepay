<?php

namespace FriendsOfBotble\SePay\Providers;

use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;

class SePayServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        $this->setNamespace('plugins/fob-sepay');
    }

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadHelpers()
            ->publishAssets();

        $this->app->register(HookServiceProvider::class);
    }
}
