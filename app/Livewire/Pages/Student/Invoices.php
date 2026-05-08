<?php

namespace App\Livewire\Pages\Student;

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
    public      $isLoading          = true;



    private ?OrderService  $orderService        = null;
    public function boot()
    {
        $this->user             = Auth::user();
        $this->orderService     = new OrderService();
    }

    public function mount()
    {
        $this->dispatch('initSelect2', target: '.am-select2' );
    }

    public function loadData()
    {
        $this->isLoading            = false;
        $this->dispatch('loadPageJs');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $orders = $this->orderService->getOrders($this->status, null, 'Desc', null , null , $this->user->id);


        return view('livewire.pages.student.invoices' , compact('orders'));
    }
}
