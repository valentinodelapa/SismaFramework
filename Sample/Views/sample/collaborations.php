<?php ob_start(); ?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Collaborazioni</li>
        </ol>
    </nav>

    <h1 class="display-5 mb-4">
        <i class="bi bi-people"></i> Collaborazioni
    </h1>

    <?php if (empty($collaborations) || count($collaborations) === 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Nessuna collaborazione disponibile. Esegui le fixtures per popolare il database.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($collaborations as $collaboration): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-link-45deg"></i>
                                <?= htmlspecialchars($collaboration->name) ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary me-2">FK 1</span>
                                <div>
                                    <strong><?= htmlspecialchars($collaboration->getSampleReferencedEntityOne()->fullName) ?></strong>
                                    <?php if ($collaboration->getSampleReferencedEntityOne()->verified): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-1" title="Verificato"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-2">FK 2</span>
                                <div>
                                    <strong><?= htmlspecialchars($collaboration->getSampleReferencedEntityTwo()->fullName) ?></strong>
                                    <?php if ($collaboration->getSampleReferencedEntityTwo()->verified): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-1" title="Verificato"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Spiegazione tecnica -->
    <div class="card border-info shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title text-info">
                <i class="bi bi-link-45deg"></i> Esempio di Multiple FK verso la stessa Entity
            </h5>
            <p class="card-text">Questa pagina dimostra:</p>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Due FK sulla stessa tabella:</strong><br>
                            <code class="small">protected SampleReferencedEntity $sampleReferencedEntityOne;</code><br>
                            <code class="small">protected SampleReferencedEntity $sampleReferencedEntityTwo;</code>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>Lazy Loading su entrambe:</strong><br>
                            <small>Le entità referenziate vengono caricate al primo accesso</small><br>
                            <code class="small">$collaboration->getSampleReferencedEntityOne()</code>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="/sample/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';
