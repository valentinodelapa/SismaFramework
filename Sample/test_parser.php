<?php

// Test parser markdown per debug code blocks

$markdown = file_get_contents('../docs/forms.md');

// Metodo attuale: split
$codeBlocks = [];
$parts = preg_split('/^```/m', $markdown);

echo "Numero di parti trovate: " . count($parts) . "\n\n";

for ($i = 0; $i < min(5, count($parts)); $i++) {
    echo "--- PARTE $i ---\n";
    echo substr($parts[$i], 0, 100) . "...\n\n";
}

echo "\n\nCode blocks trovati:\n";
for ($i = 1; $i < count($parts); $i += 2) {
    if (isset($parts[$i])) {
        $lines = explode("\n", $parts[$i], 2);
        $lang = trim($lines[0]);
        echo "Block " . (($i-1)/2 + 1) . ": lang=[$lang], length=" . strlen($lines[1] ?? '') . "\n";
    }
}
