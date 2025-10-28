<?php
// Usage: php scripts/fix_swagger_examples.php /path/to/api-docs.json
// Replaces example:null for common id fields with realistic test values.
if ($argc < 2) {
    echo "Usage: php scripts/fix_swagger_examples.php /path/to/api-docs.json\n";
    exit(1);
}
$path = $argv[1];
if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit(1);
}
$json = file_get_contents($path);
$data = json_decode($json, true);
if ($data === null) {
    echo "Invalid JSON in $path\n";
    exit(1);
}

function fix_examples(&$node) {
    if (!is_array($node)) return;
    // If we have a properties object, inspect keys
    if (isset($node['properties']) && is_array($node['properties'])) {
        foreach ($node['properties'] as $key => &$prop) {
            // If example exists and is null, set based on key/type
            if (array_key_exists('example', $prop) && $prop['example'] === null) {
                $lower = strtolower($key);
                $type = isset($prop['type']) ? $prop['type'] : null;
                $format = isset($prop['format']) ? $prop['format'] : null;
                if ($lower === 'id' || substr($lower, -3) === '_id' || substr($lower, -2) === 'id') {
                    if ($format === 'uuid' || $type === 'string') {
                        // sample UUID
                        $prop['example'] = '550e8400-e29b-41d4-a716-446655440000';
                    } elseif ($type === 'integer') {
                        $prop['example'] = 1001;
                    } else {
                        $prop['example'] = '550e8400-e29b-41d4-a716-446655440000';
                    }
                } elseif ($lower === 'motifblocage' || $lower === 'motif_blocage') {
                    $prop['example'] = "Aucun";
                } else {
                    // generic fallback for null examples: set empty string to avoid null
                    $prop['example'] = "";
                }
            }
            // Recurse inside property
            fix_examples($prop);
        }
        unset($prop);
    }
    // Also traverse other keys
    foreach ($node as $k => &$v) {
        if (is_array($v)) fix_examples($v);
    }
}

fix_examples($data);
$new = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($new === false) {
    echo "Failed to encode JSON\n";
    exit(1);
}
file_put_contents($path, $new);
echo "Patched $path\n";
exit(0);
