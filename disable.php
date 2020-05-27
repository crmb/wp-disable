<?php
/*
WP unbloat.
Corentin Vigouroux - 2020.02

Configure disable_template_redirect to your needs

*/


/* Disable XMLRPC */
add_filter('xmlrpc_enabled', '__return_false');

/* Clean up <head> */
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link');
remove_action( 'wp_head', 'wp_generator');
/* Désactiver le shortlink en header */
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0);

/* Désactiver canonical */
remove_action('wp_head', 'rel_canonical');

/* retirer rel='prev'/rel='next' */

remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');


// Remove the REST API lines from the HTML Header
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
remove_action( 'template_redirect', 'rest_output_link_header', 11 ); // dans header apache

# https://developer.wordpress.org/rest-api/using-the-rest-api/frequently-asked-questions/  
/*
add_filter('rest_authentication_errors', 'secure_api');
function secure_api( $result ){
	if ( ! empty($result) ) {
		return $result;
	}
	if ( !is_user_logged_in() ){
		return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
	}
	return $result;
}
*/


/* Kill attachment, search, author, daily archive pages */
add_action('template_redirect', 'disable_template_redirect');
function disable_template_redirect(){

	global $wp_query, $post;
     
  if ( is_author() || is_attachment() || is_day() || is_month() || is_year() || is_tag() ){
	  $wp_query->set_404();
	  status_header(404);
  }
     
  if (is_feed()){
		$author     = get_query_var('author_name');
		$attachment = get_query_var('attachment');
		$attachment = (empty($attachment)) ? get_query_var('attachment_id') : $attachment;
		$day        = get_query_var('day');
		$tag        = get_query_var('tag');
		$search     = get_query_var('s');
		     
		if (!empty($author) || !empty($attachment) || !empty($day) || !empty($tag) || !empty($search) ){
			$wp_query->is_feed = false;
			$wp_query->set_404();
			# https://fr.wordpress.org/plugins/disable-feeds/
			status_header( 404 );
		}
	}
	
}


/* Désactiver RSS */
add_action('do_feed', 'disabler_kill_rss');
add_action('do_feed_rdf', 'disabler_kill_rss');
add_action('do_feed_rss', 'disabler_kill_rss');
add_action('do_feed_rss2', 'disabler_kill_rss');
add_action('do_feed_atom', 'disabler_kill_rss');
function disabler_kill_rss(){
	wp_die( "No feeds available." );
}

remove_action( 'wp_head', 'feed_links_extra', 3 ); //Extra feeds such as category feeds
remove_action( 'wp_head', 'feed_links', 2 ); // General feeds: Post and Comment Feed



/* Supprimer le pollyfil "emoji" */
# Plugin https://wordpress.org/plugins/disable-emojis/
function disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );	
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
	
	add_filter( 'emoji_svg_url', '__return_false' );	// <link rel='dns-prefetch' href='//s.w.org'>
	
}
add_action( 'init', 'disable_emojis' );

function disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	} else {
		return array();
	}
}


/* Désactiver les smiley standard */
# https://core.trac.wordpress.org/ticket/34773
add_filter( 'option_use_smilies', '__return_false' );


/* enlever les accents dans les noms de fichiers */
add_filter('sanitize_file_name', 'remove_accents' );

/* Supprimer le menu "Aide" */
add_filter( 'contextual_help', 'mytheme_remove_help_tabs', 999, 3 );
function mytheme_remove_help_tabs($old_help, $screen_id, $screen){
	$screen->remove_help_tabs();
	return $old_help;
}

/* Supprimer les liens vers le Customizer */
function remove_some_nodes_from_admin_top_bar_menu( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'customize' );
}
add_action( 'admin_bar_menu', 'remove_some_nodes_from_admin_top_bar_menu', 999 );

function remove_customize_page(){
	global $submenu;
	unset($submenu['themes.php'][6]); // remove customize link
}
add_action( 'admin_menu', 'remove_customize_page');

# https://wpreset.com/remove-default-wordpress-rewrite-rules-permalinks/
# est-ce que ça ralenti plus qu'autre chose ?
function clean_rewrite_rules( $rules ) {
  foreach ( $rules as $rule => $rewrite ) {
    if ( preg_match( '%(feed|attachment|archives|trackback|comment|author|year|search|category|embed|tag|register|page/)%', $rule ) ) {
      unset( $rules[$rule] );
    }
  }
      
  return $rules;
}
add_filter( 'rewrite_rules_array', 'clean_rewrite_rules' );

# https://fr.wordpress.org/plugins/disable-search/
function wpb_filter_query( $query, $error = true ) {
	if(is_search() && is_main_query() && !is_admin()){
		unset( $_GET['s'] );
		unset( $_POST['s'] );
		unset( $_REQUEST['s'] );
		unset( $query->query['s'] );
		$query->set( 's', '' );
		$query->is_search = false;
		$query->set_404();
		status_header( 404 );
		//nocache_headers();
	}
}
add_action( 'parse_query', 'wpb_filter_query' );
