<?php

namespace MrSwapan\Applinks\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplinksController extends Controller
{
    private function os($agent)
    {
        $agent = strtolower($agent);

        if (str_contains($agent, 'iphone') || str_contains($agent, 'ipad')) return "IOS";
        if (str_contains($agent, 'android')) return "ANDROID";
        if (str_contains($agent, 'macintosh')) return "MACINTOSH";
        if (str_contains($agent, 'windows')) return "WINDOWS";

        return "UNKNOWN";
    }

    private function getClientIp(Request $request)
    {
        return $request->ip();
    }

    public function assetlinks()
    {
        return response()->json([
            [
                "relation" => ["delegate_permission/common.handle_all_urls"],
                "target" => [
                    "namespace" => "android_app",
                    "package_name" => config('applinks.android_package_name'),
                    "sha256_cert_fingerprints" => config('applinks.android_sha256_cert_fingerprints'),
                ]
            ]
        ]);
    }

    public function apple()
    {
        $prefix = config('applinks.applinks_url_prefix', 'applinks/*');

        return response()->json([
            "applinks" => [
                "details" => [[
                    "appIDs" => [
                        config('applinks.ios_team_id') . '.' . config('applinks.ios_package_name')
                    ],
                    "components" => [[
                        "/" => "/$prefix",
                        "comment" => "Accept all links"
                    ]]
                ]]
            ],
            "webcredentials" => [
                "apps" => [
                    config('applinks.ios_team_id') . '.' . config('applinks.ios_package_name')
                ]
            ]
        ]);
    }

    public function saveDeviceInfo(Request $request)
    {
        session(['APPLINKS_DEVICE_DATA' => $request->query()]);
        return response()->noContent();
    }

    public function jsEditor()
    {
        return response()->make(<<<HTML
<!DOCTYPE html>
<html>
<body>
<textarea id="code" style="width:100%" rows="10"></textarea>
<button onclick="run()">Execute</button>
<div id="log"></div>

<script>
function run(){
    try{
        let code = document.getElementById('code').value;
        let result = eval(code);
        document.getElementById('log').innerHTML += result + '<br>';
    }catch(e){
        document.getElementById('log').innerHTML += e.message + '<br>';
    }
}
</script>
</body>
</html>
HTML);
    }

    private function saveData($type, Request $request, $device)
    {
        $id = DB::table('applinks_data')->insertGetId([
            'device_type' => $type,
            'resolution' => ($device['width'] ?? 0) . ' x ' . ($device['height'] ?? 0),
            'ip_address' => $request->ip(),
            'query_string' => json_encode($request->query()),
            'created_at' => now(),
        ]);

        return $id;
    }

    private function redirectToPlaystore($uuid)
    {
        $url = config('applinks.android_playstore_url');
        return redirect($url . '&referrer=' . $uuid);
    }

    private function redirectToAppstore(Request $request, $device)
    {
        $url = config('applinks.ios_app_store_url');
        $ios_app_custom_link = config('applinks.ios_app_custom_link');

        return response()->make('
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Applinks</title>
            </head>
            <body style="padding-top: 40vh">
                <div style="display: flex; justify-content: center; align-items: center;"> <em>Please wait until redirect you to Mobile App...</em> </div>
                <script>
                    window.onload = function() {
                        var now = Date.now();
                        window.location = "' . $ios_app_custom_link . '?" + "' . http_build_query($request->query()) . '";
                        let timeout = 10000;
                        setTimeout(function() {
                            if (Date.now() - now < (timeout + 30)) {
                                window.location = "'. $url .'";
                            }
                        }, timeout);
                    };
                </script>
            </body>
            </html>
        ');
    }

    private function redirectToWeb(Request $request)
    {
        $url = $request->query('web_url', config('applinks.web_redirect_url', url('')));
        return redirect($url);
    }

    public function redirect(Request $request)
    {
        $agent = $request->userAgent();
        $os = $this->os($agent);

        $device = session('APPLINKS_DEVICE_DATA', []);
        session()->forget('APPLINKS_DEVICE_DATA');

        if (!$device && $os !== "ANDROID") {
            return response()->make(
                <<<HTML
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
                        await fetch('/applinks_save_device_info?is_ipad=' + (iPad() ? 1 : 0) + '&height=' + screen_height() + '&width=' + screen_width() + '&is_tab=' + (tablet() ? 1 : 0))
                        window.location.reload();
                    }
                    set_device_data();
                </script>
            </body>

            </html>
            HTML
            );
        }

        if ($os === "ANDROID" || $device['is_tab'] === 1) {
            $id = $this->saveData('android', $request, $device);
            return $this->redirectToPlaystore($id);
        }

        if ($os === "IOS" || ($os == "MACINTOSH" && $device['is_ipad'] == 1)) {
            $id = $this->saveData('ios', $request, $device);
            return $this->redirectToAppstore($request, $device);
        }

        return $this->redirectToWeb($request);
    }

    public function fetch(Request $request)
    {        
        $type = strtoupper($request->query('type', 'android'));
        $ip = $this->getClientIp($request);
        $uuid = $request->query('uuid');

        $query = DB::table('applinks_data');

        if ($type === 'ANDROID' && $uuid) {
            $query->where('id', $uuid);
        } else {
            $query->where([
                'device_type' => strtolower($type),
                'resolution' => ($request->query('width') ?? 0) . ' x ' . ($request->query('height') ?? 0),
                'ip_address' => $ip,
            ]);
        }

        $find_device_data = $query->orderBy('id', 'desc')->first();

        if ($find_device_data) {
            DB::table('applinks_data')->where('id', $find_device_data->id)->delete();
            return response()->json([
                'success' => 1,
                'parameters' => json_decode($find_device_data->query_string)
            ]);
        }

        return response()->json(['success' => 0]);
    }
}