<?php

declare(strict_types=1);

use Hwkdo\D3RestLaravel\Client;

function extractUsergroups(mixed $rows): array
{
    $method = new ReflectionMethod(Client::class, 'extractUsergroupsFromSoapTableItem');
    $method->setAccessible(true);

    return $method->invoke(new Client, $rows);
}

it('parst einen einzelnen Treffer als assoziatives Array', function (): void {
    $rows = [
        'usergroup' => '@D3EDV',
        'group_id' => 'D3EDV',
        'node_id' => '0',
        'object_id' => 'D3EDV',
        'flags' => '0000000000',
    ];

    expect(extractUsergroups($rows))->toBe(['@D3EDV']);
});

it('parst einen einzelnen Treffer als Objekt', function (): void {
    $rows = (object) [
        'usergroup' => '@Rechnungen',
        'group_id' => 'Rechnungen',
    ];

    expect(extractUsergroups($rows))->toBe(['@Rechnungen']);
});

it('parst mehrere Treffer als Liste', function (): void {
    $rows = [
        ['usergroup' => '@HWR', 'group_id' => 'HWR'],
        ['usergroup' => '@D3EDV', 'group_id' => 'D3EDV'],
        ['usergroup' => '@GL_EDV_Schulung', 'group_id' => 'GL_EDV_Schulung'],
    ];

    expect(extractUsergroups($rows))->toBe(['@HWR', '@D3EDV', '@GL_EDV_Schulung']);
});

it('liefert leeres Array wenn table.item fehlt oder leer ist', function (): void {
    expect(extractUsergroups(null))->toBe([])
        ->and(extractUsergroups([]))->toBe([]);
});
