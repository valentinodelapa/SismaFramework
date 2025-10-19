<?php ob_start(); ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cookie Policy</li>
                </ol>
            </nav>

            <h1 class="mb-4">Cookie Policy</h1>
            <p class="text-muted">Ultimo aggiornamento: <?= date('d/m/Y') ?></p>

            <div class="alert alert-success shadow-sm" role="alert">
                <h4 class="alert-heading"><i class="bi bi-shield-check"></i> Buone Notizie!</h4>
                <p class="mb-0">
                    <strong>Questo sito web NON utilizza cookie di alcun tipo.</strong>
                    Non installiamo cookie tecnici, cookie di profilazione, né cookie di terze parti sul tuo dispositivo.
                </p>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-info-circle text-primary"></i> Cosa Sono i Cookie?</h2>
                    <p>
                        I cookie sono piccoli file di testo che i siti web visitati inviano al dispositivo dell'utente
                        (computer, tablet, smartphone), dove vengono memorizzati per essere poi ritrasmessi agli stessi
                        siti alla successiva visita.
                    </p>
                    <p>
                        I cookie possono essere utilizzati per diverse finalità:
                    </p>
                    <ul>
                        <li><strong>Cookie tecnici</strong> - Necessari per il funzionamento del sito (es. sessione, autenticazione)</li>
                        <li><strong>Cookie di profilazione</strong> - Per tracciare il comportamento dell'utente e mostrare pubblicità mirata</li>
                        <li><strong>Cookie di terze parti</strong> - Installati da servizi esterni (es. Google Analytics, Facebook Pixel)</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-success">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-x-circle text-success"></i> Cookie Utilizzati da Questo Sito</h2>
                    <div class="alert alert-success mb-0">
                        <h5 class="alert-heading">NESSUN COOKIE INSTALLATO</h5>
                        <p>
                            Il sito <strong>www.sisma-framework.dev</strong> è stato progettato per rispettare
                            completamente la privacy degli utenti.
                        </p>
                        <hr>
                        <p class="mb-0">
                            <strong>Non utilizziamo:</strong>
                        </p>
                        <ul class="mb-0">
                            <li>Cookie tecnici per sessioni o autenticazione</li>
                            <li>Cookie di profilazione o tracciamento</li>
                            <li>Cookie di analytics (Google Analytics, Matomo, ecc.)</li>
                            <li>Cookie pubblicitari</li>
                            <li>Cookie di social network (Facebook, Twitter, ecc.)</li>
                            <li>Cookie di terze parti</li>
                            <li>Local Storage o Session Storage per memorizzare dati</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-eye-slash text-info"></i> Nessun Tracciamento</h2>
                    <p>
                        Il sito non utilizza sistemi di tracciamento o analytics di alcun tipo:
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-x-circle-fill text-danger"></i> Google Analytics
                                    </h5>
                                    <p class="card-text small mb-0">Non installato</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-x-circle-fill text-danger"></i> Google Tag Manager
                                    </h5>
                                    <p class="card-text small mb-0">Non installato</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-x-circle-fill text-danger"></i> Facebook Pixel
                                    </h5>
                                    <p class="card-text small mb-0">Non installato</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-x-circle-fill text-danger"></i> Altri Tracker
                                    </h5>
                                    <p class="card-text small mb-0">Non installato</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-cloud-arrow-down text-warning"></i> Risorse Esterne (CDN)</h2>
                    <p>
                        Il sito utilizza CDN (Content Delivery Network) esterni per caricare librerie CSS e JavaScript:
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Servizio</th>
                                    <th>Risorsa</th>
                                    <th>Cookie Installati</th>
                                    <th>Privacy Policy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>jsDelivr</strong></td>
                                    <td>
                                        Bootstrap CSS/JS<br>
                                        Bootstrap Icons
                                    </td>
                                    <td><span class="badge bg-success">Nessuno</span></td>
                                    <td>
                                        <a href="https://www.jsdelivr.com/privacy-policy-jsdelivr-net" target="_blank" rel="noopener">
                                            Privacy Policy
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Cloudflare CDN</strong></td>
                                    <td>Prism.js (Syntax Highlighting)</td>
                                    <td><span class="badge bg-success">Nessuno</span></td>
                                    <td>
                                        <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener">
                                            Privacy Policy
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Nota:</strong> Quando il browser carica risorse da questi CDN, i server potrebbero
                        registrare dati tecnici (indirizzo IP, User-Agent, timestamp) per finalità di delivery
                        e sicurezza. Questi CDN <strong>non installano cookie</strong> nelle richieste standard
                        per file CSS e JavaScript.
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-question-circle text-secondary"></i> Banner Cookie</h2>
                    <p>
                        <strong>Perché non c'è un banner cookie?</strong>
                    </p>
                    <p>
                        La normativa europea sui cookie (Direttiva ePrivacy e GDPR) richiede l'installazione di
                        un banner di consenso <strong>solo se il sito utilizza cookie</strong>, in particolare
                        cookie di profilazione o non strettamente necessari.
                    </p>
                    <p>
                        Poiché questo sito <strong>non utilizza alcun cookie</strong>, non è necessario richiedere
                        il consenso dell'utente tramite banner.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-gear text-primary"></i> Come Verificare i Cookie</h2>
                    <p>
                        Puoi verificare autonomamente che il sito non installi cookie utilizzando gli strumenti
                        di sviluppo del tuo browser:
                    </p>

                    <div class="accordion" id="browserAccordion">
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#chrome">
                                    <i class="bi bi-google me-2"></i> Google Chrome
                                </button>
                            </h3>
                            <div id="chrome" class="accordion-collapse collapse" data-bs-parent="#browserAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Premi <kbd>F12</kbd> per aprire DevTools</li>
                                        <li>Vai alla tab <strong>Application</strong></li>
                                        <li>Nel menu laterale, clicca su <strong>Cookies</strong></li>
                                        <li>Verifica che non ci siano cookie per questo dominio</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#firefox">
                                    <i class="bi bi-browser-firefox me-2"></i> Mozilla Firefox
                                </button>
                            </h3>
                            <div id="firefox" class="accordion-collapse collapse" data-bs-parent="#browserAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Premi <kbd>F12</kbd> per aprire Developer Tools</li>
                                        <li>Vai alla tab <strong>Storage</strong></li>
                                        <li>Espandi <strong>Cookies</strong> nel menu laterale</li>
                                        <li>Verifica che non ci siano cookie per questo dominio</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#safari">
                                    <i class="bi bi-apple me-2"></i> Safari
                                </button>
                            </h3>
                            <div id="safari" class="accordion-collapse collapse" data-bs-parent="#browserAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Abilita il menu Sviluppo: Preferenze → Avanzate → "Mostra menu Sviluppo"</li>
                                        <li>Premi <kbd>Option + Cmd + I</kbd> per aprire Web Inspector</li>
                                        <li>Vai alla tab <strong>Storage</strong></li>
                                        <li>Clicca su <strong>Cookies</strong></li>
                                        <li>Verifica che non ci siano cookie per questo dominio</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-link-45deg text-info"></i> Link Utili</h2>
                    <ul>
                        <li>
                            <a href="/home/privacy">Privacy Policy</a> - Informativa completa sul trattamento dei dati
                        </li>
                        <li>
                            <a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">
                                Garante per la Protezione dei Dati Personali
                            </a> - Autorità italiana per la privacy
                        </li>
                        <li>
                            <a href="https://ec.europa.eu/info/law/law-topic/data-protection_it" target="_blank" rel="noopener">
                                Regolamento GDPR
                            </a> - Normativa europea sulla protezione dei dati
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-arrow-repeat text-warning"></i> Modifiche alla Cookie Policy</h2>
                    <p>
                        Ci riserviamo il diritto di modificare questa Cookie Policy in qualsiasi momento.
                        Eventuali modifiche saranno pubblicate su questa pagina con aggiornamento della data.
                    </p>
                    <p class="mb-0">
                        Se in futuro dovessimo decidere di utilizzare cookie, questa pagina verrà aggiornata
                        con informazioni dettagliate sui cookie utilizzati e verrà implementato un sistema
                        di consenso conforme alla normativa vigente.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-envelope text-primary"></i> Contatti</h2>
                    <p>
                        Per qualsiasi domanda relativa a questa Cookie Policy, puoi contattarci tramite:
                    </p>
                    <ul class="mb-0">
                        <li>
                            Repository GitHub:
                            <a href="https://github.com/valentinodelapa/SismaFramework" target="_blank" rel="noopener">
                                github.com/valentinodelapa/SismaFramework
                            </a>
                        </li>
                        <li>
                            Issue Tracker:
                            <a href="https://github.com/valentinodelapa/SismaFramework/issues" target="_blank" rel="noopener">
                                Segnala un problema
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="/" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Torna alla Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../commonParts/siteLayout.php';
