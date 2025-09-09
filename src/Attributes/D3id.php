<?php

namespace Hwkdo\D3RestLaravel\Attributes;

use Attribute;

#[Attribute]
final readonly class D3id
{
    public function __construct(public string $id)
    {
    }
}