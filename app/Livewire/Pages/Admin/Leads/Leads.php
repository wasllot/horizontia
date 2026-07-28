<?php

namespace App\Livewire\Pages\Admin\Leads;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;
use App\Models\Subscriber;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class Leads extends Component
{
    use WithPagination;

    public $activeTab = 'contacts';
    public $viewingContact = null;
    protected $paginationTheme = 'bootstrap';

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $contacts = [];
        $subscribers = [];

        if ($this->activeTab === 'contacts') {
            $contacts = Contact::latest()->paginate(10);
        } else {
            $subscribers = Subscriber::latest()->paginate(10);
        }

        return view('livewire.pages.admin.leads.leads', [
            'contacts' => $contacts,
            'subscribers' => $subscribers
        ])->extends('layouts.admin-app');
    }

    public function viewContact($id)
    {
        $this->viewingContact = Contact::find($id);
        $this->dispatch('toggleModel', id: 'view-contact-modal', action: 'show');
    }

    #[On('delete-contact')]
    public function deleteContact($params = [])
    {
        if (isDemoSite()) {
            $this->dispatch('showAlertMessage', type: 'error', title: 'Oops', message: 'Action not allowed on demo site');
            return;
        }
        $contact = Contact::find($params['id']);
        if ($contact) {
            $contact->delete();
            $this->dispatch('showAlertMessage', type: 'success', title: 'Éxito', message: 'Mensaje de contacto eliminado correctamente.');
        }
    }

    #[On('delete-subscriber')]
    public function deleteSubscriber($params = [])
    {
        if (isDemoSite()) {
            $this->dispatch('showAlertMessage', type: 'error', title: 'Oops', message: 'Action not allowed on demo site');
            return;
        }
        $subscriber = Subscriber::find($params['id']);
        if ($subscriber) {
            $subscriber->delete();
            $this->dispatch('showAlertMessage', type: 'success', title: 'Éxito', message: 'Suscriptor eliminado correctamente.');
        }
    }
}
