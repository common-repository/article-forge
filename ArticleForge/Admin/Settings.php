<?php
namespace ArticleForge\Admin;

import('ArticleForge.Options');

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

interface Constraint {
	public function validate($value);
}

class ValidationException extends \Exception {

}

class NotEmpty implements Constraint {
	public function validate($value) {
		if (is_null($value)) throw new ValidationException("Value is not defined");
		$value = trim($value);
		if ($value == false) throw new ValidationException("Value cannot be empty");
		return $value;
	}
}
class NumericInteger implements Constraint {
	public function validate($value) {
		if (is_null($value) || 
			(($value = trim($value)) == false) || 
			!(is_numeric($value) && (intval($value) == floatval($value)))
		) throw new ValidationException("Value must be an Integer");
		return $value;
	}
}
class GreaterThanEqual implements Constraint {
	private $value;
	public function __construct($value) {
		$temp = new NumericInteger;
		$this->value = $temp->validate($value);
	}
	public function validate($value) {
		$temp = new NumericInteger;
		$value = $temp->validate($value);
		if ($value < $this->value) throw new ValidationException("Value must be greater than equal to " . $this->value);
		return $value;
	}
}
class WPValidTitle implements Constraint {
	public function validate($value) {
		$value = trim($value);
		if ($value != sanitize_title($value)) throw new ValidationException("Value has characters not allowed in WordPress");
		return $value;
	}
}

final class Settings {

/** Sections ******************************************************************/
	private $options;
	private $sections;
	private $errors;

	public function __construct() {
		// $this->options = \ArticleForge\Options::load();
		$this->sections = array(
			array(
				'name'     => 'main',
				'title'    => __( 'Main Settings', 'articleforge' ),
				'callback' => array( $this, 'main_section' ),
				'page'     => af_namespace(),
				'fields'   => array(
/*					array(
						'name' => 'list_slug',
						'title' => __( 'Articles slug', 'articleforge' ),
						'callback' => array( $this, 'list_slug_field' ),
						'sanitize_callback' => 'sanitize_title',
						'args' => array()
					), // field
*/
				) // fields
			), // main section
			array(
				'name'     => 'theme',
				'title'    => __( 'Theme Settings', 'articleforge' ),
				'callback' => array( $this, 'theme_section' ),
				'page'     => af_namespace(),
				'fields'   => array(
					array(
						'name' => 'content_dir',
						'title' => __( 'Content directory', 'articleforge' ),
						'callback' => array( $this, 'content_dir_field' ),
						'sanitize_callback' => array( new NotEmpty(), new WPValidTitle() ), // ValidDirectory($path)
						'args' => array()
					), // field
					array(
						'name' => 'summaries_per_page',
						'title' => __( 'Summaries per list page', 'articleforge' ),
						'callback' => array( $this, 'summaries_per_page_field' ),
						'sanitize_callback' => array( new NotEmpty(), new NumericInteger(), new GreaterThanEqual(1) ),
						'args' => array()
					),
					array(
						'name' => 'enable_revisions',
						'title' => __( 'Enable revisions', 'articleforge' ),
						'callback' => array( $this, 'enable_revisions_field' ),
						'sanitize_callback' => array( ),
						'args' => array()
					),
					array(
						'name' => 'show_on_home',
						'title' => __( 'Home page', 'articleforge' ),
						'callback' => array( $this, 'show_on_home_field' ),
						'sanitize_callback' => array( ),
						'args' => array()
					),
				) // fields
			), // theme section
			array(
				'name'     => 'slug',
				'title'    => __( 'Slugs', 'articleforge' ),
				'callback' => array( $this, 'slug_section' ),
				'page'     => af_namespace(),
				'fields'   => array(
					array(
						'name'  => 'list_slug',
						'title' => __( 'Articles slug', 'articleforge' ),
						'callback' => array( $this, 'list_slug_field' ),
						'sanitize_callback' => array( new NotEmpty(), new WPValidTitle() ),
						'args' => array()
					), // field
					array(
						'name'  => 'prefix_list_slug',
						'title' => __( 'Articles Path Prefix', 'articleforge' ),
						'callback' => array( $this, 'prefix_list_slug_field' ),
						'sanitize_callback' => array( ),
						'args' => array()
					), // field
					array(
						'name'  => 'single_slug',
						'title' => __( 'Article slug', 'articleforge' ),
						'callback' => array( $this, 'single_slug_field' ),
						'sanitize_callback' => array( new NotEmpty(), new WPValidTitle() ),
						'args' => array()
					), // field
					array(
						'name'  => 'section_slug',
						'title' => __( 'Section slug', 'articleforge' ),
						'callback' => array( $this, 'section_slug_field' ),
						'sanitize_callback' => array( new NotEmpty(), new WPValidTitle() ),
						'args' => array()
					), // field
					array(
						'name'  => 'arthur_slug',
						'title' => __( 'Arthur slug', 'articleforge' ),
						'callback' => array( $this, 'arthur_slug_field' ),
						'santize_callback' => array( new NotEmpty(), new WPValidTitle() ),
						'args' => array()
					)
				) // fields
			), // slug section
		); // sections
		$this->errors = array( );
		$this->setup_actions();
	}

