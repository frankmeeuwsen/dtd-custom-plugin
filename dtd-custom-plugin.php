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
add_action('rss_tag_pre', 'dtd_add_namespace');
add_filter('feed_content_type', function () {
	return 'text/xml';
});
add_filter( 'the_title_rss', 'dtd_changeLikeText' );
add_filter('the_permalink_rss', 'dtd_changeLikeURL' );

// Gewoon weer de WP feed templates gebruiken ipv de Post Kinds templates
remove_all_actions('do_feed_rss2');
remove_all_actions('do_feed_atom');
add_action('do_feed_rss2', function(){
	do_feed_rss2(false);
}, 10, 1);
add_action('do_feed_atom', function(){
	do_feed_atom(false);
}, 10, 1);


// Plugin Simple Social Icons wat aangepast
add_filter('simple_social_default_profiles', 'custom_reorder_simple_icons');
add_filter('simple_social_icon_html', 'custom_social_icon_html');
// add_filter('simple_social_default_profiles', 'custom_add_new_simple_icon');

// Tweak de headerinfo per post (kan nog beter)
add_filter('genesis_post_info', 'dtd_post_info_filter');
add_action('admin_post_add_foobar', 'public_to_private');
//* Display author box on single posts
// add_filter( 'get_the_author_genesis_author_box_single', '__return_true' );

//  Add featured images
add_image_size('genesis-singular-images', 400, 400, true);

add_post_type_support('post', 'genesis-singular-images');
add_action('genesis_before_entry_content', 'genesis_do_singular_image');
// add_filter('the_title', 'dtd_post_kind_title');

// add_filter('genesis_entry_content','dtd_show_full_likes');
// remove_action('genesis_entry_content', 'genesis_do_post_content');



// Om de permalink bij notities weg te halen moet ik deze in een aparte functie aanroepen ipv direct. 
// Dit omdat het nu in een aparte plugin staat. 
add_filter('genesis_entry_content', 'dtd_remove_genesis_do_post_permalink');

add_action('genesis_entry_content', 'dtd_single_post_nav', 30);
add_action('genesis_entry_content', 'dtd_pixelfed_show_featured_image', 10);
add_action("genesis_entry_content", "dtd_note_on_main", 2);

// Filter om bij een note zonder titel toch een titel te tonen. Madness. 
add_filter('genesis_post_title_text', 'dtd_show_title_with_note');


//* Modify the Genesis content limit read more link
add_filter('get_the_content_more_link', 'dtd_read_more_link');
add_action('webmention_post_send', 'dtd_webmention_log', 10,4);

add_filter( 'share_on_mastodon_status', 'dtd_share_on_mastodon_status', 10,2);



add_filter('genesis_entry_content', 'dtd_textile_be_gone', 1);


// Pixelfed dingen klaarmaken
add_action('genesis_entry_footer', 'dtd_post_pixelfed', 20);
add_filter('import_from_pixelfed_args', 'dtd_import_pixelfed_args', 20);

// RSS related
add_action('init', 'customRSS');
add_filter('posts_where', 'publish_later_on_feed');

// Post Kinds weghalen
// Dit is dus klote want dan moet je zelf weer alles er in gaan plakken naderhand...
// add_filter( 'kind_content_display', '__return_false' );

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


