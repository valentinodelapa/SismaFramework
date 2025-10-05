<?php

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * Controller per la homepage e presentazione del framework
 *
 * @author Valentino de Lapa
 */
class HomeController extends BaseController
{
    public function __construct(DataMapper $dataMapper = new DataMapper())
    {
        parent::__construct($dataMapper);
        $this->vars['metaUrl'] = Router::getMetaUrl();
    }

    /**
     * Homepage - Landing page del framework
     *
     * URL: /home/index
     */
    public function index(): Response
    {
        $this->vars['pageTitle'] = 'SismaFramework - PHP MVC Framework';

        // Features del framework
        $this->vars['features'] = [
            [
                'icon' => '🏗️',
                'title' => 'Architettura MVC',
                'description' => 'Separazione netta tra logica, dati e presentazione con pattern MVC robusto',
                'link' => '/docs/view/file/overview'
            ],
            [
                'icon' => '💾',
                'title' => 'ORM Potente',
                'description' => 'Data Mapper con lazy loading, relazioni automatiche e crittografia a livello di proprietà',
                'link' => '/docs/view/file/orm'
            ],
            [
                'icon' => '🔐',
                'title' => 'Sicurezza Integrata',
                'description' => 'Autenticazione MFA, sistema di permessi con Voters, protezione CSRF',
                'link' => '/docs/view/file/security'
            ],
            [
                'icon' => '📝',
                'title' => 'Form Avanzati',
                'description' => 'Validazione, ripopolamento automatico, gestione errori integrata',
                'link' => '/docs/view/file/forms'
            ],
            [
                'icon' => '🔗',
                'title' => 'URL Rewriting',
                'description' => 'URL SEO-friendly in kebab-case con routing automatico',
                'link' => '/docs/view/file/controllers'
            ],
            [
                'icon' => '🌍',
                'title' => 'Internazionalizzazione',
                'description' => 'Supporto completo per applicazioni multilingua',
                'link' => '/docs/view/file/internationalization'
            ],
            [
                'icon' => '⚡',
                'title' => 'Performance',
                'description' => 'Cache entità, lazy loading, query ottimizzate',
                'link' => '/docs/view/file/performance'
            ],
            [
                'icon' => '🧪',
                'title' => 'Testing',
                'description' => 'Supporto PHPUnit con coverage >85%',
                'link' => '/docs/view/file/testing'
            ]
        ];

        // Quick start steps
        $this->vars['quickStartSteps'] = [
            '1. Installa tramite Composer o download diretto',
            '2. Configura database in Config/config.php',
            '3. Crea il tuo primo modulo',
            '4. Definisci Entity e Model',
            '5. Crea Controller e Views'
        ];

        return Render::generateView('home/index', $this->vars);
    }

    /**
     * Pagina Features dettagliate
     *
     * URL: /home/features
     */
    public function features(): Response
    {
        $this->vars['pageTitle'] = 'Features - SismaFramework';

        return Render::generateView('home/features', $this->vars);
    }

    /**
     * Redirect alla homepage se si accede alla root
     *
     * URL: /
     */
    public function welcome(): Response
    {
        return Router::redirect('/home/index');
    }
}
