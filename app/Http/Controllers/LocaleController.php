<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;


class LocaleController extends Controller
{
    /**
     * Dil ve bölge tercihini kaydeder.
     *
     * "Hatırla" işaretliyse tercih bir yıllık çerezde saklanır, aksi halde
     * yalnızca oturum boyunca geçerlidir. Kullanıcı geldiği sayfaya döner.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', array_keys(config('site.locales')))],
            'region' => ['required', 'string', 'exists:countries,iso_code'],
            'remember' => ['nullable'],
        ]);

        $request->session()->put('locale', $data['locale']);
        $request->session()->put('region', $data['region']);

        $response = redirect()->back();

        if ($request->boolean('remember')) {
            $response->withCookie(cookie()->forever('locale', $data['locale']));
            $response->withCookie(cookie()->forever('region', $data['region']));
        } else {
            $response->withCookie(cookie()->forget('locale'));
            $response->withCookie(cookie()->forget('region'));
        }

        return $response;
    }
}
