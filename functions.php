<?php
/*
 	THE MAIN FUNCTIONS FILE	
 
	Written by Johan Brook except where noted.
	
 */



/* SETUP
------------------------------------------------------*/

/* Defines a constant containing the absolute path to theme
 directories. Using 'bloginfo' all the time is tough on the database.*/

define("WB_THEME_DIR", get_template_directory_uri() . "/");
define("WB_JS_DIR", WB_THEME_DIR . "resources/javascripts/");
define("WB_IMG_DIR", WB_THEME_DIR . "resources/images/");
define("WB_CSS_DIR", WB_THEME_DIR . "resources/stylesheets/");


// Include resources:

require_once "library/inc.helpers.php";
require_once "library/class.Options.php";
require_once "library/class.Metabox.php";
	
require_once "library/inc.settings.php";	


// Run setup:
add_action("after_setup_theme", "whiteboard_setup");



/* CONFIGURATION
------------------------------------------------------*/

/* Google Analytics */

// Fill in your Google Analytics tracking code:
define("GOOGLE_ANALYTICS_ID", "XX-XXXXXXX-X");


// The size (in characters) of the post excertps.
define("EXCERPT_LENGTH", 40);

// Register jQuery.
define("USE_JQUERY", true);




if(!function_exists("whiteboard_setup")):
	
function whiteboard_setup(){
	
		
	# Removes bloat from the <head>:
	add_action('init', 'remove_head_links');
	# Init custom post types (see callback function below)
	add_action("init", "whiteboard_posttypes");
	
	# Return the actual shortlink
	add_filter( 'the_shortlink', 'my_shortlink', 10, 4 );
	
	# Add handy browser classes to the body element
	add_filter('body_class','browser_body_class');
	
	# Fine tune the excerpts
	add_filter( 'excerpt_length', 'jb_excerpt_length' );
	add_filter( 'get_the_excerpt', 'custom_excerpt' );
	add_filter( 'excerpt_more', 'read_more_link' );
	
	# Add support for thumbnails, menus and RSS feed links
	add_theme_support("post-thumbnails");
	add_theme_support("menus");
	add_theme_support('automatic-feed-links');
	
	# Disable the admin bar
	add_filter( 'show_admin_bar', '__return_false' );
	
	# Add Google Analytics to the <head> (uncomment in production!)
	#add_action('wp_head', 'add_google_analytics_async');
	
	/* Post thumbnail sizes */
	set_post_thumbnail_size(456, 364, true);
	# Add more with 'add_image_size()'.
	
	/* Nav Menus */
	register_nav_menus( array(
		'main-nav' => __('Main navigation'),
		'footer-nav' => __("Footer navigation"),
	));

		
		
}

endif;


/* MISC
------------------------------------------------------*/


/* Clean up the <head> */

function remove_head_links() {
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'index_rel_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'start_post_rel_link', 10, 0);
	remove_action('wp_head', 'parent_post_rel_link', 10, 0);
	remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
}



/**
*	Use the actual short URL in shortlinks.
*
*/
function my_shortlink( $link, $shortlink, $text, $title ){
	return $shortlink;
}




/**
*	Adds the current browser as a class to the body tag. Handy for styling.
*
*/
function browser_body_class($classes) {
	global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_safari, $is_chrome, $is_iphone;

	$is_win = stripos($_SERVER["HTTP_USER_AGENT"], "windows");
	
	if($is_lynx) $classes[] = 'lynx';
	elseif($is_gecko) $classes[] = 'gecko';
	elseif($is_opera) $classes[] = 'opera';
	elseif($is_safari) $classes[] = 'safari';
	elseif($is_chrome) $classes[] = 'chrome';
	elseif($is_IE) $classes[] = 'ie';
	else $classes[] = 'unknown';

	if($is_iphone) $classes[] = 'iphone';
	
	// Adds 'windows' to the body class
	if($is_win !== false) $classes[] = 'windows';
	
	return $classes;
}	




/*	jQUERY SETUP
-------------------------------------------------*/

if ( !is_admin() && USE_JQUERY == true) {
	wp_deregister_script('jquery');

	// I often use a local copy of jQuery in dev mode, included in the style/js directory.

	#wp_register_script('jquery', ("https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"), false, "1.6.2", true);
	wp_register_script('jquery', (WB_JS_DIR . "library/jquery.min.js"), false, "1.6.1", true);
	wp_enqueue_script('jquery');
}




/* CUSTOM POST TYPES
-------------------------------------------------*/

function whiteboard_posttypes(){
	
	// Labels in wp-admin for your post type
	$portfolio_labels = array(
		"name" => "Portfolio items",
		"singular_name" => "Portfolio item",
		"add_new_item" => "Add portfolio item",
		"edit_item" => "Edit portfolio item",
		"new_item" => "New portfolio item",
		"view_item" => "View the item",
		"not_found" => "No portfolio items"
	);
	
	// The parameters. Please see the Codex for all options: http://codex.wordpress.org/Function_Reference/register_post_type
	$portfolio_args = array(
		"labels" => $portfolio_labels,
		"public" => true,
		"show_ui" => true,
		"cabability_type" => "post",
		"rewrite" => array("slug" => "portfolio"),
		"description" => "My portfolio items",
		"supports" => array("title", "editor", "custom-fields", "thumbnail", "excerpt")
	);
	
	#register_post_type("portfolio", $portfolio_args);
}





/* EXCERPTS
-------------------------------------------------*/

/**
*	Sets the excerpt length. Stored in the EXCERPT_LENGTH constant.
*/
function jb_excerpt_length( $length ) {
	return EXCERPT_LENGTH;
}



/**
 * Adds the "Read More" link to custom post excerpts.
 *
 * @return string Excerpt with "Read More" link in the end.
 */
function custom_excerpt( $output ) {
	if ( has_excerpt() && ! is_attachment() ) {
		$output .= read_more_link();
	}
	return $output;
}


/**
* 	Returns a "Read more" link in excerpts
*
*	@args String $text: The text inside the link. Defaults to 'Read more'.
*	@args String $class: The class attribute. Defaults to 'read-more'.
*
*	@return String: The formatted link with an ellipsis in the front.
*/
function read_more_link() {
	$link = ' &hellip; <a class="read-more" href="%2$s">%1$s</a>';
	
	return sprintf($link, __("Läs mer"), get_permalink());
}







/* GOOGLE ANALYTICS
--------------------------------------------------------*/

// The new, asynchronous way
function add_google_analytics_async(){?>
	
	<script>
	 var _gaq = [['_setAccount', '<?php echo GOOGLE_ANALYTICS_ID; ?>'], ['_trackPageview']];
	 (function(d, t) {
	  var g = d.createElement(t),
	      s = d.getElementsByTagName(t)[0];
	  g.async = 1;
	  g.src = '//www.google-analytics.com/ga.js';
	  s.parentNode.insertBefore(g, s);
	 }(document, 'script'));
	</script>

<?php
}
	

