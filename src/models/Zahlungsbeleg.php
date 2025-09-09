<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Services\fromApiService;
use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;

class Zahlungsbeleg extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;
    #[D3id('79')]
    public array $Benutzer;

    #[D3id('80')]
    public array $Abteilung;       

    #[D3id('82')]
    public string $Belegtyp_ZB;   

    #[D3id('60')]
    public string $Rechnungsnummer; 

    #[D3id('84')]
    public string $Nettobetrag;

    #[D3id('85')]
    public string $Bruttobetrag;

    #[D3id('86')]
    public string $Mehrwertsteuerbetrag;

    #[D3id('64')]
    public string $Lieferantennummer;

    #[D3id('65')]
    public string $Lieferantenname;

    #[D3id('7')]
    public ?string $Belegdatum;       

    #[D3id('83')]
    public string $WFL_Empfaenger;
    
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
            "Benutzer" => $fromApiService->getBenutzer(),
            "Abteilung" => $fromApiService->getAbteilung(),
            "Belegtyp_ZB" => $fromApiService->getBelegtypZB(),
            "Rechnungsnummer" => $fromApiService->getRechnungsnummer(),
            "Nettobetrag" => $fromApiService->getNettobetrag(),
            "Bruttobetrag" => $fromApiService->getBruttobetrag(),
            "Mehrwertsteuerbetrag" => $fromApiService->getMehrwertsteuerbetrag(),
            "Lieferantennummer" => $fromApiService->getLieferantennummer(),            
            "Lieferantenname" => $fromApiService->getLieferantenname(),            
            "Belegdatum" => $fromApiService->getBelegdatum(),            
            "WFL_Empfaenger" => $fromApiService->getWFL_Empfaenger(),
            "filename" => $fromApiService->getFilename(),
            "doc_type" => DocTypeEnum::Angebote,
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

}
