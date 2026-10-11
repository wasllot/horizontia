<?php

namespace App\Livewire\Pages\Student;

use App\Models\CourseCertificate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Estudiante > Mis certificados: los certificados verificables de los cursos terminados. */
class CertificateList extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pages.student.certificate-list', [
            'certificates' => CourseCertificate::where('user_id', Auth::id())->orderByDesc('issued_at')->get(),
        ]);
    }
}
