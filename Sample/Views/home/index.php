<?php ob_start(); ?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-4">
                    SismaFramework
                </h1>
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
                    <h5 class="card-title"><i class="bi bi-download"></i> Installazione</h5>
                    <h6>Clona il repository:</h6>
                    <pre><code class="language-bash">git clone https://github.com/valentinodelapa/SismaFramework.git</code></pre>

                    <h6 class="mt-4">5 passi per iniziare:</h6>
                    <ol>
                        <?php foreach ($quickStartSteps as $step): ?>
                            <li><?= htmlspecialchars($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
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
use SismaFramework\Core\HelperClasses\Render;

class HomeController extends BaseController
{
    public function index(): Response
    {
        $this->vars['message'] = 'Hello World!';
        return Render::generateView('home/index', $this->vars);
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
                            <div class="feature-icon"><?= $feature['icon'] ?></div>
                            <h5 class="card-title"><?= htmlspecialchars($feature['title']) ?></h5>
                            <p class="card-text text-muted small">
                                <?= htmlspecialchars($feature['description']) ?>
                            </p>
                            <a href="<?= $feature['link'] ?>" class="btn btn-sm btn-outline-primary">
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
                    <h2 class="gradient-text display-4">>85%</h2>
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
require __DIR__ . '/../commonParts/siteLayout.php';
