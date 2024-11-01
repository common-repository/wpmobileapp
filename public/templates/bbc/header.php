<?php
global $WPMA;
$sThemeColorManifesh = $WPMA->setting->getOption('WPMA_MF_THEME_COLOR');

?>

<!DOCTYPE html>
<html lang="en">

<head>
	<title><?php wp_title(); ?></title>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width">

	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
	
	<!--wordpress head-->
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php do_action('after_body') ?>
<div class="wpma-offline-alert-sticky"></div>
<div class="page-wrap bbc-content-color">