	private function setup_actions() {
		add_action( af_namespace() . '_init',                    array( $this, 'initialize'              ), 2 );
		add_action( af_namespace() . '_admin_menu',              array( $this, 'add_menu'                ) ); // Add menu item to settings menu
		add_action( af_namespace() . '_admin_init',              array( $this, 'register'                ) );
		add_action( 'admin_head',                                array( $this, 'head'                    ) );

//		add_action( af_namespace() . '_register_admin_settings', array( $this, 'register_admin_settings'    ) ); // Add settings
		add_action( af_namespace() . '_admin_enqueue_scripts',   array( $this, 'enqueue_scripts'          ) );
		add_action( 'wp_ajax_' . af_namespace() . '_settings_defaults', array( $this, 'settings_defaults' ) );
	}

	public function initialize() {
		$this->options = articleforge()->options; //\ArticleForge\Options::load();
	}

	public function head() {
		global $wp_settings_errors;
		$screen = get_current_screen();
		if ( $screen->id != 'settings_page_' . af_namespace() ) return;
		$this->errors = array_reduce(get_settings_errors(), function($result, $item) { 
				if ($item['type'] == 'error') { 
					array_push($result, $item); 
				}
				return $result; 
			}, array() );
		if (count($this->errors)) {
			$wp_settings_errors = array( );
			add_settings_error('ArticleForge Settings', 'articleforge_options', "There are errors in your submission; see below for details.");
		}
	}

	private function has_error( $name ) {
		foreach( $this->errors as $error ) {
			if ( $error['code'] == $name ) return true;
		}
		return false;
	}

	private function get_error( $name ) {
		foreach( $this->errors as $error ) {
			if ( $error['code'] == $name) return $error;
		}
		return null;
	}

	private function get_error_msg( $name ) {
		$error = get_error( $name );
		if ( $error ) return $error['message']['msg'];
		return null;
	}

	private function get_error_value( $name ) {
		$error = get_error( $name );
		if ( $error ) return $error['message']['value'];
		return null;
	}

	public function enqueue_scripts( $hook ) {
		if ($hook == 'settings_page_' . af_namespace()) {
			wp_enqueue_style( af_namespace() . '_admin_settings', plugin_dir_url( __FILE__ ) . 'css/admin-settings.css' );
			wp_enqueue_script( af_namespace() . '_admin_settings', plugin_dir_url( __FILE__ ) . 'js/admin-settings.js' );
		}
		wp_localize_script( af_namespace() . '_admin', af_namespace(), array(
				'nonce' => wp_create_nonce( af_namespace() . '_admin_settings' )
			)
		);
	}

	public function add_menu() {
		add_options_page(
			__( 'Article Forge',  'articleforge' ),
			__( 'Article Forge',  'articleforge' ),
			'manage_options',
			af_namespace(),
			array( $this, 'page' )
		);// or $settings, displayPage array($this->settings, 'page')
	}

