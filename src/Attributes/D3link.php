<?php

namespace Hwkdo\D3RestLaravel\Attributes;

use Attribute;

#[Attribute]
final readonly class D3link
{
    public function __construct(public string $id)
    {
    }
}