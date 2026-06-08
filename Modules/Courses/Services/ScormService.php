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
        $zip = new ZipArchive;
        $absoluteZipPath = storage_path('app/' . $zipFilePath);
        
        $extractPath = 'public/scorm/' . $curriculumId;
        $absoluteExtractPath = storage_path('app/' . $extractPath);

        if ($zip->open($absoluteZipPath) === TRUE) {
            $zip->extractTo($absoluteExtractPath);
            $zip->close();

            // Try to parse imsmanifest.xml
            $manifestPath = $absoluteExtractPath . '/imsmanifest.xml';
            if (file_exists($manifestPath)) {
                $entryPoint = $this->parseManifestForEntryPoint($manifestPath);
                if ($entryPoint) {
                    return 'storage/scorm/' . $curriculumId . '/' . ltrim($entryPoint, '/');
                }
            }

            // Fallbacks if imsmanifest.xml is missing or unparseable
            $fallbacks = ['index.html', 'story.html', 'story_html5.html'];
            foreach ($fallbacks as $fallback) {
                if (file_exists($absoluteExtractPath . '/' . $fallback)) {
                    return 'storage/scorm/' . $curriculumId . '/' . $fallback;
                }
            }

            Log::error("SCORM entry point not found for curriculum $curriculumId");
            return null;
        }

        Log::error("Failed to open SCORM zip file: $absoluteZipPath");
        return null;
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
