<!DOCTYPE html>
<html lang="it">
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
    </head>
    <body>
        <header>
            <h1>Index</h1>
        </header>
        <nav>
            <?php require_once __DIR__ . '/../commonParts/menu.php'; ?>
        </nav>
        <section class="d-flex align-items-center flex-column">
            <h1>Other - Index</h1>
            <?php echo $authentication ? 'is submitted' : 'is not submitted'; ?>
        </section>
        <footer>
            <?php require_once __DIR__ . '/../commonParts/footer.php'; ?>
        </footer>
    </body>
</html>