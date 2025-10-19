<?php ob_start(); ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
                </ol>
            </nav>

            <h1 class="mb-4">Privacy Policy</h1>
            <p class="text-muted">Ultimo aggiornamento: <?= date('d/m/Y') ?></p>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-shield-check text-success"></i> Informativa sulla Privacy</h2>
                    <p>
                        La presente Privacy Policy descrive le modalità di trattamento dei dati personali degli utenti
                        che consultano il sito <strong>SismaFramework</strong> disponibile all'indirizzo
                        <a href="https://www.sisma-framework.dev">www.sisma-framework.dev</a>.
                    </p>
                    <p>
                        Questa informativa è resa ai sensi del <strong>Regolamento UE 2016/679</strong> (GDPR)
                        e si applica esclusivamente al presente sito web.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-person-badge text-primary"></i> Titolare del Trattamento</h2>
                    <p><strong>Titolare del trattamento:</strong> Valentino de Lapa</p>
                    <p><strong>Contatto:</strong> Tramite il repository GitHub
                        <a href="https://github.com/valentinodelapa/SismaFramework" target="_blank" rel="noopener">
                            github.com/valentinodelapa/SismaFramework
                        </a>
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-database text-info"></i> Dati Raccolti e Finalità</h2>

                    <h3 class="h5 mt-4">1. Dati di Navigazione</h3>
                    <p>
                        I sistemi informatici e le procedure software preposte al funzionamento di questo sito
                        acquisiscono, nel corso del loro normale esercizio, alcuni dati personali la cui trasmissione
                        è implicita nell'uso dei protocolli di comunicazione Internet.
                    </p>
                    <p>
                        Questi dati (ad esempio <strong>indirizzi IP</strong>, <strong>tipo di browser</strong>,
                        <strong>sistema operativo</strong>, <strong>timestamp</strong>) non sono raccolti per essere
                        associati a interessati identificati, ma potrebbero permettere di identificare gli utenti
                        tramite elaborazioni e associazioni con dati detenuti da terzi.
                    </p>
                    <p>
                        <strong>Finalità:</strong> Questi dati vengono utilizzati al solo fine di:
                    </p>
                    <ul>
                        <li>Ricavare informazioni statistiche anonime sull'uso del sito</li>
                        <li>Controllare il corretto funzionamento del sito</li>
                        <li>Accertare responsabilità in caso di ipotetici reati informatici ai danni del sito</li>
                    </ul>
                    <p>
                        <strong>Base giuridica:</strong> Legittimo interesse del titolare (art. 6, par. 1, lett. f GDPR)
                    </p>

                    <h3 class="h5 mt-4">2. Cookie e Tecnologie di Tracciamento</h3>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <strong>Questo sito NON utilizza cookie.</strong>
                    </div>
                    <p>
                        Il sito <strong>www.sisma-framework.dev</strong> non installa cookie sul dispositivo dell'utente,
                        né cookie tecnici né cookie di profilazione.
                    </p>
                    <p>
                        Il sito non utilizza:
                    </p>
                    <ul>
                        <li>Google Analytics o altri sistemi di analytics</li>
                        <li>Pixel di tracciamento (Facebook Pixel, ecc.)</li>
                        <li>Cookie di sessione o autenticazione per utenti pubblici</li>
                        <li>Local Storage o Session Storage per memorizzare dati dell'utente</li>
                    </ul>
                    <p>
                        Per maggiori informazioni, consulta la <a href="/home/cookies">Cookie Policy</a>.
                    </p>

                    <h3 class="h5 mt-4">3. Dati Forniti Volontariamente dall'Utente</h3>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <strong>Questo sito NON raccoglie dati personali tramite form.</strong>
                    </div>
                    <p>
                        Il sito non include:
                    </p>
                    <ul>
                        <li>Form di contatto</li>
                        <li>Iscrizioni a newsletter</li>
                        <li>Registrazioni utente</li>
                        <li>Commenti o recensioni</li>
                    </ul>
                    <p>
                        L'unico form presente è un <strong>form di ricerca articoli</strong> che accetta una parola chiave
                        come parametro GET. La parola chiave inserita non viene memorizzata in alcun database e viene utilizzata
                        esclusivamente per filtrare i risultati della ricerca nella sessione corrente.
                    </p>

                    <h3 class="h5 mt-4">4. Statistiche Lato Server (AWStats)</h3>
                    <p>
                        Il servizio di hosting utilizzato (Aruba S.p.A.) mette a disposizione un sistema di
                        <strong>statistiche lato server</strong> basato su AWStats (Advanced Web Statistics).
                    </p>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Importante:</strong> Questo servizio <strong>NON installa cookie</strong> e
                        <strong>NON inserisce script di tracciamento</strong> nel sito.
                    </div>
                    <p>
                        <strong>Come funziona:</strong>
                    </p>
                    <ul>
                        <li>AWStats analizza i <strong>log del web server</strong> già esistenti</li>
                        <li>Non traccia attivamente gli utenti</li>
                        <li>Genera report statistici aggregati per il gestore del sito</li>
                        <li>Opera esclusivamente lato server, senza interazione con il browser dell'utente</li>
                    </ul>
                    <p>
                        <strong>Dati analizzati dai log del server:</strong>
                    </p>
                    <ul>
                        <li>Indirizzo IP dell'utente</li>
                        <li>Data e ora della visita</li>
                        <li>Pagine visitate e percorso di navigazione</li>
                        <li>Referrer (sito di provenienza)</li>
                        <li>User-Agent (browser e sistema operativo)</li>
                        <li>Codici di risposta HTTP</li>
                    </ul>
                    <p>
                        <strong>Finalità del trattamento:</strong>
                    </p>
                    <ul>
                        <li>Analisi statistica degli accessi al sito in forma aggregata</li>
                        <li>Verifica del corretto funzionamento tecnico del sito</li>
                        <li>Ottimizzazione dei contenuti e dell'esperienza utente</li>
                    </ul>
                    <p>
                        <strong>Base giuridica:</strong> Legittimo interesse del titolare (art. 6, par. 1, lett. f GDPR)
                    </p>
                    <p>
                        <strong>Responsabile del trattamento:</strong>
                    </p>
                    <ul>
                        <li>
                            <strong>Aruba S.p.A.</strong><br>
                            Piazza Garibaldi 8, 52010 Soci (AR) - Italia<br>
                            Privacy Policy: <a href="https://www.aruba.it/privacy.aspx" target="_blank" rel="noopener">www.aruba.it/privacy.aspx</a>
                        </li>
                    </ul>
                    <p>
                        I log del server sono conservati per un periodo massimo di <strong>7 giorni</strong>,
                        salvo necessità di accertamento di reati informatici.
                    </p>

                    <h3 class="h5 mt-4">5. Servizi di Terze Parti (CDN)</h3>
                    <p>
                        Il sito utilizza le seguenti risorse esterne tramite <strong>CDN</strong> (Content Delivery Network):
                    </p>
                    <ul>
                        <li>
                            <strong>jsDelivr</strong> (<code>cdn.jsdelivr.net</code>) -
                            Per la distribuzione di Bootstrap CSS/JS e Bootstrap Icons
                        </li>
                        <li>
                            <strong>Cloudflare CDN</strong> (<code>cdnjs.cloudflare.com</code>) -
                            Per la distribuzione di Prism.js (syntax highlighting)
                        </li>
                    </ul>
                    <p>
                        Quando il browser carica queste risorse, i server CDN potrebbero raccogliere dati tecnici
                        (indirizzo IP, User-Agent, timestamp) per finalità di delivery e sicurezza.
                    </p>
                    <p>
                        <strong>Informative privacy dei CDN:</strong>
                    </p>
                    <ul>
                        <li>
                            jsDelivr:
                            <a href="https://www.jsdelivr.com/privacy-policy-jsdelivr-net" target="_blank" rel="noopener">
                                Privacy Policy
                            </a>
                        </li>
                        <li>
                            Cloudflare:
                            <a href="https://www.cloudflare.com/privacypolicy/" target="_blank" rel="noopener">
                                Privacy Policy
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-clock-history text-warning"></i> Periodo di Conservazione</h2>
                    <p>
                        I dati di navigazione (log del server web) vengono conservati per il tempo strettamente
                        necessario agli adempimenti di legge e comunque non oltre <strong>7 giorni</strong>,
                        salvo necessità di accertamento di reati.
                    </p>
                    <p>
                        Non essendoci raccolta di dati personali tramite form, non vi sono altri dati da conservare.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-people text-secondary"></i> Destinatari dei Dati</h2>
                    <p>
                        I dati di navigazione potrebbero essere condivisi con:
                    </p>
                    <ul>
                        <li>
                            <strong>Provider di hosting</strong> - Per la gestione tecnica del server
                        </li>
                        <li>
                            <strong>Autorità competenti</strong> - Su richiesta motivata per adempimenti di legge
                        </li>
                    </ul>
                    <p>
                        I dati non vengono comunicati a terzi per finalità commerciali o di marketing.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-globe text-primary"></i> Trasferimento Dati Extra-UE</h2>
                    <p>
                        I CDN utilizzati (jsDelivr, Cloudflare) potrebbero avere server ubicati al di fuori
                        dell'Unione Europea. Questi fornitori adottano misure di sicurezza conformi al GDPR
                        e utilizzano meccanismi di trasferimento approvati dalla Commissione Europea.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-hand-thumbs-up text-success"></i> Diritti dell'Interessato</h2>
                    <p>
                        Gli utenti hanno il diritto di ottenere dal Titolare:
                    </p>
                    <ul>
                        <li><strong>Accesso</strong> ai propri dati personali (art. 15 GDPR)</li>
                        <li><strong>Rettifica</strong> dei dati inesatti (art. 16 GDPR)</li>
                        <li><strong>Cancellazione</strong> dei dati (art. 17 GDPR)</li>
                        <li><strong>Limitazione</strong> del trattamento (art. 18 GDPR)</li>
                        <li><strong>Portabilità</strong> dei dati (art. 20 GDPR)</li>
                        <li><strong>Opposizione</strong> al trattamento (art. 21 GDPR)</li>
                    </ul>
                    <p>
                        Per esercitare tali diritti, è possibile contattare il Titolare tramite il repository GitHub.
                    </p>
                    <p>
                        Gli interessati hanno inoltre il diritto di proporre <strong>reclamo</strong> all'Autorità
                        di controllo (Garante per la Protezione dei Dati Personali -
                        <a href="https://www.garanteprivacy.it" target="_blank" rel="noopener">www.garanteprivacy.it</a>).
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-shield-lock text-danger"></i> Sicurezza dei Dati</h2>
                    <p>
                        Il Titolare adotta misure di sicurezza tecniche e organizzative adeguate per proteggere
                        i dati personali da accessi non autorizzati, divulgazione, modifica o distruzione.
                    </p>
                    <p>
                        Il sito utilizza protocollo <strong>HTTPS</strong> per garantire la cifratura delle
                        comunicazioni tra il browser dell'utente e il server.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-arrow-repeat text-info"></i> Modifiche alla Privacy Policy</h2>
                    <p>
                        Il Titolare si riserva il diritto di modificare la presente Privacy Policy in qualsiasi momento.
                        Le modifiche saranno pubblicate su questa pagina con aggiornamento della data in alto.
                    </p>
                    <p>
                        Si invitano gli utenti a consultare periodicamente questa pagina per verificare eventuali aggiornamenti.
                    </p>
                </div>
            </div>

            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h4 mb-3"><i class="bi bi-envelope text-primary"></i> Contatti</h2>
                    <p>
                        Per qualsiasi domanda relativa alla presente Privacy Policy o per esercitare i propri diritti,
                        è possibile contattare il Titolare tramite:
                    </p>
                    <ul>
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
