<?php

namespace App\Providers;

use App\Services\FifaCookies;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\ServiceProvider;

class ResaleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $clients = $this->app->make('config')->get('fifa.clients');

        $this->app->singleton(FifaCookies::class, function () {
            $cookieJar = new CookieJar();
            $cookies   = json_decode($this->app->make('cache.store')->get(FifaCookies::CACHE_KEY), true);

            foreach ($cookies as $cookie) {
                $cookieJar->setCookie(new SetCookie($cookie));
            }

            return $cookieJar;
        });

        foreach ($clients as $name => $clientConfig) {
            $this->app->singleton($clientConfig['class'], function ($app) use ($clientConfig) {
                return new $clientConfig['class']([
                    'base_uri' => $clientConfig['http']['base_uri'],
                    'headers'  => [
                        'User-agent'                  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
                        'Accept'                      => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                        'Accept-Encoding'             => 'gzip, deflate, br, zstd',
                        'Accept-Language'             => 'en-GB,en-US;q=0.9,en;q=0.8',
                        'Cache-Control'               => 'max-age=0',
                        'Connection'                  => 'keep-alive',
                        'DNT'                         => '1',
                        'Host'                        => 'fwc26-resale-usd.tickets.fifa.com',
                        'Sec-Fetch-Dest'              => 'document',
                        'Sec-Fetch-Mode'              => 'navigate',
                        'Sec-Fetch-Site'              => 'none',
                        'Sec-Fetch-User'              => '?1',
                        'Upgrade-Insecure-Requests'   => '1',
                        'User-Agent'                  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
                        'sec-ch-device-memory'        => '8',
                        'sec-ch-ua'                   => '"Google Chrome";v="141", "Not?A_Brand";v="8", "Chromium";v="141"',
                        'sec-ch-ua-arch'              => '"arm"',
                        'sec-ch-ua-full-version-list' => '"Google Chrome";v="141.0.7390.54", "Not?A_Brand";v="8.0.0.0", "Chromium";v="141.0.7390.54"',
                        'sec-ch-ua-mobile'            => '?0',
                        'sec-ch-ua-model'             => '""',
                    ],
                    'cookies'  => $app->make(FifaCookies::class)
                ]);
            });

            $this->app->alias($clientConfig['class'], $name);
        }
    }
}