	public function register() {

		// Bail if no sections available
		$sections = $this->sections;
		if ( empty( $sections ) )
			return false;

		// Loop through sections
		foreach ( (array) $sections as $section ) {

/*			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) )
				continue;*/

			// Only add section and fields if section has fields
			$fields = $section['fields'];
			if ( empty( $fields ) )
				continue;

			// Add the section
			$section_id = af_namespace() . '_settings_' . $section['name'] . '_section';
			add_settings_section( $section_id, $section['title'], $section['callback'], $section['page'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field ) {

				// Add the field
				$field_id = '_' . af_namespace() . '_' .$field['name'];
				add_settings_field( $field_id, $field['title'], $field['callback'], $section['page'], $section_id, $field['args'] );

				// Register the setting
				//register_setting( $section['page'], $field_id, $field['sanitize_callback'] );
			}
		}

		// Finally register the group option setting
		register_setting( af_namespace(), af_namespace() . '_options', array( $this, 'sanitize_options' ) );
	}

	private function validate_option( $option, $field ) {
		foreach ( (array) $field['sanitize_callback'] as $constraint) {
			$option = $constraint->validate($option);
		}
		return $option;
	}

	public function sanitize_options( $options ) {

		$options['prefix_list_slug'] = $options['prefix_list_slug'] == 'true' ? 'true' : 'false';
		$options['enable_revisions'] = $options['enable_revisions'] == 'true' ? 'true' : 'false';
		$options['show_on_home'] = $options['show_on_home'] == 'true' ? 'true' : 'false';

		// report errors
		$errors = array();
		foreach ( (array) $this->sections as $section ) {
			foreach ( (array) $section['fields'] as $field ) {
				try {
					$options[$field['name']] = $this->validate_option($options[$field['name']], $field);
				} catch (ValidationException $ve) {
					$error_msg = str_replace("Value", $field['title'], $ve->getMessage());
					$errors[$field['name']] = $error_msg;
					add_settings_error($field['title'], $field['name'], array( 'msg' => $error_msg, 'value' => $options[$field['name']]));
				}
			}
		}
		if (count($errors)) {
			return array_reduce($this->options->names(), function($result, $item) { $result[$item] = $this->options->$item; return $result; }, array());
		}

		// compare slug options with current slug options
		$slug_section; // for now, do it this way
		foreach ( (array) $this->sections as $section ) {
			if ($section['name'] == 'slug') {
				$slug_section = $section;
				break;
			}
		}
		foreach ( (array) $slug_section['fields'] as $field) {
			if ($options[$field['name']] != $this->options->$field['name']) {
				delete_option(af_namespace() . '_rewrite_rules');
				break;
			}
		}
		// $this->options = options; // implement this for PHP service
		// $articleforge()->options = $options; or $articleforge()->reloadOptions();
		return $options;
	}

	// ajax_callback_for settings defaults
	public function settings_defaults() {
		if ( current_user_can( 'manage_options' ) && check_ajax_referer(af_namespace() . '_admin_settings', 'nonce')  ) {
			$options = \ArticleForge\Options::defaults();
			$defaults = array();
			foreach ($options->names() as $name) {
				$defaults[$name] = $options->$name;
			}
			wp_send_json(array(
				id => 1,
				data => $defaults,
				action => 'options_defaults'
			));
		}
	}

public function main_section() {
?>

	<p><?php _e( 'Main forum settings for enabling features and setting time limits', 'articleforge' ); ?></p>

<?php
}

public function theme_section() {
?>

	<p><?php _e( 'Location of template files used to render and display articles', 'articleforge' ); ?></p>

<?php
}

public function content_dir_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[content_dir]" type="text" id="_<?php echo af_namespace(); ?>_content_dir" class="regular-text code" value="<?php esc_attr_e($this->options->content_dir); ?>" />

<?php
}

public function summaries_per_page_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[summaries_per_page]" type="text" id="_<?php echo af_namespace(); ?>_summaries_per_page" class="regular-text code" value="<?php esc_attr_e($this->has_error('summaries_per_page') ? $this->get_error_value('summaries_per_page') : $this->options->summaries_per_page); ?>" />
	<?php if ( $this->has_error('summaries_per_page') ) { ?><span class="articleforge-settings-error"><?php echo $this->get_error_msg('summaries_per_page') ?></span><?php } ?>

<?php
}

