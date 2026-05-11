<?php

namespace Hwkdo\D3RestLaravel;

use Hwkdo\D3RestLaravel\DTO\NewObjectDTO;
use Hwkdo\D3RestLaravel\DTO\TempUploadDTO;
use Hwkdo\D3RestLaravel\Enums\DocTypeEnum;
use Hwkdo\D3RestLaravel\models\Angebot;
use Hwkdo\D3RestLaravel\models\BenutzerAbwesenheit;
use Hwkdo\D3RestLaravel\models\Bestellschein;
use Hwkdo\D3RestLaravel\models\Bestellvorgang;
use Hwkdo\D3RestLaravel\models\Handwerksrolle;
use Hwkdo\D3RestLaravel\models\HandwerksrolleOnline;
use Hwkdo\D3RestLaravel\models\Lieferschein;
use Hwkdo\D3RestLaravel\models\Zahlungsbeleg;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SoapClient;
use SoapFault;

class Client extends Eloquent
{
    protected $classes = [
        DocTypeEnum::Bestellschein->value => Bestellschein::class,
        DocTypeEnum::Handwerksrolle->value => Handwerksrolle::class,
        DocTypeEnum::Angebote->value => Angebot::class,
        DocTypeEnum::Zahlungsbeleg->value => Zahlungsbeleg::class,
        DocTypeEnum::Bestellvorgang->value => Bestellvorgang::class,
        DocTypeEnum::Lieferschein->value => Lieferschein::class,
        DocTypeEnum::HandwerksrolleOnline->value => HandwerksrolleOnline::class,
    ];

    public static function getBaseUrl(): string
    {
        return str(config('d3-rest-laravel.api-base-url'))->beforeLast('/')->value();
    }

    public function getDoc($id, $raw = false)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->get(config('d3-rest-laravel.api-dms-url').'o2/'.$id.'/');

