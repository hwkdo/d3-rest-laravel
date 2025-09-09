<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;
use Hwkdo\D3RestLaravel\d3RestLaravelFacade;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Services\fromApiService;


class Lieferschein extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;
    #[D3id('1')]
    public ?string $betreff;    

    #[D3id('18')]
    public int $Nummer;    

    #[D3id('217')]
    public string $Lieferscheinnummer;    
    
    #[D3id('62')]
    public ?string $lieferantenSuchfeld;

    #[D3id('64')]
    public ?string $Lieferantennummer;

    #[D3id('65')]
    public ?string $lieferantenName;

    #[D3id('63')]
    public ?string $lieferantenAuswahl;
    
    #[D3id('92')]
    public ?int $haushaltsjahr; 

    #[D3id('8')]
    public ?string $Erfassungsdatum;   
    
    #[D3id('94')]
    public ?int $bueBelegnummer; 

    #[D3id('79')]
    public array $Benutzer;

    #[D3id('7')]
    public ?string $Belegdatum;        
    
    #[D3id('80')]
    public array $Abteilung;        
    
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
            "betreff" => $fromApiService->getBetreff(),
            "Nummer" => $fromApiService->getNummer(),
            "Lieferscheinnummer" => $fromApiService->getLieferscheinnummer(),
            "lieferantenSuchfeld" => $fromApiService->getLieferantenSuchfeld(),
            "Lieferantennummer" => $fromApiService->getLieferantennummer(),
            "lieferantenName" => $fromApiService->getLieferantenName(),
            "lieferantenAuswahl" => $fromApiService->getLieferantenAuswahl(),
            "haushaltsjahr" => $fromApiService->getHaushaltsjahr(),
            "Erfassungsdatum" => $fromApiService->getErfassungsdatum(),
            "bueBelegnummer" => $fromApiService->getBueBelegnummer(),
            "Benutzer" => $fromApiService->getBenutzer(),
            "Belegdatum" => $fromApiService->getBelegdatum(),
            "Abteilung" => $fromApiService->getAbteilung(),            
            "doc_type" => DocTypeEnum::Lieferschein,
            "filename" => $fromApiService->getFilename(),
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

}