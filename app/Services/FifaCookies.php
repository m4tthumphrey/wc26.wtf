<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJar;

class FifaCookies extends CookieJar
{
    public const string CACHE_KEY = 'resale.cookies';
}
