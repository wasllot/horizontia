<?php

namespace App\Livewire\Pages\Admin\ConfigTransfer;

use App\Services\ConfigTransfer\ConfigTransfer;
use App\Services\ConfigTransfer\ConfigTransferException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Admin > Ajustes > Exportar / importar: move the site configuration (not courses, blogs or users) between installations. */
class ConfigTransferPage extends Component
{
    use WithFileUploads;
    use \App\Livewire\Concerns\AuthorizesAdmin;

    #[Url]
    public string $tab = 'export';

    // ---- export
    /** @var array<int, string> */
    public array $exportSections = [];
    public bool $withCredentials = false;
    public bool $allPages = true;
    /** @var array<int, string> */
    public array $exportPages = [];

    // ---- import
    public $upload = null;
    #[Locked]
    public ?string $token = null;
    public array $summary = [];
    public array $meta = [];
    /** @var array<int, string> */
    public array $importSections = [];
    public string $mode = ConfigTransfer::MODE_SKIP;
    public bool $backup = true;
    public bool $allImportPages = true;
    /** @var array<int, string> */
    public array $importPages = [];
    public array $pageList = [];
    public ?array $report = null;
    public ?string $error = null;

    public function mount(): void
    {
        $this->exportSections = collect(ConfigTransfer::sections())->filter(fn ($s) => $s['default'])->keys()->all();
    }

    private function service(): ConfigTransfer
    {
        return app(ConfigTransfer::class);
    }

    private function cacheKey(): string
    {
        return 'config-import:' . auth()->id() . ':' . $this->token;
    }

    // ------------------------------------------------------------------ export

    public function download()
    {
        $sections = array_values(array_intersect($this->exportSections, array_keys(ConfigTransfer::sections())));
        if ($sections === []) {
            $this->addError('exportSections', 'Elige al menos una sección para exportar.');

            return null;
        }
        $this->resetErrorBag();
        $payload = $this->service()->export($sections, ['credentials' => $this->withCredentials, 'pages' => $this->allPages ? null : array_values($this->exportPages)]);
        $json = $this->service()->toJson($payload);
        $name = 'horizontia-config-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $name, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    // ------------------------------------------------------------------ import

    public function updatedUpload(): void
    {
        $this->resetImportState(keepUpload: true);
        try {
            $this->validate(['upload' => 'required|file|max:20000']);   // KB
            $payload = $this->service()->parse((string) file_get_contents($this->upload->getRealPath()));
        } catch (ConfigTransferException $e) {
            $this->error = $e->getMessage();
            $this->upload = null;

            return;
        } catch (\Illuminate\Validation\ValidationException) {
            $this->error = 'Sube un archivo .json de exportación (máximo 20 MB).';
            $this->upload = null;

            return;
        }

        $this->token = Str::random(32);
        Cache::put($this->cacheKey(), $payload, now()->addHour());
        $this->meta = Arr::only($payload['meta'], ['exported_at', 'source', 'site', 'includes_credentials']);
        $this->summary = $this->service()->summarize($payload);
        $this->importSections = collect($this->summary)->filter(fn ($s, $k) => !ConfigTransfer::sections()[$k]['sensitive'])->keys()->all();
        $this->pageList = collect($payload['data']['pages'] ?? [])->map(fn ($p) => ['slug' => $p['slug'] ?? '', 'name' => $p['name'] ?? ($p['slug'] ?? ''), 'exists' => DB::table(config('pagebuilder.db_prefix', 'pagebuilder__') . 'pages')->where('slug', $p['slug'] ?? '')->exists()])->all();
        $this->importPages = array_column($this->pageList, 'slug');
    }

    private function payload(): ?array
    {
        return $this->token ? Cache::get($this->cacheKey()) : null;
    }

    private function runImport(bool $dry): void
    {
        $this->error = null;
        $payload = $this->payload();
        if (!$payload) {
            $this->error = 'El archivo ya no está disponible (pasó mucho tiempo). Súbelo de nuevo.';

            return;
        }
        $sections = array_values(array_intersect($this->importSections, array_keys($payload['data'])));
        if ($sections === []) {
            $this->error = 'Elige al menos una sección para importar.';

            return;
        }

        if (!$dry && $this->backup) {
            try {
                $code = Artisan::call('app:backup-database');
            } catch (\Throwable) {
                $code = 1;
            }
            if ($code !== 0) {
                $this->error = 'No se pudo hacer el respaldo previo de la base de datos (¿mysqldump no está disponible?). Desmarca «Hacer un respaldo antes de importar» si quieres continuar sin él.';

                return;
            }
        }

        try {
            $this->report = $this->service()->import($payload, $sections, $this->mode, [
                'dry' => $dry,
                'pages' => $this->allImportPages ? null : array_values($this->importPages),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'La importación falló y no se guardó nada: ' . $e->getMessage();

            return;
        }
        if (!$dry) {
            Cache::forget($this->cacheKey());
        }
    }

    public function preview(): void
    {
        $this->runImport(true);
    }

    public function import(): void
    {
        $this->runImport(false);
    }

    public function resetImport(): void
    {
        $this->resetImportState();
    }

    private function resetImportState(bool $keepUpload = false): void
    {
        if ($this->token) {
            Cache::forget($this->cacheKey());
        }
        $this->reset('token', 'summary', 'meta', 'importSections', 'pageList', 'importPages', 'report', 'error', 'mode', 'backup', 'allImportPages');
        if (!$keepUpload) {
            $this->upload = null;
        }
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        return view('livewire.pages.admin.config-transfer.config-transfer-page', [
            'sections' => ConfigTransfer::sections(),
            'localPages' => DB::table(config('pagebuilder.db_prefix', 'pagebuilder__') . 'pages')->orderBy('name')->get(['slug', 'name', 'status']),
        ]);
    }
}
