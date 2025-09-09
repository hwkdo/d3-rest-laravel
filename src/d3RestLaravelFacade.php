<?php
namespace AlexanderDieckmann\d3RestLaravel;
use Illuminate\Support\Facades\Facade;
class d3RestLaravelFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'd3-rest-laravel';
    }
}