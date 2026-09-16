<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'Africa/Addis_Ababa',

    'locale' => 'en',

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        Spatie\Permission\PermissionServiceProvider::class,
        Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
        Spatie\Backup\BackupServiceProvider::class,
        Spatie\Activitylog\ActivitylogServiceProvider::class,
        Laravel\Scout\ScoutServiceProvider::class,
        Laravel\Sanctum\SanctumServiceProvider::class,
        MatanYadaev\EloquentSpatial\EloquentSpatialServiceProvider::class,
        //Spatie\Sluggable\SluggableServiceProvider::class,
        // Laravel\Horizon\HorizonServiceProvider::class,   // <-- disabled for now (no Redis)
        // Laravel\Telescope\TelescopeServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\AIServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        // 'ExampleClass' => App\Example\ExampleClass::class,
    ])->toArray(),

];