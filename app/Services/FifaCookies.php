<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Cache\Repository;

class FifaCookies extends CookieJar
{
    public const string CACHE_KEY = 'resale.cookies';

    public function save(Repository $cache): self
    {
        $cache->put(FifaCookies::CACHE_KEY, json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $this;
    }

    public function sync(Repository $cache, bool $clear = true): self
    {
        if ($clear) {
            $this->clear();
        }

        $cookies = json_decode($cache->get(static::CACHE_KEY), true);

        foreach ($cookies as $cookie) {
            $this->setCookie(new SetCookie($cookie));
        }

        return $this;
    }

    public static function load(Repository $cache, bool $clear = true): self
    {
        $cookies = new static();
        $cookies->sync($cache, $clear);

        return $cookies;
    }
}
