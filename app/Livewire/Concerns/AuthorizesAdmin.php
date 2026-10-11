<?php

namespace App\Livewire\Concerns;

/**
 * Admin screens re-check the role on every Livewire request (render and actions), not only on the page route:
 * action calls arrive at /livewire/update where the route-level `role:admin` middleware is not in play.
 */
trait AuthorizesAdmin
{
    public function bootAuthorizesAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
    }
}
