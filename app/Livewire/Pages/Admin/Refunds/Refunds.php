<?php

namespace App\Livewire\Pages\Admin\Refunds;

use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Admin > Finanzas > Reembolsos: solicitudes de la garantía, para aprobar (devuelve el dinero) o rechazar con motivo. */
class Refunds extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    #[Url(as: 'estado')]
    public string $filter = 'pending';

    public ?int $rejecting = null;
    public string $note = '';

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['pending', 'resolved', 'all'], true) ? $filter : 'pending';
        $this->cancelReject();
    }

    public function approve(int $id): void
    {
        $result = app(RefundRequestService::class)->approve(RefundRequest::findOrFail($id), Auth::user());
        $this->toast($result['message'], $result['ok'] ? 'success' : 'error');
    }

    public function askReject(int $id): void
    {
        $this->rejecting = RefundRequest::findOrFail($id)->id;
        $this->note = '';
        $this->resetErrorBag();
    }

    public function cancelReject(): void
    {
        $this->reset('rejecting', 'note');
        $this->resetErrorBag();
    }

    public function reject(): void
    {
        abort_if($this->rejecting === null, 422);
        $result = app(RefundRequestService::class)->reject(RefundRequest::findOrFail($this->rejecting), Auth::user(), $this->note);
        if (!$result['ok']) {
            $this->addError('note', $result['message']);

            return;
        }
        $this->cancelReject();
        $this->toast($result['message']);
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $requests = RefundRequest::query()->with(['order.items', 'user.profile'])
            ->when($this->filter === 'pending', fn ($q) => $q->where('status', RefundRequest::PENDING))
            ->when($this->filter === 'resolved', fn ($q) => $q->where('status', '!=', RefundRequest::PENDING))
            ->latest('id')->limit(100)->get();

        return view('livewire.pages.admin.refunds.refunds', [
            'requests' => $requests,
            'pending' => RefundRequest::where('status', RefundRequest::PENDING)->count(),
        ]);
    }
}
