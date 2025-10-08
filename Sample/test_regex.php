<?php

$markdown = file_get_contents(__DIR__ . '/../docs/forms.md');

echo "=== TEST REGEX ===\n";
$count = preg_match_all('/```([^\r\n]*)\r?\n(.*?)\r?\n```\s*/s', $markdown, $matches);
echo "Blocchi trovati con regex attuale: $count\n\n";

if ($count > 0) {
    echo "=== TUTTI I MATCH ===\n";
    for ($i = 0; $i < count($matches[0]); $i++) {
        echo "Match $i:\n";
        echo "Lang: [" . $matches[1][$i] . "]\n";
        echo "Code (primi 80 char): " . substr($matches[2][$i], 0, 80) . "...\n\n";
    }
} else {
    echo "NESSUN MATCH TROVATO!\n";
}
