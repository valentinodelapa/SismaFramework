<?php ob_start(); ?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">Changelog</li>
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
                <i class="bi bi-chat-square-text"></i> Hai trovato un problema?
            </h5>
            <p class="card-text">
                Se hai trovato errori o vuoi suggerire miglioramenti, apri una issue su GitHub.
            </p>
            <a href="https://github.com/valentinodelapa/SismaFramework/issues"
               target="_blank"
               class="btn btn-info">
                <i class="bi bi-github"></i> Report Issue
            </a>
            <a href="https://github.com/valentinodelapa/SismaFramework/blob/master/CHANGELOG.md"
               target="_blank"
               class="btn btn-outline-info">
                <i class="bi bi-pencil"></i> Edit on GitHub
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';
