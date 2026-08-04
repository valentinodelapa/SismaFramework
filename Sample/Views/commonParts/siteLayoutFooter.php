    <!-- Footer -->
    <footer class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="gradient-text">SismaFramework</h5>
                    <p style="color: rgba(255,255,255,0.85)"><strong>SI</strong>mple <strong>SMA</strong>rt Framework</p>
                    <p style="color: rgba(255,255,255,0.7)">Framework PHP MVC moderno per applicazioni web robuste e manutenibili.</p>
                    <p style="color: rgba(255,255,255,0.7)">
                        <i class="bi bi-globe"></i> <a href="https://www.sisma-framework.dev/" target="_blank">www.sisma-framework.dev</a>
                    </p>
                    <?php if ($frameworkVersion !== ""): ?>
                    <p style="color: rgba(255,255,255,0.7)">
                        <i class="bi bi-tag"></i> <a href="/docs/changelog">Versione <?= htmlspecialchars(
                            $frameworkVersion,
                        ) ?></a><?= $frameworkReleaseDate !== ""
                            ? " &mdash; rilasciata il " .
                                htmlspecialchars($frameworkReleaseDate)
                            : "" ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase" style="color: rgba(255,255,255,0.9)">Link Utili</h6>
                    <ul class="list-unstyled">
                        <li><a href="/docs/view/file/getting-started">Getting Started</a></li>
                        <li><a href="/docs/view/file/api-reference">API Reference</a></li>
                        <li><a href="/sample/index">Esempi</a></li>
                        <li><a href="/home/privacy">Privacy Policy</a></li>
                        <li><a href="/home/cookies">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase" style="color: rgba(255,255,255,0.9)">Community</h6>
                    <ul class="list-unstyled">
                        <li><a href="https://github.com/valentinodelapa/SismaFramework" target="_blank">GitHub</a></li>
                        <li><a href="https://github.com/valentinodelapa/SismaFramework/issues" target="_blank">Issues</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1)">
            <div class="text-center" style="color: rgba(255,255,255,0.7)">
                <small>&copy; 2020-present Valentino de Lapa. Licensed under <a href="https://opensource.org/licenses/MIT" target="_blank">MIT License</a>.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Prism.js per syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
</body>
</html>
