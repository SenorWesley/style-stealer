<?php

namespace OWOW\StyleStealer\Facades;

use Illuminate\Support\Facades\Facade;

class StyleStealer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'stylestealer';
    }
}