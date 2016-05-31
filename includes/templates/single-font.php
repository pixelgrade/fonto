<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Fonto
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		$template_path='/template-parts/content-'.get_post_type().'.php';
		while ( have_posts() ) : the_post();
			$theme_files = array($template_path);
	    		// Check for overiding template in theme
	    	$exists_in_theme = locate_template($theme_files);
	    	
	    	if ($exists_in_theme) {
	    		get_template_part( $template_path );
	    	} else {
				$template=dirname(__FILE__)."/".$template_path;
				$file_exists=file_exists($template);
				
				if ($file_exists) {
					include($template);
				}
				 
			}
			the_post_navigation();

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endwhile; // End of the loop.
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
