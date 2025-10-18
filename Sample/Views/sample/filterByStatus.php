<?php ob_start(); ?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Filtra per Stato</li>
        </ol>
    </nav>

    <h1 class="display-5 mb-4">
        <i class="bi bi-funnel"></i> Articoli - <?= $statusLabel ?>
    </h1>

    <!-- Info sul filtro corrente -->
    <div class="alert alert-primary shadow-sm">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle fs-3 me-3"></i>
            <div>
                <strong>Stato selezionato:</strong> <?= $statusLabel ?> (<?= $status->value ?>)<br>
                <strong>Numero di articoli:</strong> <?= $articleCount ?>
            </div>
        </div>
    </div>

    <!-- Link agli altri stati -->
    <div class="mb-4">
        <h5>Filtra per stato:</h5>
        <div class="btn-group" role="group">
            <a href="/sample/filter-by-status/status/D"
               class="btn btn-<?= $status === \SismaFramework\Sample\Enumerations\ArticleStatus::DRAFT ? 'primary' : 'outline-primary' ?>">
                <i class="bi bi-pencil"></i> Bozze
            </a>
            <a href="/sample/filter-by-status/status/P"
               class="btn btn-<?= $status === \SismaFramework\Sample\Enumerations\ArticleStatus::PUBLISHED ? 'success' : 'outline-success' ?>">
                <i class="bi bi-check-circle"></i> Pubblicati
            </a>
            <a href="/sample/filter-by-status/status/A"
               class="btn btn-<?= $status === \SismaFramework\Sample\Enumerations\ArticleStatus::ARCHIVED ? 'secondary' : 'outline-secondary' ?>">
                <i class="bi bi-archive"></i> Archiviati
            </a>
        </div>
    </div>

    <!-- Spiegazione tecnica -->
    <div class="card border-warning shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-warning">
                <i class="bi bi-lightbulb"></i> Esempio di Enum Parameter Binding
            </h5>
            <p class="card-text">Questa action dimostra il binding automatico di BackedEnum:</p>
            <pre class="bg-dark text-light p-3 rounded"><code class="language-php">public function filterByStatus(ArticleStatus $status): Response</code></pre>
            <p class="card-text">Quando visiti <code class="text-primary">/sample/filter-by-status/status/P</code>:</p>
            <ol class="mb-0">
                <li>Il framework estrae il valore <code>'P'</code> dall'URL</li>
                <li>Cerca l'enum <code>ArticleStatus</code> con valore <code>'P'</code></li>
                <li>Inietta automaticamente <code class="text-success">ArticleStatus::PUBLISHED</code> nel parametro</li>
                <li>Il controller può usare metodi dell'enum: <code>$status->getLabel()</code>, <code>$status->isPublic()</code></li>
            </ol>
        </div>
    </div>

    <!-- Info sull'enum -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-code-slash"></i> Enum ArticleStatus</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Case</th>
                        <th>Valore</th>
                        <th>Label</th>
                        <th>Pubblico?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (\SismaFramework\Sample\Enumerations\ArticleStatus::cases() as $case): ?>
                        <tr class="<?= $case === $status ? 'table-primary' : '' ?>">
                            <td><code><?= $case->name ?></code></td>
                            <td><code><?= $case->value ?></code></td>
                            <td><?= $case->getLabel() ?></td>
                            <td><?= $case->isPublic() ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
