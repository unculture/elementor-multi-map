<?php
/**
 * Elementor_Multi_Map class.
 *
 * @category   Class
 * @package    ElementorMultiMap
 * @subpackage WordPress
 * @author     James Browne <jb@jamesbrowne.me>
 * @copyright  2022 James Browne
 */
namespace ElementorMultiMap\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;


defined( 'ABSPATH' ) || die();

class MultiMap extends Widget_Base {

  private static $javascriptRegistered = false;

  public function __construct($data = [], $args = null) {
    parent::__construct($data, $args);
    $this->enqueueScripts();
  }

  public function get_script_depends() {
    return ['elementor_multi_map_init'];
  }

  public function enqueueScripts()
  {
    // Only do this once!
    if (static::$javascriptRegistered) {
      return;
    }
    static::$javascriptRegistered = true;
    wp_register_script('elementor_multi_map_init', plugins_url('elementor-multi-map/js/elementor-multi-map.js'), [], false, false);

    $options = get_option('elementor_multi_map_options');
    $apiKey = '';
    if ($options['elementor_multi_map_field_api_key'] && is_string($options['elementor_multi_map_field_api_key']) ) {
      $apiKey = urlencode($options['elementor_multi_map_field_api_key']);
    }
    $includeGoogleMapsApiScript = <<<EOT
  if (window.google && google.maps && google.maps.Map) {
    // No op - Google Maps already loaded
  } else {
    // Load Google Maps API v3 with the correct API Key
    var googleMapsScript = document.createElement('script')
    googleMapsScript.onload = function() {
      console.log("init map with script")
    }
    googleMapsScript.src = "https://maps.googleapis.com/maps/api/js?key=" + "$apiKey"
    document.head.append(googleMapsScript)
  }
EOT;
    wp_add_inline_script('elementor_multi_map_init', $includeGoogleMapsApiScript, 'before');
  }


  public function get_name() {
    return 'multi-map';
  }

  public function get_title() {
    return __( 'Multi Map', 'elementor-multi-map' );
  }

  public function get_icon() {
    return 'eicon-map-pin';
  }

  public function get_categories() {
    return array( 'general' );
  }

