<?php

namespace App\Livewire\Pages\Admin\SystemStatus;

use App\Services\Preflight;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Admin > Estado del sistema: the production-readiness checklist (same checks as `php artisan academy:preflight`). */
class SystemStatus extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    public ?string $backupMessage = null;

    public function backupNow(): void
    {
        $code = Artisan::call('app:backup-database');
        $this->backupMessage = $code === 0 ? 'Respaldo creado correctamente.' : 'No se pudo crear el respaldo: revisa el log del servidor.';
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $checks = (new Preflight())->run();

        return view('livewire.pages.admin.system-status.system-status', [
            'groups' => collect($checks)->groupBy('group'),
            'summary' => Preflight::summary($checks),
        ]);
    }
}
