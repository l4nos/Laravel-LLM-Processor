<?php

namespace Lanos\LLMProcessor\Services;

class TemplateProcessor
{
    /**
     * Process template with data.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function process(string $template, array $data): string
    {
        // Find all {{variable}} patterns
        $variables = $this->extractVariables($template);
        
        // Replace each variable with its value
        foreach ($variables as $variable) {
            $value = $this->getValueFromData($variable, $data);
            $template = str_replace("{{{$variable}}}", $value, $template);
        }
        
        return $template;
    }

    /**
     * Extract variables from template.
     *
     * @param string $template
     * @return array
     */
    public function extractVariables(string $template): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $template, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Flatten array to dot notation.
     *
     * @param array $array
     * @param string $prefix
     * @return array
     */
    public function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value) && !empty($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Get value from data using dot notation.
     *
     * @param string $key
     * @param array $data
     * @return string
     */
    private function getValueFromData(string $key, array $data): string
    {
        // Handle dot notation
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $current = $data;
            
            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    return config('llm-processor.processing.missing_variable_placeholder', '');
                }
                $current = $current[$k];
            }
            
            return $this->formatValue($current);
        }
        
        // Handle simple key
        if (!isset($data[$key])) {
            return config('llm-processor.processing.missing_variable_placeholder', '');
        }
        
        return $this->formatValue($data[$key]);
    }

    /**
     * Format value for template.
     *
     * @param mixed $value
     * @return string
     */
    private function formatValue($value): string
    {
        if (is_null($value)) {
            return config('llm-processor.processing.missing_variable_placeholder', '');
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }
}