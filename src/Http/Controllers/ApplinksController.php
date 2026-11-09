<?php

namespace MrSwapan\Applinks\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ApplinksController extends Controller
{
    private function os(?string $agent): string
    {
        $agent = strtolower($agent ?? '');

        if (str_contains($agent, 'iphone') || str_contains($agent, 'ipad')) {
            return 'IOS';
        }
        if (str_contains($agent, 'android')) {
            return 'ANDROID';
        }
        if (str_contains($agent, 'macintosh')) {
            return 'MACINTOSH';
        }
        if (str_contains($agent, 'windows')) {
            return 'WINDOWS';
        }

        return 'UNKNOWN';
    }

    private function getClientIp(Request $request): string
    {
        return $request->ip() ?? 'UNKNOWN';
    }

    public function assetlinks()
    {
        if (! config('applinks.enable_android', true)) {
            abort(404);
        }

        $packageName = config('applinks.android_package_name');
        $fingerprints = (array) config('applinks.android_sha256_cert_fingerprints', []);

        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => $packageName,
                    'sha256_cert_fingerprints' => array_values($fingerprints),
                ],
            ],
        ]);
    }

    public function apple()
    {
        if (! config('applinks.enable_ios', true)) {
            abort(404);
        }

        $teamId = config('applinks.ios_team_id');
        $packageName = config('applinks.ios_package_name');
        $prefix = config('applinks.applinks_url_prefix', 'applinks/*');
        if (! str_starts_with($prefix, '/')) {
            $prefix = '/' . $prefix;
        }

        $appId = "{$teamId}.{$packageName}";

        return response()->json([
            'applinks' => [
                'details' => [
                    [
                        'appIDs' => [$appId],
                        'components' => [
                            [
                                '/' => $prefix,
                                'comment' => "Accept all links starting with {$prefix}",
                            ],
                        ],
                    ],
                ],
            ],
            'webcredentials' => [
                'apps' => [$appId],
            ],
        ]);
    }

    public function saveDeviceInfo(Request $request)
    {
        session(['APPLINKS_DEVICE_DATA' => $request->query()]);

        return response()->noContent();
    }

    public function jsEditor()
    {
        return response()->make(<<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>JS Editor</title>
</head>
<body>
<textarea id="code" style="width:100%" rows="10" autocomplete="off"></textarea>
<button id="execute" style="padding: 10px;">Execute</button>
<div id="log"></div>

<script>
(function() {
    var oldLog = console.log;
    console.log = function(message) {
        oldLog.apply(console, arguments);
        var logDiv = document.getElementById('log');
        logDiv.innerHTML += message + '<br>';
    };
})();

document.getElementById('execute').addEventListener('click', function() {
    try {
        let code = document.getElementById('code');
        eval(eval(`console.log(${code.value})`));
        code.value = "";
    } catch (error) {
        console.log('error: ', error.message);
    }
});
</script>
</body>
</html>
HTML
        );
    }

    private function saveData(string $type, Request $request, array $device): int
    {
        $ip = $this->getClientIp($request);
        $width = $device['width'] ?? 0;
        $height = $device['height'] ?? 0;
        $resolution = "{$width} x {$height}";

        if ($type === 'ios') {
            DB::table('applinks_data')->where([
                'device_type' => 'ios',
                'resolution' => $resolution,
                'ip_address' => $ip,
            ])->delete();
        }

        return (int) DB::table('applinks_data')->insertGetId([
            'device_type' => $type,
            'resolution' => $resolution,
            'ip_address' => $ip,
            'query_string' => json_encode($request->query()),
            'created_at' => now(),
        ]);
    }

    private function redirectToPlaystore(int $uuid)
    {
        $url = config('applinks.android_playstore_url');
        $packageName = config('applinks.android_package_name');

        if (empty($url)) {
            $url = 'https://play.google.com/store/apps/details?id=' . $packageName;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return redirect($url . $separator . 'referrer=' . $uuid);
    }

    private function redirectToAppstore(Request $request, array $device)
    {
        $url = config('applinks.ios_app_store_url', 'https://apps.apple.com/us/app');
        $iosAppCustomLink = config('applinks.ios_app_custom_link');

        if (empty($iosAppCustomLink)) {
            $iosAppCustomLink = config('applinks.ios_package_name') . '://applinks';
        }

        $queryString = http_build_query($request->query());
        $schemeUrl = $iosAppCustomLink . ($queryString !== '' ? '?' . $queryString : '');

        return response()->make(<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applinks</title>
</head>
<body style="padding-top: 40vh">
    <div style="display: flex; justify-content: center; align-items: center;"> <em>Please wait until redirect you to ACTS Mobile ...</em> </div>
    <script>
        window.onload = function() {
            var now = Date.now();
            window.location = "{$schemeUrl}";
            let timeout = 30000;
            setTimeout(function() {
                if (Date.now() - now < (timeout + 30)) {
                    window.location = "{$url}";
                }
            }, timeout);
        };
    </script>
</body>
</html>
HTML
        );
    }

    private function redirectToWeb(Request $request)
    {
        $url = $request->query('web_url');
        if (empty($url)) {
            $url = config('applinks.web_redirect_url') ?: url('/');
        }

        return redirect(rawurldecode($url));
    }

    public function redirect(Request $request)
    {
        $agent = $request->userAgent();
        $os = $this->os($agent);

        $device = session('APPLINKS_DEVICE_DATA');
        if ($device !== null) {
            session()->forget('APPLINKS_DEVICE_DATA');
        }

        if ($device === null && $os !== 'ANDROID') {
            $saveUrl = url('applinks_save_device_info');

            return response()->make(<<<HTML
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applinks</title>
</head>

<body>
    <script async="true">
        function iPad() {
            return [
                    'iPad Simulator',
                    'iPhone Simulator',
                    'iPod Simulator',
                    'iPad',
                    'iPhone',
                    'iPo'
                ].includes(navigator.platform)
                // iPad on iOS 13 detection
                ||
                (navigator.userAgent.includes('Mac') && 'ontouchend' in document)
        }

        function tablet(){
            return navigator.userAgent.toLocaleLowerCase().includes('android');
        }

        function screen_height() {
            return screen.height;
        }

        function screen_width() {
            return screen.width;
        }
        async function set_device_data() {
            await fetch('{$saveUrl}?is_ipad=' + (iPad() ? 1 : 0) + '&height=' + screen_height() + '&width=' + screen_width() + '&is_tab=' + (tablet() ? 1 : 0))
            window.location.reload();
        }
        set_device_data();
    </script>
</body>

</html>
HTML
            );
        }

        $deviceData = is_array($device) ? $device : [];
        $isTab = ! empty($deviceData['is_tab']) && ((int) $deviceData['is_tab'] === 1 || $deviceData['is_tab'] === '1');
        $isIpad = ! empty($deviceData['is_ipad']) && ((int) $deviceData['is_ipad'] === 1 || $deviceData['is_ipad'] === '1');

        if ($os === 'ANDROID' || $isTab) {
            $id = $this->saveData('android', $request, $deviceData);

            return $this->redirectToPlaystore($id);
        }

        if ($os === 'IOS' || ($os === 'MACINTOSH' && $isIpad)) {
            $this->saveData('ios', $request, $deviceData);

            return $this->redirectToAppstore($request, $deviceData);
        }

        return $this->redirectToWeb($request);
    }

    public function fetch(Request $request)
    {
        $type = strtolower($request->query('type', 'android'));
        $ip = $this->getClientIp($request);
        $uuid = $request->query('uuid');

        $query = DB::table('applinks_data');

        if ($type === 'android' && $uuid) {
            $query->where('id', $uuid);
        } else {
            $width = $request->query('width', 0);
            $height = $request->query('height', 0);
            $query->where([
                'device_type' => $type,
                'resolution' => "{$width} x {$height}",
                'ip_address' => $ip,
            ]);
        }

        $findDeviceData = $query->orderBy('id', 'desc')->first();

        if ($findDeviceData) {
            DB::table('applinks_data')->where('id', $findDeviceData->id)->delete();

            return response()->json([
                'success' => 1,
                'parameters' => json_decode($findDeviceData->query_string),
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'GET');
        }

        return response()->json([
            'success' => 0,
            'data' => null,
            'parameters' => null,
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET');
    }
}
