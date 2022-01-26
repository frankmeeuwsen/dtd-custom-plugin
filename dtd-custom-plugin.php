<?php

/**
 * @package dtd-custom-plugin
 * @author  Frank Meeuwsen
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://diggingthedigital.com/

 * @wordpress-plugin
 * Plugin Name: Digging the Digital Custom Functions
 * Plugin URI: https://diggingthedigital.com/
 * Description: This plugin contains all of my custom functions. It's amazin
 * Version: 0.2
 * Author: Frank Meeuwsen
 * Author URI: https://diggingthedigital.com/
 * Text Domain: dtd
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Microformats toevoegen in de header
add_action('wp_head', 'microformats_header');

// Disable Google's floc
add_filter('wp_headers', 'disable_floc');

//Add search bar and change search form text
add_filter('wp_nav_menu_items', 'dtd_menu_extras', 10, 2);
add_filter('genesis_markup_search-form-submit_open', 'dtd_search_form_submit');
add_filter('genesis_search_text', 'dtd_search_button_text');


// Apply microformats to posts
add_filter('genesis_attr_entry-title', 'entry_title');
// add_filter( 'genesis_attr_entry-title-link', 'entry_title_link' );
add_filter('genesis_attr_entry-content', 'entry_content');
add_filter('genesis_attr_comment-content', 'comment_content');
add_filter('genesis_attr_comment-author', 'comment_entry_author');
add_filter('genesis_attr_entry-author', 'entry_author');
add_filter('genesis_attr_entry-time', 'time_stamps');
add_filter('genesis_attr_comment-time', 'time_stamps');
add_filter('author-box', 'author_description');
add_filter('genesis_attr_author-archive-description', 'author_archive_description');
add_filter('post_class', 'post_content', 10, 3);
add_filter('genesis_post_categories_shortcode', 'category_shortcode_class');
add_filter('genesis_post_title_output', 'singular_entry_title_link', 10, 3);

add_shortcode('dtd_permalink', 'dtd_permalink');
// add_action('genesis_before_loop', 'themeprefix_remove_post_info');
// add_shortcode('my_permalink', 'my_permalink');

// Toevoegingen aan de RSS feed
add_filter('the_excerpt_rss', 'my_excerpt_rss');
add_filter('the_content_feed', 'my_content_feed');

// Plugin Simple Social Icons wat aangepast
add_filter('simple_social_default_profiles', 'custom_reorder_simple_icons');
add_filter('simple_social_icon_html', 'custom_social_icon_html');

// Tweak de headerinfo per post (kan nog beter)
add_filter('genesis_post_info', 'dtd_post_info_filter');
add_action('admin_post_add_foobar', 'public_to_private');
//* Display author box on single posts
// add_filter( 'get_the_author_genesis_author_box_single', '__return_true' );

remove_action('genesis_entry_content', 'genesis_do_singular_image', 8);
add_post_type_support('post', 'genesis-singular-images');
add_action('genesis_before_entry_content', 'genesis_do_singular_image');

// add_filter('genesis_entry_content','dtd_show_full_likes');
remove_action('genesis_entry_content', 'genesis_do_post_content', 10);

// Om de permalink bij notities weg te halen moet ik deze in een aparte functie aanroepen ipv direct. 
// Dit omdat het nu in een aparte plugin staat. 
add_filter('genesis_entry_content', 'dtd_remove_genesis_do_post_permalink');

add_action('genesis_entry_content', 'dtd_single_post_nav', 30);



// Oude textile posts on the spot aanpassen. Nu nog uit omdat ik de pandoc shizzle nog moet testen op live. 
add_filter('genesis_entry_content', 'dtd_textile_be_gone', 1);

//* Modify the Genesis content limit read more link
add_filter('get_the_content_more_link', 'dtd_read_more_link');

/**  ===============================================================
 * START FUNCTIES
 *  ===============================================================
 */

/**
 * Filter menu items, appending a a search icon at the end.
 *
 * @param string   $menu HTML string of list items.
 * @param stdClass $args Menu arguments.
 *
 * @return string Amended HTML string of list items.
 */
function dtd_menu_extras($menu, $args)
{

	if ('primary' !== $args->theme_location) {
		return $menu;
	}

	$menu .= '<li class="menu-item">' . get_search_form(false) . '</li>';

	return $menu;
}


