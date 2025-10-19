<?php ob_start(); ?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Content First (mobile-first approach) -->
        <div class="col-lg-9 order-1 order-lg-2">
            <!-- Mobile Toggle Button for Sidebar -->
            <button class="btn btn-outline-primary d-lg-none mb-3 w-100" type="button" data-bs-toggle="collapse" data-bs-target="#docsSidebarCollapse" aria-expanded="false" aria-controls="docsSidebarCollapse">
                <i class="bi bi-list"></i> Indice Documentazione
            </button>

            <!-- Mobile: Collapsible sidebar (solo su mobile) -->
            <div class="d-lg-none mb-4">
                <div class="collapse" id="docsSidebarCollapse">
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
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item"><a href="/docs/index">Documentazione</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle) ?></li>
                </ol>
            </nav>

            <div class="docs-content">
                <?= $htmlContent ?>
            </div>

            <hr class="my-4">

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

        <!-- Sidebar Desktop (sempre visibile) -->
        <div class="col-lg-3 order-2 order-lg-1">
            <div class="d-none d-lg-block">
                <div class="docs-sidebar mb-4">
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
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';
