<?php

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\Interfaces\Controllers\DefaultControllerInterface;
use SismaFramework\Orm\CustomTypes\SismaDateTime;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Sample\Entities\SampleBaseEntity;
use SismaFramework\Sample\Enumerations\ArticleStatus;
use SismaFramework\Sample\Models\SampleBaseEntityModel;
use SismaFramework\Sample\Models\SampleDependentEntityModel;
use SismaFramework\Sample\Models\SampleReferencedEntityModel;
use SismaFramework\Security\HttpClasses\Authentication;

/**
 * Controller di esempio che mostra tutte le funzionalità del framework
 *
 * Questo controller dimostra:
 * - Autowiring di servizi (Request, Authentication)
 * - Binding automatico di parametri tipizzati
 * - Entity injection dall'URL
 * - Gestione di enum nei parametri
 * - Interazione con i Model
 * - Rendering di viste con dati
 *
 * @author Valentino de Lapa
 */
class SampleController extends BaseController implements DefaultControllerInterface
{
    public function __construct(DataMapper $dataMapper = new DataMapper())
    {
        parent::__construct($dataMapper);
        $this->vars['metaUrl'] = $this->router->getMetaUrl();
    }

    /**
     * Action base - Lista tutti gli articoli
     *
     * URL: /sample/index
     */
    public function index(): Response
    {
        $model = new SampleBaseEntityModel($this->dataMapper);

        // Recupera tutti gli articoli
        $this->vars['articles'] = $model->getEntityCollection();

        // Recupera solo articoli pubblicati
        $this->vars['publishedArticles'] = $model->getPublishedArticles(5);

        // Recupera articoli in evidenza
        $this->vars['featuredArticles'] = $model->getFeaturedArticles();

        return $this->render->generateView('sample/index', $this->vars);
    }

    /**
     * Mostra un singolo articolo - Esempio di ENTITY INJECTION
     *
     * URL: /sample/show-article/article/1
     *
     * Il framework carica automaticamente l'entity dal database:
     * - Il nome del parametro nell'URL ('article') deve corrispondere al nome del parametro del metodo
     * - Il valore (1) viene usato per chiamare SampleBaseEntityModel::getEntityById(1)
     * - Il Model viene derivato automaticamente dall'Entity (Entities -> Models + 'Model')
     */
    public function showArticle(SampleBaseEntity $article): Response
    {
        $this->vars['article'] = $article;
        $this->vars['pageTitle'] = $article->title;

        return Render::generateView('sample/showArticle', $this->vars);
    }

    /**
     * Filtra articoli per stato - Esempio di ENUM PARAMETER BINDING
     *
     * URL: /sample/filter-by-status/status/P
     * URL: /sample/filter-by-status/status/D
     *
     * Il framework converte automaticamente 'P' in ArticleStatus::PUBLISHED
     */
    public function filterByStatus(ArticleStatus $status): Response
    {
        $model = new SampleBaseEntityModel($this->dataMapper);

        $this->vars['status'] = $status;
        $this->vars['statusLabel'] = $status->getLabel();
        $this->vars['articleCount'] = $model->countByStatus($status);

        return Render::generateView('sample/filterByStatus', $this->vars);
    }

    /**
     * Mostra articoli di un autore - Esempio di RELAZIONI e LAZY LOADING
     *
     * URL: /sample/articles-by-author/authorId/1
     */
    public function articlesByAuthor(int $authorId): Response
    {
        $articleModel = new SampleDependentEntityModel($this->dataMapper);
        $authorModel = new SampleReferencedEntityModel($this->dataMapper);

        // Carica l'autore usando il Model
        $author = $authorModel->getEntityById($authorId);

        // Recupera gli articoli dell'autore usando il model
        $this->vars['author'] = $author;
        $this->vars['articles'] = $articleModel->getArticlesByAuthor($author);

        // Esempio di collezione inversa: accesso agli articoli tramite la property magica
        // $this->vars['articles'] = $author->sampleDependentEntityCollection;

        return Render::generateView('sample/articlesByAuthor', $this->vars);
    }

    /**
     * Ricerca articoli - Esempio di AUTOWIRING del Request
     *
     * URL: /sample/search?q=parola+chiave
     *
     * Il framework inietta automaticamente l'oggetto Request
     */
    public function search(Request $request): Response
    {
        $searchKey = $request->query['q'] ?? '';
        $model = new SampleBaseEntityModel($this->dataMapper);

        $this->vars['searchKey'] = $searchKey;
        $this->vars['results'] = $model->getEntityCollection($searchKey);

        return Render::generateView('sample/search', $this->vars);
    }

    /**
     * Area protetta - Esempio di AUTHENTICATION
     *
     * URL: /sample/protected
     *
     * Dimostra l'autowiring di Authentication per gestire utenti autenticati
     */
    public function protected(Authentication $auth): Response
    {
        // Verifica se l'utente è autenticato
        if (!$auth->isLogged()) {
            return $this->router->redirect('/sample/error/message/Devi essere autenticato');
        }

        // Recupera i dati dell'utente loggato
        $this->vars['user'] = $auth->getAuthenticatedUser();
        $this->vars['username'] = $auth->getUserIdentifier();

        return Render::generateView('sample/protected', $this->vars);
    }

    /**
     * Esempio con DateTime - CUSTOM TYPE PARAMETER BINDING
     *
     * URL: /sample/articles-by-date/date/2025-01-15 14:30:00
     *
     * Il framework converte automaticamente la stringa in SismaDateTime
     */
    public function articlesByDate(SismaDateTime $date): Response
    {
        $this->vars['date'] = $date;
        $this->vars['formattedDate'] = $date->format('d/m/Y H:i');

        return Render::generateView('sample/articlesByDate', $this->vars);
    }

    #[\Override]
    public function error(string $message, ResponseType $responseType): Response
    {
        $this->vars['message'] = urldecode($message);
        return Render::generateView('sample/error', $this->vars, $responseType);
    }

    #[\Override]
    public function notify(string $message): Response
    {
        $this->vars['message'] = urldecode($message);
        return Render::generateView('sample/notify', $this->vars);
    }
}
