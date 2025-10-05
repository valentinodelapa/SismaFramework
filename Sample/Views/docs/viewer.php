<?php ob_start(); ?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="docs-sidebar">
                <nav class="nav flex-column">
                    <a href="/docs/index" class="nav-link">
                        <i class="bi bi-arrow-left"></i> Torna all'indice
                    </a>
                    <hr>

                    <?php foreach ($docsSections as $sectionName => $docs): ?>
                        <h6><?= htmlspecialchars($sectionName) ?></h6>
                        <?php foreach ($docs as $doc): ?>
                            <a href="/docs/view/file/<?= urlencode($doc['file']) ?>"
                               class="nav-link <?= ($currentFile === $doc['file']) ? 'active' : '' ?>">
                                <?= htmlspecialchars($doc['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="col-lg-9">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/home/index">Home</a></li>
                    <li class="breadcrumb-item"><a href="/docs/index">Documentazione</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle) ?></li>
                </ol>
            </nav>

            <div class="docs-content">
                <?= $htmlContent ?>
            </div>

            <!-- Navigation buttons -->
            <hr class="my-5">
            <div class="d-flex justify-content-between">
                <?php
                // Trova il documento precedente e successivo
                $allDocs = [];
                foreach ($docsSections as $docs) {
                    $allDocs = array_merge($allDocs, $docs);
                }
                $currentIndex = array_search($currentFile, array_column($allDocs, 'file'));
                $prevDoc = $currentIndex > 0 ? $allDocs[$currentIndex - 1] : null;
                $nextDoc = $currentIndex < count($allDocs) - 1 ? $allDocs[$currentIndex + 1] : null;
                ?>

                <div>
                    <?php if ($prevDoc): ?>
                        <a href="/docs/view/file/<?= urlencode($prevDoc['file']) ?>"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> <?= htmlspecialchars($prevDoc['title']) ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if ($nextDoc): ?>
                        <a href="/docs/view/file/<?= urlencode($nextDoc['file']) ?>"
                           class="btn btn-outline-primary">
                            <?= htmlspecialchars($nextDoc['title']) ?> <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="card border-info mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-chat-square-text"></i> Questa pagina ti è stata utile?
                    </h5>
                    <p class="card-text">
                        Se hai trovato errori o vuoi suggerire miglioramenti, apri una issue su GitHub.
                    </p>
                    <a href="https://github.com/valentinodelapa/SismaFramework/issues"
                       target="_blank"
                       class="btn btn-info">
                        <i class="bi bi-github"></i> Report Issue
                    </a>
                    <a href="https://github.com/valentinodelapa/SismaFramework/blob/master/docs/<?= htmlspecialchars($currentFile) ?>.md"
                       target="_blank"
                       class="btn btn-outline-info">
                        <i class="bi bi-pencil"></i> Edit on GitHub
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';
