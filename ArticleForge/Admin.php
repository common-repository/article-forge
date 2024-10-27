<?php
namespace ArticleForge;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

import('ArticleForge.Admin.Settings');
import('ArticleForge.Admin.ActionMap');
import('ArticleForge.Admin.Hacks');
import('ArticleForge.Options');

final class Admin {
	private $settings;
	private $errors;
	private $error_callback;

	public function __construct() {
		// TODO: Check if user has capabilities for admin
		$this->errors = array();
		$this->settings = new Admin\Settings;
		new Admin\Hacks;
//		self::$instance->setup_fields();
		$this->setup_actions();
		$this->setup_filters();
	}

	private function setup_actions() {
		new \ArticleForge\Admin\ActionMap;
		add_action( af_namespace() . '_admin_init',              array( $this, 'initialize'               ) );
		add_action( af_namespace() . '_admin_enqueue_scripts',   array( $this, 'enqueue_scripts'          ), 0, 1 );
		add_action( af_namespace() . '_add_meta_boxes',          array( $this, 'add_meta_boxes'           ) );
		add_action( af_namespace() . '_new_content',             array( $this, 'new_content'              ) );
		add_action( af_namespace() . '_updated_article',         array( $this, 'updated_article'          ) );
		add_action( af_namespace() . '_updated_content',         array( $this, 'updated_content'          ) );
		add_action( af_namespace() . '_trashed_article',         array( $this, 'trashed_article'          ) );
		add_action( af_namespace() . '_trashed_content',         array( $this, 'trashed_content'          ) );
		add_action( af_namespace() . '_admin_notices',           array( $this, 'display_errors'           ) );
	}

	private function setup_filters() {
		add_filter('redirect_post_location', array( $this, 'track_errors') );
	}

	public function track_errors($loc) {
		if (count($this->errors)) {
			$loc = remove_query_arg('message', $loc);
			set_transient(af_namespace() . '_admin_errors-' . get_current_user_id(), $this->errors, 30); // or add_query_arg(nonce);
		}
		return $loc;
	}

	public function display_errors() {
		$trans_id = af_namespace() . '_admin_errors-' . get_current_user_id();
		$af_errors = get_transient($trans_id);
		if ($af_errors) {
			delete_transient($trans_id);
			$output = '';
			foreach ( $af_errors as $key => $details ) {
				$css_id = $details['source'];
				$css_class = $details['type'] . ' settings-error';
				$output .= "<div id='$css_id' class='$css_class'> \n";
				$output .= "<p><strong>{$details['message']}</strong></p>";
				$output .= "</div> \n";
			}
			echo $output;
		}
	}

	public function initialize() {
	}

	public function enqueue_scripts( $hook ) {
		global $post;

		if (($hook == 'edit_page') || ($hook == 'new_page')) {
			if ( $post->post_type == af_namespace() . '_' . Constants::SummaryPostType ) {
				wp_enqueue_script( af_namespace() . '_admin_edit_summary', plugin_dir_url( __FILE__ ) . 'Admin/js/admin-edit-summary.js' );
			} else if ( $post->post_type == af_namespace() . '_' . Constants::ContentPostType ) {
				wp_enqueue_style( af_namespace() . '_admin_edit_content', plugin_dir_url( __FILE__ ) . 'Admin/css/admin-edit-content.css' );
				wp_enqueue_script( af_namespace() . '_admin_edit_content', plugin_dir_url( __FILE__ ) . 'Admin/js/admin-edit-content.js' );
			}
		} else if ($hook == 'all_page') {
			if ( $post->post_type == af_namespace() . '_' . Constants::ContentPostType ) {
				wp_enqueue_style( af_namespace() . '_admin_edit_content', plugin_dir_url( __FILE__ ) . 'Admin/css/admin-edit-content.css' );
			}
		}

		wp_localize_script( af_namespace() . '_admin', af_namespace(), array(
				'nonce' => wp_create_nonce( af_namespace() . '_admin' )
			)
		);
	}

