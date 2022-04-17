<?php
/**
 * Elementor Multi Map WordPress Plugin
 *
 * @package ElementorAwesomesauce
 *
 * Plugin Name: Elementor Multi Map
 * Description: Map with many pins for Elementor
 * Plugin URI:  https://github.com/unculture/elementor-multi-map
 * Version:     1.0.0
 * Author:      James Browne
 * Author URI:  https://jamesbrowne.me
 * Text Domain: elementor-multi-map
 */
define( 'ELEMENTOR_MULTI_MAP', __FILE__ );

function register_multi_map_widget( $widgets_manager ) {

  require_once( __DIR__ . '/class-multi-map.php' );

  $widgets_manager->register( new \ElementorMultiMap\Widgets\MultiMap() );

}
add_action( 'elementor/widgets/register', 'register_multi_map_widget' );


/////
/**
 * custom option and settings
 */
function elementor_multi_map_settings_init() {
  // Register a new setting for "elementor_multi_map" page.
  register_setting( 'elementor_multi_map', 'elementor_multi_map_options' );

  // Register a new section in the "elementor_multi_map" page.
  add_settings_section(
    'elementor_multi_map_section_google_api',
    __( 'Google Maps API for JS', 'elementor_multi_map' ), 'elementor_multi_map_section_google_api_callback',
    'elementor_multi_map'
  );

  // Register a new field in the "elementor_multi_map_section_google_api" section, inside the "elementor_multi_map" page.
  add_settings_field(
    'elementor_multi_map_field_api_key', // As of WP 4.6 this value is used only internally.
    // Use $args' label_for to populate the id inside the callback.
    __( 'API Key', 'elementor_multi_map' ),
    'elementor_multi_map_field_api_key_cb',
    'elementor_multi_map',
    'elementor_multi_map_section_google_api',
    array(
      'label_for'         => 'elementor_multi_map_field_api_key',
      'class'             => 'elementor_multi_map_row',
      'elementor_multi_map_custom_data' => 'custom',
    )
  );
}

/**
 * Register our elementor_multi_map_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'elementor_multi_map_settings_init' );


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function elementor_multi_map_section_google_api_callback( $args ) {
  ?>
  <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Enter the key for the Google Maps API for JS', 'elementor_multi_map' ); ?></p>
  <?php
}

/**
 * API Key field callback function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function elementor_multi_map_field_api_key_cb( $args ) {
  // Get the value of the setting we've registered with register_setting()
  $options = get_option( 'elementor_multi_map_options' );
  ?>
  <input
    type="text"
    value="<?php echo is_array($options) ? $options[ $args['label_for'] ]: ""; ?>"
    id="<?php echo esc_attr( $args['label_for'] ); ?>"
    data-custom="<?php echo esc_attr( $args['elementor_multi_map_custom_data'] ); ?>"
    name="elementor_multi_map_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
    />
  <?php
}

/**
 * Add the top level menu page.
 */
function elementor_multi_map_options_page() {
  add_submenu_page(
    'options-general.php',
    'elementor_multi_map',
    'Elementor Multi Map',
    'manage_options',
    'elementor_multi_map',
    'elementor_multi_map_options_page_html'
  );
}


/**
 * Register our elementor_multi_map_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'elementor_multi_map_options_page' );


/**
 * Top level menu callback function
 */
function elementor_multi_map_options_page_html() {
  // check user capabilities
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  // show error/update messages
  settings_errors( 'elementor_multi_map_messages' );
  ?>
  <div class="wrap">
    <h1><?php echo __( 'Elementor Multi Map', 'elementor_multi_map' ) ?></h1>

    <form action="options.php" method="post">
      <?php
      // output security fields for the registered setting "elementor_multi_map"
      settings_fields( 'elementor_multi_map' );
      // output setting sections and their fields
      // (sections are registered for "elementor_multi_map", each field is registered to a specific section)
      do_settings_sections( 'elementor_multi_map' );
      // output save settings button
      submit_button( 'Save Settings' );
      ?>
    </form>
  </div>
  <?php
}
////
