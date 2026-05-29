<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Illuminate\Contracts\Auth\Authenticatable;

class BenutzerAbwesenheit
{
    public ?string $userid;

    public bool $abwesend;

    public ?Authenticatable $user;

    public ?Authenticatable $vertreter;

    public function __construct($data)
    {
        $this->userid = $data['userId'];
        $this->abwesend = $data['isAbsent'];
        $this->user = app(config('d3-rest-laravel.USER_MODEL'))::firstWhere('username', D3RestLaravel::getUsernameByUserId($data['userId']));

        $nextPresentDeputyId = $data['nextPresentDeputyId'] ?? null;
        $this->vertreter = $this->abwesend && ! empty($nextPresentDeputyId)
            ? app(config('d3-rest-laravel.USER_MODEL'))::firstWhere('username', D3RestLaravel::getUsernameByUserId($nextPresentDeputyId))
            : null;
    }
}
