<?php

// config for Hwkdo/D3RestLaravel
return [
    'api-key' => env('D3_REST_API_KEY'),
    'api-base-url' => env('D3_REST_API_BASE_URL'),
    'api-dms-url' => env('D3_REST_API_DMS_URL'),
    'api-identity-url' => env('D3_REST_API_IDENTITY_URL'),
    'api-userprofile-url' => env('D3_REST_API_USERPROFILE_URL'),
    'LDAP_DOMAIN_PREFIX' => env('D3_REST_LDAP_DOMAIN_PREFIX'),
    'LDAP_GRUPPE_DOZENTEN' => 'DG_d3_Dozenten',
    'LDAP_GRUPPE_EDV' => 'DG_d3_edv',
    'LDAP_GRUPPE_RECHNUNGEN' => 'DG_d3_Dozenten',
    'LDAP_GRUPPEN_PREFIX_ALT' => 'DG_d3_',
    'LDAP_GRUPPEN_PREFIX_NEU' => 'DG_RE_',
    'USER_MODEL' => env('D3_REST_USER_MODEL','App\Models\User'),
];
