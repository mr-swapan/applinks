<?php

namespace MrSwapan\Applinks\Services;

use InvalidArgumentException;

class ApplinksService
{
    private function os(): string
    {
        $userAgent = strtolower(request()->userAgent() ?? '');

        if (str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) {
            return "IOS";
        }
        if (str_contains($userAgent, 'android')) {
            return "ANDROID";
        }
        if (str_contains($userAgent, 'macintosh')) {
            return "MACINTOSH";
        }
        if (str_contains($userAgent, 'windows')) {
            return "WINDOWS";
        }

        return "UNKNOWN";
    }

    public function create(array|string $data, string $webUrl = ''): string
    {
        $prefix = config('applinks.applinks_url_prefix', 'applinks/*');
        $prefix = trim(rtrim($prefix, "/*"), '/');
        if ($prefix === '') {
            $prefix = 'applinks';
        }

        if (is_array($data)) {
            if ($webUrl !== '') {
                $data['web_url'] = $webUrl;
            }
            $query = http_build_query(array_map('rawurlencode', $data));
        } elseif (is_string($data)) {
            $query = $data;
        } else {
            throw new InvalidArgumentException("first argument of function 'Applinks::create' should be array or string.");
        }

        $associatedDomain = config('applinks.associated_domain');
        if (empty($associatedDomain)) {
            $associatedDomain = url('/');
        }

        if (!str_ends_with($associatedDomain, '/')) {
            $associatedDomain .= '/';
        }

        if (in_array($this->os(), ['MACINTOSH', 'IOS'])) {
            $associatedDomain = str_replace(['WWW.', 'www.'], '', $associatedDomain);
        }

        return $associatedDomain . $prefix . ($query !== '' ? '?' . $query : '');
    }
}
