<?php

function renderCategoryTree(iterable $nodes, int $depth = 0): void
{
    foreach ($nodes as $node):
        $indent = $depth > 0 ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '└─ ' : '';
        $hasChildren = !empty($node->sonCollection) && count($node->sonCollection) > 0;
        ?>
        <li class="list-group-item py-2">
            <span class="text-muted"><?= $indent ?></span>
            <i class="bi bi-<?= $depth === 0 ? 'folder-fill text-warning' : ($hasChildren ? 'folder text-primary' : 'tag text-secondary') ?>"></i>
            <strong><?= htmlspecialchars($node->name) ?></strong>
            <?php if ($hasChildren): ?>
                <span class="badge bg-secondary ms-1"><?= count($node->sonCollection) ?></span>
            <?php endif; ?>
        </li>
        <?php if ($hasChildren): ?>
            <?php renderCategoryTree($node->sonCollection, $depth + 1); ?>
        <?php endif; ?>
    <?php endforeach;
}

ob_start();
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Categorie</li>
        </ol>
    </nav>

    <h1 class="display-5 mb-4">
        <i class="bi bi-diagram-3"></i> Albero Categorie
    </h1>

    <?php if (empty($categoryTree) || count($categoryTree) === 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Nessuna categoria disponibile. Esegui le fixtures per popolare il database.
        </div>
    <?php else: ?>
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-folder-fill"></i> Struttura Gerarchica
                </h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php renderCategoryTree($categoryTree); ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Spiegazione tecnica -->
    <div class="card border-warning shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-warning">
                <i class="bi bi-lightbulb"></i> Esempio di SelfReferencedEntity e getEntityTree()
            </h5>
            <p class="card-text">Questa pagina dimostra:</p>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>SelfReferencedEntity:</strong><br>
                            <small>Entity che si referenzia ricorsivamente tramite <code>parentSampleSelfReferencedEntity</code></small>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>getEntityTree():</strong><br>
                            <code class="small">$model->getEntityTree(null, ['name' => 'ASC'])</code><br>
                            <small>Carica l'intero albero ricorsivamente in una sola chiamata</small>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>sonCollection:</strong><br>
                            <small>Ogni nodo espone i figli tramite la property <code>$node->sonCollection</code></small>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> <strong>getEntityCollectionByParent():</strong><br>
                            <small>Recupera i figli diretti di un nodo specifico</small>
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
