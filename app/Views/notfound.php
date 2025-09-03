<?php 
$title = 'Notfound - BilloCraft';
$description = 'Description for Notfound';
$viteEntry = 'resources/js/Notfound/app.jsx';
?>
<?php ob_start(); ?>

<div id="app"></div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>