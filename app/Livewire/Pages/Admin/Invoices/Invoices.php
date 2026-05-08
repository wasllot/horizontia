<?php

namespace App\Livewire\Pages\Admin\Invoices;

use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public      $search             = '';
    public      $sortby             = 'desc';
    public      $status             = '';
    public      $user;
    private ?OrderService  $orderService        = null;
    public  $invoice;
    public function boot()
    {
        $this->user             = Auth::user();
        $this->orderService     = new OrderService();
    }

    public function mount()
    {
        $this->dispatch('initSelect2', target: '.am-select2' );
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $orders = $this->orderService->getOrdersList($this->status, $this->search, $this->sortby);
       
        return view('livewire.pages.admin.invoices.invoices',compact('orders'));
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['status', 'search', 'sortby'])) {
            $this->resetPage();
        }
    }

    public function viewInvoice($id)
    {
        $this->invoice = $this->orderService->getOrdeWrWithItem($id);
        $this->dispatch('openInvoiceModal', id: 'invoicePreviewModal', action: 'show');
    }
}
