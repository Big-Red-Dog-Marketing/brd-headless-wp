<?php
/**
 * Only previews reach this template: the BRD Headless plugin redirects every
 * other front-end request to the public site.
 */

if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<style>
  body { max-width: 42rem; margin: 2rem auto; padding: 0 1rem; font: 18px/1.6 system-ui, sans-serif; color: #1e293b; }
  img { max-width: 100%; height: auto; }
  .preview-note { padding: .5rem .75rem; background: #f1f5f9; font-size: 15px; }
</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main>
<?php while (have_posts()) : the_post(); ?>
  <article>
    <p class="preview-note">Preview. The public site shows this once it's published and the site rebuilds.</p>
    <h1><?php the_title(); ?></h1>
    <?php the_post_thumbnail('large'); ?>
    <?php the_content(); ?>
  </article>
<?php endwhile; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
