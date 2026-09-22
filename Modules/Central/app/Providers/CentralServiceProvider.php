<?php

namespace Modules\Central\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CentralServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Central';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'central';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

}