        if (! $raw) {
            $data = $response->json();
            $category = collect($data['systemProperties'])->where('id', 'property_category')->first()['value'];
            $class = $this->classes[$category];

            return $class::fromApi($response->json());
        } else {
            return $response->json();
        }
    }

    public function getD3OneObjectUrl(string $id): string
    {
        $template = trim((string) config('d3-rest-laravel.d3one-object-url-template', ''));
        if ($template !== '') {
            return str_replace('{id}', $id, $template);
        }

        return rtrim((string) config('d3-rest-laravel.api-dms-url'), '/').'/o2/'.$id.'/';
    }

    /**
     * Delete a DMS object.
     *
     * d.velop Open API: DELETE r/{repositoryId}/o2m/{dmsObjectId}
     *
     * @see https://help.d-velop.de/dev/documentation/dms-app#tag/dmsobjects/delete/r/{repositoryId}/o2m/{dmsObjectId}
     *
     * @param  string  $id  DMS object id (dmsObjectId)
     * @param  bool  $raw  When true, returns decoded JSON body; when false, returns whether the request was successful
     * @return bool|array|null
     */
    public function deleteDoc($id, $raw = false)
    {
        $url = $this->resolveDmsObjectDeleteUrl((string) $id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->delete($url);

        if ($raw) {
            return $response->json();
        }

        return $response->successful();
    }

    /**
     * Build DELETE URL for a DMS object (Open API o2m) or legacy o2 path.
     */
    protected function resolveDmsObjectDeleteUrl(string $dmsObjectId): string
    {
        $base = rtrim((string) config('d3-rest-laravel.api-dms-url'), '/');

        if (! (bool) config('d3-rest-laravel.dms_delete_uses_o2m_api', true)) {
            return $base.'/o2/'.$dmsObjectId.'/';
        }

        $pathSuffix = '/o2m/'.$dmsObjectId.'/';

        if (preg_match('#/r/[a-f0-9-]{36}$#i', $base)) {
            return $base.$pathSuffix;
        }

        $repositoryId = config('d3-rest-laravel.repository-id');
        if (is_string($repositoryId) && $repositoryId !== '') {
            return $base.'/r/'.$repositoryId.$pathSuffix;
        }

        return $base.$pathSuffix;
    }

    /**
     * „Quasi-Löschen“: Dokumentenart ohne Mapping auf den Papierkorb-Typ setzen (wie d.3 Web-Client: o2, kein o2m).
     *
     * Request: PUT {api-dms-url}o2/{dmsObjectId} mit multipart/form-data, Feld „data“ = JSON (wie pushDocument).
     * Dazu werden aktuelle Metadaten per {@see getDoc()} geladen (u. a. eTag, lockToken), anschließend objectDefinitionId gesetzt.
     *
     * @param  string  $id  dmsObjectId
     * @param  string|null  $targetObjectDefinitionId  Ziel-Dokumentenart (interner Code, z. B. TEST); Standard config dms_quasi_delete_category_id
     * @param  string|null  $displayValue  Anzeigetext im Ablage-Objekt; Standard config dms_quasi_delete_display_value (null = DMS-Nummer / id)
     * @param  array<string, mixed>|null  $detail  Optional: vorgefülltes GET-o2-JSON (frisches eTag); sonst getDoc($id, true)
     * @param  array<string, mixed>  $additionalPayload  Wird rekursiv in die generierte Payload gemerged (nur Top-Level-Keys)
     * @param  bool  $raw  true: ['successful' => bool, 'status' => int, 'body' => string, 'json' => ?array]
     * @return bool|array<string, mixed>|null
     */
    public function quasiDeleteDoc(
        string $id,
        ?string $targetObjectDefinitionId = null,
        ?string $displayValue = null,
        ?array $detail = null,
        array $additionalPayload = [],
        bool $raw = false
    ): bool|array|null {
        $objectDefinitionId = $targetObjectDefinitionId ?? config('d3-rest-laravel.dms_quasi_delete_category_id');
        if (! is_string($objectDefinitionId) || $objectDefinitionId === '') {
            throw new \InvalidArgumentException('Ziel-Dokumentenart fehlt: D3_REST_QUASI_DELETE_CATEGORY_ID setzen oder Parameter $targetObjectDefinitionId angeben.');
        }

        $detail ??= $this->getDoc($id, true);
        if (! is_array($detail)) {
            throw new \InvalidArgumentException('DMS-Details konnten nicht geladen werden (ungültige Antwort).');
        }

        $payload = $this->buildO2MultipartUpdatePayloadForQuasiDelete(
            $detail,
            $objectDefinitionId,
            $displayValue ?? config('d3-rest-laravel.dms_quasi_delete_display_value')
        );

        if ($additionalPayload !== []) {
            $payload = array_replace_recursive($payload, $additionalPayload);
        }

        $url = $this->resolveO2ObjectPutUrl($id);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->attach('data', json_encode($payload))->put($url);

        if ($raw) {
            return [
                'successful' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ];
        }

        return $response->successful();
    }

    /**
     * PUT-Ziel-URL für ein bestehendes DMS-Objekt (ohne Mapping): …/o2/{dmsObjectId}
     */
    protected function resolveO2ObjectPutUrl(string $dmsObjectId): string
    {
        return rtrim((string) config('d3-rest-laravel.api-dms-url'), '/').'/o2/'.$dmsObjectId;
    }

    /**
     * @param  array<string, mixed>  $detail  Rohe GET-Antwort o2/{id}/
     * @return array<string, mixed>
     */
    protected function buildO2MultipartUpdatePayloadForQuasiDelete(array $detail, string $objectDefinitionId, ?string $displayValue): array
    {
        $dmsObjectId = $detail['id'] ?? null;
        if (! is_string($dmsObjectId) || $dmsObjectId === '') {
            throw new \InvalidArgumentException('DMS-Detail enthält keine id.');
        }

        $systemFlat = $this->flattenO2SystemPropertiesForUpdate($detail['systemProperties'] ?? []);

        $caption = $displayValue;
        if ($caption === null || $caption === '') {
            $caption = $systemFlat['property_document_number'] ?? $dmsObjectId;
        }

        $remarks = [];
        for ($i = 1; $i <= 4; $i++) {
            $key = 'property_remark'.$i;
            $remarks[(string) $i] = array_key_exists($key, $systemFlat) ? (string) $systemFlat[$key] : '';
        }

        $extended = $this->buildO2ExtendedPropertiesForQuasiDelete($detail);
        $multivalue = $this->buildO2MultivalueExtendedPropertiesForQuasiDelete($detail);

        $selfHref = $detail['_links']['self']['href'] ?? null;
        if (! is_string($selfHref) || $selfHref === '') {
            $repoPath = parse_url(rtrim((string) config('d3-rest-laravel.api-dms-url'), '/'), PHP_URL_PATH);
            $selfHref = (is_string($repoPath) && $repoPath !== '')
                ? $repoPath.'/o2/'.$dmsObjectId
                : '/o2/'.$dmsObjectId;
        }

        $lockTokenUrl = $detail['_links']['lockToken']['href'] ?? null;
        if (! is_string($lockTokenUrl) || $lockTokenUrl === '') {
            throw new \InvalidArgumentException('DMS-Detail enthält keine _links.lockToken.href (Lock für Update erforderlich).');
        }

        $eTag = $detail['eTag'] ?? null;
        if (! is_string($eTag) || $eTag === '') {
            throw new \InvalidArgumentException('DMS-Detail enthält kein eTag (für Update erforderlich).');
        }

        $filename = $systemFlat['property_filename'] ?? '';

        return [
            'type' => 1,
            'objectDefinitionId' => $objectDefinitionId,
            'systemProperties' => $systemFlat,
            'remarks' => $remarks,
            'multivalueExtendedProperties' => empty($multivalue) ? new \stdClass : $multivalue,
            'extendedProperties' => empty($extended) ? new \stdClass : $extended,
            'docNumber' => $dmsObjectId,
            'id' => $dmsObjectId,
            'storeObject' => [
                'displayValue' => $caption,
                'filename' => $filename,
                'dmsObjectId' => $dmsObjectId,
                'dmsobject' => [
                    'href' => $selfHref,
                    'id' => $dmsObjectId,
                ],
                'doMapping' => false,
                'isInUpdateMode' => true,
                'doValidate' => false,
                'eTag' => $eTag,
                'lockTokenUrl' => $lockTokenUrl,
                'fileSelect' => false,
                'id' => 0,
                '_links' => new \stdClass,
                '_embedded' => new \stdClass,
            ],
            'state' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $systemProperties
     * @return array<string, scalar|null>
     */
    protected function flattenO2SystemPropertiesForUpdate(array $systemProperties): array
    {
        $out = [];
        foreach ($systemProperties as $prop) {
            if (! is_array($prop) || ! isset($prop['id'])) {
                continue;
            }
            $id = (string) $prop['id'];
            if (! str_starts_with($id, 'property_')) {
                continue;
            }
            if ($id === 'property_category') {
                continue;
            }
            $val = $prop['value'] ?? null;
            $out[$id] = $id === 'property_state'
                ? $this->normalizeO2PropertyStateForStoreApi($val)
                : $val;
        }

        return $out;
    }

    /**
     * o2-Store/Update erwartet Kurzcodes Be/Pr/Fr/Ar; GET liefert oft „Processing“, „Released“ o. Ä.
     */
    protected function normalizeO2PropertyStateForStoreApi(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Fr';
        }

        $raw = is_string($value) ? trim($value) : (string) $value;

        foreach (['Be', 'Pr', 'Fr', 'Ar'] as $code) {
            if (strcasecmp($raw, $code) === 0) {
                return $code;
            }
        }

        return match (strtolower($raw)) {
            'processing', 'bearbeitung' => 'Be',
            'verification', 'prüfung', 'pruefung' => 'Pr',
            'release', 'released', 'freigabe' => 'Fr',
            'archive', 'archiv' => 'Ar',
            default => 'Fr',
        };
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    protected function buildO2ExtendedPropertiesForQuasiDelete(array $detail): array
    {
        $allowed = config('d3-rest-laravel.dms_quasi_delete_extended_property_ids');
        $extended = [];
        foreach ($detail['objectProperties'] ?? [] as $prop) {
            if (! is_array($prop) || ! isset($prop['id'])) {
                continue;
            }
            if (($prop['isMultivalue'] ?? false) === true) {
                continue;
            }
            $pid = (string) $prop['id'];
            if (is_array($allowed) && $allowed !== [] && ! in_array($pid, $allowed, true)) {
                continue;
            }
            $extended[$pid] = $prop['value'] ?? null;
        }

        return $extended;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, array<int|string, mixed>>
     */
    protected function buildO2MultivalueExtendedPropertiesForQuasiDelete(array $detail): array
    {
        if (! (bool) config('d3-rest-laravel.dms_quasi_delete_preserve_multivalues', false)) {
            return [];
        }
        $out = [];
        foreach ($detail['multivalueProperties'] ?? [] as $mv) {
            if (! is_array($mv) || ! isset($mv['id'], $mv['values']) || ! is_array($mv['values'])) {
                continue;
            }
            $out[(string) $mv['id']] = $mv['values'];
        }

        return $out;
    }

    public function downloadDoc($id, $target_filepath = null)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/octet-stream',
        ])->sink($target_filepath)->get(config('d3-rest-laravel.api-dms-url').'o2/'.$id.'/v/current/b/main/c');

        return $response->successful();
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
        if (is_null($file) && is_null($filepath)) {
            throw new \Exception('Datei oder Pfad nicht angegeben');
        } elseif (! is_null($file) && ! is_null($filepath)) {
            throw new \Exception('Entweder Datei oder Pfad muss angegeben werden, nicht beide');
        } elseif (! is_null($filepath)) {
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

    public function SearchResult($fulltext = null, ?DocTypeEnum $doc_type = null, $children_of = null, $page_size = 200, $raw = false)
    {
        $url = config('d3-rest-laravel.api-dms-url').'sr?fulltext='.$fulltext;
        if ($doc_type) {
            $url .= '&objectdefinitionids=['.$doc_type->value.']';
        }
        if ($page_size) {
            $url .= '&pagesize='.$page_size;
        }
        if ($children_of) {
            $url .= '&children_of='.$children_of;
        }
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('d3-rest-laravel.api-key'),
            'Accept' => 'application/json',
        ])->get($url);

        return $raw ? $response->json() : collect($response->json()['items'])->map(function ($item) {
            if (DocTypeEnum::tryFrom($item['category']['id'])) {
                $class = $this->classes[$item['category']['id']];

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

    public function getUserSoap(string $username, bool $raw = false): array
    {
        $response = $this->callSoapMethod('d3.GetUser', [
            'import' => [
                'user' => $username,
                'no_sysuser' => 1,
                'hidden' => 0,
                'inactive' => 0,
            ],
        ]);

        if ($raw) {
            return $response;
        }

        $item = data_get($response, 'table.item');

        return is_array($item) ? $item : (array) $item;
    }

    public function getUserInGroupsSoap(string $username, bool $raw = false): array
    {
        $response = $this->callSoapMethod('d3.GetUserInGroup', [
            'import' => [
                'user' => $username,
            ],
        ]);

        if ($raw) {
            return $response;
        }

        $rows = data_get($response, 'table.item');
        if ($rows === null) {
            return [];
        }

        if (is_object($rows) && property_exists($rows, 'usergroup')) {
            return [(string) $rows->usergroup];
        }

        return collect(is_array($rows) ? $rows : [])
            ->map(function ($row) {
                if (is_object($row) && property_exists($row, 'usergroup')) {
                    return (string) $row->usergroup;
                }

                if (is_array($row) && isset($row['usergroup'])) {
                    return (string) $row['usergroup'];
                }

                return null;
            })
            ->filter(fn ($group) => is_string($group) && $group !== '')
            ->values()
            ->all();
    }

    public function getUserInGroupsSoapCached(string $username, ?int $ttlSeconds = null): array
    {
        $normalizedUsername = strtolower(trim($username));
        if ($normalizedUsername === '') {
            return [];
        }

        $cacheKey = 'd3rest:soap:user-groups:'.$normalizedUsername;

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($this->normalizeCacheTtlSeconds($ttlSeconds)),
            fn (): array => $this->getUserInGroupsSoap($normalizedUsername)
        );
    }

    public function getD3GroupsSoap(bool $raw = false): array
    {
        $response = $this->callSoapMethod('d3.GetUserGroup', [
            'import' => [],
        ]);

        if ($raw) {
            return $response;
        }

        $rows = data_get($response, 'table.item');
        if ($rows === null) {
            return [];
        }

        if (is_object($rows) && property_exists($rows, 'usergroup')) {
            return [(string) $rows->usergroup];
        }

        return collect(is_array($rows) ? $rows : [])
            ->map(function ($row) {
                if (is_object($row) && property_exists($row, 'usergroup')) {
                    return (string) $row->usergroup;
                }

                if (is_array($row) && isset($row['usergroup'])) {
                    return (string) $row['usergroup'];
                }

                return null;
            })
            ->filter(fn ($group) => is_string($group) && $group !== '')
            ->values()
            ->all();
    }

    public function getD3GroupsSoapCached(?int $ttlSeconds = null): array
    {
        $cacheKey = 'd3rest:soap:all-groups';

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($this->normalizeCacheTtlSeconds($ttlSeconds)),
            fn (): array => $this->getD3GroupsSoap()
        );
    }

    protected function callSoapMethod(string $method, array $payload): array
    {
        if (! config('d3-rest-laravel.soap-enabled', false)) {
            throw new RuntimeException('SOAP ist nicht aktiviert. Setze D3_REST_SOAP_ENABLED=true.');
        }

        $wsdl = (string) config('d3-rest-laravel.soap-wsdl', '');
        if ($wsdl === '') {
            throw new RuntimeException('SOAP WSDL fehlt. Setze D3_REST_SOAP_WSDL.');
        }

        $client = $this->makeSoapClient();
        $methodsToTry = collect([
            $method,
            str_contains($method, '.') ? (string) str($method)->afterLast('.') : null,
        ])->filter()->unique()->values()->all();

        $lastException = null;
        $response = null;

        $requestPayload = $this->buildSoapRequestPayload($payload);

        foreach ($methodsToTry as $candidate) {
            try {
                $response = $client->__soapCall($candidate, [$requestPayload]);
                break;
            } catch (SoapFault $exception) {
                $lastException = $exception;
            }
        }

        if ($response === null) {
            throw new RuntimeException(
                'SOAP-Aufruf fehlgeschlagen: '.($lastException?->getMessage() ?? 'Unbekannter SOAP-Fehler'),
                previous: $lastException
            );
        }

        $normalized = json_decode(json_encode($response), true);
        if (! is_array($normalized)) {
            throw new RuntimeException('SOAP-Antwort ist nicht auswertbar.');
        }

        $returnCode = (int) ($normalized['ReturnCode'] ?? 1);
        if ($returnCode !== 0) {
            $returnMessage = (string) ($normalized['ReturnMessage'] ?? 'Unbekannter SOAP-Fehler');
            throw new RuntimeException($returnMessage);
        }

        return $normalized;
    }

    protected function buildSoapRequestPayload(array $payload): array
    {
        $username = (string) config('d3-rest-laravel.soap-username', '');
        $password = (string) config('d3-rest-laravel.soap-password', '');
        $import = $payload['import'] ?? $payload;

        return [
            'archiv' => [
                'IpAddr' => (string) config('d3-rest-laravel.soap-dms-ip-addr', ''),
                'Server' => (string) config('d3-rest-laravel.soap-archive-server', 'T'),
                'User' => $username,
                'Password' => $password,
                'Language' => (string) config('d3-rest-laravel.soap-language', 'de'),
            ],
            'WSDownloadFormat' => new \stdClass,
            'import' => is_array($import) ? $import : [],
        ];
    }

    protected function makeSoapClient(): SoapClient
    {
        $username = (string) config('d3-rest-laravel.soap-username', '');
        $password = (string) config('d3-rest-laravel.soap-password', '');

        return new SoapClient((string) config('d3-rest-laravel.soap-wsdl'), [
            'exceptions' => true,
            'trace' => false,
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'connection_timeout' => (int) config('d3-rest-laravel.soap-timeout', 10),
            'login' => $username !== '' ? $username : null,
            'password' => $password !== '' ? $password : null,
        ]);
    }

    protected function normalizeCacheTtlSeconds(?int $ttlSeconds): int
    {
        if (is_int($ttlSeconds) && $ttlSeconds > 0) {
            return $ttlSeconds;
        }

        return 60 * 60 * 24;
    }
}
