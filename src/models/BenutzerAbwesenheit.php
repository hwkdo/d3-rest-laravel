<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\d3RestLaravelFacade;

class BenutzerAbwesenheit
{
    public ?string $userid;
    public bool $abwesend;
    public ?Illuminate\Contracts\Auth\Authenticatable $user;
    public ?Illuminate\Contracts\Auth\Authenticatable $vertreter;
    
    public function __construct($data)
    {
        $this->userid = $data['userId'];
        $this->abwesend = $data['isAbsent'];
        $this->user = app(config('d3-rest-laravel.USER_MODEL'))::firstWhere('username', d3RestLaravelFacade::getUsernameByUserId($data['userId']));
        $this->vertreter = ($this->abwesend && $data['nextPresentDeputyId']) ? app(config('d3-rest-laravel.USER_MODEL'))::firstWhere('username', d3RestLaravelFacade::getUsernameByUserId($data['nextPresentDeputyId'])) : null;
    }
}

