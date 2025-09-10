<?php

namespace Hwkdo\D3RestLaravel;

use Hwkdo\D3RestLaravel\DTO\NewObjectDTO;
use Hwkdo\D3RestLaravel\DTO\TempUploadDTO;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\models\Angebot;
use Hwkdo\D3RestLaravel\models\BenutzerAbwesenheit;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use Hwkdo\D3RestLaravel\models\Bestellschein;
use Hwkdo\D3RestLaravel\models\Handwerksrolle;
use Hwkdo\D3RestLaravel\models\Zahlungsbeleg;
use Hwkdo\D3RestLaravel\models\Bestellvorgang;
use Hwkdo\D3RestLaravel\models\Lieferschein;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class Client extends Eloquent
{

    protected $classes = [
        DocTypeEnum::Bestellschein->value => Bestellschein::class,
        DocTypeEnum::Handwerksrolle->value => Handwerksrolle::class,
        DocTypeEnum::Angebote->value => Angebot::class,
        DocTypeEnum::Zahlungsbeleg->value => Zahlungsbeleg::class,
        DocTypeEnum::Bestellvorgang->value => Bestellvorgang::class,
        DocTypeEnum::Lieferschein->value => Lieferschein::class,
    ];

    public static function getBaseUrl(): string
    {
        return str(config("d3-rest-laravel.api-base-url"))->beforeLast("/")->value();
    }

    public function getDoc($id, $raw = false)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->get(config('d3-rest-laravel.api-dms-url').'o2/'.$id.'/');

        if(!$raw) {
            $data = $response->json();
            $category = collect($data["systemProperties"])->where('id', 'property_category')->first()["value"];
            $class = $this->classes[$category];            
            return $class::fromApi($response->json());                        
        } else {
            return $response->json();
        }        
    }    

    public function sendNote($von, $message, $id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/octet-stream',
        ])->withBody(json_encode(['text' => 'Von '.$von.': '.$message]), 'application/json')->post(config('d3-rest-laravel.api-dms-url').'o2/'.$id.'/n/');
        $url = config('d3-rest-laravel.api-dms-url').'o2/'.$id.'/n/';
        $message = $response->created() ? 'Datei erfolgreich hochgeladen' : json_encode($response->json());
    }

    public function temporaryUpload($filepath = null, $file = null)
    {
        if(is_null($file) && is_null($filepath)) {
            throw new \Exception('Datei oder Pfad nicht angegeben');
        }
        elseif(!is_null($file) && !is_null($filepath)) {
            throw new \Exception('Entweder Datei oder Pfad muss angegeben werden, nicht beide');
        }
        elseif(!is_null($filepath)) {
            $file = File::get($filepath);
        }
        
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/octet-stream',
        ])->withBody($file, 'application/octet-stream')->post(config('d3-rest-laravel.api-dms-url').'blob/chunk/');
                        
        $message = $response->created() ? 'Datei erfolgreich hochgeladen' : json_encode($response->json());
        return new TempUploadDTO(
            success: $response->created(),
            message: $message,
            filename: $response->getHeader('MASTER-FILE-NAME')[0] ?? null,
            location: $response->getHeader('Location')[0] ?? null
        );        
    }

    public function pushDocument($data)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->attach('data', json_encode($data))->post(config('d3-rest-laravel.api-dms-url').'o2/');

        $message = $response->created() ? 'Dokument erfolgreich erstellt' : json_encode($response->json());
        return new NewObjectDTO(
            success: $response->created(),
            message: $message,
            location: $response->getHeader('Location')[0] ?? null,
            id: str($response->getHeader('Location')[0] ?? null)->afterLast('/')->value() ?? null
        );
    }
    

    public function SearchResult($fulltext = null, DocTypeEnum $doc_type = null, $children_of = null, $page_size = 200, $raw = false)
    {
        $url = config('d3-rest-laravel.api-dms-url').'sr?fulltext='.$fulltext;
        if($doc_type)
        {
            $url .= '&objectdefinitionids=['.$doc_type->value.']';
        }
        if($page_size)
        {
            $url .= '&pagesize='.$page_size;
        }
        if($children_of)
        {
            $url .= '&children_of='.$children_of;
        }
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->get($url);        
        
        return $raw ? $response->json() : collect($response->json()['items'])->map(function($item) {
            if (DocTypeEnum::tryFrom($item["category"]["id"])) {
                $class = $this->classes[$item["category"]["id"]];            
                return $class::fromApi($item);
            }            
        })->filter();
    }        

    public function getUserAbsence($user_id, $raw = false)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->get(config('d3-rest-laravel.api-userprofile-url').'absence?userId='.$user_id);

        return $raw ? $response->json() : new BenutzerAbwesenheit($response->json());
    }

    public function setUserAbsence($username, $vertretung_username, $text, $start_date, $end_date, $raw = false)
    {
        $userId = $this->getUserIdByUsername($username);
        $data = [
            'absenceText' => $text,
            'deputyId' => $this->getUserIdByUsername($vertretung_username),
            'endDateTime' => $end_date,
            'startDateTime' => $start_date,
            'userId' => $userId,
            'isAbsent' => true,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->post(config('d3-rest-laravel.api-userprofile-url').'absence?isAdmin=true&isOwnUser=false', $data);
                
        return $raw ? $response->json() : $this->getUserAbsence($userId, $raw);
    }

    public function unsetUserAbsence($username, $raw = false)
    {
        $userId = $this->getUserIdByUsername($username);
        $data = [
            'isAbsent' => false,
            'userId' => $userId,
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->post(config('d3-rest-laravel.api-userprofile-url').'absence?isAdmin=true&isOwnUser=false', $data);
                    
        return $raw ? $response->json() : $this->getUserAbsence($userId, $raw);
    }

    public function getUsers()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->get(config('d3-rest-laravel.api-identity-url').'users');

        return $response->json();
    }

    public function getUserIdByUsername($username)
    {
        return collect($this->getUsers()['resources'])->firstWhere('userName', config('d3-rest-laravel.LDAP_DOMAIN_PREFIX').'\\'.$username)['id'];
    }

    public function getUsernameByUserId($user_id)
    {
        $username = collect($this->getUsers()['resources'])->firstWhere('id', $user_id)['userName'];
        return str($username)->after(config('d3-rest-laravel.LDAP_DOMAIN_PREFIX').'\\')->value();
    }
}