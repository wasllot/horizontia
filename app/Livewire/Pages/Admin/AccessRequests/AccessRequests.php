<?php

namespace App\Livewire\Pages\Admin\AccessRequests;

use App\Models\AccessRequest;
use App\Services\AccessRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin > Solicitudes de acceso: aprobar (crea la cuenta y envía el enlace) o rechazar con un motivo. */
class AccessRequests extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;
    use WithPagination;

    #[Url(as: 'estado')]
    public string $filter = 'pending';

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $rejectingId = null;
    public string $note = '';
    public ?int $openId = null;

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['pending', 'approved', 'rejected', 'all'], true) ? $filter : 'pending';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
    }

    public function approve(int $id, AccessRequestService $service): void
    {
        $request = AccessRequest::findOrFail($id);
        if ($request->status !== AccessRequest::PENDING) {
            return;
        }
        $service->approve($request, Auth::user());
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Cuenta creada. Le enviamos por correo el enlace para elegir su contraseña.');
    }

    public function askReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->note = '';
    }

    public function cancelReject(): void
    {
        $this->reset('rejectingId', 'note');
    }

    public function reject(AccessRequestService $service): void
    {
        $this->validate(['note' => 'nullable|string|max:300']);
        $request = AccessRequest::findOrFail($this->rejectingId);
        if ($request->status === AccessRequest::PENDING) {
            $service->reject($request, Auth::user(), $this->note);
        }
        $this->reset('rejectingId', 'note');
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Solicitud rechazada. Le avisamos por correo.');
    }

    public function delete(int $id): void
    {
        AccessRequest::whereKey($id)->where('status', '!=', AccessRequest::PENDING)->delete();
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $q = AccessRequest::query()->orderByRaw("status = 'pending' desc")->orderByDesc('id');
        if ($this->filter !== 'all') {
            $q->where('status', $this->filter);
        }
        if (trim($this->search) !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($this->search)) . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        return view('livewire.pages.admin.access-requests.access-requests', [
            'requests' => $q->paginate(20),
            'counts' => AccessRequest::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }
}
