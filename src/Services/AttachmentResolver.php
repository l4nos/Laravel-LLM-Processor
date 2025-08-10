<?php

namespace Lanos\LLMProcessor\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentResolver
{
    /**
     * Timeout for downloading attachments.
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Create a new AttachmentResolver instance.
     */
    public function __construct()
    {
        $this->timeout = config('llm-processor.storage.attachment_timeout', 30);
    }

    /**
     * Resolve attachment paths to actual attachments.
     *
     * @param array $attachmentPaths
     * @param array $modelData
     * @return array
     */
    public function resolve(array $attachmentPaths, array $modelData): array
    {
        $attachments = [];
        
        foreach ($attachmentPaths as $path) {
            // Extract URL from model data using path
            $url = $this->extractUrlFromData($path, $modelData);
            
            if ($url && $this->validateUrl($url)) {
                $attachment = $this->downloadFile($url);
                
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }
        }
        
        return $attachments;
    }

    /**
     * Download file from URL and convert to data URL.
     *
     * @param string $url
     * @return array|null
     */
    public function downloadFile(string $url): ?array
    {
        try {
            $response = Http::timeout($this->timeout)->get($url);
            
            if ($response->successful()) {
                $content = $response->body();
                $mimeType = $response->header('content-type', 'application/octet-stream');
                
                // Convert to base64 data URL
                $base64 = base64_encode($content);
                $dataUrl = "data:{$mimeType};base64,{$base64}";
                
                return [
                    'url' => $dataUrl,
                    'original_url' => $url,
                    'mime_type' => $mimeType,
                ];
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the entire process
            report($e);
        }
        
        return null;
    }

    /**
     * Validate URL is accessible.
     *
     * @param string $url
     * @return bool
     */
    public function validateUrl(string $url): bool
    {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if resource exists with a HEAD request
        try {
            $response = Http::timeout($this->timeout)->head($url);
            return $response->successful();
        } catch (\Exception $e) {
            // Log the error but don't fail the entire process
            report($e);
            return false;
        }
    }

    /**
     * Extract URL from model data using dot notation path.
     *
     * @param string $path
     * @param array $data
     * @return string|null
     */
    private function extractUrlFromData(string $path, array $data): ?string
    {
        // Handle dot notation
        if (strpos($path, '.') !== false) {
            $keys = explode('.', $path);
            $current = $data;
            
            foreach ($keys as $key) {
                if (!isset($current[$key])) {
                    return null;
                }
                $current = $current[$key];
            }
            
            return $this->getUrlFromStringOrArray($current);
        }
        
        // Handle simple key
        if (!isset($data[$path])) {
            return null;
        }
        
        return $this->getUrlFromStringOrArray($data[$path]);
    }

    /**
     * Extract URL from string or array.
     *
     * @param mixed $value
     * @return string|null
     */
    private function getUrlFromStringOrArray($value): ?string
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_array($value)) {
            // Look for common URL fields
            foreach (['url', 'path', 'link'] as $key) {
                if (isset($value[$key]) && is_string($value[$key])) {
                    return $value[$key];
                }
            }
        }
        
        return null;
    }
}