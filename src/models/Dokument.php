<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3link;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;
use Hwkdo\D3RestLaravel\d3RestLaravelFacade;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\models\Bestellschein;
use Hwkdo\D3RestLaravel\models\Handwerksrolle;
use Hwkdo\D3RestLaravel\models\Angebot;
use Hwkdo\D3RestLaravel\models\Zahlungsbeleg;
use Hwkdo\D3RestLaravel\models\Bestellvorgang;
use Hwkdo\D3RestLaravel\models\Lieferschein;
use Hwkdo\D3RestLaravel\models\HandwerksrolleOnline;
use Illuminate\Support\Facades\Validator;
use ReflectionClass;
use ReflectionProperty;

abstract class Dokument implements DokumentInterface
{
    public ?string $id;
    public DocTypeEnum $doc_type;

    const FIELDS = [];
    
    const doc_types = [
        Bestellschein::class => DocTypeEnum::Bestellschein,
        Handwerksrolle::class => DocTypeEnum::Handwerksrolle,
        Angebot::class => DocTypeEnum::Angebote,
        Zahlungsbeleg::class => DocTypeEnum::Zahlungsbeleg,
        Bestellvorgang::class => DocTypeEnum::Bestellvorgang,
        Lieferschein::class => DocTypeEnum::Lieferschein,
        HandwerksrolleOnline::class => DocTypeEnum::HandwerksrolleOnline,
    ];

    public function __construct($data)
    {        
        $data['doc_type'] = (self::doc_types[get_class($this)]);
        $this->fill($data);        
    }
    
    public function getBaseApiData($filledProperties): array
    {
        $data = [];
        
        $data["type"] = 1;
        $data["systemProperties"] = [];
        $data["systemProperties"]["property_variant_number"] = "1";
        $data["systemProperties"]["property_state"] = "Fr";
        $data["systemProperties"]["property_colorcode"] = "0";
        $data["remarks"] = [];
        $data["remarks"]["1"] = "Intranet-Upload Rest-API";        
        $data["multivalueExtendedProperties"] = [];
        $data["extendedProperties"] = [];
        $data["state"] = "Release";
        foreach($filledProperties as $property) {
            if($property['d3id']) {
                if(is_array($property['value'])) {
                    $data["extendedProperties"][$property['d3id']] = $property['value'][0];
                    $i = 1;
                    foreach($property['value'] as $value) {
                        $data["multivalueExtendedProperties"][$property['d3id']][$i] = $value;
                        $i++;
                    }
                } else {
                    $data["extendedProperties"][$property['d3id']] = $property['value'];
                }
            }
            if($property['d3systemproperty']) {
                $data["systemProperties"][$property['d3systemproperty']] = $property['value'];
                if($property['d3systemproperty'] == 'property_filename') {
                    $data["systemProperties"]['property_filetype'] = str($property['value'])->afterLast('.')->value();
                }
            }
        }        
        return $data;
    }

    public function getFilledProperties(): array 
    {
        $reflection = new ReflectionClass($this);
        return collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
            ->filter(function(ReflectionProperty $property) {
                $name = $property->getName();
                return isset($this->{$name}) && 
                       $this->{$name} !== null && 
                       $this->{$name} !== '' && 
                       $this->{$name} !== [];
            })
            ->map(function(ReflectionProperty $property) {
                $name = $property->getName();
                return [
                    'name' => $name,
                    'value' => $this->{$name},
                    'd3id' => $this->getD3id($name),
                    'd3systemproperty' => $this->getD3SystemProperty($name)
                ];
            })
            ->toArray();
    }

    public function getD3id(string $propertyName): ?string 
    {
        $reflection = new ReflectionClass($this);
        $property = $reflection->getProperty($propertyName);
        $attributes = $property->getAttributes(D3id::class);
        
        if (empty($attributes)) {
            return null;
        }
        
        return $attributes[0]->getArguments()[0];
    }

    public function getD3SystemProperty(string $propertyName): ?string 
    {
        $reflection = new ReflectionClass($this);
        $property = $reflection->getProperty($propertyName);
        $attributes = $property->getAttributes(D3SystemProperty::class);
        
        if (empty($attributes)) {
            return null;
        }
        
        return $attributes[0]->getArguments()[0];
    }
    
    public function isRequired(string $propertyName): bool 
    {
        $reflection = new ReflectionClass($this);
        $property = $reflection->getProperty($propertyName);
        $type = $property->getType();
        
        return !$type->allowsNull();
    }

    public function isMulti(string $propertyName): bool 
    {
        $value = $this->{$propertyName} ?? null;
        return is_array($value);
    }

    public function getRequiredFields(): array 
    {
        $reflection = new ReflectionClass($this);
        return collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
            ->filter(function(ReflectionProperty $property) {
                $type = $property->getType();
                return $type && !$type->allowsNull();
            })
            ->map(fn($property) => $property->getName())
            ->toArray();
    }

    public function getOptionalFields(): array 
    {
        $reflection = new ReflectionClass($this);
        return collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
            ->filter(function(ReflectionProperty $property) {
                $type = $property->getType();
                return $type && $type->allowsNull();
            })
            ->map(fn($property) => $property->getName())
            ->toArray();
    }
    
    public function makeValidatorRules(): array
    {
        $rules = [];
        foreach (collect(self::FIELDS)->where('required', true) as $field) {
            $rules[$field['name']] = 'required';
        }
        return $rules;
    }

    public function makeValidationData(): array
    {
        $data = [];
        foreach (collect(self::FIELDS)->where('required', true) as $field) {
            $data[$field['name']] = $this->{$field['name']};
        }
        return $data;
    }

    public function validate(): bool
    {
        $validator = Validator::make($this->makeValidationData(), $this->makeValidatorRules());
        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
        return true;
    }

    public function save($file = null, $filepath = null)
    {
        $data = $this->makeApiData(file: $file, filepath: $filepath);
        $this->validate();
        $response = d3RestLaravelFacade::pushDocument($data);
        if($response->success) {
            $this->id = $response->id;
        }
        return $response;
    }

    public function fill(array $data): void
    {
        foreach ($this->getAttributeNames() as $attribute) {
            $this->{$attribute} = $data[$attribute] ?? null;
        }        
    }

    public function getAttributeNames(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        
        return array_map(
            fn(ReflectionProperty $property) => $property->getName(),
            $properties
        );
    }

    public function exists(): bool
    {
        return $this->id !== null;
    }
}