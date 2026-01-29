<?php ob_start(); ?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-3">
                    SismaFramework
                </h1>
                <p class="fs-5 mb-3" style="opacity: 0.95;">
                    <strong>SI</strong>mple <strong>SMA</strong>rt Framework
                </p>
                <p class="lead mb-4">
                    Framework PHP MVC moderno, robusto e manutenibile per lo sviluppo di applicazioni web professionali.
                    Sfrutta le potenzialità di PHP 8.1+ con tipizzazione forte, ORM potente e sicurezza integrata.
                </p>
                <div class="d-flex gap-3">
                    <a href="/docs/view/file/getting-started" class="btn btn-light btn-lg">
                        <i class="bi bi-rocket-takeoff"></i> Getting Started
                    </a>
                    <a href="/docs/index" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-book"></i> Documentazione
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <div class="p-5">
                    <i class="bi bi-hexagon-fill" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Start Section -->
<div class="container my-5">
    <div class="row">
        <div class="col-lg-6">
            <h2 class="mb-4">Quick Start</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-terminal"></i> Scaffolding CLI</h5>
                    <p class="text-muted small mb-3">
                        SismaFramework include un <strong>Project Bootstrapper</strong> che genera automaticamente
                        tutta la struttura del progetto con un singolo comando.
                    </p>
                    <pre><code class="language-bash"># 1. Crea il progetto e aggiungi il framework
mkdir mio-progetto && cd mio-progetto
git init
git submodule add https://github.com/valentinodelapa/SismaFramework.git

# 2. Installa le dipendenze
cd SismaFramework && composer install && cd ..

# 3. Esegui lo scaffolding automatico
php SismaFramework/Console/sisma install MioProgetto</code></pre>
                    <p class="mb-0 mt-3">
                        <a href="#how-it-works" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-arrow-down-circle"></i> Vedi cosa genera
                        </a>
                        <a href="/docs/view/file/installation" class="btn btn-sm btn-gradient ms-2">
                            Guida completa →
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="mb-4">Primo Esempio</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-code-slash"></i> Hello World Controller</h5>
                    <pre><code class="language-php">&lt;?php
namespace MyApp\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HttpClasses\Response;

class HomeController extends BaseController
{
    public function index(): Response
    {
        $this->vars['message'] = 'Hello World!';
        // Sintassi moderna (v11.0.0+)
        return $this->render->generateView('home/index', $this->vars);
    }
}</code></pre>
                    <p class="mb-0">
                        <a href="/docs/view/file/getting-started" class="btn btn-sm btn-gradient">
                            Vedi tutorial completo →
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How Installation Works Section -->
<div class="bg-white py-5 border-top border-bottom" id="how-it-works">
    <div class="container">
        <h2 class="text-center mb-2">
            <span class="gradient-text">Come Funziona l'Installazione</span>
        </h2>
        <p class="text-center text-muted mb-5">
            Il comando <code>sisma install</code> trasforma il submodule in un progetto completo e pronto all'uso
        </p>

        <div class="row align-items-stretch">
            <!-- PRIMA -->
            <div class="col-lg-5">
                <div class="card h-100 border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-folder"></i> <strong>PRIMA</strong> <span class="small">(dopo git submodule add)</span>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; font-size: 0.85rem;"><code>mio-progetto/
└── SismaFramework/
    ├── Console/
    ├── Core/
    ├── ORM/
    ├── Security/
    ├── Config/        <span class="text-muted"># template</span>
    ├── Public/        <span class="text-muted"># template</span>
    └── ...</code></pre>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="bi bi-info-circle"></i> Solo il framework come submodule Git
                        </p>
                    </div>
                </div>
            </div>

            <!-- Freccia -->
            <div class="col-lg-2 d-flex align-items-center justify-content-center py-4 py-lg-0">
                <div class="text-center">
                    <div class="d-none d-lg-block">
                        <i class="bi bi-arrow-right-circle-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <div class="d-lg-none">
                        <i class="bi bi-arrow-down-circle-fill text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="mt-2">
                        <code class="small">sisma install</code>
                    </div>
                </div>
            </div>

            <!-- DOPO -->
            <div class="col-lg-5">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-folder-check"></i> <strong>DOPO</strong> <span class="small">(progetto pronto)</span>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; font-size: 0.85rem;"><code>mio-progetto/
