<?php

namespace App\Livewire\Pages\Admin\Certificates;

use App\Models\CourseCertificate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin > Certificados: lo emitido, con búsqueda y revocación (queda en la página pública como «revocado»). */
class Certificates extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';
    public ?int $revokingId = null;
    public string $reason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askRevoke(int $id): void
    {
        $this->revokingId = $id;
        $this->reason = '';
    }

    public function cancelRevoke(): void
    {
        $this->reset('revokingId', 'reason');
    }

    public function revoke(): void
    {
        $this->validate(['reason' => 'required|string|max:160'], ['reason.required' => 'Escribe el motivo: se muestra en la página pública del certificado.']);
        CourseCertificate::findOrFail($this->revokingId)->update(['revoked_at' => now(), 'revoked_reason' => trim(strip_tags($this->reason))]);
        $this->reset('revokingId', 'reason');
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Certificado revocado.');
    }

    public function restore(int $id): void
    {
        CourseCertificate::findOrFail($id)->update(['revoked_at' => null, 'revoked_reason' => null]);
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Certificado restaurado.');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $term = trim($this->search);
        $q = CourseCertificate::query()->orderByDesc('issued_at');
        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $q->where(fn ($w) => $w->where('code', 'like', $like)->orWhere('student_name', 'like', $like)->orWhere('course_title', 'like', $like));
        }

        return view('livewire.pages.admin.certificates.certificates', ['certificates' => $q->paginate(20), 'total' => CourseCertificate::count()]);
    }
}
