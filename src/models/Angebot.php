<?php

namespace Hwkdo\D3RestLaravel\models;

use Hwkdo\D3RestLaravel\Attributes\D3id;
use Hwkdo\D3RestLaravel\Attributes\D3SystemProperty;
use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\Interfaces\DokumentInterface;
use Hwkdo\D3RestLaravel\Services\fromApiService;


class Angebot extends Dokument implements DokumentInterface
{

    public ?string $id;
    public ?string $link;
    public ?string $d3one;

    #[D3id('1')]
    public ?string $betreff;        
    
    #[D3id('18')]
    public int $Nummer;    

    #[D3id('8')]
    public ?string $Erfassungsdatum;    

    #[D3id('79')]
    public array $Benutzer;

    #[D3id('7')]
    public ?string $Belegdatum;    

    #[D3id('319')]
    public ?string $Begründung;  # Ja | Nein #Wenn Ja dann ist es eine Ausnahmebegruendung  

    #[D3id('218')]
    public string $Angebotsnummer;
    
    #[D3id('80')]
    public array $Abteilung;        
    
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
            "Nummer" => $fromApiService->getNummer(),
            "Erfassungsdatum" => $fromApiService->getErfassungsdatum(),
            "Benutzer" => $fromApiService->getBenutzer(),
            "Belegdatum" => $fromApiService->getBelegdatum(),
            "Begründung" => $fromApiService->getBegruendung(),
            "Angebotsnummer" => $fromApiService->getAngebotsnummer(),
            "Abteilung" => $fromApiService->getAbteilung(),            
            "doc_type" => DocTypeEnum::Angebote,
            "filename" => $fromApiService->getFilename(),
            "link" => $fromApiService->getLink(),
            "d3one" => $fromApiService->getLink(),
        ]);
    }

    public function angebot_ist_begruendung()
    {
        return $this->Begründung == 'Ja';
    }

}