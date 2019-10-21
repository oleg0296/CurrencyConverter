<?php

namespace oleg0296\CurrencyConverter\Facades;

use Illuminate\Support\Facades\Facade;
use oleg0296\CurrencyConverter\RBCConverter;

class RBCCOnverterFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RBCConverter::class;
    }
}