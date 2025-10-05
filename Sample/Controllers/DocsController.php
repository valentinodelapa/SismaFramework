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
     * Parser Markdown semplificato
     *
     * Converte markdown in HTML. Per progetti reali, considera l'uso di
     * librerie come Parsedown o league/commonmark
     */
    private function parseMarkdown(string $markdown): string
    {
        // Headers
        $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $markdown);
        $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $html);

        // Code blocks con highlight
        $html = preg_replace_callback('/```(\w+)?\n(.*?)\n```/s', function($matches) {
            $lang = $matches[1] ?? '';
            $code = htmlspecialchars($matches[2]);
            return '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
        }, $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // Bold
        $html = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // Unordered lists
        $html = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $html);

        // Ordered lists
        $html = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $html);

        // Paragrafi
        $html = preg_replace('/^(?!<[h|u|l|p|c])(.*?)$/m', '<p>$1</p>', $html);

        // Pulisci paragrafi vuoti
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);

        // Horizontal rule
        $html = preg_replace('/^---$/m', '<hr>', $html);

        // Blockquote
        $html = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $html);

        return $html;
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
     * Struttura della documentazione organizzata per sezioni
     */
    private function getDocsStructure(): array
    {
        return [
            'Introduzione' => [
                ['file' => 'index', 'title' => 'Panoramica'],
                ['file' => 'installation', 'title' => 'Installazione'],
                ['file' => 'getting-started', 'title' => 'Getting Started'],
                ['file' => 'overview', 'title' => 'Introduzione al Framework'],
            ],
            'Core Concepts' => [
                ['file' => 'module-architecture', 'title' => 'Architettura Modulare'],
                ['file' => 'controllers', 'title' => 'Controller'],
                ['file' => 'views', 'title' => 'Views'],
                ['file' => 'conventions', 'title' => 'Convenzioni'],
            ],
            'ORM & Database' => [
                ['file' => 'orm', 'title' => 'ORM - Introduzione'],
                ['file' => 'advanced-orm', 'title' => 'ORM Avanzato'],
                ['file' => 'orm-additional-features', 'title' => 'Features Aggiuntive'],
                ['file' => 'custom-types', 'title' => 'Custom Types'],
            ],
            'Funzionalità' => [
                ['file' => 'forms', 'title' => 'Form e Validazione'],
                ['file' => 'security', 'title' => 'Sicurezza'],
                ['file' => 'internationalization', 'title' => 'Internazionalizzazione'],
                ['file' => 'static-assets', 'title' => 'Asset Statici'],
            ],
            'Avanzato' => [
                ['file' => 'enumerations', 'title' => 'Enumerations'],
                ['file' => 'traits', 'title' => 'Traits'],
                ['file' => 'helper-classes', 'title' => 'Helper Classes'],
                ['file' => 'data-fixtures', 'title' => 'Data Fixtures'],
                ['file' => 'debug-bar', 'title' => 'Debug Bar'],
            ],
            'Testing & Deploy' => [
                ['file' => 'testing', 'title' => 'Testing'],
                ['file' => 'performance', 'title' => 'Performance'],
                ['file' => 'deployment', 'title' => 'Deployment'],
                ['file' => 'troubleshooting', 'title' => 'Troubleshooting'],
            ],
            'Reference' => [
                ['file' => 'api-reference', 'title' => 'API Reference'],
                ['file' => 'configuration-reference', 'title' => 'Configurazione'],
                ['file' => 'best-practices', 'title' => 'Best Practices'],
            ],
        ];
    }
}
