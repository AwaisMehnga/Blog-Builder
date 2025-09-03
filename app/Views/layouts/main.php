<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'BilloCraft' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $description ?? 'BilloCraft Application' ?>">
    <?php if (isset($viteEntry)): ?>
        <?= vite_css($viteEntry) ?>
        <?= vite_react_refresh() ?>
    <?php endif; ?>
</head>
<body>
    <!-- <?php if (!isset($hideNav)): ?>
        <?php if (file_exists(view_path('partials.nav'))): ?>
            <?php include view_path('partials.nav'); ?>
        <?php endif; ?>
    <?php endif; ?> -->

    <main>
        <?= $content ?? '' ?>
    </main>

    <?php if (isset($viteEntry)): ?>
        <?= vite_asset($viteEntry) ?>
    <?php endif; ?>
</body>
</html>
