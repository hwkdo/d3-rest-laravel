<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;
use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Services\fromApiService;


class HandwerksrolleOnline extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;
    
    #[D3id('30')]
    public string $vorgangsnummer;

    #[D3id('15')]
    public ?string $hwro_typ;

    #[D3id('7')]
    public ?string $datum;    
    
    public DocTypeEnum $doc_type;
    
    #[D3SystemProperty('property_filename')]
    public string $filename;     

    

    public function makeApiData($file = null, $filepath = null): array
    {
        $data = $this->getBaseApiData($this->getFilledProperties());
        $data["objectDefinitionId"] = $this->doc_type->value;                       
        $data["masterFileName"] = D3RestLaravel::temporaryUpload(file: $file, filepath: $filepath)->filename;
        return $data;
    }        

    public static function fromApi(array $data): self
    {
        $fromApiService = new fromApiService($data);
        
        return new self([
            "id" => $fromApiService->getId(),
            "vorgangsnummer" => $fromApiService->getHWRO_Vorgangsnummer(),
            "hwro_typ" => $fromApiService->getHWRO_Betreff(),
            "datum" => $fromApiService->getBelegdatum(),
            "doc_type" => DocTypeEnum::HandwerksrolleOnline,
            "filename" => $fromApiService->getFilename(),
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

}