├── <span class="text-success fw-bold">Config/</span>              <span class="text-success"># creata</span>
│   └── configFramework.php
├── <span class="text-success fw-bold">Public/</span>              <span class="text-success"># creata</span>
│   └── index.php
├── <span class="text-success fw-bold">Cache/</span>               <span class="text-success"># creata</span>
├── <span class="text-success fw-bold">Logs/</span>                <span class="text-success"># creata</span>
├── <span class="text-success fw-bold">filesystemMedia/</span>     <span class="text-success"># creata</span>
├── <span class="text-success fw-bold">.htaccess</span>            <span class="text-success"># creato</span>
├── <span class="text-success fw-bold">composer.json</span>        <span class="text-success"># creato</span>
└── SismaFramework/      <span class="text-muted"># intatto</span></code></pre>
                        <p class="text-muted small mt-3 mb-0">
                            <i class="bi bi-check-circle text-success"></i> Struttura completa, configurata e pronta all'uso
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dettagli -->
        <div class="row mt-5">
            <div class="col-lg-4">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="bi bi-gear-fill text-primary fs-3 me-3"></i>
                    </div>
                    <div>
                        <h5>Configurazione Automatica</h5>
                        <p class="text-muted small mb-0">
                            I path dell'autoloader vengono aggiornati automaticamente.
                            Il nome del progetto viene configurato nel file di config.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="bi bi-database-fill text-primary fs-3 me-3"></i>
                    </div>
                    <div>
                        <h5>Setup Database Interattivo</h5>
                        <p class="text-muted small mb-0">
                            Configura host, nome, utente e password del database
                            in modo interattivo o tramite parametri CLI.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="bi bi-shield-fill-check text-primary fs-3 me-3"></i>
                    </div>
                    <div>
                        <h5>Permessi Corretti</h5>
                        <p class="text-muted small mb-0">
                            Le cartelle Cache, Logs e filesystemMedia vengono create
                            con i permessi appropriati per il web server.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="/docs/view/file/installation" class="btn btn-gradient btn-lg">
                <i class="bi bi-book"></i> Leggi la Guida Completa all'Installazione
            </a>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">
            <span class="gradient-text">Caratteristiche Principali</span>
        </h2>

        <div class="row g-4">
            <?php foreach ($features as $feature): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center">
                            <div class="feature-icon"><?= $feature[
                                "icon"
                            ] ?></div>
                            <h5 class="card-title"><?= htmlspecialchars(
                                $feature["title"],
                            ) ?></h5>
                            <p class="card-text text-muted small">
                                <?= htmlspecialchars($feature["description"]) ?>
                            </p>
                            <a href="<?= $feature[
                                "link"
                            ] ?>" class="btn btn-sm btn-outline-primary">
                                Scopri di più →
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Why Choose Section -->
<div class="container my-5 py-5">
    <div class="row">
        <div class="col-lg-6">
            <h2 class="mb-4">Perché SismaFramework?</h2>
            <div class="d-flex mb-3">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                </div>
                <div>
                    <h5>Moderno e Type-Safe</h5>
                    <p class="text-muted">Sfrutta PHP 8.1+ con tipizzazione forte, readonly properties, BackedEnum e attributi.</p>
                </div>
            </div>
            <div class="d-flex mb-3">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                </div>
                <div>
                    <h5>ORM Potente</h5>
                    <p class="text-muted">Data Mapper con lazy loading, relazioni automatiche, crittografia a livello di proprietà.</p>
                </div>
            </div>
            <div class="d-flex mb-3">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                </div>
                <div>
                    <h5>Sicurezza Integrata</h5>
                    <p class="text-muted">Autenticazione MFA, sistema di permessi, protezione CSRF, validazione input.</p>
                </div>
            </div>
            <div class="d-flex mb-3">
                <div class="flex-shrink-0">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                </div>
                <div>
                    <h5>Architettura Modulare</h5>
                    <p class="text-muted">Organizza il codice in moduli riutilizzabili e indipendenti.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="mb-4">Pronto a Iniziare?</h2>
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-lightbulb"></i> Esplora gli Esempi</h5>
                    <p class="card-text">
                        Abbiamo preparato esempi completi che mostrano tutte le funzionalità del framework:
                    </p>
                    <ul class="mb-4">
                        <li>Entity con tutti i tipi supportati</li>
                        <li>Model con query avanzate</li>
                        <li>Controller con autowiring</li>
                        <li>Views con best practices</li>
                        <li>Relazioni e lazy loading</li>
                        <li>Crittografia e sicurezza</li>
                    </ul>
                    <a href="/sample/index" class="btn btn-gradient btn-lg w-100">
                        <i class="bi bi-play-circle"></i> Vedi Esempi Live
                    </a>
                </div>
            </div>

            <div class="card border-info mt-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-book"></i> Leggi la Documentazione</h5>
                    <p class="card-text mb-3">
                        Documentazione completa con guide, tutorial e API reference.
                    </p>
                    <a href="/docs/index" class="btn btn-outline-primary w-100">
                        Vai alla Documentazione →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="bg-dark text-white py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="p-3">
                    <h2 class="gradient-text display-4">8.1+</h2>
                    <p>PHP Version</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <h2 class="gradient-text display-4">>80%</h2>
                    <p>Test Coverage</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <h2 class="gradient-text display-4">MIT</h2>
                    <p>Open Source License</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <h2 class="gradient-text display-4">MVC</h2>
                    <p>Architecture Pattern</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . "/../commonParts/siteLayout.php";

