<?php

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * Controller per la visualizzazione della documentazione
 *
 * Legge i file Markdown dalla cartella docs/ e li renderizza in HTML
 *
 * @author Valentino de Lapa
 */
class DocsController extends BaseController
{
    private string $docsPath;

    public function __construct(DataMapper $dataMapper = new DataMapper())
    {
        parent::__construct($dataMapper);
        $this->vars['metaUrl'] = Router::getMetaUrl();
        $this->docsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR;
    }

    /**
     * Indice della documentazione
     *
     * URL: /docs/index
     */
    public function index(): Response
    {
        $this->vars['pageTitle'] = 'Documentazione - SismaFramework';

        // Leggi la struttura della documentazione
        $this->vars['docsSections'] = $this->getDocsStructure();

        return Render::generateView('docs/index', $this->vars);
    }

    /**
     * Visualizza un file di documentazione
     *
     * URL: /docs/view/file/getting-started
     * URL: /docs/view/file/orm
     */
    public function view(string $file): Response
    {
        $filePath = $this->docsPath . $file . '.md';

        // Verifica che il file esista
        if (!file_exists($filePath)) {
            return Router::redirect('/docs/index');
        }

        // Leggi il contenuto Markdown
        $markdownContent = file_get_contents($filePath);

        // Converti Markdown in HTML
        $this->vars['htmlContent'] = $this->parseMarkdown($markdownContent);
        $this->vars['currentFile'] = $file;
        $this->vars['pageTitle'] = $this->extractTitle($markdownContent);

        // Sidebar con navigazione
        $this->vars['docsSections'] = $this->getDocsStructure();

        return Render::generateView('docs/viewer', $this->vars);
    }

    /**
     * Parser Markdown migliorato
     *
     * Converte markdown in HTML con supporto per:
     * - Code blocks (con protezione del contenuto)
     * - Tabelle
     * - Headers, bold, italic, links
     * - Liste
     */
    private function parseMarkdown(string $markdown): string
    {
        // Step 1: Proteggi i code blocks (non devono essere processati)
        $codeBlocks = [];
        $html = preg_replace_callback('/```([^\r\n]*)\r?\n(.*?)\r?\n```\s*/s', function($matches) use (&$codeBlocks) {
            $lang = trim($matches[1]) ?: 'plaintext';
            $code = $matches[2];

            $placeholder = '___CODEBLOCK_' . count($codeBlocks) . '___';
            $codeBlocks[$placeholder] = '<pre><code class="language-' . htmlspecialchars($lang) . '">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></pre>';

            return "\n" . $placeholder . "\n";
        }, $markdown);

        // Step 2: Proteggi inline code
        $inlineCodes = [];
        $html = preg_replace_callback('/`([^`]+)`/', function($matches) use (&$inlineCodes) {
            $placeholder = '___INLINECODE_' . count($inlineCodes) . '___';
            $inlineCodes[$placeholder] = '<code>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</code>';
            return $placeholder;
        }, $html);

        // Step 3: Processa tabelle
        $html = preg_replace_callback('/^\|(.+)\|[ ]*\n\|[\s\-:|]+\|[ ]*\n((?:\|.+\|[ ]*\n?)+)/m', function($matches) {
            $headers = array_map('trim', explode('|', trim($matches[1], '|')));
            $rows = array_filter(explode("\n", trim($matches[2])));

            $table = '<table class="table table-striped table-bordered">';

            // Header
            $table .= '<thead><tr>';
            foreach ($headers as $header) {
                $table .= '<th>' . $this->parseInlineMarkdown(trim($header)) . '</th>';
            }
            $table .= '</tr></thead>';

            // Body
            $table .= '<tbody>';
            foreach ($rows as $row) {
                $cells = array_map('trim', explode('|', trim($row, '|')));
                $table .= '<tr>';
                foreach ($cells as $cell) {
                    $table .= '<td>' . $this->parseInlineMarkdown(trim($cell)) . '</td>';
                }
                $table .= '</tr>';
            }
            $table .= '</tbody></table>';

            return $table;
        }, $html);

        // Step 4: Headers (dal più specifico al meno)
        $html = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);

        // Step 5: Horizontal rule
        $html = preg_replace('/^[\-\*]{3,}$/m', '<hr>', $html);

        // Step 6: Blockquote
        $html = preg_replace('/^> (.+)$/m', '<blockquote class="blockquote">$1</blockquote>', $html);

