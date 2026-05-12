<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deep Link Configurations
    |--------------------------------------------------------------------------
    */

    'applinks_url_prefix' => env('APPLINKS_PREFIX', 'applinks/*'),
    'enable_ios' => env('APPLINKS_IOS', true),
    'enable_web' => env('APPLINKS_WEB', true),
    'web_redirect_url' => env('APPLINKS_WEB_URL', ''),
    'associated_domain' => env('APPLINKS_DOMAIN', ''),

    /*
    |--------------------------------------------------------------------------
    | Android
    |--------------------------------------------------------------------------
    */

    'enable_android' => env('APPLINKS_ANDROID', true),
    'android_package_name' => env('ANDROID_PACKAGE', 'com.example.app'),
    'android_sha256_cert_fingerprints' => [
        env('ANDROID_SHA256_CERT_FINGERPRINT', ""),
    ],
    'android_playstore_url' => env('ANDROID_PLAYSTORE_URL', 'market://details?id=com.example.app'),

    /*
    |--------------------------------------------------------------------------
    | iOS
    |--------------------------------------------------------------------------
    */

    'ios_package_name' => env('IOS_PACKAGE', 'com.example.app'),
    'ios_app_custom_link' => env('IOS_APP_CUSTOM_LINK', ''), // com.example.app://applinks
    'ios_team_id' => env('IOS_TEAM_ID', ''),
    'ios_app_store_url' => env('IOS_APPSTORE_URL', ''),

];