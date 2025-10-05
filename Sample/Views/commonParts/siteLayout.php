<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SismaFramework') ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Prism.js per syntax highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #7b68ee;
            --dark-bg: #1a1a2e;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 0;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
        }

        /* Feature Cards */
        .feature-card {
            border: none;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Code blocks */
        pre[class*="language-"] {
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        code:not([class*="language-"]) {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e83e8c;
            font-size: 0.9em;
        }

        /* Sidebar docs */
        .docs-sidebar {
            position: sticky;
            top: 80px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .docs-sidebar .nav-link {
            color: #495057;
            padding: 0.5rem 1rem;
            border-left: 3px solid transparent;
        }

        .docs-sidebar .nav-link:hover {
            background-color: #f8f9fa;
            border-left-color: var(--primary-color);
        }

        .docs-sidebar .nav-link.active {
            color: var(--primary-color);
            background-color: #e7f3ff;
            border-left-color: var(--primary-color);
            font-weight: 600;
        }

        .docs-sidebar h6 {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #6c757d;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            padding-left: 1rem;
        }

        /* Docs content */
        .docs-content h1 {
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .docs-content h2 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .docs-content h3 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        /* Footer */
        footer {
            background-color: var(--dark-bg);
            color: white;
            margin-top: 4rem;
        }

        footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* Utility classes */
        .gradient-text {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
        }

        .btn-gradient:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/home/index">
                <i class="bi bi-hexagon-fill"></i> SismaFramework
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/home/index"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/docs/index"><i class="bi bi-book"></i> Documentazione</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/sample/index"><i class="bi bi-code-square"></i> Esempi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://github.com/valentinodelapa/SismaFramework" target="_blank">
                            <i class="bi bi-github"></i> GitHub
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Content (da sovrascrivere nelle view) -->
    <?= $content ?? '' ?>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="gradient-text">SismaFramework</h5>
                    <p class="text-muted">Framework PHP MVC moderno per applicazioni web robuste e manutenibili.</p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-uppercase">Link Utili</h6>
                    <ul class="list-unstyled">
                        <li><a href="/docs/view/file/getting-started">Getting Started</a></li>
                        <li><a href="/docs/view/file/api-reference">API Reference</a></li>
                        <li><a href="/sample/index">Esempi</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="text-uppercase">Community</h6>
                    <ul class="list-unstyled">
                        <li><a href="https://github.com/valentinodelapa/SismaFramework" target="_blank">GitHub</a></li>
                        <li><a href="https://github.com/valentinodelapa/SismaFramework/issues" target="_blank">Issues</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1)">
            <div class="text-center text-muted">
                <small>&copy; <?= date('Y') ?> SismaFramework. Licensed under MIT.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Prism.js per syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
</body>
</html>