        // Step 7: Unordered lists (supporta indentazione)
        $html = preg_replace_callback('/(?:^[ ]{0,4}[\*\-\+] .+\n?)+/m', function($matches) {
            $lines = explode("\n", trim($matches[0]));
            $result = '<ul>';
            $inNested = false;

            foreach ($lines as $line) {
                if (preg_match('/^[ ]{2,4}[\*\-\+] (.+)$/', $line, $m)) {
                    // Lista annidata (indentata)
                    if (!$inNested) {
                        $result .= '<ul>';
                        $inNested = true;
                    }
                    $result .= '<li>' . trim($m[1]) . '</li>';
                } else if (preg_match('/^[\*\-\+] (.+)$/', $line, $m)) {
                    // Lista principale
                    if ($inNested) {
                        $result .= '</ul>';
                        $inNested = false;
                    }
                    $result .= '<li>' . trim($m[1]) . '</li>';
                }
            }

            if ($inNested) {
                $result .= '</ul>';
            }
            $result .= '</ul>';

            return $result;
        }, $html);

        // Step 8: Ordered lists
        $html = preg_replace_callback('/(?:^\d+\. .+\n?)+/m', function($matches) {
            $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $matches[0]);
            return '<ol>' . $items . '</ol>';
        }, $html);

        // Step 9: Inline formatting (bold, italic, links)
        $html = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*([^\*\n]+)\*/', '<em>$1</em>', $html);

        // Links: converte link .md in percorsi corretti
        $html = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
            $text = $matches[1];
            $url = $matches[2];

            // Se è un link relativo .md, convertilo in /docs/view/file/
            if (preg_match('/^([^\/]+)\.md$/', $url, $m)) {
                $url = '/docs/view/file/' . $m[1];
            } else if (preg_match('/^([^\/]+)\.md#(.+)$/', $url, $m)) {
                // Con anchor
                $url = '/docs/view/file/' . $m[1] . '#' . $m[2];
            }

            return '<a href="' . htmlspecialchars($url) . '">' . $text . '</a>';
        }, $html);

        // Step 10: Paragrafi (solo linee non vuote che non sono già HTML)
        $lines = explode("\n", $html);
        $result = [];
        $inParagraph = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Salta linee vuote, già formattate, e placeholder dei code blocks
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

        // Step 11: Ripristina code blocks e inline code
        foreach ($codeBlocks as $placeholder => $code) {
            $html = str_replace($placeholder, $code, $html);
        }
        foreach ($inlineCodes as $placeholder => $code) {
            $html = str_replace($placeholder, $code, $html);
        }

        // Step 12: Pulisci paragrafi vuoti
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);

        return $html;
    }

    /**
     * Parse inline markdown (per celle di tabelle)
     */
    private function parseInlineMarkdown(string $text): string
    {
        $text = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
        return $text;
    }

    /**
     * Estrae il titolo dal contenuto Markdown (primo # heading)
     */
    private function extractTitle(string $markdown): string
    {
        if (preg_match('/^# (.+)$/m', $markdown, $matches)) {
            return $matches[1];
        }
        return 'Documentazione';
    }

    /**
     * Estrae la struttura della documentazione da index.md
     *
     * Legge il file index.md e costruisce l'array delle sezioni
     * basandosi sulle liste annidate presenti nel file.
     */
    private function getDocsStructure(): array
    {
        $indexPath = $this->docsPath . 'index.md';

        if (!file_exists($indexPath)) {
            return [];
        }

        $content = file_get_contents($indexPath);
        $lines = explode("\n", $content);

        $structure = [];
        $currentSection = null;

        foreach ($lines as $line) {
            // Sezione principale: * **Nome Sezione**
            if (preg_match('/^\* \*\*(.+?)\*\*/', $line, $matches)) {
                $currentSection = trim($matches[1]);
                $structure[$currentSection] = [];
            }
            // Elemento lista annidata: [Titolo](file.md)
            else if (preg_match('/^\s+\* \[(.+?)\]\((.+?)\.md\)/', $line, $matches)) {
                if ($currentSection !== null) {
                    $title = trim($matches[1]);
                    // Rimuovi ** se presente
                    $title = str_replace(['**', '*'], '', $title);
                    $file = trim($matches[2]);

                    $structure[$currentSection][] = [
                        'file' => $file,
                        'title' => $title
                    ];
                }
            }
        }

        return $structure;
    }
}
