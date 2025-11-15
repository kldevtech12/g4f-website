<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <header class="ak-header">
        <div class="ak-banner">
            <h1 class="ak-banner-title">Template Academy</h1>
        </div>
        <nav class="ak-nav">
            <div class="ak-container">
                <ul class="ak-nav-menu">
                    <li><a href="<?php echo home_url('/'); ?>">Обучение</a></li>
                    <li><a href="<?php echo home_url('/documents'); ?>">Документы</a></li>
                    <li><a href="#" id="ak-calendar-toggle">Календарь</a></li>
                </ul>
            </div>
        </nav>
    </header>
    
    <main class="ak-main">
