<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Modules\Courses\Models\Media;

class OptimizeMedia extends Command
{
    protected $signature = 'media:optimize
        {--width=640 : Width of the course thumbnail variants}
        {--force : Rebuild variants that already exist}';

    protected $description = 'Create WebP variants: bundled brand images (public/images) and course thumbnails (storage disk).';

    public function handle(): int
    {
        $this->brandImages();
        $this->logos();
        $this->courseThumbnails((int) $this->option('width'));

        return self::SUCCESS;
    }

    private function brandImages(): void
    {
        $done = $saved = 0;
        $files = array_merge(   // array_merge, not `+`: the numeric keys of each glob() would overwrite each other
            glob(public_path('images/**/*.{jpg,jpeg,png}'), GLOB_BRACE),
            glob(public_path('images/*.{jpg,jpeg,png}'), GLOB_BRACE),
            glob(public_path('storage/optionbuilder/uploads/*.{jpg,jpeg,png}'), GLOB_BRACE)   // images uploaded through the page/option builders
        );
        foreach ($files as $file) {
            $target = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
            if (!$this->option('force') && is_file($target) && filemtime($target) >= filemtime($file)) {
                continue;
            }
            // Logos and small assets are not worth converting.
            if (filesize($file) < 120 * 1024) {
                continue;
            }
            $image = Image::read($file)->scaleDown(width: 1920);
            $image->toWebp(80)->save($target);
            $saved += filesize($file) - filesize($target);
            $done++;
        }
        $this->info("Brand images converted: $done (saved " . round($saved / 1048576, 1) . ' MB per full load)');
    }

    /** The logos are 1–2k px wide but shown at 175–250 px: build light `.sm.webp` variants (see logoAsset()). */
    private function logos(): void
    {
        $done = 0;
        foreach (['images/nuevo/logo-horizontia.png' => 480, 'images/nuevo/Logo-Horizontia-footer.png' => 520, 'images/design/sunburst-icon.png' => 160] as $path => $width) {
            $file = public_path($path);
            $target = preg_replace('/\.png$/i', '.sm.webp', $file);
            if (!is_file($file) || (!$this->option('force') && is_file($target) && filemtime($target) >= filemtime($file))) {
                continue;
            }
            Image::read($file)->scaleDown(width: $width)->toWebp(90)->save($target);
            $done++;
        }
        $this->info("Logo variants built: $done");
    }

    private function courseThumbnails(int $width): void
    {
        $count = 0;
        foreach (Media::where('type', 'thumbnail')->pluck('path')->unique() as $path) {
            $variant = 'thumbnails/w' . $width . '/' . md5($path) . '.webp';
            if (!$this->option('force') && Storage::disk(getStorageDisk())->exists($variant)) {
                continue;
            }
            mediaThumb($path, $width);
            $count++;
        }
        $this->info("Course thumbnail variants built: $count");
    }
}
