<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Her istekte uygulama dilini belirler.
 *
 * Öncelik sırası: oturum → çerez → yapılandırmadaki varsayılan.
 * Çerez, kullanıcı "Seçimlerimi hatırla" kutusunu işaretlediğinde yazılır;
 * oturum kapansa bile tercih korunur.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('site.locales'));

        $locale = $request->session()->get('locale')
            ?? $request->cookie('locale')
            ?? config('app.locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