	public function add_meta_boxes() {
		add_meta_box( af_namespace() . '_content-list', 'Contents',
			array( $this, 'build_content_list_meta_box' ), af_namespace() . '_' . Constants::SummaryPostType, 'side', 'high' );
		add_meta_box( af_namespace() . '_article-options', 'Options',
			array( $this, 'build_article_options_meta_box' ), af_namespace() . '_' . Constants::SummaryPostType, 'side', 'high' );
		add_meta_box( af_namespace() . '_parent-article', 'Article',
			array( $this, 'build_parent_article_meta_box' ), af_namespace() . '_' . Constants::ContentPostType, 'side', 'high' );
		add_meta_box( af_namespace() . '_content-order', 'Position',
			array( $this, 'build_content_order_meta_box' ), af_namespace() . '_' . Constants::ContentPostType, 'side', 'high' );
	}

	public function build_article_options_meta_box( $post, $box ) {
//  always_show_summary
		$always_show_toc = get_post_meta( $post->ID, af_namespace() . '_always_show_toc', 'false' );
		$show_all_sections = get_post_meta( $post->ID, af_namespace() . '_show_all_sections', 'false' );

		wp_nonce_field( 'meta-box-save', '_' . af_namespace() . '_nonce' );
?>
<table>
	<tr>
		<td><input type="checkbox" name="always_show_toc" value="true" <?php echo $always_show_toc == 'true' ? 'checked' : '' ?> style="margin: 5px; vertical-align: middle; position: relative;">Display TOC on each section</td>
	</tr>
	<tr>
		<td><input type="checkbox" name="show_all_sections" value="true" <?php echo $show_all_sections == 'true' ? 'checked' : '' ?> style="margin: 5px; vertical-align: middle; position: relative;">Display all sections on one page</td>
	</tr>
</table>
<?php
	}

	private function sanitize_bool_field($field = '') {
		return $field == 'true' ? 'true' : 'false';
	}

	public function updated_article( $post_id ) {
			$this->save_article_options( $post_id );
	}

	private function save_article_options( $post_id ) {

		// skip if autosaving post
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// check nonce for security
		check_admin_referer( 'meta-box-save', '_' . af_namespace() . '_nonce' );

		update_post_meta( $post_id, af_namespace() . '_always_show_toc', isset($_POST['always_show_toc']) ? 'true' : 'false' );
		update_post_meta( $post_id, af_namespace() . '_show_all_sections', isset($_POST['show_all_sections']) ? 'true' : 'false' );

		if ( isset( $_POST['content_order'] ) && $_POST['content_order'] ) {
			global $wpdb;
			$content_ids = explode(',', $_POST['content_order'] );
			foreach ($content_ids as $index => $id) {
				if (!$wpdb->update( $wpdb->posts, array( 'menu_order' => $index + 1), array( 'ID' => $id))) {
					if ($wpdb->last_error) {
						array_push($this->errors, $wpdb->last_error);
						break;
					}
				}
			}
		}
	}

