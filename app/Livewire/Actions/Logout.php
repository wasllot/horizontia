<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        Cache::forget('user-online-'. Auth::user()->id);
        Auth::guard('web')->logout();
        Session::invalidate();
        Session::regenerateToken();
    } 
}