/**
 * Change Search Form submit button markup.
 *
 * @return string Modified HTML for search forms' submit button.
 */
function dtd_search_form_submit()
{

	$search_button_text = apply_filters('genesis_search_button_text', esc_attr__('Search', 'genesis'));

	$searchicon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="search-icon"><path d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z"></path></svg>';

	return sprintf('<button type="submit" class="search-form-submit" aria-label="Search">%s<span class="screen-reader-text">%s</span></button>', $searchicon, $search_button_text);
}



function dtd_search_button_text($text)
{
	return ('Vind je favoriete artikel');
}


// Add logo to site title
// HIER NOG LOGO CHECKEN OP GROOTTE
// add_action('genesis_header', 'custom_site_image', 5);
/**
 * Output image before site title.
 *
 * Checks to see if a header image exists. If so, output that in an `img` tag. If not, get
 * the Gravatar associated with the site administrator's email (under Settings > General).
 *
 * @see get_header_image()	Retrieve header image for custom header.
 * @see get_avatar() 		Retrieve the avatar `<img>` tag for a user.
 *
 * @return string 		HTML for site logo/image.
 */
function custom_site_image()
{
	$header_image = get_header_image() ? '<img alt="" src="' . get_header_image() . '" />' : '<img alt="" src="/wp-content/uploads/dtd-logo.png" />';
	printf('<a href="/"><div class="site-image">%s</div></a>', $header_image);
}



// add_action( 'genesis_comments', 'display_webmention_likes', 1 ); 

function microformats_header()
{
?>
	<link rel="profile" href="http://microformats.org/profile/specs" />
	<link rel="profile" href="http://microformats.org/profile/hatom" />
<?php
}



function entry_title($attributes)
{
	$attributes['class'] .= ' p-entry-title p-name';
	$attributes['id'] .= get_the_ID();
	return $attributes;
}


function entry_content($attributes)
{
	$attributes['class'] .= ' e-entry-content e-content';
	return $attributes;
}

function entry_author($attributes)
{
	$attributes['class'] .= ' p-author h-card';
	return $attributes;
}


function comment_content($attributes)
{
	$attributes['class'] .= 'comment-content p-summary p-name';
	return $attributes;
}

function comment_entry_author($attributes)
{
	$attributes['class'] .= 'comment-author p-author vcard hcard h-card';
	return $attributes;
}
function time_stamps($attributes)
{
	$attributes['class'] .= ' dt-updated dt-published';
	return $attributes;
}
function author_description($attributes)
{
	$attributes['class'] .= ' p-note';
	return $attributes;
}

function author_archive_description($attributes)
{
	$attributes['class'] .= ' vcard h-card';
	return $attributes;
}

function post_content($classes, $class, $post_id)
{
	$classes[] .= 'h-entry';
	return $classes;
}

function category_shortcode_class($output)
{
	$output = str_replace('<a ', '<a class="p-category"', $output);
	return $output;
}

function singular_entry_title_link($output, $wrap, $title)
{
	if (!is_singular()) {
		return $output;
	}

	$title = genesis_markup(
		[
			'open'    => '<a %s>',
			'close'   => '</a>',
			'content' => $title,
			'context' => 'entry-title-link',
			'atts' => ['class' => 'entry-title-link u-url',],
			'echo'    => false,
		]
	);

	$output = genesis_markup(
		[
			'open'    => "<{$wrap} %s>",
			'close'   => "</{$wrap}>",
			'content' => $title,
			'context' => 'entry-title',
			'params'  => [
				'wrap' => $wrap,
			],
			'echo'    => false,
		]
	);

	return $output;
}


// The Permalink Shortcode
function dtd_permalink()
{
	ob_start();
	the_permalink();
	return ob_get_clean();
}


// Remove Post Info, Post Meta from CPT
function themeprefix_remove_post_info()
{
	if ('custom_post_type_name' == get_post_type()) { //add in your CPT name
		remove_action('genesis_entry_header', 'genesis_post_info', 12);
		remove_action('genesis_entry_footer', 'genesis_post_meta');
	}
}

function disable_floc($headers)
{
	$headers['Permissions-Policy'] = 'interest-cohort=()';
	return $headers;
}




// The Permalink Shortcode XXXXXX UITZOEKEN XXXXXX
function my_permalink()
{
	ob_start();
	the_permalink();
	return ob_get_clean();
}


