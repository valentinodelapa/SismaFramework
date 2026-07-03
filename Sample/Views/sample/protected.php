<?php require __DIR__ . '/../commonParts/siteLayoutHeader.php'; ?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/sample/index">Esempi</a></li>
            <li class="breadcrumb-item active">Area Protetta</li>
        </ol>
    </nav>

    <h1 class="display-5 mb-4">
        <i class="bi bi-shield-lock"></i> Area Protetta
    </h1>

    <div class="alert alert-success shadow-sm">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle fs-3 me-3"></i>
            <div>
                <strong>Accesso consentito.</strong> Utente in sessione: <code><?= htmlspecialchars((string) $userId) ?></code>
            </div>
        </div>
    </div>

    <div class="card border-warning shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title text-warning">
                <i class="bi bi-lightbulb"></i> Esempio di controllo di accesso tramite Session
            </h5>
            <p class="card-text">
                La classe <code>Authentication</code> si occupa esclusivamente della <em>validazione</em> delle credenziali
                durante il login (identificatore, password, CSRF token) — non espone un metodo per verificare se l'utente
                è già autenticato in una richiesta successiva. La persistenza dello stato di login va gestita manualmente
                tramite la classe <code>Session</code>:
            </p>
            <pre class="bg-dark text-light p-3 rounded"><code class="language-php">if (!Session::hasItem('userId')) {
    return $this->router->redirect('/sample/error/message/Devi essere autenticato');
}</code></pre>
            <p class="card-text mb-0">
                Questo modulo demo non include un flusso di login (nessuna entity implementa <code>AuthenticableInterface</code>),
                quindi in condizioni normali questa pagina reindirizza sempre alla pagina di errore.
                Per l'esempio completo del flusso di login vedi <a href="/docs/view/file/security">la documentazione sulla sicurezza</a>.
            </p>
        </div>
    </div>

    <div class="mt-4">
        <a href="/sample/index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>
</div>

<?php require __DIR__ . '/../commonParts/siteLayoutFooter.php'; ?>
