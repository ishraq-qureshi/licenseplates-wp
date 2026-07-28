<?php
/**
 * The template for displaying post index
 */

get_header(); ?>

<div id="content">
    <div class="container">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <div <?php post_class() ?> id="post-<?php the_ID(); ?>">
                    <h2 class="postTitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
                    <small><?php the_time('F jS, Y') ?></small>

                    <div class="entry">
                        <?php the_content('Read more...'); ?>
                    </div>

                    <p class="postmetadata"><?php the_tags('Tags: ', ', ', '<br />'); ?> Posted in <?php the_category(', ') ?></p>
                </div>
            <?php endwhile; ?>
            <div class="navigation">
                <div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
                <div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>





