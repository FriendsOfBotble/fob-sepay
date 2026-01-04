<?php

namespace FriendsOfBotble\SePay\Providers;

use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;

class SePayServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this
            ->setNamespace('plugins/fob-sepay')
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadHelpers()
            ->publishAssets();

        $this->app->booted(fn () => $this->app->register(HookServiceProvider::class));
    }
}