	public function updated_content( $post_id ) {
		// skip if autosaving post
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// check nonce for security
		check_admin_referer( 'meta-box-save', '_' . af_namespace() . '_nonce' );

		if ( isset( $_POST['new_menu_order'] ) && $_POST['new_menu_order'] ) {
			global $wpdb;
			$post = get_post( $post_id );
			$old_menu_order = $post->menu_order;
			$new_menu_order = $_POST['new_menu_order'];
			try {
				if ($new_menu_order > $old_menu_order) {
					$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET menu_order = menu_order - 1 WHERE " .
					"menu_order > %d AND menu_order <= %d AND post_parent = %d " .
					"AND post_status IN ('publish', 'draft')", $old_menu_order, $new_menu_order, $post->post_parent));
					if ($wpdb->last_error)
						throw new \Exception('Could not update section order: ' . $wpdb->last_error);
					$wpdb->update($wpdb->posts, array( 'menu_order' => $new_menu_order), array('ID' => $post_id));
					if ($wpdb->last_error)
						throw new \Exception('Could not update section order: ' . $wpdb->last_error);
				} else if ($new_menu_order < $old_menu_order) {
					$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET menu_order = menu_order + 1 WHERE " .
					"menu_order >= %d AND menu_order < %d AND post_parent = %d " .
					"AND post_status IN ('publish', 'draft')", $new_menu_order, $old_menu_order, $post->post_parent));
					if ($wpdb->last_error)
						throw new \Exception('Could not update section order: ' . $wpdb->last_error);
					$wpdb->update($wpdb->posts, array( 'menu_order' => $new_menu_order), array('ID' => $post_id));
					if ($wpdb->last_error)
						throw new \Exception('Could not update section order: ' . $wpdb->last_error);
				}
			} catch (\Exception $e) {
				array_push( $this->errors,
							array( 'source' => af_namespace() . '_content-order',
								   'post_type' => af_namespace() . '_' . Constants::ContentPostType,
								   'message' => $e->getMessage(),
								   'type'   => 'error'
							)
						);
			}
		}
	}

	public function build_parent_article_meta_box( $post, $box ) {
		$post_parent = $post->post_parent == 0 ? (array_key_exists('post_parent', $_GET) ? $_GET['post_parent'] : 0 ) : $post->post_parent;
		$article = get_post($post_parent);
?>
<input type="hidden" id="parent_id" name="parent_id" value="<?php echo $post_parent; ?>"/>
<h2><?php echo $article->post_title; ?></h2>
<table>
	<tr>
		<td><div class="pub-section"><a class="preview button" href="post-new.php?post_type=articleforge_content&post_parent=<?php echo $post_parent ?>">Add New Section</a></div></td>
		<td><div class="pub-section"><a class="preview button" href="post.php?post=<?php echo $post_parent ?>&action=edit">Return to Summary</a></div></td>
	</tr>
</table>
<?php
	}

	public function build_content_order_meta_box( $post, $box ) {
		global $wpdb;
		$post_parent = $post->post_parent == 0 ? (array_key_exists('post_parent', $_GET) ? $_GET['post_parent'] : 0 ) : $post->post_parent;
		$menu_order = $post->menu_order;
		if (($menu_order == 0) && ($post_parent != 0)) {
			$menu_order = $wpdb->get_var($wpdb->prepare("SELECT IFNULL(MAX(menu_order), 0) + 1 AS menu_order FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", $post_parent, af_namespace() . '_' . Constants::ContentPostType));
		}
		$contents = new \WP_Query(
			array(
				'post_parent' => $post_parent,
				'post_type'   => af_namespace() . '_' . Constants::ContentPostType,
				'post_status' => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC'
			)
		);
?>
<style>
#content-order-sortable .content-order-placeholder {
	border: 1px dashed rgba(0, 0, 0, 1.0);
}
#content-order-sortable li {
	border: 1px solid rgba(0, 0, 0, 0.0);
	margin: 0px 0px 0px 0px;
}
#content-order-sortable li:hover {
	background: rgba(0, 0, 0, 0.05);
}
#content-order-sortable h2 {
	margin: 0;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
#content-order-sortable li.not-sortable h2 { opacity: 0.25;}
</style>
	<input type="hidden" id="new_menu_order" name="new_menu_order" value="<?php echo $menu_order; ?>"/>
	<div class="<?php echo af_namespace() ?>"><ul id="content-order-sortable">
<?php
	while ( $contents->have_posts() ) {
		$contents->next_post(); $cpost = $contents->post;
?>
		<li id='<?php echo 'content-' . $cpost->ID ?>'
			<?php if ($cpost->ID != $post->ID) { echo " class='not-sortable'"; }?> 
		>
			<h2><?php echo $cpost->post_title; ?></h2>
		</li>
<?php
	}
	// check if this is a new section
	if (($post_parent != 0) && ($post->menu_order == 0)) {
?>
		<li id='content-<?php echo $post->ID ?>'><h2>&nbsp;</h2></li>
<?php
	}
?>
	</ul></div>
<?php
	wp_nonce_field( 'meta-box-save', '_' . af_namespace() . '_nonce' );
	}

	public function build_content_list_meta_box( $post, $box ) {
		$contents = new \WP_Query(
			array(
				'post_parent' => $post->ID,
				'post_type'   => af_namespace() . '_' . Constants::ContentPostType,
				'post_status' => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC'
			)
		);
?>
<style>
#content-list-sortable { list-style-type: none; margin: 0; padding: 0; }
#content-list-sortable .articleforge li {
	margin: 0;
	height: 50px;
	border: 1px solid rgba(255, 0, 0, 0);
}
#content-list-sortable li:hover {
	background: rgba(0, 0, 0, 0.05);
}
#content-list-sortable .content-list-placeholder {
	height: 48px;
	border: 1px dashed rgba(0, 0, 0, 1.0); 
}
#content-list-sortable li.articleforge h2 {
	height: 30px;
	line-height: 30px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	padding: 0;
	margin: 0;
}
#content-list-sortable li.articleforge div.controls {
	height: 20px;
	margin-left: 15px;
}


