<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;
use Hwkdo\D3RestLaravel\d3RestLaravelFacade;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Services\fromApiService;


class Bestellschein extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;
    
    #[D3id('18')]
    public int $nummer;

    #[D3id('65')]
    public ?string $lieferantenName;

    #[D3id('62')]
    public ?string $lieferantenSuchfeld;

    #[D3id('67')]
    public ?string $lieferantenPlz;

    #[D3id('68')]
    public ?string $lieferantenOrt;

    #[D3id('63')]
    public ?string $lieferantenAuswahl;

    #[D3id('219')]
    public int $kostenstelle;

    #[D3id('92')]
    public int $haushaltsjahr;    

    #[D3id('8')]
    public ?string $erfassungsdatum;    

    #[D3id('94')]
    public ?int $bueBelegnummer;    

    #[D3id('1')]
    public ?string $betreff;

    #[D3id('79')]
    public array $benutzer;

    #[D3id('7')]
    public ?string $belegdatum;    

    #[D3id('80')]
    public array $abteilung;
    
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
            "nummer" => $fromApiService->getNummer(),
            "lieferantenSuchfeld" => $fromApiService->getLieferantenSuchfeld(),
            "lieferantenPlz" => $fromApiService->getLieferantenPlz(),
            "lieferantenOrt" => $fromApiService->getLieferantenOrt(),
            "lieferantenName" => $fromApiService->getLieferantenName(),
            "lieferantenAuswahl" => $fromApiService->getLieferantenAuswahl(),
            "kostenstelle" => $fromApiService->getKostenstelle(),
            "haushaltsjahr" => $fromApiService->getHaushaltsjahr(),
            "erfassungsdatum" => $fromApiService->getErfassungsdatum(),
            "bueBelegnummer" => $fromApiService->getBueBelegnummer(),
            "betreff" => $fromApiService->getBetreff(),
            "benutzer" => $fromApiService->getBenutzer(),
            "belegdatum" => $fromApiService->getBelegdatum(),
            "abteilung" => $fromApiService->getAbteilung(),
            "doc_type" => DocTypeEnum::Bestellschein,
            "filename" => $fromApiService->getFilename(),
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

}