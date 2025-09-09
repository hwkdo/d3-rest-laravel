<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Services\fromApiService;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;

class Handwerksrolle extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;
    #[D3id('1')]
    public ?string $betreff;

    #[D3id('26')]
    public ?string $Verfahrensnummer;

    #[D3id('4')]
    public ?string $Straße;

    #[D3id('13')]
    public ?string $Schlagwort;

    #[D3id('6')]
    public ?string $PLZ;

    #[D3id('5')]
    public ?string $Ort;

    #[D3id('3')]
    public ?string $Name;

    #[D3id('14')]
    public ?array $Matchcode;

    #[D3id('12')]
    public ?string $Löschdatum;

    #[D3id('8')]
    public ?string $Erfassungsdatum;

    #[D3id('2')]
    public string $BetriebsNr;

    #[D3id('1')]
    public string $Belegtyp_HR;

    #[D3id('7')]
    public ?string $Belegdatum;
    
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
            "betreff" => $fromApiService->getBetreff(),
            "Verfahrensnummer" => $fromApiService->getVerfahrensnummer(),
            "Straße" => $fromApiService->getStrasse(),
            "Schlagwort" => $fromApiService->getSchlagwort(),
            "PLZ" => $fromApiService->getPLZ(),
            "Ort" => $fromApiService->getOrt(),
            "Name" => $fromApiService->getName(),
            "Matchcode" => $fromApiService->getMatchcode(),
            "Löschdatum" => $fromApiService->getLoeschdatum(),
            "Erfassungsdatum" => $fromApiService->getErfassungsdatum(),
            "BetriebsNr" => $fromApiService->getBetriebsNr(),
            "Belegtyp_HR" => $fromApiService->getBelegtypHR(),            
            "Belegdatum" => $fromApiService->getBelegdatum(),            
            "doc_type" => DocTypeEnum::Handwerksrolle,
            "filename" => $fromApiService->getFilename(),
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

}