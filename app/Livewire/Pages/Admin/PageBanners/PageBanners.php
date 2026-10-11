<?php

namespace App\Livewire\Pages\Admin\PageBanners;

use App\Models\PageBanner;
use App\Models\SitePage;
use App\Services\PageBannerService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Admin > Banners de páginas: cambia el título, el subtítulo y la imagen de la portada de cada página estática. */
class PageBanners extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;
    use WithFileUploads;

    public ?string $editingKey = null;
    public bool $enabled = true;
    public string $title = '';
    public string $subtitle = '';
    public int $overlay = 55;
    public string $align = 'left';
    public ?string $image = null;        // ruta ya guardada
    public $upload = null;               // archivo nuevo (aún sin guardar)
    public bool $removeImage = false;

    /** Slugs de páginas con vista propia: su banner se edita como página fija, no como página del editor. */
    private function dedicatedSlugs(): array
    {
        return collect(\App\Http\Controllers\PageController::DEDICATED)->map(fn ($r) => \App\Http\Controllers\PageController::PAGES[$r][0])->all();
    }

    /** Páginas que se pueden editar: [clave => [label, url, defaults, kind]]. */
    private function pages(): array
    {
        $out = [];
        foreach (SitePage::where('slug', '!=', '/')->whereNotIn('slug', $this->dedicatedSlugs())->where(fn ($q) => $q->whereNull('gjs_html')->orWhere('gjs_html', '!=', ''))->orderBy('name')->get() as $p) {
            $out[PageBannerService::keyFor($p)] = ['label' => $p->name, 'url' => $p->url(), 'kind' => 'page', 'model' => $p];
        }
        foreach (PageBannerService::SYSTEM as $name => $def) {
            $out['system:' . $name] = ['label' => $def['label'], 'url' => url($def['url']), 'kind' => 'system', 'def' => $def];
        }

        return $out;
    }

    public function edit(string $key): void
    {
        $page = $this->pages()[$key] ?? abort(404);
        $banner = PageBanner::where('page_key', $key)->first();
        $this->reset('upload', 'removeImage');
        $this->resetValidation();
        $this->editingKey = $key;
        if ($banner) {
            [$this->enabled, $this->title, $this->subtitle, $this->overlay, $this->align, $this->image] = [$banner->enabled, (string) $banner->title, (string) $banner->subtitle, $banner->overlay, $banner->align, $banner->image];

            return;
        }
        // Primera vez: se parte de lo que la página ya muestra para que solo haya que cambiarlo.
        $seed = $page['kind'] === 'page' ? app(PageBannerService::class)->heroOf($page['model']) : ['title' => $page['def']['title'], 'subtitle' => $page['def']['subtitle']];
        [$this->enabled, $this->title, $this->subtitle, $this->overlay, $this->align, $this->image] = [true, $seed['title'], $seed['subtitle'], 55, 'left', null];
    }

    public function cancel(): void
    {
        $this->reset('editingKey', 'upload', 'removeImage');
        $this->resetValidation();
    }

    public function removeCurrentImage(): void
    {
        $this->removeImage = true;
        $this->upload = null;
    }

    public function updatedUpload(): void
    {
        try {
            $this->validateOnly('upload', ['upload' => 'image|mimes:jpg,jpeg,png,webp,avif|max:4096'], ['upload.image' => 'El archivo debe ser una imagen.', 'upload.mimes' => 'Usa una imagen JPG, PNG, WEBP o AVIF.', 'upload.max' => 'La imagen pesa más de 4 MB.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->upload = null;   // un archivo no válido no se queda a medias ni se intenta previsualizar
            throw $e;
        }
        $this->removeImage = false;
    }

    public function save(): void
    {
        $key = $this->editingKey;
        abort_unless($key && isset($this->pages()[$key]), 404);

        $this->validate([
            'title' => $this->enabled ? 'required|string|max:140' : 'nullable|string|max:140',
            'subtitle' => 'nullable|string|max:280',
            'overlay' => 'integer|min:0|max:85',
            'align' => 'in:left,center',
            'upload' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:4096',
        ], ['title.required' => 'Escribe el título del banner.', 'upload.image' => 'El archivo debe ser una imagen.', 'upload.max' => 'La imagen pesa más de 4 MB.', 'upload.mimes' => 'Usa una imagen JPG, PNG, WEBP o AVIF.']);

        $banner = PageBanner::firstOrNew(['page_key' => $key]);
        $path = $banner->image;
        if ($this->upload) {
            $path = Storage::disk(getStorageDisk())->putFileAs('page-banners/' . now()->format('Y/m'), $this->upload, Str::random(24) . '.' . $this->upload->extension(), ['visibility' => 'public']);
        } elseif ($this->removeImage) {
            $path = null;
        }
        if ($banner->image && $banner->image !== $path && str_starts_with($banner->image, 'page-banners/')) {
            Storage::disk(getStorageDisk())->delete($banner->image);   // no se acumulan imágenes viejas
        }

        $banner->fill(['enabled' => $this->enabled, 'title' => trim(strip_tags($this->title)), 'subtitle' => trim(strip_tags($this->subtitle)) ?: null, 'image' => $path, 'overlay' => $this->overlay, 'align' => $this->align])->save();
        PageBannerService::forget($key);

        $this->cancel();
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Banner guardado.');
    }

    public function reset_(string $key): void
    {
        $banner = PageBanner::where('page_key', $key)->first();
        if ($banner) {
            if ($banner->image && str_starts_with($banner->image, 'page-banners/')) {
                Storage::disk(getStorageDisk())->delete($banner->image);
            }
            $banner->delete();
            PageBannerService::forget($key);
        }
        $this->dispatch('showAlertMessage', type: 'success', title: __('general.success_title'), message: 'Se restauró el banner original de la página.');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $pages = $this->pages();
        $saved = PageBanner::whereIn('page_key', array_keys($pages))->get()->keyBy('page_key');

        $preview = null;
        if ($this->editingKey) {
            $imageUrl = $this->upload && !$this->removeImage && $this->upload->isPreviewable() ? $this->upload->temporaryUrl() : (!$this->removeImage && $this->image ? mediaUrl($this->image, getStorageDisk()) : null);
            $preview = ['title' => $this->title ?: 'Título de la página', 'subtitle' => $this->subtitle, 'image' => $imageUrl, 'overlay' => $this->overlay, 'align' => $this->align];
        }

        return view('livewire.pages.admin.page-banners.page-banners', ['pages' => $pages, 'saved' => $saved, 'preview' => $preview]);
    }
}
