<!DOCTYPE html>
<html>
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
            <?php foreach ($array as $key => $value) { ?>
                <div><?php echo $key; ?>: <?php echo $value; ?></div>
            <?php } ?>
        </section>
        <footer>
            <?php require_once __DIR__ . '/../commonParts/footer.php'; ?>
        </footer>
    </body>
</html>