<?php 
$title = 'About Us - BilloCraft';
$description = 'Learn more about BilloCraft and our mission';
?>
<?php ob_start(); ?>

<h1>About Us</h1>
<p>Welcome to the about page!</p>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>