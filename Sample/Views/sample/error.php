<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
    </head>
    <body>
        <header>Errore</header>
        <nav>

        </nav>
        <div class="body">
            <article>
                <?php echo $message; ?>
            </article>     
        </div>
        <footer>
            <?php require_once __DIR__ . '/../commonParts/footer.php'; ?>
        </footer>
    </body>
</html>