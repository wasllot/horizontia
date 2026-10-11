<?php

namespace Modules\Courses\Services;

use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScormService
{
    /**
     * Extract a SCORM zip file and find its entry point.
     *
     * @param string $zipFilePath The path to the uploaded zip file (relative to storage/app)
     * @param int $curriculumId The ID of the curriculum this SCORM belongs to
     * @return string|null Returns the relative path to the entry HTML file, or null on failure.
     */
    public function processScormZip($zipFilePath, $curriculumId)
    {
        // Dedicated private disk (R2 bucket or non-public local disk): extract in a temp dir and store the files
        // there; ScormAssetController serves them after an enrollment check. A single shared disk keeps the legacy
        // behaviour (extracted under public/storage/scorm).
        if (getPrivateDisk() !== getStorageDisk()) {
            return $this->processOnRemoteDisk($zipFilePath, $curriculumId, getPrivateDisk());
        }

        $uploadDisk = getStorageDisk();

        $zip = new ZipArchive;

        // Resolve zip path via the configured public disk (works regardless of
        // whether the disk root is storage/app/public or public/storage)
        $absoluteZipPath = Storage::disk($uploadDisk)->path($zipFilePath);

        // Extract to public/storage/scorm/{id}/ so the web server can serve it
        $absoluteExtractPath = public_path('storage/scorm/' . $curriculumId);

        if ($zip->open($absoluteZipPath) === TRUE) {
            // Replacing a package must not leave files of the previous one (stale index.html fallbacks).
            \Illuminate\Support\Facades\File::deleteDirectory($absoluteExtractPath);
            $safe = $this->safeExtract($zip, $absoluteExtractPath);
            $zip->close();
            if (!$safe) {
                \Illuminate\Support\Facades\File::deleteDirectory($absoluteExtractPath);
                return null;
            }

            $entry = $this->resolveEntryPoint($absoluteExtractPath);
            if ($entry) {
                return 'storage/scorm/' . $curriculumId . '/' . $entry;
            }

            Log::error("SCORM entry point not found for curriculum $curriculumId");
            return null;
        }

        Log::error("Failed to open SCORM zip file: $absoluteZipPath");
        return null;
    }

    /** Web assets a SCORM package legitimately ships. Anything executable on a server is never extracted. */
    public const ALLOWED_EXTENSIONS = [
        'html', 'htm', 'xhtml', 'js', 'mjs', 'css', 'json', 'xml', 'xsd', 'dtd', 'txt', 'md', 'csv', 'map', 'vtt', 'srt',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp',
        'mp3', 'mp4', 'webm', 'ogg', 'ogv', 'wav', 'm4a', 'm4v', 'mov',
        'woff', 'woff2', 'ttf', 'otf', 'eot', 'pdf', 'swf', 'wasm',
    ];
    public const MAX_ENTRIES = 8000;
    public const MAX_UNCOMPRESSED_BYTES = 800 * 1024 * 1024;

    /**
     * Extract a package entry by entry: refuse path traversal, absolute paths, symlinks, zip bombs and, above all,
     * server-side scripts (a .php file under the public directory would otherwise be executed by the web server).
     * Files outside the allow-list are skipped; a package that needs them is rejected as invalid by the entry check.
     */
    public function safeExtract(ZipArchive $zip, string $destination): bool
    {
        if ($zip->numFiles > self::MAX_ENTRIES) {
            Log::warning('SCORM package rejected: too many entries (' . $zip->numFiles . ')');
            return false;
        }

        $total = 0;
        $root = rtrim(str_replace('\\', '/', $destination), '/');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) $stat['name']);
            if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('~(^|/)\.\.(/|$)~', $name) || preg_match('~^[A-Za-z]:~', $name)) {
                Log::warning("SCORM package rejected: unsafe entry name [$name]");
                return false;
            }
            $total += (int) $stat['size'];
            if ($total > self::MAX_UNCOMPRESSED_BYTES) {
                Log::warning('SCORM package rejected: uncompressed size too large');
                return false;
            }

            // Symbolic links are marked in the unix mode stored in the external attributes.
            $zip->getExternalAttributesIndex($i, $opsys, $attr);
            if ($opsys === ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
                continue;
            }

            if (str_ends_with($name, '/')) {
                @mkdir($root . '/' . $name, 0755, true);
                continue;
            }

            $base = basename($name);
            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            if ($base[0] === '.' || !in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $target = $root . '/' . $name;
            @mkdir(dirname($target), 0755, true);
            $in = $zip->getStream($stat['name']);
            if (!$in) {
                continue;
            }
            $out = fopen($target, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
        }

        return true;
    }

    /**
     * Returns the entry point relative to the package root, or null.
     */
    private function resolveEntryPoint(string $extractPath): ?string
    {
        $manifestPath = $extractPath . '/imsmanifest.xml';
        if (file_exists($manifestPath)) {
            $entryPoint = $this->parseManifestForEntryPoint($manifestPath);
            if ($entryPoint) {
                return ltrim($entryPoint, '/');
            }
        }

        // Fallbacks if imsmanifest.xml is missing or unparseable
        foreach (['index.html', 'story.html', 'story_html5.html'] as $fallback) {
            if (file_exists($extractPath . '/' . $fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * R2/S3 flow. media_path is stored as scorm/{id}/{entry}; files are served by ScormAssetController.
     */
    private function processOnRemoteDisk($zipFilePath, $curriculumId, string $uploadDisk): ?string
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'scorm');
        $tmpDir = sys_get_temp_dir() . '/scorm_' . $curriculumId . '_' . uniqid();

        try {
            $in = Storage::disk($uploadDisk)->readStream($zipFilePath);
            file_put_contents($tmpZip, $in);
            if (is_resource($in)) {
                fclose($in);
            }

            $zip = new ZipArchive;
            if ($zip->open($tmpZip) !== true) {
                Log::error("Failed to open SCORM zip file: $zipFilePath");
                return null;
            }
            mkdir($tmpDir, 0755, true);
            $safe = $this->safeExtract($zip, $tmpDir);
            $zip->close();
            if (!$safe) {
                return null;
            }

            $entry = $this->resolveEntryPoint($tmpDir);
            if (!$entry) {
                Log::error("SCORM entry point not found for curriculum $curriculumId");
                return null;
            }

            $private = Storage::disk(getPrivateDisk());
            $private->deleteDirectory("scorm/$curriculumId");
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($tmpDir))), '/');
                $stream = fopen($file->getPathname(), 'rb');
                $private->writeStream("scorm/$curriculumId/$relative", $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return "scorm/$curriculumId/$entry";
        } finally {
            @unlink($tmpZip);
            if (is_dir($tmpDir)) {
                \Illuminate\Support\Facades\File::deleteDirectory($tmpDir);
            }
            Storage::disk($uploadDisk)->delete($zipFilePath);
        }
    }

    /**
     * Parse the SCORM imsmanifest.xml to find the main resource's href.
     */
    private function parseManifestForEntryPoint($manifestPath)
    {
        try {
            $xml = simplexml_load_file($manifestPath);
            if ($xml === false) {
                return null;
            }

            // Register namespaces to query them
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('def', isset($namespaces['']) ? $namespaces[''] : 'http://www.imsproject.org/xsd/imscp_rootv1p1p2');

            // Find the default organization's item
            $organizations = $xml->organizations;
            $defaultOrgId = (string) $organizations['default'];
            
            // Find the identifierref of the first item
            $identifierref = null;
            foreach ($organizations->organization as $org) {
                if ((string)$org['identifier'] === $defaultOrgId || empty($defaultOrgId)) {
                    if (isset($org->item)) {
                        $identifierref = (string) $org->item['identifierref'];
                        break;
                    }
                }
            }

            // Find the resource with that identifier
            if ($identifierref && isset($xml->resources->resource)) {
                foreach ($xml->resources->resource as $resource) {
                    if ((string)$resource['identifier'] === $identifierref) {
                        return (string)$resource['href'];
                    }
                }
            }
            
            // If the above fails, just return the href of the first resource
            if (isset($xml->resources->resource[0])) {
                return (string)$xml->resources->resource[0]['href'];
            }

        } catch (\Exception $e) {
            Log::error("Error parsing imsmanifest.xml: " . $e->getMessage());
        }

        return null;
    }
}
