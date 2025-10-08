<?php

$markdown = file_get_contents(__DIR__ . '/../docs/forms.md');

// Step 1: Proteggi i code blocks
$codeBlocks = [];
$html = preg_replace_callback('/```([^\n]*)\n(.*?)\n```/s', function($matches) use (&$codeBlocks) {
    $lang = trim($matches[1]) ?: 'plaintext';
    $code = $matches[2];

    $placeholder = '___CODEBLOCK_' . count($codeBlocks) . '___';
    $codeBlocks[$placeholder] = '<pre><code class="language-' . htmlspecialchars($lang) . '">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></pre>';

    return "\n" . $placeholder . "\n";
}, $markdown);

echo "=== DOPO STEP 1 (protezione code blocks) ===\n";
echo "Numero di code blocks trovati: " . count($codeBlocks) . "\n\n";
echo "Placeholders:\n";
foreach ($codeBlocks as $placeholder => $code) {
    echo $placeholder . " => " . substr($code, 0, 100) . "...\n";
}

echo "\n\n=== HTML CON PLACEHOLDER ===\n";
echo substr($html, 0, 2000);

// Simula il resto del processing...
// Step 2: Headers
$html = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $html);
$html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html);
$html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
$html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);

// Step 3: Paragrafi
$lines = explode("\n", $html);
$result = [];
$inParagraph = false;

foreach ($lines as $line) {
    $trimmed = trim($line);

    if (empty($trimmed) || preg_match('/^<(h\d|ul|ol|li|table|pre|hr|blockquote)/', $trimmed) || preg_match('/^___CODEBLOCK_\d+___$/', $trimmed)) {
        if ($inParagraph) {
            $result[] = '</p>';
            $inParagraph = false;
        }
        $result[] = $line;
    } else {
        if (!$inParagraph) {
            $result[] = '<p>';
            $inParagraph = true;
        }
        $result[] = $line;
    }
}

if ($inParagraph) {
    $result[] = '</p>';
}

$html = implode("\n", $result);

echo "\n\n=== DOPO PARAGRAFI ===\n";
echo substr($html, 0, 2000);

// Step 4: Ripristina code blocks
foreach ($codeBlocks as $placeholder => $code) {
    $html = str_replace($placeholder, $code, $html);
}

echo "\n\n=== DOPO RIPRISTINO ===\n";
echo substr($html, 0, 2000);

echo "\n\n=== VERIFICA FINALE ===\n";
echo "Numero di <pre> nel risultato: " . substr_count($html, '<pre>') . "\n";
echo "Numero di placeholder rimasti: " . preg_match_all('/___CODEBLOCK_\d+___/', $html) . "\n";
