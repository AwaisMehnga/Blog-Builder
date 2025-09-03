<?php 
$title = 'Admin Dashboard - BilloCraft';
$description = 'Description for Admin Dashboard';
$viteEntry = 'resources/js/Admin/Dashboard/app.jsx';
?>
<?php ob_start(); ?>

<div id="app"></div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>