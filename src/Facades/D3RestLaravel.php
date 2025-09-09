<?php

namespace Hwkdo\D3RestLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\D3RestLaravel\D3RestLaravel
 */
class D3RestLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\D3RestLaravel\D3RestLaravel::class;
    }
}