public function enable_revisions_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[enable_revisions]" type="checkbox" id="_<?php echo af_namespace(); ?>_enable_revisions" value="true" <?php echo $this->options->enable_revisions == 'true' ? 'checked="checked"' : '' ?>/><label for="_<?php echo af_namespace(); ?>_enable_revisions"> Enable revisions for Article Summaries and Content</label>

<?php
}

public function show_on_home_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[show_on_home]" type="checkbox" id="_<?php echo af_namespace(); ?>_show_on_home" value="true" <?php echo $this->options->show_on_home == 'true' ? 'checked="checked"' : '' ?>/><label for="_<?php echo af_namespace(); ?>_show_on_home"> Display Articles on home page</label>

<?php
}

public function slug_section() {
?>

	<p><?php _e( 'URL path slugs to designate article lists and single posts, article sections, and article comments', 'articleforge' ); ?></p>

<?php
}

public function list_slug_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[list_slug]" type="text" id="_<?php echo af_namespace(); ?>_list_slug" class="regular-text code" value="<?php esc_attr_e($this->has_error('list_slug') ? $this->get_error_value('list_slug') : $this->options->list_slug); ?>" />
	<?php if ( $this->has_error('list_slug') ) { ?><span class="articleforge-settings-error"><?php echo $this->get_error_msg('list_slug') ?></span><?php } ?>

<?php
}

public function prefix_list_slug_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[prefix_list_slug]" type="checkbox" id="_<?php echo af_namespace(); ?>_prefix_list_slug" value="true" <?php echo $this->options->prefix_list_slug == 'true' ? 'checked="checked"' : '' ?>/><label for="_<?php echo af_namespace(); ?>_prefix_list_slug"> Use List Slug as path prefix for all Article Forge content</label>

<?php
}

public function single_slug_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[single_slug]" type="text" id="_<?php echo af_namespace(); ?>_single_slug" class="regular-text code" value="<?php esc_attr_e($this->has_error('single_slug') ? $this->get_error_value('single_slug') : $this->options->single_slug); ?>" />
	<?php if ( $this->has_error('single_slug') ) { ?><span class="articleforge-settings-error"><?php echo $this->get_error_msg('single_slug') ?></span><?php } ?>

<?php
}

public function section_slug_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[section_slug]" type="text" id="_<?php echo af_namespace(); ?>_section_slug" class="regular-text code" value="<?php esc_attr_e($this->has_error('section_slug') ? $this->get_error_value('section_slug') : $this->options->section_slug); ?>" />
	<?php if ( $this->has_error('section_slug') ) { ?><span class="articleforge-settings-error"><?php echo $this->get_error_msg('section_slug') ?></span><?php } ?>

<?php
}

public function arthur_slug_field() {
?>

	<input name="<?php echo af_namespace(); ?>_options[arthur_slug]" type="text" id="_<?php echo af_namespace(); ?>_arthur_slug" class="regular-text code" value="<?php esc_attr_e($this->has_error('arthur_slug') ? $this->get_error_value('arthur_slug') : $this->options->arthur_slug); ?>" />
	<?php if ( $this->has_error('arthur_slug') ) { ?><span class="articleforge-settings-error"><?php echo $this->get_error_msg('arthur_slug') ?></span><?php } ?>

<?php
}



	public function page() {
?>

	<div class="wrap">

		<?php screen_icon(); ?>

		<h2><?php _e( 'Article Forge Settings', 'articleforge' ) ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( 'articleforge' ); ?>

			<?php do_settings_sections( 'articleforge' ); ?>

				<table class="submit" width="600px"><tr>
					<td width="25%"><input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save', 'articleforge' ); ?>" /></td>
					<td width="25%"><input type="button" name="cancel" class="button-primary" onClick="window.location='<?php echo admin_url(); ?>'; return false;" value="<?php esc_attr_e( 'Cancel', 'articleforge' ); ?>" /></td>
					<td width="25%">&nbsp;</td>
					<td width="25%"><input type="button" name="defaults" class="button-primary" onClick="setDefaults(); return false;" value="<?php esc_attr_e( 'Defaults', 'articleforge' ); ?>" /></td>
				</tr></table>
		</form>
	</div>

<?php
	}

}
