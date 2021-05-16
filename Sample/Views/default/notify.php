<!DOCTYPE html>
<html>
    <head>
        <?php require_once __DIR__ . '/../commonParts/baseHead.php'; ?>
    </head>
    <body>
        <header>
            <?php echo $projectTitle; ?>
        </header>
        <nav>
            <?php require_once __DIR__ . '/commonParts/menu.php'; ?>
        </nav>
        <section class="d-flex align-items-center flex-column">
            <h1>Notify</h1>
            <?php echo $message; ?>
        </section>
        <footer>

        </footer>
    </body>
</html>