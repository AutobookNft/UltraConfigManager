<?php

namespace Ultra\UltraConfigManager\Facades;

use Illuminate\Support\Facades\Facade;

class UConfig extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'uconfig'; 
    }
} 