function my_excerpt_rss($content)
{
	global $post;

	if (has_category('rss-club', $post->ID)) {
		// Excerpts usually don't contain HTML. Leave out the link.
		// $content = 'Dit bericht is alleen voor abonnees. ' . $content;

		// However, you probably could get away with it, like so:
		$content = '<p>' . $content . '</p><p>Dit is een geheim bericht voor iedereen. RSS Only. Niet strikt geheim, maar niet direct publiek zichtbaar. Alle vormen van reacties en links zijn welkom. <a href="' . get_permalink(get_page_by_path('rss-club')) . '">Lees alles over de RSS Club.</a></p>';
	}

	return $content;
}


function my_content_feed($content)
{
	global $post;

	if (has_category('rss-club', $post->ID)) {
		$content = $content . '
		<aside class="notice">
      <svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 24 24" width="16" height="16" style="height: 1.2em; width: 1.2em; vertical-align: text-bottom">
        <path fill="currentColor" d="M 4 4.44 v 2.83 c 7.03 0 12.73 5.7 12.73 12.73 h 2.83 c 0 -8.59 -6.97 -15.56 -15.56 -15.56 Z m 0 5.66 v 2.83 c 3.9 0 7.07 3.17 7.07 7.07 h 2.83 c 0 -5.47 -4.43 -9.9 -9.9 -9.9 Z M 6.18 15.64 A 2.18 2.18 0 0 1 6.18 20 A 2.18 2.18 0 0 1 6.18 15.64" />
      </svg><br />
      <tt>Dit is een geheim bericht voor iedereen. RSS Only. Niet strikt geheim, maar niet direct publiek zichtbaar. Alle vormen van reacties en links zijn welkom. <br />
      <a href="' . get_permalink(get_page_by_path('rss-club')) . '">Lees alles over de RSS Club.</a>.<br />
      </tt><br />
    </aside>';
	}

	return $content;
}



function custom_reorder_simple_icons($icons)
{

	// Set your new order here
	$new_icon_order = array(
		'rss'         => '',
		'linkedin'    => '',
		'medium'      => '',
		'twitter'     => '',
		'github'      => '',
		'instagram'   => '',
		'email'       => '',
		'phone'       => '',
		'youtube'     => '',
		// 'behance'     => '',
		// 'bloglovin'   => '',
		// 'dribbble'    => '',
		// 'facebook'    => '',
		// 'flickr'      => '',
		// 'gplus'       => '',
		// 'periscope'   => '',
		// 'pinterest'   => '',
		// 'snapchat'    => '',
		// 'stumbleupon' => '',
		// 'tumblr'      => '',
		// 'vimeo'       => '',
		// 'xing'        => '',
	);


	foreach ($new_icon_order as $icon => $icon_info) {
		$new_icon_order[$icon] = $icons[$icon];
	}

	return $new_icon_order;
}


function custom_social_icon_html($html)
{
	return str_replace('<a', '<a rel="me"', $html);
}

add_shortcode('blogroll_links', function () {
	$out = wp_list_bookmarks('title_li=');
	return $out;
});

// Add Shortcode
// add_shortcode( 'get_current_author_avatar', function () {
//      global $post;
//     $post_author = $post->post_author;
//     return get_avatar($post_author, '32');

// });



function dtd_post_info_filter($post_info)
{

	// get author details
	$entry_author = get_avatar(get_the_author_meta('email'), 32, null, null, array('class' => array('u-photo'), 'extra_attr' => 'style="display:none"'));
	$author_link = get_author_posts_url(get_the_author_meta('ID'));
	// $post_kind = get_post_kind_string();
	// build updated post_info
	$post_info = '[post_date] door ';
	$post_info .= sprintf('<span class="author-avatar"><a href="%s">%s</a></span>', $author_link, $entry_author);
	$post_info .= '[post_author_posts_link] [post_comments] ';
	if (has_post_kind('note') | has_post_kind('like')) {
		$post_info .= sprintf('<span class="permalink"><a href="%s">§</a></span> ', get_permalink());
	}
	// $post_info .= '[post_edit]';
	if (current_user_can('edit_posts') && get_post_status() !== 'private') {
		$post_info .= '<a class="dtd_privatelink" href="' . wp_nonce_url(admin_url('/admin-post.php?action=add_foobar&post=' . get_the_ID() . ''), "add_foobar", "foobar_nonce") . '">PRIVATE</a>';
		// $post_info .='<a class="dtd_privatelink" href="'.admin_url('/admin-post.php?action=add_foobar&post='.get_the_ID().'').'">PRIVATE</a>';
	}

	return $post_info;
}



