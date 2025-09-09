<?php

namespace Hwkdo\D3RestLaravel\Interfaces;

use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;

interface DokumentInterface
{
    public function makeApiData($file = null, $filepath = null): array;
    public static function fromApi(array $data): self;
    public function save($file = null, $filepath = null);
    public function fill(array $data): void;
    public function getAttributeNames(): array;
    public function exists(): bool;
    public function makeValidatorRules(): array;
    public function makeValidationData(): array;
    public function validate(): bool;

}