<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deep Link Configurations
    |--------------------------------------------------------------------------
    */

    'applinks_url_prefix' => env('APPLINKS_PREFIX', 'applinks/*'),
    'enable_ios' => env('APPLINKS_IOS', true),
    'enable_android' => env('APPLINKS_ANDROID', true),
    'enable_web' => env('APPLINKS_WEB', true),
    'web_redirect_url' => env('APPLINKS_WEB_URL', ''),
    'associated_domain' => env('APPLINKS_DOMAIN', 'https://www.actsnet.org/'),

    /*
    |--------------------------------------------------------------------------
    | Android Configurations
    |--------------------------------------------------------------------------
    */

    'android_package_name' => env('ANDROID_PACKAGE', 'org.actsnet.home'),
    'android_sha256_cert_fingerprints' => [
        '70:50:2F:71:41:DE:B4:7B:BA:6D:FC:80:08:9E:88:06:A6:2B:0F:FD:36:92:BF:BA:6E:2F:3D:F0:1D:4E:4B:A1',
        '51:23:06:47:80:49:88:B5:C8:F0:FF:B7:D1:0F:64:EC:D1:22:39:1C:70:52:5A:B7:B7:AA:5C:1D:81:AB:FB:E6',
    ],
    'android_playstore_url' => env('ANDROID_PLAYSTORE_URL', 'market://details?id=org.actsnet.home'),

    /*
    |--------------------------------------------------------------------------
    | iOS Configurations
    |--------------------------------------------------------------------------
    */

    'ios_package_name' => env('IOS_PACKAGE', 'org.actsnet.home'),
    'ios_app_custom_link' => env('IOS_APP_CUSTOM_LINK', 'org.actsnet.home://applinks'),
    'ios_team_id' => env('IOS_TEAM_ID', 'US9GF4T232'),
    'ios_app_store_url' => env('IOS_APPSTORE_URL', 'https://apps.apple.com/us/app/acts-mobile/id1456736511'),

];