// add_action( 'admin_post_nopriv_add_foobar', 'public_to_private' );

function public_to_private()
{
	// var_dump($_GET);
	// wp_die();

	if (
		!isset($_GET['foobar_nonce']) || !wp_verify_nonce($_GET['foobar_nonce'], 'add_foobar')
	) {
		print 'Sorry, your nonce did not verify.';
		exit;
	} else {
		wp_update_post(array('ID' =>  $_GET['post'], 'post_status' => 'private'));
		if (wp_get_referer()) {
			wp_safe_redirect(wp_get_referer() . '#' . $_GET['post']);
		} else {
			wp_safe_redirect(get_home_url());
		}
	}
}




function dtd_single_post_nav()
{

	if (!is_singular('post'))
		return;

	echo '<div class="pagination-previous alignleft">';
	previous_post_link('%link', '&#x000AB; %title', FALSE);
	echo '</div>';

	echo '<div class="pagination-next alignright">';
	next_post_link('%link', '%title &#x000BB;', FALSE);
	echo '</div>';
}
// Speciale css voor notitie
// add_filter('post_class', 'add_post_class');
function add_post_class($classes)
{
	$additional = 'dtd-note';
	foreach ($classes as $class) {
		if ($class == 'kind-note') {
			array_push($classes, $additional);
			break;
		}
	}
	return $classes;
}



// add_action( 'pre_get_posts', 'custom_query_vars',20);
function custom_query_vars($query)
{
	if (!is_admin() && $query->is_main_query() && is_tag('open')) {
		$query->set('post_type', array(
			'post',
			'newsletterglue'
		));
		return $query;
	}
	// var_dump($query);
	// wp_reset_postdata();
}



// add_action('save_post','add_open_newsletter_taxonomy',10,3);
function add_open_newsletter_taxonomy($post_id, $post, $update)
{
	if ('newsletterglue' == $post->post_type) {
		$term = get_term_by('term_id', 114);
		wp_set_post_terms($post_id, array($term->term_id), 'post_tag', true);
	}
}

//  Add featured images

add_image_size('genesis-singular-images', 800, 400, true);

// Give bookmarks specific emoji and remove older "Bookmark: " prefix
add_filter('the_title', 'dtd_filternote');
function dtd_filternote($title)
{
	if (has_post_kind('bookmark') & in_the_loop()) {
		if (strncmp($title, "Bookmark: ", 9) === 0) {
			$title = substr($title, 10);
		}
		$title = sprintf('🔖 %s', $title);
	}
	return $title;
};

// add custom content to all feeds
function dtd_add_content_to_all_feeds($content)
{

	$before = 'Like of'.$post->url;
	// $after = '<p>Custom content displayed after content.</p>';

	if (is_feed() && has_post_kind('like', $post->ID)) {

		return $before . $content; //. $after;
	} else {

		return $content;
	}
}
// add_filter('the_excerpt_rss', 'dtd_add_content_to_all_feeds');

// function dtd_show_full_likes(){
	
// }

// add_action('genesis_before_entry', 'dtd_textile_be_gone');


function dtd_textile_be_gone($content){
	if(strpos( get_the_content(), '":http' ) !== false){
		remove_filter('genesis_entry_content','genesis_do_post_content');
		// $content = 'Nieuwe content';
	require_once(get_stylesheet_directory() . '/vendor/autoload.php');
		$content = (new \Pandoc\Pandoc)
			->from('textile')
			->input(get_the_content())
			->to('html')
			->option('css', get_stylesheet_uri())
			->run();
		// } else{
	// 	$output_content = get_the_content();
	// }
	}

	echo $content;

}

function dtd_remove_genesis_do_post_permalink(){
	remove_filter('genesis_entry_content', 'genesis_do_post_permalink',14);
}

function dtd_read_more_link()
{
	return '... <p><a class="more-link" href="' . get_permalink() . '">[Lees verder]</a></p>';
}