function dtd_post_kind_title($title){
	if (has_post_kind('note') & in_the_loop()) {
		$title = kind_get_the_title();
	}
	return $title;
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
	// $attributes['id'] .= get_the_ID();
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

function dtd_add_namespace(){
	if(is_feed() && $_SERVER['REQUEST_URI']!=='/feed/dtd/'){
		echo
		'<?xml-stylesheet href="'. get_stylesheet_directory_uri(__FILE__) .'/feed.xsl' . '" type="text/xsl"?>';
 
	}
}

function dtd_changeLikeText($title){
	$post_id = get_the_ID();
	$post_kind = get_post_kind($post_id);
	$kind_post = new Kind_Post($post_id);
	$cite      = $kind_post->get_cite();
	$cite      = $kind_post->normalize_cite($cite);
	if (has_post_kind('like') && is_feed() && $title == '[Like]') {
			$title = sprintf('[%1$s] %2$s (%3$s) %4$s', $post_kind, $cite['name'], $cite['publication'],'↗️');
		return $title;
	} else {
		return $title;
	}

}
function dtd_changeLikeURL($url){
	$post_id = get_the_ID();
	$post_kind = get_post_kind($post_id);
	$kind_post = new Kind_Post($post_id);
	$cite      = $kind_post->get_cite();
	$cite      = $kind_post->normalize_cite($cite);
	if (has_post_kind('like') && is_feed()) {
		$url = $cite['url'];
		return $url;
	} else {
		return $url;
	}

}

function custom_reorder_simple_icons($icons)
{

	// Set your new order here
	$new_icon_order = array(
		'rss'         => '',
		'linkedin'    => '',
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

function custom_add_new_simple_icon($icons)
{
	$icons['mastodon'] = [
		'label'   => __('Mastodon', 'simple-social-icons'),
		'pattern' => '<li class="social-mastodon"><a href="%s" %s><svg role="img" class="social-mastodon-svg" aria-labelledby="social-mastodon"><title id="social-mastodon">' . __('Mastodon icon', 'simple-social-icons') . '</title><use xlink:href="' . esc_url(get_stylesheet_directory_uri(__FILE__) . '/ssi.svg#social-mastodon') . '"></use></svg></a></li>',
	];

	return $icons;
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
	if (has_post_kind('note') | has_post_kind('like') | has_post_kind('reply')) {
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


function dtd_pixelfed_show_featured_image()
{
	if(!is_singular('post'))
	return;
	if (metadata_exists('post', get_the_ID(), '_import_from_pixelfed_url')) {
		the_post_thumbnail([250], ['class' => 'aligncenter']);
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


// Give bookmarks specific emoji and remove older "Bookmark: " prefix
// add_filter('the_title', 'dtd_filternote');
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


function dtd_textile_be_gone($content){
	if(strpos( get_the_content(), '":http' ) !== false){
		remove_filter('genesis_entry_content','genesis_do_post_content');
		require_once(get_stylesheet_directory() . '/vendor/autoload.php');
		$content = new \Netcarver\Textile\Parser();
		echo $content
			->setDocumentType('html5')
		// Hier nog een preg_replace om de image direct zichtbaar te maken. Met harde link naar een aparte dir in de uploads dir. 
			->parse(preg_replace('#\[\[image:([0-9A-Za-z]*)(\.jpg|\.gif|\.JPG|\.GIF|\.png|\.PNG)::([\w]*):([\d]*)\]\]#', '<img src="/wp-content/uploads/punkeycomimages/${1}${2}" class="aligncenter"/><br />', get_the_content()));
		// ->parse(get_the_content());	
		} else {
	$content = get_the_content();
	}
}

function dtd_remove_genesis_do_post_permalink(){
	remove_filter('genesis_entry_content', 'genesis_do_post_permalink',14);
	
}

function dtd_read_more_link()
{
	return '... <p><a class="more-link" href="' . get_permalink() . '">[Lees verder]</a></p>';
}


function dtd_import_pixelfed_args( $args ) { 
	// Set post category. 
	$args['post_category'] = array(14);
	$args['post_author'] = 1; 
	// Or `array( 1, 11 )` or whatever ;-) 
	return $args; } ;

// Het werkt maar de class is nog niet helemaal netjes. Moet eigenlijk aan post_meta worden toegevoegd. 
function dtd_post_pixelfed($post){
	if(metadata_exists('post',get_the_ID(), '_import_from_pixelfed_url')){
	echo sprintf('<p class="entry-meta">Gepost op <a href="%s">Pixelfed</a></p>', get_post_meta(get_the_ID(), '_import_from_pixelfed_url', true));
	}
	}

function dtd_webmention_log($response, $source, $target, $post_id)
{

	if (is_wp_error($response)) {
		// Something went wrong.
		error_log('Error trying to send webmention to ' . esc_url_raw($target) . ': ' . $response->get_error_message());
	} else {
		error_log('Sent webmention to ' . esc_url_raw($target) . '; response code: ' . wp_remote_retrieve_response_code($response));
	}
};


// function rssLanguage()
// {
// 	update_option('rss_language', 'en');
// }
// add_action('admin_init', 'rssLanguage');

function customRSS()
{
	add_feed('dtd', 'customRSSFunc');
}

function customRSSFunc()
{
	get_template_part('feed', 'dtd');
}

function publish_later_on_feed($where) {
 
    global $wpdb;
 
    if ( is_feed() ) {
        // timestamp in WP-format
        $now = gmdate('Y-m-d H:i:s');
 
        // value for wait; + device
        $wait = '30'; // integer
 
        // http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_timestampdiff
        $device = 'MINUTE'; //MINUTE, HOUR, DAY, WEEK, MONTH, YEAR
 
        // add SQL-sytax to default $where
        $where .= " AND TIMESTAMPDIFF($device, $wpdb->posts.post_date_gmt, '$now') > $wait ";
    }
    return $where;
}

function dtd_share_on_mastodon_status($status, $post)
{
	$status = wp_strip_all_tags($post->post_content);
	$status .= "\n\n" . get_permalink($post);
	return $status;
};

function dtd_note_on_main(){
	if(is_home() && ((has_post_kind('note') || has_post_kind('bookmark') || has_post_kind('like'))|| (function_exists('register_block_type') && has_blocks()))){
			remove_action('genesis_entry_content', 'genesis_do_post_content');
			the_content();
		} 
}

function dtd_show_title_with_note($title){
	// Limit the title to exactly 3 words only on the home page and when it's a note without a title
	if (!is_single() && has_post_kind('note') && $title ==='') {
		return wp_trim_words(get_the_content(), 5, '…');
	}
	// Otherwise return the full title.
	return $title;
}

// add_filter('genesis_search_title_output', 'my_custom_search_results');
// function my_custom_search_results($content)
// {
// 	if (is_search()) {
// 		global $post;
// 		$content = '<h2 class="entry-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
// 	}
// 	return $content;
// }

