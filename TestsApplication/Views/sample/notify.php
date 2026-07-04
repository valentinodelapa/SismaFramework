<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require __DIR__ . '/../commonParts/baseHead.php'; ?>
    </head>
    <body>
        <header>
            <h1>Notifica</h1>
        </header>
        <nav>
            <?php require __DIR__ . '/../commonParts/menu.php'; ?>
        </nav>
        <section class="d-flex align-items-center flex-column">
            <h1>Notify</h1>
            <?php echo $message; ?>
        </section>
        <footer>

        </footer>
    </body>
</html>