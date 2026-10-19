<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Services\Settings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'site' => function () {
                $settings = app(Settings::class);

                return [
                    'name' => $settings->site()['name'],
                    'tagline' => $settings->site()['tagline'],
                    'contact' => $settings->contact(),
                    'social' => $settings->socialLinks(),
                ];
            },
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->isAdmin(),
                ] : null,
            ],
            'cartCount' => fn () => $user ? app(CartService::class)->count($user) : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
