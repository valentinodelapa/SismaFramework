<?php ob_start(); ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active">Documentazione</li>
                </ol>
            </nav>

            <h1 class="mb-4">
                <i class="bi bi-book"></i> Documentazione SismaFramework
            </h1>
            <p class="lead">
                Guida completa all'utilizzo del framework, dalle basi ai concetti avanzati.
            </p>
        </div>
    </div>

    <div class="row mt-5">
        <?php foreach ($docsSections as $sectionName => $docs): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= htmlspecialchars($sectionName) ?></h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <?php foreach ($docs as $doc): ?>
                                <li class="mb-2">
                                    <a href="/docs/view/file/<?= urlencode($doc['file']) ?>"
                                       class="text-decoration-none">
                                        <i class="bi bi-file-text"></i> <?= htmlspecialchars($doc['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Links -->
    <div class="row mt-5">
        <div class="col-lg-4">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <i class="bi bi-rocket-takeoff"></i> Inizio Rapido
                    </h5>
                    <p class="card-text">Inizia subito a sviluppare con SismaFramework</p>
                    <a href="/docs/view/file/getting-started" class="btn btn-success">
                        Getting Started →
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">
                        <i class="bi bi-database"></i> ORM Guide
                    </h5>
                    <p class="card-text">Scopri come usare il potente ORM del framework</p>
                    <a href="/docs/view/file/orm" class="btn btn-primary">
                        Leggi ORM Docs →
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="bi bi-code-square"></i> API Reference
                    </h5>
                    <p class="card-text">Consulta la documentazione completa delle API</p>
                    <a href="/docs/view/file/api-reference" class="btn btn-warning">
                        API Docs →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="alert alert-info mt-5" role="alert">
        <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Hai bisogno di aiuto?</h5>
        <p>
            Se non trovi quello che cerchi nella documentazione, puoi:
        </p>
        <ul class="mb-0">
            <li>Consultare gli <a href="/sample/index" class="alert-link">esempi pratici</a></li>
            <li>Aprire una <a href="https://github.com/valentinodelapa/SismaFramework/issues" target="_blank" class="alert-link">issue su GitHub</a></li>
            <li>Leggere la sezione <a href="/docs/view/file/troubleshooting" class="alert-link">Troubleshooting</a></li>
        </ul>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';
