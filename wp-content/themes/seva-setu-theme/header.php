<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header class="site-header">
    <div class="container">
        <h1 class="site-title"><?php bloginfo('name'); ?></h1>
        <p class="tagline">Connecting Citizens with Government Opportunities</p>
        <nav>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                <li><a href="<?php echo esc_url(home_url('/government-schemes')); ?>">Government Schemes</a></li>
                <li><a href="<?php echo esc_url(home_url('/become-agent')); ?>">Become Agent</a></li>
                <li><a href="<?php echo esc_url(home_url('/citizen-portal')); ?>">Citizen Portal</a></li>
                <li><a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a></li>
            </ul>
        </nav>
    </div>
</header>
<main class="main-content">
    <div class="container">
