<?php

namespace App\Livewire\Pages\Common;

use App\Facades\DbNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class Notifications extends Component
{
    use WithPagination;

    public $perPage = 5;

    public function markAsRead($id, $redirect = null)
    {
        DbNotification::markAsRead(Auth::user(), $id);
        $this->dispatch('notifications-count-updated', count: Auth::user()->unreadNotifications()->count());
        if ($redirect) {
            return $this->redirect($redirect);
        }
    }

    public function markAllAsRead()
    {
        DbNotification::markAllAsRead(Auth::user());
        $this->dispatch('notifications-count-updated', count: Auth::user()->unreadNotifications()->count());
    }

    /** Fired by the panel's background pulse when new notifications arrive: re-rendering is enough. */
    #[On('notifications-refresh')]
    public function refreshList(): void
    {
    }

    public function loadMore()
    {
        $this->perPage += 5;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $notifications = DbNotification::getUserNotifications(Auth::user(), $this->perPage);
        return view('livewire.pages.common.notifications', compact('notifications'));
    }
}
