<?php
/**
 * Template Name: Privacy Policy
 */
?>
<?php get_header(); ?>

<main class="privacy-policy">
  <div class="container">
    <br><br>
    <h1 class="privacy-policy__title"><?php the_title(); ?></h1>
    <div class="privacy-policy__content">
      <?php the_content(); ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>