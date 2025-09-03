<?php

/**
 * Plugin Name: Kato Sync
 * Plugin URI: https://osull.io/kato-sync
 * Description: Import property data from external XML feeds and store in WordPress for efficient local access.
 * Version: 1.0.0
 * Author: David O'Sullivan
 * License: GPL v2 or later
 * Text Domain: kato-sync
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('KATO_SYNC_VERSION', '1.0.0');
define('KATO_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KATO_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KATO_SYNC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
  $prefix = 'KatoSync\\';
  $base_dir = KATO_SYNC_PLUGIN_DIR . 'includes/';

  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    return;
  }

  $relative_class = substr($class, $len);
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  if (file_exists($file)) {
    require $file;
  }
});

// Initialize the plugin
function kato_sync_init() {
  // Load text domain for internationalisation
  load_plugin_textdomain('kato-sync', false, dirname(KATO_SYNC_PLUGIN_BASENAME) . '/languages');

  // Initialize main plugin class
  KatoSync\Plugin::init();

  // Provide global class alias for template usage
  if (class_exists('\\KatoSync\\PostTypes\\Kato_Property')) {
    class_alias('\\KatoSync\\PostTypes\\Kato_Property', 'Kato_Property');
  }
}
add_action('plugins_loaded', 'kato_sync_init');

// Global function for easy access from themes
if (!function_exists('kato_property')) {
  /**
   * Get Kato Property instance
   *
   * @param int|null $post_id WordPress post ID (optional, uses current post if not provided)
   * @return \KatoSync\PostTypes\Kato_Property|null
   * @throws \Exception
   */
  // Deprecated: removed in favour of direct class instantiation
  // function kato_property($post_id = null) {}
}

// Register simplified image function immediately for theme access
if (!function_exists('get_kato_card_image')) {
  /**
   * Simplified function to get property card image
   *
   * Checks if image is imported to media library, if so returns WordPress image.
   * If not imported, returns the stored XML feed URL with alt text.
   *
   * @param string $size Image size (default: 'medium')
   * @param string $class CSS class (default: 'property-card__img')
   * @param array $atts Additional HTML attributes
   * @return string HTML img tag or empty string
   */
  function get_kato_card_image(string $size = 'medium', string $class = 'property-card__img', array $atts = array()): string {
    $post_id = \get_the_ID();
    if (!$post_id) {
      return '';
    }

    // Check if the ImageDisplay class is available
    if (class_exists('\KatoSync\Utils\ImageDisplay')) {
      return \KatoSync\Utils\ImageDisplay::get_simple_card_image($post_id, $size, $class, $atts);
    }

    // Fallback: try to get images directly from post meta
    $images = get_post_meta($post_id, '_kato_sync_local_images', true);
    if (empty($images)) {
      $images = get_post_meta($post_id, '_kato_sync_external_images', true);
    }

    if (empty($images) || !is_array($images)) {
      return '';
    }

    $first_image = $images[0];

    // Set up default attributes
    $default_attributes = array(
      'class' => $class,
      'loading' => 'lazy'
    );
    $attributes = \wp_parse_args($atts, $default_attributes);

    // Handle local images (imported to WordPress media library)
    if (isset($first_image['attachment_id'])) {
      $attachment_id = $first_image['attachment_id'];
      $image_alt = $first_image['name'] ?? \get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

      // Use WordPress responsive image function
      $image_html = \wp_get_attachment_image($attachment_id, $size, false, array_merge($attributes, array(
        'alt' => $image_alt
      )));

      return $image_html;
    }

    // Handle external images (not yet imported)
    if (isset($first_image['url'])) {
      $image_url = $first_image['url'];
      $image_alt = $first_image['name'] ?? basename($image_url);

      // Build attributes string
      $attr_string = '';
      foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . \esc_attr($key) . '="' . \esc_attr($value) . '"';
      }

      return sprintf(
        '<img src="%s" alt="%s"%s>',
        \esc_url($image_url),
        \esc_attr($image_alt),
        $attr_string
      );
    }

    return '';
  }
}

/**
 * Theme helper functions for image handling
 * These functions can be used in theme templates
 */

/**
 * Get property card image with automatic fallback
 * Uses responsive WordPress images if imported, otherwise shows feed URL
 */
function kato_property_card_image(int $post_id, string $size = 'medium', array $attributes = []): string {
  if (!class_exists('KatoSync\\Utils\\ImageDisplay')) {
    return '';
  }
  return \KatoSync\Utils\ImageDisplay::get_enhanced_card_image($post_id, $size, $attributes);
}

/**
 * Check if property images have been imported
 */
function kato_property_images_imported(int $post_id): bool {
  if (!class_exists('KatoSync\\Utils\\ImageDisplay')) {
    return false;
  }
  return \KatoSync\Utils\ImageDisplay::are_all_images_imported($post_id);
}

/**
 * Get property image import status
 */
function kato_property_image_import_status(int $post_id): array {
  if (!class_exists('KatoSync\\Utils\\ImageDisplay')) {
    return ['total_images' => 0, 'imported_images' => 0, 'pending_images' => 0, 'import_percentage' => 0, 'all_imported' => false];
  }
  return \KatoSync\Utils\ImageDisplay::get_import_status($post_id);
}

/**
 * Display import status indicator (admin)
 */
function kato_property_import_status_indicator(int $post_id): string {
  if (!class_exists('KatoSync\\Utils\\ImageDisplay')) {
    return '';
  }
  return \KatoSync\Utils\ImageDisplay::display_import_status_indicator($post_id);
}

/**
 * Get property images with detailed status
 */
