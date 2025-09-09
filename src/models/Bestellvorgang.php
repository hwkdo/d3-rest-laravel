<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;
use Hwkdo\D3RestLaravel\d3RestLaravelFacade;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Services\fromApiService;


class Bestellvorgang extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;
    
    #[D3id('18')]
    public int $Nummer;        
    
    #[D3id('65')]
    public ?string $lieferantenName;

    public DocTypeEnum $doc_type;
    
    #[D3SystemProperty('property_filename')]
    public string $filename;        

    public function makeApiData($file = null, $filepath = null): array
    {
        $data = $this->getBaseApiData($this->getFilledProperties());
        $data["objectDefinitionId"] = $this->doc_type->value;                       
        $data["masterFileName"] = d3RestLaravelFacade::temporaryUpload(file: $file, filepath: $filepath)->filename;
        return $data;
    }
    
    

    public static function fromApi(array $data): self
    {
        $fromApiService = new fromApiService($data);
        
        return new self([
            "id" => $fromApiService->getId(),
            "Nummer" => $fromApiService->getNummer(),
            "lieferantenName" => $fromApiService->getLieferantenName(),            
            "doc_type" => DocTypeEnum::Bestellvorgang,
            "filename" => $fromApiService->getFilename(),
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

}