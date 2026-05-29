<?php

use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Hwkdo\D3RestLaravel\models\BenutzerAbwesenheit;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BenutzerAbwesenheitTestUser extends Authenticatable
{
    protected $guarded = [];

    public static function firstWhere($column, $operator = null, $value = null)
    {
        $username = func_num_args() === 2 ? $operator : $value;

        return new self(['username' => $username]);
    }
}

it('does not fail when nextPresentDeputyId is missing while user is absent', function () {
    config(['d3-rest-laravel.USER_MODEL' => BenutzerAbwesenheitTestUser::class]);

    D3RestLaravel::shouldReceive('getUsernameByUserId')
        ->once()
        ->with('user-id-1')
        ->andReturn('hwkdo569');

    $absence = new BenutzerAbwesenheit([
        'userId' => 'user-id-1',
        'isAbsent' => true,
    ]);

    expect($absence->abwesend)->toBeTrue()
        ->and($absence->vertreter)->toBeNull()
        ->and($absence->user->username)->toBe('hwkdo569');
});

it('resolves vertreter when nextPresentDeputyId is present', function () {
    config(['d3-rest-laravel.USER_MODEL' => BenutzerAbwesenheitTestUser::class]);

    D3RestLaravel::shouldReceive('getUsernameByUserId')
        ->with('user-id-1')
        ->andReturn('hwkdo569');

    D3RestLaravel::shouldReceive('getUsernameByUserId')
        ->with('deputy-id-1')
        ->andReturn('hwkdo123');

    $absence = new BenutzerAbwesenheit([
        'userId' => 'user-id-1',
        'isAbsent' => true,
        'nextPresentDeputyId' => 'deputy-id-1',
    ]);

    expect($absence->vertreter)->not->toBeNull()
        ->and($absence->vertreter->username)->toBe('hwkdo123');
});
