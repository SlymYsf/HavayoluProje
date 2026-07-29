<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryCodeController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Country $c) => [
                'code' => $c->iso_code,
                'dial' => $c->dial_code,
                'name' => $c->name,
                'flag' => $c->flag_url,
            ]);

        return response()->json($countries);
    }
}
