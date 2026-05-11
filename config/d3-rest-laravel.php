<?php

// config for Hwkdo/D3RestLaravel
return [
    'log_bestellschein_push' => (bool) env('D3_REST_LOG_BESTELLSCHEIN_PUSH', true),
    'log_http_traffic' => (bool) env('D3_REST_LOG_HTTP_TRAFFIC', false),

    'dms_delete_uses_o2m_api' => (bool) env('D3_REST_DMS_DELETE_USES_O2M', true),
    'repository-id' => env('D3_REST_REPOSITORY_ID'),

    'dms_quasi_delete_category_id' => env('D3_REST_QUASI_DELETE_CATEGORY_ID', 'TEST'),

    'dms_quasi_delete_display_value' => env('D3_REST_QUASI_DELETE_DISPLAY_VALUE') ?: null,

    'dms_quasi_delete_extended_property_ids' => ($ids = (string) env('D3_REST_QUASI_DELETE_EXTENDED_IDS', '')) !== ''
        ? array_values(array_filter(array_map('trim', explode(',', $ids))))
        : null,

    'dms_quasi_delete_preserve_multivalues' => (bool) env('D3_REST_QUASI_DELETE_PRESERVE_MULTIVALUES', false),

    'api-key' => env('D3_REST_API_KEY'),
    'api-base-url' => env('D3_REST_API_BASE_URL'),
    'api-dms-url' => env('D3_REST_API_DMS_URL'),
    'd3one-object-url-template' => env('D3_REST_D3ONE_OBJECT_URL_TEMPLATE'),
    'dms-search-url' => env('D3_REST_DMS_SEARCH_URL', 'https://d3one.hwk-do.de/dms/r/254733d1-1130-5cad-becd-6ca766c084d6/sr/?fulltext='),
    'api-identity-url' => env('D3_REST_API_IDENTITY_URL'),
    'api-userprofile-url' => env('D3_REST_API_USERPROFILE_URL'),
    'soap-enabled' => (bool) env('D3_REST_SOAP_ENABLED', false),
    'soap-wsdl' => env('D3_REST_SOAP_WSDL', dirname(__DIR__).'/resources/wsdl/D3WServiceGen.wsdl'),
    'soap-username' => env('D3_REST_SOAP_USERNAME'),
    'soap-password' => env('D3_REST_SOAP_PASSWORD'),
    'soap-dms-ip-addr' => env('D3_REST_SOAP_DMS_IP_ADDR'),
    'soap-archive-server' => env('D3_REST_SOAP_ARCHIVE_SERVER', 'T'),
    'soap-language' => env('D3_REST_SOAP_LANGUAGE', 'de'),
    'soap-timeout' => (int) env('D3_REST_SOAP_TIMEOUT', 10),
    'LDAP_DOMAIN_PREFIX' => env('D3_REST_LDAP_DOMAIN_PREFIX'),
    'LDAP_GRUPPE_DOZENTEN' => 'DG_d3_Dozenten',
    'LDAP_GRUPPE_EDV' => 'DG_d3_edv',
    'LDAP_GRUPPE_RECHNUNGEN' => 'DG_d3_Dozenten',
    'LDAP_GRUPPEN_PREFIX_ALT' => 'DG_d3_',
    'LDAP_GRUPPEN_PREFIX_NEU' => 'DG_RE_',
    'USER_MODEL' => env('D3_REST_USER_MODEL','App\Models\User'),
];