  protected function _register_controls() {
    $this->start_controls_section(
      'section_settings',
      array(
        'label' => __( 'Settings', 'elementor-multi-map' ),
      )
    );
    $this->add_control(
      'aspect_ratio',
      array(
        'label'   => __( 'Aspect Ratio (eg. "16:9" or "3:2")', 'elementor-multi-map' ),
        'type'    => Controls_Manager::TEXT,
        'default' => __( "16:9", 'elementor-multi-map' ),
      )
    );

    $this->end_controls_section();

    $this->start_controls_section(
      'section_pins',
      array(
        'label' => __( 'Pins', 'elementor-multi-map' ),
      )
    );

    $repeater = new \Elementor\Repeater();

    $repeater->add_control(
      'pins_image', [
        'label' => __( 'Image', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::MEDIA,
        'default' => [
          'url' => \Elementor\Utils::get_placeholder_image_src(),
        ],
        'show_label' => true,
      ]
    );
    $repeater->add_control(
      'pins_name', [
        'label' => esc_html__( 'Location Name', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => esc_html__( '' , 'elementor-multi-map' ),
        'label_block' => true,
      ]
    );

    $repeater->add_control(
      'pins_address', [
        'label' => esc_html__( 'Location Address', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::TEXT,
        'default' => esc_html__( '' , 'elementor-multi-map' ),
        'label_block' => true,
      ]
    );

    $repeater->add_control(
      'pins_url', [
        'label' => esc_html__( 'Location URL', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::URL,
        'label_block' => true,
      ]
    );

    $repeater->add_control(
      'pins_lat', [
        'label' => esc_html__( 'Latitude', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'default' => esc_html__( 0 , 'elementor-multi-map' ),
        'label_block' => true,
      ]
    );

    $repeater->add_control(
      'pins_lng', [
        'label' => esc_html__( 'Longitude', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'default' => esc_html__( 0 , 'elementor-multi-map' ),
        'label_block' => true,
      ]
    );

    $this->add_control(
      'pins',
      [
        'label' => esc_html__( 'Pins', 'elementor-multi-map' ),
        'type' => \Elementor\Controls_Manager::REPEATER,
        'fields' => $repeater->get_controls(),
        'prevent_empty' => false,
        'defaults' => [],
        'title_field' => '{{{ pins_name }}}',
      ]
    );

    $this->end_controls_section();
  }

  private function generateJsonForMap($settings) {
    // Check map has pins
    $pins = $settings['pins'];

    // If pins is not an array for some reason, set it to an empty one
    if (!is_array($pins)) {
      $pins = [];
    }

    // Check each pin has at least lat and lng
    // Set to zero if one found
    for ($x = 0; $x < count($pins); $x++) {
      if (!is_numeric($pins[$x]['pins_lat'])) {
        $pins[$x]['pins_lat'] = 0;
      }

      if (!is_numeric($pins[$x]['pins_lng'])) {
        $pins[$x]['pins_lng'] = 0;
      }
    }

    // Transform pins
    $pinsToReturn = array_map(function($pin) {
      // Resolve medium image
      $imageId = null;
      if (is_array($pin['pins_image']) && !empty($pin['pins_image']['id'])) {
        $imageId = $pin['pins_image']['id'];
      }

      $image = null;
      if (is_integer($imageId)) {
        $imageAttributes = wp_get_attachment_image_src( $imageId, 'medium' );
        if (is_array($imageAttributes) && !empty($imageAttributes[0])) {
          $image = $imageAttributes[0];
        }
      }

      // Resolve URL
      $url = null;
      if (is_array($pin['pins_url']) && !empty($pin['pins_url']['url']) && is_string(($pin['pins_url']['url']))) {
        $url = $pin['pins_url']['url'];
      }

      return [
        'url' => $url,
        'image' => $image,
        'name' => $pin['pins_name'],
        'address' => $pin['pins_address'],
        'html' => $this->generatePopoverHTML($pin['pins_name'], $pin['pins_address'],$image, $url),
        'lat' => $pin['pins_lat'],
        'lng' => $pin['pins_lng'],
      ];
    }, $pins);

    $returnObject = new \stdClass;
    $returnObject->instanceId = $this->get_id();
    $returnObject->pins = $pinsToReturn;

    return json_encode($returnObject,  JSON_THROW_ON_ERROR);
  }

  private function generatePopoverHTML($name, $address, $image, $url) {
    $urlEscaped = esc_url($url);
    $imageEscaped = esc_url($image);
    $nameAttributeEscaped = esc_attr($name);
    $nameHtmlEscaped = esc_html($name);
    $addressHtmlEscaped = esc_html($address);

    $html = <<<EOT
  <style>
    .no-decoration-on-hover:hover {
      text-decoration:none !important;
    }
  </style>
  <div style='width:250px;'>
 EOT;
    if (!empty($urlEscaped)) {
      $html .= "<a class=\"no-decoration-on-hover\"  href=\"$urlEscaped\">";
    }

    if (!empty($imageEscaped)) {
      $html .= <<<EOT
    <div style="position:relative;height:0;overflow:hidden;padding-top:calc(200 / 300 * 100%);">
      <img style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;object-position: center;" src="$imageEscaped" alt="$nameAttributeEscaped" />
    </div>
EOT;
    }

    $html .= <<<EOT
    <h1 style="font-family:'Outfit', sans-serif;font-size: 22px; font-weight: 600; color: rgb(0, 34, 51)">$nameHtmlEscaped</h1>                                                                                    
    <h2 class="no-decoration-on-hover" style="font-family:'Outfit', sans-serif;font-size: 16px; font-weight: 400; color: rgb(0, 34, 51)">$addressHtmlEscaped</h2>                                                                                     
EOT;

    if (!empty($urlEscaped)) {
      $html .= "</a>";
    }
    $html .= "</div>";

    return $html;
  }

  protected function render() {
    [ $instanceId, $json, $aspectRatioWidth, $aspectRatioHeight ] = $this->getRenderVariables();

    echo <<<EOT
    <style>
    .elementMultiMapDivWrapper {
      position:relative;
      height:0;
      padding-top:calc($aspectRatioHeight / $aspectRatioWidth * 100%);
    }
    .elementMultiMapDiv {
      position:absolute;
      top:0;
      left:0;
      width:100%;
      height: 100%;
    }
    @media only screen and (max-width: 600px) {
      /** On small screens override aspect ratio settings to make the map taller **/
      .elementMultiMapDivWrapper {
        position:relative;
        height:0;
        padding-top:calc(16 / 9 * 100%);
      }
    }
    
    </style>
    <div class="elementMultiMapDivWrapper">
      <div class="elementMultiMapDiv" id="elementorMultiMap$instanceId"></div>
    </div>
    <script>
      if (!window.elementorMultiMapInit) {
        // Function not yet loaded, save data to be called as it loads
        if (!window.elementorMultiMapCallbackData) {
          window.elementorMultiMapCallbackData = []
        }
        window.elementorMultiMapCallbackData.push($json)
      } else {
        elementorMultiMapInit($json)
      }
    </script>

EOT;
  }

  private function getRenderVariables() {
    $json = '';
    try {
      $settings = $this->get_settings_for_display();
      $json = $this->generateJsonForMap($settings);
    } catch (\Exception $e) {
      // Can't generate the map if the data is wrong
      return;
    }

    // Get aspect ratio
    $aspectRatio = $settings['aspect_ratio'];
    $defaultAspectRatio = "16:9";
    $aspectRatioWidth = "16";
    $aspectRatioHeight = "9";
    if (!is_string($aspectRatio)) {
      $aspectRatio = $defaultAspectRatio;
    }
    $aspectRatioSplit = explode(":", $aspectRatio);
    if (count($aspectRatioSplit) > 1) {
      if (is_numeric($aspectRatioSplit[0]) && is_numeric($aspectRatioSplit[1])) {
        $aspectRatioWidth = $aspectRatioSplit[0];
        $aspectRatioHeight = $aspectRatioSplit[1];
      }
    }

    $instanceId = $this->get_id();

    return [
      $instanceId,
      $json,
      $aspectRatioWidth,
      $aspectRatioHeight,
    ];
  }
}
