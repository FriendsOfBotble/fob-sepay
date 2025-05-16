<?php

namespace FriendsOfBotble\SePay;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function removed(): void
    {
        Setting::newQuery()
            ->where('key', 'like', 'sepay_%')
            ->orWhere('key', 'like', 'payment_sepay_%')
            ->delete();
    }
}
