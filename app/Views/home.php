<?php 
$title = 'Home - BilloCraft';
$description = 'Welcome to BilloCraft - Your awesome application';
$viteEntry = 'resources/js/homePage/app.jsx';
?>
<?php ob_start(); ?>

<div id="app"></div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