function kato_property_images_with_status(int $post_id): array {
  if (!class_exists('KatoSync\\Utils\\ImageDisplay')) {
    return [];
  }
  return \KatoSync\Utils\ImageDisplay::get_property_images_with_import_status($post_id);
}

// Add custom cron frequency for image processing and auto-sync
add_filter('cron_schedules', 'kato_sync_add_cron_interval');
function kato_sync_add_cron_interval($schedules) {
  $schedules['every_1_minute'] = array(
    'interval' => 60,
    'display' => __('Every 1 Minute', 'kato-sync')
  );
  $schedules['every_2_minutes'] = array(
    'interval' => 120,
    'display' => __('Every 2 Minutes', 'kato-sync')
  );
  $schedules['every_5_minutes'] = array(
    'interval' => 300,
    'display' => __('Every 5 Minutes', 'kato-sync')
  );
  $schedules['every_10_minutes'] = array(
    'interval' => 600,
    'display' => __('Every 10 Minutes', 'kato-sync')
  );
  $schedules['every_15_minutes'] = array(
    'interval' => 900,
    'display' => __('Every 15 Minutes', 'kato-sync')
  );
  $schedules['every_30_minutes'] = array(
    'interval' => 1800,
    'display' => __('Every 30 Minutes', 'kato-sync')
  );

  // Add auto-sync specific intervals that match the settings values
  $schedules['15mins'] = array(
    'interval' => 900,
    'display' => __('Every 15 Minutes', 'kato-sync')
  );
  $schedules['30mins'] = array(
    'interval' => 1800,
    'display' => __('Every 30 Minutes', 'kato-sync')
  );
  $schedules['1hour'] = array(
    'interval' => 3600,
    'display' => __('Every Hour', 'kato-sync')
  );
  $schedules['3hours'] = array(
    'interval' => 10800,
    'display' => __('Every 3 Hours', 'kato-sync')
  );
  $schedules['6hours'] = array(
    'interval' => 21600,
    'display' => __('Every 6 Hours', 'kato-sync')
  );
  $schedules['12hours'] = array(
    'interval' => 43200,
    'display' => __('Every 12 Hours', 'kato-sync')
  );
  $schedules['24hours'] = array(
    'interval' => 86400,
    'display' => __('Every 24 Hours', 'kato-sync')
  );

  return $schedules;
}

// Add cron hooks
add_action('kato_sync_auto_sync', array('KatoSync\Sync\SyncManager', 'auto_sync'));
add_action('kato_sync_process_images', array('KatoSync\Sync\ImageProcessor', 'process_image_queue'));

// Activation hook
register_activation_hook(__FILE__, 'kato_sync_activate');
function kato_sync_activate() {
  // Run database migrations
  KatoSync\DB\Migrations::activate();

  // Create custom post type
  KatoSync\PostTypes\Property::register();

  // Flush rewrite rules
  flush_rewrite_rules();

  // Set default options
  $default_options = array(
    'feed_url' => '',
    'request_timeout' => 30,
    'batch_size' => 50,
    'auto_sync_enabled' => true,
    'auto_sync_frequency' => '1hour',
    'url_pattern' => '{name}-{address1}-{postcode}',
    'force_update_all' => false,
    'remove_data_on_uninstall' => false,
    'cleanup_logs_after_days' => 30,
    'image_mode' => 'local',
    'image_cron_interval' => 'every_2_minutes',
  );

  add_option('kato_sync_settings', $default_options);

  // Schedule auto-sync cron job with proper frequency calculation
  if (!wp_next_scheduled('kato_sync_auto_sync')) {
    $frequency = $default_options['auto_sync_frequency'];
    $current_time = current_time('timestamp');

    switch ($frequency) {
      case '15mins':
        $next_run = $current_time + 900; // 15 minutes from now
        break;

      case '30mins':
        $next_run = $current_time + 1800; // 30 minutes from now
        break;

      case '1hour':
        $next_run = $current_time + 3600; // 1 hour from now
        break;

      case '3hours':
        $next_run = $current_time + 10800; // 3 hours from now
        break;

      case '6hours':
        $next_run = $current_time + 21600; // 6 hours from now
        break;

      case '12hours':
        $next_run = $current_time + 43200; // 12 hours from now
        break;

      case '24hours':
        $next_run = $current_time + 86400; // 24 hours from now
        break;

      default:
        $next_run = $current_time + 86400; // 24 hours from now
        break;
    }

    wp_schedule_event($next_run, $frequency, 'kato_sync_auto_sync');
    update_option('kato_sync_next_sync', $next_run);
  }

  // Schedule image processing cron job
  if (!wp_next_scheduled('kato_sync_process_images')) {
    $image_cron_interval = $default_options['image_cron_interval'] ?? 'every_2_minutes';
    wp_schedule_event(time(), $image_cron_interval, 'kato_sync_process_images');
  }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'kato_sync_deactivate');
function kato_sync_deactivate() {
  // Clear scheduled cron jobs
  wp_clear_scheduled_hook('kato_sync_auto_sync');
  wp_clear_scheduled_hook('kato_sync_process_images');

  // Flush rewrite rules
  flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'kato_sync_uninstall');
function kato_sync_uninstall() {
  $settings = get_option('kato_sync_settings', array());

  // Remove all data if option is enabled
  if (!empty($settings['remove_data_on_uninstall'])) {
    // Delete all property posts
    $properties = get_posts(array(
      'post_type' => 'kato-property',
      'numberposts' => -1,
      'post_status' => 'any'
    ));

    foreach ($properties as $property) {
      wp_delete_post($property->ID, true);
    }

    // Delete plugin options
    delete_option('kato_sync_settings');
    delete_option('kato_sync_sync_logs');
    delete_option('kato_sync_last_sync');
    delete_option('kato_sync_next_sync');
  }
}
