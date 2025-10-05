<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
        <title>Filtra per Stato - SismaFramework</title>
    </head>
    <body>
        <div class="container my-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/sample/index">Home</a></li>
                    <li class="breadcrumb-item active">Filtra per Stato</li>
                </ol>
            </nav>

            <h1>Articoli - <?= $statusLabel ?></h1>

            <!-- Info sul filtro corrente -->
            <div class="alert alert-info">
                <strong>Stato selezionato:</strong> <?= $statusLabel ?> (<?= $status->value ?>)
                <br><strong>Numero di articoli:</strong> <?= $articleCount ?>
            </div>

            <!-- Link agli altri stati -->
            <div class="mb-4">
                <h5>Filtra per stato:</h5>
                <div class="btn-group" role="group">
                    <a href="/sample/filter-by-status/status/D"
                       class="btn btn-<?= $status === \SismaFramework\Sample\Enumerations\ArticleStatus::DRAFT ? 'primary' : 'outline-primary' ?>">
                        Bozze
                    </a>
                    <a href="/sample/filter-by-status/status/P"
                       class="btn btn-<?= $status === \SismaFramework\Sample\Enumerations\ArticleStatus::PUBLISHED ? 'success' : 'outline-success' ?>">
                        Pubblicati
                    </a>
                    <a href="/sample/filter-by-status/status/A"
                       class="btn btn-<?= $status === \SismaFramework\Sample\Enumerations\ArticleStatus::ARCHIVED ? 'secondary' : 'outline-secondary' ?>">
                        Archiviati
                    </a>
                </div>
            </div>

            <!-- Spiegazione tecnica -->
            <div class="alert alert-warning">
                <h5>🎯 Esempio di Enum Parameter Binding</h5>
                <p>Questa action dimostra il binding automatico di BackedEnum:</p>
                <pre class="mb-2"><code>public function filterByStatus(ArticleStatus $status): Response</code></pre>
                <p>Quando visiti <code>/sample/filter-by-status/status/P</code>:</p>
                <ol class="mb-0">
                    <li>Il framework estrae il valore <code>'P'</code> dall'URL</li>
                    <li>Cerca l'enum <code>ArticleStatus</code> con valore <code>'P'</code></li>
                    <li>Inietta automaticamente <code>ArticleStatus::PUBLISHED</code> nel parametro</li>
                    <li>Il controller può usare metodi dell'enum: <code>$status->getLabel()</code>, <code>$status->isPublic()</code></li>
                </ol>
            </div>

            <!-- Info sull'enum -->
            <div class="card">
                <div class="card-header">
                    <strong>Enum ArticleStatus</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
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
                                <tr class="<?= $case === $status ? 'table-active' : '' ?>">
                                    <td><code><?= $case->name ?></code></td>
                                    <td><code><?= $case->value ?></code></td>
                                    <td><?= $case->getLabel() ?></td>
                                    <td><?= $case->isPublic() ? '✓' : '✗' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="/sample/index" class="btn btn-secondary mt-4">← Torna alla lista</a>
        </div>
    </body>
</html>
