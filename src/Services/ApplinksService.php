<?php

namespace MrSwapan\Applinks\Services;

class ApplinksService
{
    public function create($data, $webUrl = '')
    {
        $prefix = rtrim(config('applinks.applinks_url_prefix', 'applinks/*'), '/*');

        if (is_array($data)) {
            if ($webUrl) $data['web_url'] = $webUrl;
            $query = http_build_query($data);
        } else {
            $query = $data;
        }

        return url($prefix . '?' . $query);
    }
}