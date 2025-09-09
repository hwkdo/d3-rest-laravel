<?php

namespace Hwkdo\D3RestLaravel\Services;

use Hwkdo\D3RestLaravel\Facades\D3RestLaravel;
use Illuminate\Support\Collection;

class fromApiService
{
    public function __construct(
        protected array $data,
    ) {}

    public function getLink(): string
    {
        return D3RestLaravel::getBaseUrl().$this->data['_links']['details']['href'];
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getNummer(): int
    {
        return (int)$this->getDisplayProperty(18);
    }

    public function getLieferantenSuchfeld(): ?string
    {
        return $this->getDisplayProperty(62);
    }

    public function getLieferantenPlz(): ?string
    {
        return $this->getDisplayProperty(67);
    }

    public function getLieferantenOrt(): ?string
    {
        return $this->getDisplayProperty(68);
    }

    public function getLieferantenName(): string
    {
        return $this->getDisplayProperty(65);
    }

    public function getLieferantenAuswahl(): ?string
    {
        return $this->getDisplayProperty(63);
    }

    public function getKostenstelle(): int
    {
        return (int)$this->getDisplayProperty(219);
    }

    public function getHaushaltsjahr(): int
    {
        return (int)$this->getDisplayProperty(92);
    }

    public function getErfassungsdatum(): ?string
    {
        return $this->getDisplayProperty(8);  
    }

    public function getBueBelegnummer(): ?int
    {
        $data = $this->getDisplayProperty(94);
        return $data ? (int)($data) : null;            
    }

    public function getBetreff(): ?string
    {
        return $this->data['caption'];
    }

    public function getBenutzer(): array
    {
        $benutzer = $this->getDisplayProperty(79);
        if(str($benutzer)->contains(' ...')) {
            $extendedData = D3RestLaravel::getDoc($this->getId(), true);
            return collect($extendedData["multivalueProperties"])->where('id', 79)->first()["values"];
        } else {
            return [$benutzer];
        }
    }

    public function getBelegdatum(): ?string
    {
        return $this->getDisplayProperty(7);
    }

    public function getAbteilung(): array
    {
        $abteilung = $this->getDisplayProperty(80);
        if(str($abteilung)->contains(' ...')) {
            $extendedData = D3RestLaravel::getDoc($this->getId(), true);
            return collect($extendedData["multivalueProperties"])->where('id', 80)->first()["values"];
        } else {
            return [$abteilung];
        }
    }    

    public function getVerfahrensnummer(): ?string
    {
        return $this->getDisplayProperty(26);
    }

    public function getStrasse(): ?string
    {
        return $this->getDisplayProperty(4);
    }

    public function getSchlagwort(): ?string
    {
        return $this->getDisplayProperty(13);
    }

    public function getPLZ(): ?string
    {
        return $this->getDisplayProperty(6);
    }

    public function getOrt(): ?string
    {
        return $this->getDisplayProperty(5);
    }

    public function getName(): ?string
    {
        return $this->getDisplayProperty(3);
    }

    public function getMatchcode(): ?array
    {
        return [$this->getDisplayProperty(14)];
    }

    public function getLoeschdatum(): ?string
    {
        return $this->getDisplayProperty(12);
    }

    public function getBetriebsNr(): ?string
    {
        return $this->getDisplayProperty(2);
    }

    public function getBelegtypHR(): ?string
    {
        return $this->getDisplayProperty(1);
    }

    public function getAngebotsnummer(): ?string
    {
        return $this->getDisplayProperty(218);
    }

    public function getBegruendung(): ?string
    {
        return $this->getDisplayProperty(319);
    }

    public function getBelegtypZB(): ?string
    {
        return $this->getDisplayProperty(82);
    }

    public function getRechnungsnummer(): ?string
    {
        return $this->getDisplayProperty(60);
    }
    
    public function getNettobetrag(): ?string
    {
        return $this->getDisplayProperty(84);
    }

    public function getBruttobetrag(): ?string
    {
        return $this->getDisplayProperty(85);
    }

    public function getMehrwertsteuerbetrag(): ?string
    {
        return $this->getDisplayProperty(86);
    }

    public function getLieferantennummer(): ?string
    {
        return $this->getDisplayProperty(64);
    }    

    public function getWFL_Empfaenger(): ?string
    {
        return $this->getDisplayProperty(83);
    }

    public function getFilename(): ?string
    {
        return $this->getDisplayProperty("property_filename");
    }

    public function getLieferscheinnummer(): ?string
    {
        return $this->getDisplayProperty(217);
    }    

    public function getHWRO_Vorgangsnummer(): ?string
    {
        return $this->getDisplayProperty(30);
    }

    public function getHWRO_Betreff(): ?string
    {
        return $this->getDisplayProperty(15);
    }

    public function data() : Collection
    {
        return collect($this->data);
    }

    public function getDisplayProperties() : Collection
    {        
        return isset($this->data()["displayProperties"]) ? collect($this->data()["displayProperties"]) : collect($this->data()["objectProperties"]);        
    }

    public function getSystemProperties() : Collection
    {
        return collect($this->data()["systemProperties"]);
    }

    public function getDisplayProperty(string $propertyId) : ?string
    {
        return $this->getDisplayProperties()->where('id', $propertyId)->first()['value'] ?? $this->getSystemProperties()->where('id', $propertyId)->first()['value'];
    }
}