</style>
		<input type="hidden" id="content_order" name="content_order" value="" />
		<div class="<?php echo af_namespace() ?>"><ul id="content-list-sortable">
<?php
	while ( $contents->have_posts() ) {
		$contents->next_post(); $cpost = $contents->post;
		$curl = html_entity_decode($cpost->guid); $view_text = 'View';
		if ($cpost->post_status == 'draft') {
			$curl = add_query_arg(array( 'preview' => 'true' ), $curl);
			$view_text = 'Preview';
		}
?>
		<li id='<?php echo 'content-' . $cpost->ID ?>' class='articleforge'>
			<h2><?php echo $cpost->post_title ?></h2>
			<div class="controls">
				<a title="<?php echo $view_text ?> this section" href="<?php echo $curl ?>" target="_blank"><?php echo $view_text ?></a>
				| <a title="Edit this section" href="post.php?post=<?php echo $cpost->ID ?>&action=edit">Edit</a>
				| Status: <span id="post-status-display"><?php echo $cpost->post_status ?></span>
			</div>
		</li>
<?php
	}
?>
		</ul></div>
	<table>
		<tr>
			<td><div class="pub-section"><a class="preview button" href="post-new.php?post_type=articleforge_content&post_parent=<?php echo $post->ID ?>">Add New Section</a></div></td>
		</tr>
	</table>
<?php
	}


	public function new_content() {
		if (!array_key_exists('post_parent', $_GET)) {
			wp_die('Create new content sections from the article edit page please.');
		}
		$post_parent = $_GET['post_parent'];
		$article = get_post($post_parent);
		if (!$article || ($article->post_type != af_namespace() . '_' . Constants::SummaryPostType) || 
			!in_array($article->post_status, array('draft', 'publish')) || 
			(!current_user_can('edit_post', $post_parent))
			) {
				wp_die('The parent article is not available for new sections.');
		}
	}

/*****
 **
 ** Functions to perform clean-up after post's are moved to the trash
 **
 **
 */

	public function trashed_article( $article_id ) {
		// trash contents
		// Validate article ID
		$article = get_post( $article_id );
		if ( empty( $article ) && ($article->post_status != 'trash'))
			return;

		if ( $contents = new \WP_Query( array(
			'suppress_filters' => true,
			'post_type'        => af_namespace() . '_' . Constants::ContentPostType,
			'post_parent'      => $article_id,
			'post_status'      => array( 'publish', 'closed', 'pending', 'draft' ), // we want everything except inherit, and trash and auto-draft(?)
			'posts_per_page'   => -1,
			'nopaging'         => true,
			'fields'           => 'ids'
		) ) ) {

			while ( $contents->have_posts() ) {
				$contents->next_post();
				wp_trash_post( $contents->post );
			}
		}

		// trash comments

		// Reset the $post global
		wp_reset_postdata();
	}

	public function trashed_content( $section_id ) {
		global $wpdb;

		// update menu_order only if parent isn't trashed, otherwise a waste of effort
		$section = get_post( $section_id );
		if ( empty( $section ) || ($section->post_status != 'trash')) return;

		wp_update_post( array( 'ID' => $section_id, 'menu_order' => 0 ) );

		$article = get_post( $section->post_parent );
		if ( empty( $article) || ($article->post_status == 'trash')) return;
		// update wpposts set menu_order = menu_order - 1 where menu_order > xxx and post_parent = xxx;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->posts
				 SET menu_order = menu_order - 1
				 WHERE post_parent = %d
				 	AND menu_order > %d",
				 $article->ID,
				 $section->menu_order
			)
		);

	}

	public function trashed_comment( $comment_id ) {
	}
}