<?php

namespace KatoSync\Sync;

use KatoSync\PostTypes\Property;

/**
 * Sync manager for handling XML feed imports with comprehensive data mapping
 */
class SyncManager {

  /**
   * Constructor
   */
  public function __construct() {
    // Register taxonomies
    add_action('init', array($this, 'register_taxonomies'));
  }

  /**
   * Register custom taxonomies for filtering
   */
  public function register_taxonomies(): void {
    // Property Type taxonomy
    register_taxonomy('kato_property_type', 'kato-property', array(
      'labels' => array(
        'name' => __('Property Types', 'kato-sync'),
        'singular_name' => __('Property Type', 'kato-sync'),
        'menu_name' => __('Property Types', 'kato-sync'),
        'all_items' => __('All Property Types', 'kato-sync'),
        'edit_item' => __('Edit Property Type', 'kato-sync'),
        'view_item' => __('View Property Type', 'kato-sync'),
        'update_item' => __('Update Property Type', 'kato-sync'),
        'add_new_item' => __('Add New Property Type', 'kato-sync'),
        'new_item_name' => __('New Property Type Name', 'kato-sync'),
        'parent_item' => __('Parent Property Type', 'kato-sync'),
        'parent_item_colon' => __('Parent Property Type:', 'kato-sync'),
        'search_items' => __('Search Property Types', 'kato-sync'),
        'popular_items' => __('Popular Property Types', 'kato-sync'),
        'separate_items_with_commas' => __('Separate property types with commas', 'kato-sync'),
        'add_or_remove_items' => __('Add or remove property types', 'kato-sync'),
        'choose_from_most_used' => __('Choose from the most used property types', 'kato-sync'),
        'not_found' => __('No property types found', 'kato-sync'),
      ),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_nav_menus' => true,
      'show_in_rest' => true,
      'show_admin_column' => true,
      'query_var' => true,
      'rewrite' => array('slug' => 'property-type'),
    ));

    // Location (County) taxonomy
    register_taxonomy('kato_location', 'kato-property', array(
      'labels' => array(
        'name' => __('Locations', 'kato-sync'),
        'singular_name' => __('Location', 'kato-sync'),
        'menu_name' => __('Locations', 'kato-sync'),
        'all_items' => __('All Locations', 'kato-sync'),
        'edit_item' => __('Edit Location', 'kato-sync'),
        'view_item' => __('View Location', 'kato-sync'),
        'update_item' => __('Update Location', 'kato-sync'),
        'add_new_item' => __('Add New Location', 'kato-sync'),
        'new_item_name' => __('New Location Name', 'kato-sync'),
        'parent_item' => __('Parent Location', 'kato-sync'),
        'parent_item_colon' => __('Parent Location:', 'kato-sync'),
        'search_items' => __('Search Locations', 'kato-sync'),
        'popular_items' => __('Popular Locations', 'kato-sync'),
        'separate_items_with_commas' => __('Separate locations with commas', 'kato-sync'),
        'add_or_remove_items' => __('Add or remove locations', 'kato-sync'),
        'choose_from_most_used' => __('Choose from the most used locations', 'kato-sync'),
        'not_found' => __('No locations found', 'kato-sync'),
      ),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_nav_menus' => true,
      'show_in_rest' => true,
      'show_admin_column' => true,
      'query_var' => true,
      'rewrite' => array('slug' => 'location'),
    ));

    // Availability taxonomy
    register_taxonomy('kato_availability', 'kato-property', array(
      'labels' => array(
        'name' => __('Availabilities', 'kato-sync'),
        'singular_name' => __('Availability', 'kato-sync'),
        'menu_name' => __('Availabilities', 'kato-sync'),
        'all_items' => __('All Availabilities', 'kato-sync'),
        'edit_item' => __('Edit Availability', 'kato-sync'),
        'view_item' => __('View Availability', 'kato-sync'),
        'update_item' => __('Update Availability', 'kato-sync'),
        'add_new_item' => __('Add New Availability', 'kato-sync'),
        'new_item_name' => __('New Availability Name', 'kato-sync'),
        'parent_item' => __('Parent Availability', 'kato-sync'),
        'parent_item_colon' => __('Parent Availability:', 'kato-sync'),
        'search_items' => __('Search Availabilities', 'kato-sync'),
        'popular_items' => __('Popular Availabilities', 'kato-sync'),
        'separate_items_with_commas' => __('Separate availabilities with commas', 'kato-sync'),
        'add_or_remove_items' => __('Add or remove availabilities', 'kato-sync'),
        'choose_from_most_used' => __('Choose from the most used availabilities', 'kato-sync'),
        'not_found' => __('No availabilities found', 'kato-sync'),
      ),
      'hierarchical' => false,
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_nav_menus' => true,
      'show_in_rest' => true,
      'show_admin_column' => true,
      'query_var' => true,
      'rewrite' => array('slug' => 'availability'),
    ));
  }

  /**
   * Auto sync function
   */
  public static function auto_sync(): void {

    $settings = get_option('kato_sync_settings', array());

    if (empty($settings['auto_sync_enabled'])) {
      // Log that auto-sync is disabled
      error_log('Kato Sync: Auto-sync is disabled in settings');
      return;
    }

    // Check if sync is already running with a more robust lock check
    $sync_lock = get_transient('kato_sync_running');
    if ($sync_lock) {
      // Check if the lock is stale (older than 10 minutes)
      $lock_time = get_transient('kato_sync_running_time');
      $current_time = time();

      if ($lock_time && ($current_time - $lock_time) > 600) { // 10 minutes
        error_log('Kato Sync: Clearing stale sync lock (age: ' . ($current_time - $lock_time) . ' seconds)');
        delete_transient('kato_sync_running');
        delete_transient('kato_sync_running_time');
      } else {
        error_log('Kato Sync: Auto-sync skipped - sync already in progress (lock age: ' . ($current_time - $lock_time) . ' seconds)');
        return;
      }
    }

    // Log that auto-sync is starting
    error_log('Kato Sync: Auto-sync starting at ' . date('Y-m-d H:i:s'));

    // Set sync lock with timestamp
    set_transient('kato_sync_running', true, 300); // 5 minutes
    set_transient('kato_sync_running_time', time(), 300); // 5 minutes

    // Use the image mode setting for automatic imports
    $image_mode = $settings['image_mode'] ?? 'local';
    $is_property_only = ($image_mode === 'external');

    // Store the sync mode used for this import
    $import_type = $is_property_only ? 'properties_only' : 'properties_and_images';
    update_option('kato_sync_last_import_mode', $import_type);

    try {
      $result = self::sync_properties('auto', $is_property_only);

      // Log the result
      if ($result['success']) {
        error_log('Kato Sync: Auto-sync completed successfully - Added: ' . $result['added'] . ', Updated: ' . $result['updated'] . ', Skipped: ' . $result['skipped']);
      } else {
        error_log('Kato Sync: Auto-sync failed - ' . $result['message']);
      }
    } catch (\Exception $e) {
      error_log('Kato Sync: Auto-sync exception - ' . $e->getMessage());
    } finally {
      // Clear sync lock
      delete_transient('kato_sync_running');
      delete_transient('kato_sync_running_time');
      error_log('Kato Sync: Auto-sync completed, lock cleared');
    }
  }

  /**
   * Manual sync via AJAX
   */
  public static function ajax_manual_sync(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    // Get form data
    $import_type = $_POST['import_type'] ?? 'properties_only';
    $force_update = isset($_POST['force_update']);
    $is_property_only = ($import_type === 'properties_only');


    // Update settings if force update is requested
    if ($force_update) {
      $settings = get_option('kato_sync_settings', array());
      $settings['force_update_all'] = true;
      update_option('kato_sync_settings', $settings);
    }

    // Store the sync mode used for this import
    update_option('kato_sync_last_import_mode', $import_type);

    $result = self::sync_properties('manual', $is_property_only);

    if ($result['success']) {
      // Handle image processing for properties_and_images import type
      if ($import_type === 'properties_and_images') {

        // Get settings for cron scheduling
        $settings = get_option('kato_sync_settings', array());

        // Only schedule cron - don't process images in AJAX to avoid timeouts
        self::ensure_image_cron_scheduled($settings);

        // Get queue status for user message
        $queue_status = \KatoSync\Sync\ImageProcessor::get_queue_status();

        // Create a user-friendly message
        $message = sprintf(
          __('Import completed successfully! %d properties added, %d updated, %d skipped. %d images queued for background processing.', 'kato-sync'),
          $result['added'],
          $result['updated'],
          $result['skipped'],
          $queue_status['pending']
        );
      } else {
        // Create a user-friendly message for properties-only
        $message = sprintf(
          __('Import completed successfully! %d properties added, %d updated, %d skipped.', 'kato-sync'),
          $result['added'],
          $result['updated'],
          $result['skipped']
        );
      }

      $result['message'] = $message;
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result['message']);
    }
  }

  /**
   * Ensure image processing cron job is scheduled (lightweight version for AJAX)
   */
  private static function ensure_image_cron_scheduled(array $settings): void {

    // Skip if cron is disabled
    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
      return;
    }

    // Get the cron interval from settings with fallback
    $interval = $settings['image_cron_interval'] ?? 'every_2_minutes';

    // Quick validation - use built-in intervals if custom ones aren't available
    $schedules = wp_get_schedules();
    if (!isset($schedules[$interval])) {
      $interval = 'hourly'; // Safe fallback
    }

    // Only schedule if not already scheduled
    if (!wp_next_scheduled('kato_sync_process_images')) {
      wp_schedule_event(time(), $interval, 'kato_sync_process_images');
    } else {
    }
  }

  /**
   * Test feed via AJAX
   */
  public static function ajax_test_feed(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    $settings = get_option('kato_sync_settings', array());
    $feed_url = $settings['feed_url'] ?? '';

    if (empty($feed_url)) {
      wp_send_json_error(__('No feed URL configured.', 'kato-sync'));
    }

    $result = self::test_feed($feed_url);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result['message']);
    }
  }

  /**
   * Main sync function
   */
  public static function sync_properties(string $type = 'manual', bool $is_property_only = false): array {
    $start_time = time();
    $settings = get_option('kato_sync_settings', array());
    $feed_url = $settings['feed_url'] ?? '';

    // Set timeout for this operation
    set_time_limit(300); // 5 minutes max
    ini_set('max_execution_time', 300);

    // Check if sync is already running (only for manual syncs, auto-sync handles its own lock)
    if ($type === 'manual') {
      $sync_lock = get_transient('kato_sync_running');
      if ($sync_lock) {
        return array(
          'success' => false,
          'message' => __('Sync already in progress. Please wait for the current sync to complete.', 'kato-sync'),
          'type' => $type,
          'timestamp' => $start_time,
          'duration' => 0,
          'total_properties' => 0,
          'added' => 0,
          'updated' => 0,
          'removed' => 0,
          'skipped' => 0,
        );
      }

      // Set sync lock for manual syncs only
      set_transient('kato_sync_running', true, 300); // 5 minutes
    }

    if (empty($feed_url)) {
      return array(
        'success' => false,
        'message' => __('No feed URL configured.', 'kato-sync'),
        'type' => $type,
        'timestamp' => $start_time,
        'duration' => 0,
        'total_properties' => 0,
        'added' => 0,
        'updated' => 0,
        'removed' => 0,
        'skipped' => 0,
      );
    }

    // Debug logging removed to prevent excessive log output

    try {
      // Fetch XML feed
      $xml_data = self::fetch_xml_feed($feed_url, $settings['request_timeout'] ?? 30);

      if (!$xml_data) {
        throw new \Exception(__('Failed to fetch XML feed. Please check the feed URL and try again.', 'kato-sync'));
      }

      // Parse XML
      $properties = self::parse_xml_properties($xml_data);

      if (empty($properties)) {
        throw new \Exception(__('No properties found in XML feed. Please check the feed format.', 'kato-sync'));
      }

      // Process properties in batches
      $batch_size = $settings['batch_size'] ?? 50;
      $force_update = $settings['force_update_all'] ?? false;



      $stats = self::process_properties($properties, $batch_size, $force_update, $is_property_only);

      $duration = time() - $start_time;

      // Log sync results
      $log_entry = array(
        'type' => $type,
        'timestamp' => $start_time,
        'status' => 'success',
        'duration' => $duration,
        'total_properties' => count($properties),
        'added' => $stats['added'],
        'updated' => $stats['updated'],
        'removed' => $stats['removed'],
        'skipped' => $stats['skipped'],
      );

      self::log_sync($log_entry);

      // Update last sync timestamp
      update_option('kato_sync_last_sync', $start_time);

      // Clear sync lock (only for manual syncs, auto-sync handles its own lock)
      if ($type === 'manual') {
        delete_transient('kato_sync_running');
      }

      return array(
        'success' => true,
        'message' => sprintf(
          __('Sync completed successfully. Added: %d, Updated: %d, Skipped: %d', 'kato-sync'),
          $stats['added'],
          $stats['updated'],
          $stats['skipped']
        ),
        'type' => $type,
        'timestamp' => $start_time,
        'duration' => $duration,
        'total_properties' => count($properties),
        'added' => $stats['added'],
        'updated' => $stats['updated'],
        'removed' => $stats['removed'],
        'skipped' => $stats['skipped'],
      );
    } catch (\Exception $e) {
      $duration = time() - $start_time;

      // Log sync failure
      $log_entry = array(
        'type' => $type,
        'timestamp' => $start_time,
        'status' => 'error',
        'duration' => $duration,
        'total_properties' => 0,
        'added' => 0,
        'updated' => 0,
        'removed' => 0,
        'skipped' => 0,
        'error' => $e->getMessage(),
      );

      self::log_sync($log_entry);

      // Clear sync lock on error (only for manual syncs, auto-sync handles its own lock)
      if ($type === 'manual') {
        delete_transient('kato_sync_running');
      }

      return array(
        'success' => false,
        'message' => $e->getMessage(),
        'type' => $type,
        'timestamp' => $start_time,
        'duration' => $duration,
        'total_properties' => 0,
        'added' => 0,
        'updated' => 0,
        'removed' => 0,
        'skipped' => 0,
      );
    }
  }

  /**
   * Fetch XML feed
   */
  private static function fetch_xml_feed(string $url, int $timeout): ?string {
    $response = wp_remote_get($url, array(
      'timeout' => $timeout,
      'user-agent' => 'Kato Sync WordPress Plugin/' . KATO_SYNC_VERSION,
    ));

    if (is_wp_error($response)) {
      return null;
    }

    $body = wp_remote_retrieve_body($response);
    $status_code = wp_remote_retrieve_response_code($response);
    $response_message = wp_remote_retrieve_response_message($response);

    if ($status_code !== 200) {
      return null;
    }

    if (empty($body)) {
      return null;
    }

    return $body;
  }

  /**
   * Parse XML properties from feed data
   */
  private static function parse_xml_properties(string $xml_data): array {
    $properties = array();
    try {
      // Load XML data
      $xml = simplexml_load_string($xml_data);

      if ($xml === false) {
        return $properties;
      }

      // Check if we have properties
      if (!isset($xml->property)) {
        return $properties;
      }

      // Parse each property
      foreach ($xml->property as $property_node) {
        $property_data = self::extract_property_data($property_node);
        if (!empty($property_data['id'])) {
          $properties[] = $property_data;
        }
      }
    } catch (\Exception $e) {
      // Debug logging removed to prevent excessive log output
    }
    return $properties;
  }

  /**
   * Extract property data from XML node with comprehensive mapping
   */
  private static function extract_property_data(\SimpleXMLElement $node): array {
    $data = array();
    $property_id = (string)($node->id ?? '');

    // Get all child elements and their values
    $all_elements = array();
    foreach ($node->children() as $child) {
      $element_name = $child->getName();
      $element_value = self::extract_element_value($child);

      // Handle multiple elements with the same name by creating an array
      if (isset($all_elements[$element_name])) {
        // If this element already exists, convert to array if not already
        if (!is_array($all_elements[$element_name])) {
          $all_elements[$element_name] = array($all_elements[$element_name]);
        }
        $all_elements[$element_name][] = $element_value;
      } else {
        $all_elements[$element_name] = $element_value;
      }
    }

    // Debug logging removed to prevent excessive log output

    // Map all fields comprehensively
    $data = self::map_basic_fields($all_elements);
    $data = self::map_location_fields($all_elements, $data);
    $data = self::map_pricing_fields($all_elements, $data);
    $data = self::map_specification_fields($all_elements, $data);
    $data = self::map_agent_fields($all_elements, $data);
    $data = self::map_media_fields($all_elements, $data);

    // Process complex data with dedicated functions
    $data['types'] = self::process_types_data($all_elements);
    $data['availabilities'] = self::process_availabilities_data($all_elements);
    $data['key_selling_points'] = self::process_key_selling_points_data($all_elements);
    $data['amenities_specifications'] = self::process_amenities_specifications_data($all_elements);
    $data['floor_units'] = self::process_floor_units_data($all_elements);

    // Process size and price ranges with fallback logic
    $size_range = self::calculate_size_range($all_elements, $data['floor_units']);
    $data['size_min'] = $size_range['min'];
    $data['size_max'] = $size_range['max'];

    // Also store total property size separately (not part of unit ranges)
    $total_property_size = self::extract_main_property_size($all_elements);
    if ($total_property_size) {
      $data['total_property_size_min'] = $total_property_size['min'];
      $data['total_property_size_max'] = $total_property_size['max'];
    }

    $price_range = self::calculate_price_range($all_elements, $data['floor_units'], $data['availabilities']);
    $data['price_min'] = $price_range['min'];
    $data['price_max'] = $price_range['max'];
    $data['price_type'] = $price_range['type'];





    // Debug logging removed to prevent excessive log output

    return $data;
  }

  /**
   * Extract element value handling different types
   */
  private static function extract_element_value(\SimpleXMLElement $element) {
    if ($element->count() > 0) {
      // Complex element with child nodes
      return self::extract_complex_element($element);
    } else {
      // Simple text element
      return (string)$element;
    }
  }

  /**
   * Extract complex XML elements with child nodes
   */
  private static function extract_complex_element(\SimpleXMLElement $element): array {
    $result = array();

    // Get all children with the same name to handle multiple elements properly
    $children_by_name = array();
    foreach ($element->children() as $child) {
      $child_name = $child->getName();
      if (!isset($children_by_name[$child_name])) {
        $children_by_name[$child_name] = array();
      }
      $children_by_name[$child_name][] = $child;
    }

    // Process each group of children with the same name
    foreach ($children_by_name as $child_name => $children) {
      if (count($children) === 1) {
        // Single element
        $child = $children[0];
        if ($child->count() > 0) {
          // Nested complex element
          $child_value = self::extract_complex_element($child);
        } else {
          // Simple text element - check for attributes
          $child_value = self::extract_element_with_attributes($child);
        }
        $result[$child_name] = $child_value;
      } else {
        // Multiple elements with the same name - create an array
        $child_values = array();
        foreach ($children as $child) {
          if ($child->count() > 0) {
            // Nested complex element
            $child_value = self::extract_complex_element($child);
          } else {
            // Simple text element - check for attributes
            $child_value = self::extract_element_with_attributes($child);
          }
          $child_values[] = $child_value;
        }
        $result[$child_name] = $child_values;
      }
    }

    // Debug logging removed to prevent excessive log output

    return $result;
  }

  /**
   * Extract element value and handle attributes
   */
  private static function extract_element_with_attributes(\SimpleXMLElement $element) {
    $element_name = $element->getName();
    $content = (string)$element;

    // Debug logging removed to prevent excessive log output

    // Decode Unicode escape sequences
    $content = self::decode_unicode_escapes($content);

    // Debug logging removed to prevent excessive log output

    // Check if element has attributes
    $attributes = $element->attributes();
    if ($attributes && count($attributes) > 0) {
      // Special handling for taxonomy elements (type, availability, etc.)
      if (in_array($element_name, ['type', 'availability', 'property_type'])) {
        $result = array();
        $result['name'] = $content; // The content is the taxonomy name
        $result['id'] = (string)$attributes->id; // The ID attribute
        return $result;
      }

      // For other elements with attributes, create structured data
      $result = array();

      // Add content as 'url' or 'value' if it's not empty
      if (!empty($content)) {
        $result['url'] = $content;
      }

      // Add all attributes
      foreach ($attributes as $attr_name => $attr_value) {
        $decoded_attr_value = self::decode_unicode_escapes((string)$attr_value);
        $result[$attr_name] = $decoded_attr_value;

        // Debug logging removed to prevent excessive log output
      }

      // Debug logging removed to prevent excessive log output

      return $result;
    }

    // Debug logging removed to prevent excessive log output

    // No attributes, return simple string
    return $content;
  }

  /**
   * Decode Unicode escape sequences in strings
   */
  private static function decode_unicode_escapes(string $string): string {
    // Debug logging removed to prevent excessive log output

    // Decode Unicode escape sequences like \u00a3 to actual characters
    $decoded = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
      $result = mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
      // Debug logging removed to prevent excessive log output
      return $result;
    }, $string);

    // Debug logging removed to prevent excessive log output

    return $decoded;
  }

  /**
   * Clean up Unicode issues in strings (fallback method)
   */
  private static function clean_unicode_string(string $string): string {
    // Replace common Unicode issues
    $cleaned = str_replace('u00a3', '£', $string);
    $cleaned = str_replace('u00a0', ' ', $cleaned); // Non-breaking space
    $cleaned = str_replace('u2019', "'", $cleaned); // Right single quotation mark
    $cleaned = str_replace('u2018', "'", $cleaned); // Left single quotation mark
    $cleaned = str_replace('u201c', '"', $cleaned); // Left double quotation mark
    $cleaned = str_replace('u201d', '"', $cleaned); // Right double quotation mark

    return $cleaned;
  }

  /**
   * Process floor unit data to extract both formatted and numeric values
   */
  private static function process_floor_unit_data(array $floor_unit): array {
    $processed = $floor_unit;

    // Helper function to clean Unicode currency symbols
    $clean_unicode = function ($value) {
      if (!is_string($value)) return $value;
      return str_replace(['u00a3', '\\u00a3', '\u00a3'], '£', $value);
    };

    // Clean all string values in the floor unit data
    foreach ($processed as $key => $value) {
      if (is_string($value)) {
        $processed[$key] = $clean_unicode($value);
      }
    }

    // Process rent_sqft - keep original string formatting, don't convert to float
    if (isset($floor_unit['rent_price'])) {
      $rent_price_value = trim($clean_unicode($floor_unit['rent_price']));

      // Check if it's "On Application" or similar text
      if (
        strtolower($rent_price_value) === 'on application' ||
        stripos($rent_price_value, 'application') !== false ||
        (!is_numeric($rent_price_value) && !preg_match('/£?\d+\.?\d*/', $rent_price_value))
      ) {
        $formatted_rent = 'POA';
      } else {
        // Keep the original string value, just clean Unicode
        $formatted_rent = $rent_price_value;
        // Add £ prefix if not already present and it's a plain number
        if (is_numeric($rent_price_value)) {
          $formatted_rent = '£' . $rent_price_value;
        }
      }

      $processed['rent_sqft'] = $formatted_rent; // Keep as string for display
    }

    // Note: All other fields including total_sqft, total_month, total_year are already
    // cleaned by the Unicode cleaning loop above. We keep them as strings.

    return $processed;
  }

  /**
   * Map basic property fields
   */
  private static function map_basic_fields(array $elements): array {
    return array(
      'id' => $elements['id'] ?? '',
      'object_id' => $elements['object_id'] ?? '',
      'name' => $elements['name'] ?? '',
      'property_type' => $elements['property_type'] ?? '',
      'status' => $elements['status'] ?? '',
      'description' => $elements['description'] ?? '',
      'features' => $elements['features'] ?? '',
      'created_at' => $elements['created_at'] ?? '',
      'last_updated' => $elements['last_updated'] ?? '',
      'featured' => $elements['featured'] ?? '',
      'fitted' => $elements['fitted'] ?? '',
      'fitted_comment' => $elements['fitted_comment'] ?? '',
      'specification_summary' => $elements['specification_summary'] ?? '',
      'specification_promo' => $elements['specification_promo'] ?? '',
      'specification_description' => $elements['specification_description'] ?? '',
      'url' => $elements['url'] ?? '',
      'particulars_url' => $elements['particulars_url'] ?? '',
      'togc' => $elements['togc'] ?? '',
      'sale_type' => $elements['sale_type'] ?? '',
      'tenancy_passing_giy' => $elements['tenancy_passing_giy'] ?? '',
      'tenancy_passing_niy' => $elements['tenancy_passing_niy'] ?? '',
      'turnover_pa' => $elements['turnover_pa'] ?? '',
      'tenancy_status' => $elements['tenancy_status'] ?? '',
      'class_of_use' => $elements['class_of_use'] ?? '',
      'legal_fees_applicable' => $elements['legal_fees_applicable'] ?? '',
      'lease_length' => $elements['lease_length'] ?? '',
      'protected_act' => $elements['protected_act'] ?? '',
      'insurance_type' => $elements['insurance_type'] ?? '',
      'availability_reasons' => $elements['availability_reasons'] ?? '',
      'shop_frontage_ft' => $elements['shop_frontage_ft'] ?? '',
      'shop_frontage_m' => $elements['shop_frontage_m'] ?? '',
      'shop_frontage_inches' => $elements['shop_frontage_inches'] ?? '',
      'travel_times' => $elements['travel_times'] ?? '',
      'tags' => $elements['tags'] ?? '',
      'marketing_title_1' => $elements['marketing_title_1'] ?? '',
      'marketing_title_2' => $elements['marketing_title_2'] ?? '',
      'marketing_title_3' => $elements['marketing_title_3'] ?? '',
      'marketing_title_4' => $elements['marketing_title_4'] ?? '',
      'marketing_title_5' => $elements['marketing_title_5'] ?? '',
      'marketing_text_1' => $elements['marketing_text_1'] ?? '',
      'marketing_text_2' => $elements['marketing_text_2'] ?? '',
      'marketing_text_3' => $elements['marketing_text_3'] ?? '',
      'marketing_text_4' => $elements['marketing_text_4'] ?? '',
      'marketing_text_5' => $elements['marketing_text_5'] ?? '',
      'marketing_title_transport' => $elements['marketing_title_transport'] ?? '',
      'marketing_text_transport' => $elements['marketing_text_transport'] ?? '',
    );
  }

  /**
   * Map location fields
   */
  private static function map_location_fields(array $elements, array $data): array {
    $location_fields = array(
      'address1' => $elements['address1'] ?? '',
      'address2' => $elements['address2'] ?? '',
      'town' => $elements['town'] ?? '',
      'city' => $elements['city'] ?? '',
      'county' => $elements['county'] ?? '',
      'postcode' => $elements['postcode'] ?? '',
      'location' => $elements['location'] ?? '',
      'street_view_data' => $elements['street_view_data'] ?? '',
      'submarkets' => $elements['submarkets'] ?? '',
    );

    // Only add coordinate values if they exist and are not empty strings
    // This preserves numeric strings like "-0.11313595337296"
    if (isset($elements['lat']) && $elements['lat'] !== '') {
      $location_fields['lat'] = $elements['lat'];
    }
    if (isset($elements['lon']) && $elements['lon'] !== '') {
      $location_fields['lon'] = $elements['lon'];
    }
    if (isset($elements['latitude']) && $elements['latitude'] !== '') {
      $location_fields['latitude'] = $elements['latitude'];
    }
    if (isset($elements['longitude']) && $elements['longitude'] !== '') {
      $location_fields['longitude'] = $elements['longitude'];
    }

    return array_merge($data, $location_fields);
  }

  /**
   * Map pricing fields
   */
  private static function map_pricing_fields(array $elements, array $data): array {

    $pricing_fields = array(
      'price' => $elements['price'] ?? '',
      'rent' => $elements['rent'] ?? '',
      'price_per_sqft' => $elements['price_per_sqft'] ?? '',
      'price_per_sqft_min' => $elements['price_per_sqft_min'] ?? '',
      'price_per_sqft_max' => $elements['price_per_sqft_max'] ?? '',
      'total_price' => $elements['total_price'] ?? '',
      'total_monthly_min' => $elements['total_monthly_min'] ?? '',
      'total_monthly_max' => $elements['total_monthly_max'] ?? '',
      'total_yearly_min' => $elements['total_yearly_max'] ?? '',
      'total_yearly_max' => $elements['total_yearly_max'] ?? '',
      'turnover' => $elements['turnover'] ?? '',
      'profit_gross' => $elements['profit_gross'] ?? '',
      'profit_net' => $elements['profit_net'] ?? '',
      'initial_yield' => $elements['initial_yield'] ?? '',
      'premium' => $elements['premium'] ?? '',
      'premium_nil' => $elements['premium_nil'] ?? '',
      'parking_ratio' => $elements['parking_ratio'] ?? '',
      'rent_components' => $elements['rent_components'] ?? '',
      'service_charge' => $elements['service_charge'] ?? '',
    );


    return array_merge($data, $pricing_fields);
  }

  /**
   * Map specification fields
   */
  private static function map_specification_fields(array $elements, array $data): array {
    $specification_fields = array(
      'size_sqft' => $elements['size_sqft'] ?? '',
      'size_from' => $elements['size_from'] ?? '',
      'size_to' => $elements['size_to'] ?? '',
      'total_property_size' => $elements['total_property_size'] ?? '',
      'total_property_size_metric' => $elements['total_property_size_metric'] ?? '',
      'area_size_unit' => $elements['area_size_unit'] ?? '',
      'area_size_type' => $elements['area_size_type'] ?? '',
      'size_from_sqft' => $elements['size_from_sqft'] ?? '',
      'size_to_sqft' => $elements['size_to_sqft'] ?? '',
      'size_measure' => $elements['size_measure'] ?? '',
      'land_size_from' => $elements['land_size_from'] ?? '',
      'land_size_to' => $elements['land_size_to'] ?? '',
      'land_size_metric' => $elements['land_size_metric'] ?? '',
    );

    return array_merge($data, $specification_fields);
  }

  /**
   * Map agent fields
   */
  private static function map_agent_fields(array $elements, array $data): array {
    // Handle contacts - extract all contact data from the nested structure
    $contacts = array();

    // Debug logging for contacts

    // Check for various possible contact field names
    $possible_contact_fields = ['contacts', 'contact', 'agent_contacts', 'agent_contact', 'agents', 'agent'];
    foreach ($possible_contact_fields as $field) {
      if (isset($elements[$field])) {
      }
    }

    if (isset($elements['contacts']) && is_array($elements['contacts'])) {
      if (isset($elements['contacts']['contact'])) {
        // Handle single contact or multiple contacts
        if (is_array($elements['contacts']['contact'])) {
          // Check if this is a single contact (has name field) or multiple contacts
          if (isset($elements['contacts']['contact']['name'])) {
            // Single contact
            $contacts[] = $elements['contacts']['contact'];
          } else {
            // Multiple contacts
            foreach ($elements['contacts']['contact'] as $contact) {
              if (is_array($contact)) {
                $contacts[] = $contact;
              }
            }
          }
        } else {
          // Single contact as non-array (shouldn't happen with proper XML)
        }
      } else {
      }
    } else {
    }


    $agent_fields = array(
      'agent_name' => $elements['agent_name'] ?? '',
      'agent_email' => $elements['agent_email'] ?? '',
      'agent_phone' => $elements['agent_phone'] ?? '',
      'agent_company' => $elements['agent_company'] ?? '',
      'joint_agents' => $elements['joint_agents'] ?? '',
    );

    // Add contacts at the top level so they can be stored properly
    $data['contacts'] = $contacts;

    return array_merge($data, $agent_fields);
  }

  /**
   * Map media fields
   */
  private static function map_media_fields(array $elements, array $data): array {
    // Handle images - extract all image URLs from the nested structure
    $images = array();
    if (isset($elements['images']) && is_array($elements['images'])) {
      if (isset($elements['images']['image'])) {
        // Handle single image or multiple images
        if (is_array($elements['images']['image'])) {
          foreach ($elements['images']['image'] as $image) {
            if (is_string($image)) {
              $images[] = $image;
            } elseif (is_array($image) && isset($image['url'])) {
              $images[] = $image['url'];
            } elseif (is_array($image) && isset($image['name'])) {
              // Handle case where image has name attribute
              $images[] = $image;
            }
          }
        } else {
          // Single image
          $images[] = $elements['images']['image'];
        }
      }
    }

    // Handle original images - extract all image URLs from the nested structure
    $original_images = array();
    if (isset($elements['original_images']) && is_array($elements['original_images'])) {
      if (isset($elements['original_images']['original_image'])) {
        // Handle single image or multiple images
        if (is_array($elements['original_images']['original_image'])) {
          foreach ($elements['original_images']['original_image'] as $image) {
            if (is_string($image)) {
              $original_images[] = $image;
            } elseif (is_array($image)) {
              // Handle structured image data with name and URL
              if (isset($image['name']) && isset($image['url'])) {
                $original_images[] = array(
                  'name' => $image['name'],
                  'url' => $image['url']
                );
              } elseif (isset($image['name']) && !empty($image['name'])) {
                // If only name is available, check if it's a valid URL
                if (filter_var($image['name'], FILTER_VALIDATE_URL)) {
                  $original_images[] = array(
                    'name' => basename($image['name']),
                    'url' => $image['name']
                  );
                } else {
                  // Name is not a URL, skip this image
                }
              } elseif (isset($image['url'])) {
                // If only URL is available, use filename as name
                $original_images[] = array(
                  'name' => basename($image['url']),
                  'url' => $image['url']
                );
              } else {
                // Fallback: treat as URL string if it's a valid URL
                if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                  $original_images[] = array(
                    'name' => basename($image),
                    'url' => $image
                  );
                } else {
                }
              }
            }
          }
        } else {
          // Single image
          $original_images[] = $elements['original_images']['original_image'];
        }
      }
    }

    // Handle files - extract all file data from the nested structure
    $files = array();

    if (isset($elements['files'])) {
      if (is_array($elements['files'])) {
        if (isset($elements['files']['file'])) {
          // Standard nested structure: files > file

          // Check if it's a single file (associative array with keys like 'name', 'url', 'type')
          // vs multiple files (numeric array of file objects)
          $is_single_file = isset($elements['files']['file']['name']) ||
            isset($elements['files']['file']['url']) ||
            isset($elements['files']['file']['type']);

          if ($is_single_file) {
            // Single file - treat as single file object
            $files[] = $elements['files']['file'];
          } elseif (is_array($elements['files']['file'])) {
            // Multiple files - array of file objects
            foreach ($elements['files']['file'] as $file) {
              if (is_array($file)) {
                $files[] = $file;
              }
            }
          } else {
            // Fallback: treat as single file
            $files[] = $elements['files']['file'];
          }
        } else {
          // Files array might contain file data directly
          // Check if it has a URL key (improperly extracted structure)
          if (isset($elements['files']['url'])) {
            // Extract was incomplete - create proper file structure
            $file_data = array(
              'url' => $elements['files']['url'],
              'name' => basename($elements['files']['url']),
              'type' => '11', // Default to brochure
              'description' => 'Brochure'
            );
            $files[] = $file_data;
          } else {
            // Assume it's a complete file structure
            $files[] = $elements['files'];
          }
        }
      } else {
        // Handle case where files element is extracted as a simple URL string
        if (is_string($elements['files']) && filter_var($elements['files'], FILTER_VALIDATE_URL)) {
          $files[] = array(
            'url' => $elements['files'],
            'name' => basename($elements['files']),
            'type' => '11', // Default to brochure
            'description' => 'File'
          );
        }
      }
    }

    // Handle EPCs
    $epcs = array();
    if (isset($elements['epcs'])) {
      if (is_array($elements['epcs'])) {
        $epcs = $elements['epcs'];
      } elseif (is_string($elements['epcs'])) {
        // Try to decode JSON first
        $decoded = json_decode($elements['epcs'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $epcs = is_array($decoded) ? $decoded : array($decoded);
        } else {
          // Handle comma-separated string
          $epcs = array_map('trim', explode(',', $elements['epcs']));
        }
      }
    }

    // Handle videos
    $videos = array();
    if (isset($elements['videos'])) {
      if (is_array($elements['videos'])) {
        $videos = $elements['videos'];
      } elseif (is_string($elements['videos'])) {
        // Try to decode JSON first
        $decoded = json_decode($elements['videos'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $videos = is_array($decoded) ? $decoded : array($decoded);
        } else {
          // Handle comma-separated string
          $videos = array_map('trim', explode(',', $elements['videos']));
        }
      }
    }

    // Handle videos detail
    $videos_detail = array();
    if (isset($elements['videos_detail'])) {
      if (is_array($elements['videos_detail'])) {
        $videos_detail = $elements['videos_detail'];
      } elseif (is_string($elements['videos_detail'])) {
        // Try to decode JSON first
        $decoded = json_decode($elements['videos_detail'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $videos_detail = is_array($decoded) ? $decoded : array($decoded);
        } else {
          // Handle comma-separated string
          $videos_detail = array_map('trim', explode(',', $elements['videos_detail']));
        }
      }
    }

    $media_fields = array(
      'images' => $images,
      'original_images' => $original_images,
      'files' => $files,
      'epcs' => $epcs,
      'videos' => $videos,
      'videos_detail' => $videos_detail,
    );

    return array_merge($data, $media_fields);
  }

  /**
   * Process types data from XML elements
   * Extracts only the type names (text content), ignoring IDs
   */
  private static function process_types_data(array $elements): array {
    $types = array();

    if (!isset($elements['types'])) {
      return $types;
    }

    $types_data = $elements['types'];

    // Handle types structure: <types><type id="1">Office</type></types>
    if (is_array($types_data) && isset($types_data['type'])) {
      $type_elements = $types_data['type'];

      // Handle single type or multiple types
      if (is_array($type_elements)) {
        // Check if it's a single type (has 'name' key) or array of types
        if (isset($type_elements['name'])) {
          // Single type with name/id structure
          $types[] = trim($type_elements['name']);
        } else {
          // Multiple types
          foreach ($type_elements as $type_element) {
            if (is_array($type_element) && isset($type_element['name'])) {
              // Type with name/id structure - extract only the name
              $types[] = trim($type_element['name']);
            } elseif (is_string($type_element) && !empty(trim($type_element))) {
              // Simple string type
              $types[] = trim($type_element);
            }
          }
        }
      } else {
        // Single type element as string
        if (is_string($type_elements) && !empty(trim($type_elements))) {
          $types[] = trim($type_elements);
        }
      }
    }

    // Clean and deduplicate
    return array_unique(array_filter($types, function ($type) {
      return !empty(trim($type));
    }));
  }

  /**
   * Process availabilities data from XML elements
   * Extracts only the availability names (text content), ignoring IDs
   */
  private static function process_availabilities_data(array $elements): array {
    $availabilities = array();

    if (!isset($elements['availabilities'])) {
      return $availabilities;
    }

    $availabilities_data = $elements['availabilities'];

    // Handle availabilities structure: <availabilities><type id="forsale">For Sale</type></availabilities>
    if (is_array($availabilities_data) && isset($availabilities_data['type'])) {
      $type_elements = $availabilities_data['type'];

      // Handle single availability or multiple availabilities
      if (is_array($type_elements)) {
        // Check if it's a single availability (has 'name' key) or array of availabilities
        if (isset($type_elements['name'])) {
          // Single availability with name/id structure
          $availabilities[] = trim($type_elements['name']);
        } else {
          // Multiple availabilities
          foreach ($type_elements as $type_element) {
            if (is_array($type_element) && isset($type_element['name'])) {
              // Availability with name/id structure - extract only the name
              $availabilities[] = trim($type_element['name']);
            } elseif (is_string($type_element) && !empty(trim($type_element))) {
              // Simple string availability
              $availabilities[] = trim($type_element);
            }
          }
        }
      } else {
        // Single availability element as string
        if (is_string($type_elements) && !empty(trim($type_elements))) {
          $availabilities[] = trim($type_elements);
        }
      }
    }

    // Clean and deduplicate
    return array_unique(array_filter($availabilities, function ($availability) {
      return !empty(trim($availability));
    }));
  }

  /**
   * Process key selling points data from XML elements
   * Extracts each selling point as plain text
   */
  private static function process_key_selling_points_data(array $elements): array {
    $key_selling_points = array();

    if (!isset($elements['key_selling_points'])) {
      return $key_selling_points;
    }

    $ksp_data = $elements['key_selling_points'];

    // Handle key_selling_points structure: <key_selling_points><key_selling_point>Text</key_selling_point></key_selling_points>
    if (is_array($ksp_data) && isset($ksp_data['key_selling_point'])) {
      $ksp_elements = $ksp_data['key_selling_point'];

      // Handle single point or multiple points
      if (is_array($ksp_elements)) {
        foreach ($ksp_elements as $ksp_element) {
          if (is_string($ksp_element) && !empty(trim($ksp_element))) {
            $key_selling_points[] = trim($ksp_element);
          }
        }
      } else {
        // Single selling point element
        if (is_string($ksp_elements) && !empty(trim($ksp_elements))) {
          $key_selling_points[] = trim($ksp_elements);
        }
      }
    }

    // Clean and filter empty values
    return array_filter($key_selling_points, function ($point) {
      return !empty(trim($point));
    });
  }

  /**
   * Process amenities specifications data from XML elements
   * Extracts label/value pairs
   */
  private static function process_amenities_specifications_data(array $elements): array {
    $amenities_specs = array();

    if (!isset($elements['amenities_specifications'])) {
      return $amenities_specs;
    }

    $amenities_data = $elements['amenities_specifications'];

    // Handle amenities_specifications structure: <amenities_specifications><amenities_specification><label>Label</label><value>Value</value></amenities_specification></amenities_specifications>
    if (is_array($amenities_data) && isset($amenities_data['amenities_specification'])) {
      $spec_elements = $amenities_data['amenities_specification'];

      // Handle single spec or multiple specs
      if (is_array($spec_elements)) {
        // Check if it's an array of specs or a single spec
        if (isset($spec_elements['label']) && isset($spec_elements['value'])) {
          // Single specification
          $amenities_specs[] = array(
            'label' => trim($spec_elements['label']),
            'value' => trim($spec_elements['value'])
          );
        } else {
          // Multiple specifications
          foreach ($spec_elements as $spec) {
            if (is_array($spec) && isset($spec['label']) && isset($spec['value'])) {
              $amenities_specs[] = array(
                'label' => trim($spec['label']),
                'value' => trim($spec['value'])
              );
            }
          }
        }
      }
    }

    return $amenities_specs;
  }

  /**
   * Process floor units data
   */
  private static function process_floor_units_data(array $elements): array {
    $floor_units = array();
    if (isset($elements['floor_units']) && is_array($elements['floor_units'])) {
      if (isset($elements['floor_units']['floor_unit'])) {
        // Handle single floor unit or multiple floor units
        if (is_array($elements['floor_units']['floor_unit'])) {
          foreach ($elements['floor_units']['floor_unit'] as $index => $floor_unit) {
            if (is_array($floor_unit)) {
              // Process the floor unit to extract both formatted and numeric values
              $processed_floor_unit = self::process_floor_unit_data($floor_unit);
              $floor_units[] = $processed_floor_unit;
            }
          }
        } else {
          // Single floor unit
          if (is_array($elements['floor_units']['floor_unit'])) {
            // Process the floor unit to extract both formatted and numeric values
            $processed_floor_unit = self::process_floor_unit_data($elements['floor_units']['floor_unit']);
            $floor_units[] = $processed_floor_unit;
          }
        }
      }
    }

    return $floor_units;
  }

  /**
   * Calculate size range with fallback logic:
   * 1. Aggregate floor units (min = smallest unit, max = largest unit)
   * 2. Use unit size fields if no floor units
   * 3. Mark as null if neither available
   *
   * NOTE: This should NOT include total property size in unit ranges
   */
  private static function calculate_size_range(array $elements, array $floor_units): array {
    $size_min = null;
    $size_max = null;

    // Priority 1: Aggregate floor units ONLY
    if (!empty($floor_units)) {
      $floor_sizes = array();
      foreach ($floor_units as $floor_unit) {
        if (isset($floor_unit['total_sqft_numeric']) && $floor_unit['total_sqft_numeric'] > 0) {
          $floor_sizes[] = intval($floor_unit['total_sqft_numeric']);
        }
      }

      if (!empty($floor_sizes)) {
        $size_min = min($floor_sizes);
        $size_max = max($floor_sizes);
        return array('min' => $size_min, 'max' => $size_max);
      }
    }

    // Priority 2: Use individual unit size fields (NOT total property size)
    $unit_size = self::extract_unit_size_range($elements);
    if ($unit_size) {
      return $unit_size;
    }

    // Priority 3: No unit size data available
    return array('min' => null, 'max' => null);
  }

  /**
   * Calculate price range with fallback logic:
   * 1. Aggregate floor unit pricing (min = cheapest, max = highest)
   * 2. Use main property pricing if no floor units
   * 3. Mark as POA if neither available
   */
  private static function calculate_price_range(array $elements, array $floor_units, array $availabilities): array {
    $price_min = null;
    $price_max = null;
    $price_type = 'poa';

    // Priority 1: Aggregate floor unit pricing
    if (!empty($floor_units)) {
      $floor_prices = array();
      foreach ($floor_units as $floor_unit) {
        if (isset($floor_unit['rent_sqft_numeric']) && $floor_unit['rent_sqft_numeric'] > 0) {
          $floor_prices[] = intval($floor_unit['rent_sqft_numeric']);
        }
      }

      if (!empty($floor_prices)) {
        $price_min = min($floor_prices);
        $price_max = max($floor_prices);
        $price_type = 'per_sqft';
        return array('min' => $price_min, 'max' => $price_max, 'type' => $price_type);
      }
    }

    // Priority 2: Use main property pricing
    $main_price = self::extract_main_property_price($elements, $availabilities);
    if ($main_price) {
      return $main_price;
    }

    // Priority 3: No price data available
    return array('min' => null, 'max' => null, 'type' => 'poa');
  }

  /**
   * Extract unit size range from various XML elements (excludes total property size)
   */
  private static function extract_unit_size_range(array $elements): ?array {
    // Look for unit size fields - exclude total_property_size as that's for the whole property
    $size_fields = ['size_from_sqft', 'size_to_sqft', 'size_from', 'size_to', 'unit_size_range'];

    foreach ($size_fields as $field) {
      if (isset($elements[$field])) {
        $size_data = self::parse_size_value($elements[$field]);
        if ($size_data) {
          return $size_data;
        }
      }
    }

    return null;
  }

  /**
   * Extract main property size from various XML elements (for total property size)
   */
  private static function extract_main_property_size(array $elements): ?array {
    // Look for total property size fields only
    $size_fields = ['total_property_size'];

    foreach ($size_fields as $field) {
      if (isset($elements[$field])) {
        $size_data = self::parse_size_value($elements[$field]);
        if ($size_data) {
          return $size_data;
        }
      }
    }

    return null;
  }

  /**
   * Extract main property price from various XML elements
   */
  private static function extract_main_property_price(array $elements, array $availabilities): ?array {
    // Determine price type based on availability
    $is_to_let = in_array('To Let', $availabilities) || in_array('tolet', $availabilities);
    $is_for_sale = in_array('For Sale', $availabilities) || in_array('forsale', $availabilities);

    // Look for price in various fields - based on actual XML structure
    $price_fields = ['rent', 'price', 'rent_components', 'price_components'];

    // Debug logging removed to prevent excessive log output

    foreach ($price_fields as $field) {
      if (isset($elements[$field])) {
        $price_data = self::parse_price_value($elements[$field], $is_to_let, $is_for_sale);
        if ($price_data) {
          return $price_data;
        }
      }
    }

    return null;
  }

  /**
   * Parse size value handling concatenated ranges and various formats
   */
  private static function parse_size_value($size_value): ?array {
    if (empty($size_value)) {
      return null;
    }

    $size_string = is_string($size_value) ? $size_value : strval($size_value);

    // Handle concatenated ranges like "29205840sqft" (2920-5840)
    if (preg_match('/^(\d{4,})(\d{4})sqft?$/i', $size_string, $matches)) {
      $full_number = $matches[1] . $matches[2];
      $length = strlen($full_number);

      if ($length >= 8) {
        // Split roughly in half
        $split_point = intval($length / 2);
        $min_size = intval(substr($full_number, 0, $split_point));
        $max_size = intval(substr($full_number, $split_point));

        if ($min_size > 0 && $max_size > 0 && $min_size <= $max_size) {
          return array('min' => $min_size, 'max' => $max_size);
        }
      }
    }

    // Handle range formats like "2920 - 5840 sqft" or "2920-5840"
    if (preg_match('/(\d+)\s*[-–]\s*(\d+)/u', $size_string, $matches)) {
      $min_size = intval($matches[1]);
      $max_size = intval($matches[2]);

      if ($min_size > 0 && $max_size > 0 && $min_size <= $max_size) {
        return array('min' => $min_size, 'max' => $max_size);
      }
    }

    // Handle single size value
    $numeric_size = intval(preg_replace('/[^0-9]/', '', $size_string));
    if ($numeric_size > 0) {
      return array('min' => $numeric_size, 'max' => $numeric_size);
    }

    return null;
  }

  /**
   * Parse price value handling various formats and determining type
   */
  private static function parse_price_value($price_value, bool $is_to_let, bool $is_for_sale): ?array {
    if (empty($price_value)) {
      return null;
    }

    $price_string = is_string($price_value) ? $price_value : strval($price_value);

    // Check for "On Application" or similar
    if (preg_match('/on\s+application|poa|price\s+on\s+application/i', $price_string)) {
      return array('min' => null, 'max' => null, 'type' => 'poa');
    }

    // Determine price type
    $price_type = 'poa';
    if (stripos($price_string, 'per sq ft') !== false || stripos($price_string, 'per sqft') !== false) {
      $price_type = 'per_sqft';
    } elseif (stripos($price_string, 'per annum') !== false || stripos($price_string, 'annually') !== false) {
      $price_type = 'per_annum';
    } elseif ($is_for_sale && !$is_to_let) {
      $price_type = 'total';
    } elseif ($is_to_let) {
      $price_type = 'per_sqft';
    }

    // Extract numeric values - handle ranges
    $numbers = array();
    if (preg_match_all('/£?(\d+(?:,\d{3})*(?:\.\d{2})?)/u', $price_string, $matches)) {
      foreach ($matches[1] as $match) {
        $numeric_value = floatval(str_replace(',', '', $match));
        if ($numeric_value > 0) {
          $numbers[] = intval($numeric_value);
        }
      }
    }

    if (!empty($numbers)) {
      if (count($numbers) == 1) {
        return array('min' => $numbers[0], 'max' => $numbers[0], 'type' => $price_type);
      } else {
        return array('min' => min($numbers), 'max' => max($numbers), 'type' => $price_type);
      }
    }

    return null;
  }

  /**
   * Process properties in batches
   */
  private static function process_properties(array $properties, int $batch_size, bool $force_update, bool $is_property_only = false): array {
    $stats = array(
      'added' => 0,
      'updated' => 0,
      'removed' => 0,
      'skipped' => 0,
    );

    $batches = array_chunk($properties, $batch_size);
    $total_batches = count($batches);
    $current_batch = 0;



    foreach ($batches as $batch) {
      $current_batch++;

      foreach ($batch as $index => $property_data) {
        $property_id = $property_data['id'] ?? 'unknown';

        try {
          $result = self::process_single_property($property_data, $force_update, $is_property_only);
          $stats[$result]++;
        } catch (\Exception $e) {
          $stats['skipped']++;
        }
      }

      // Small delay between batches to prevent server overload
      if (count($batches) > 1) {
        usleep(100000); // 0.1 second delay
      }
    }

    return $stats;
  }

  /**
   * Process a single property
   */
  private static function process_single_property(array $property_data, bool $force_update, bool $is_property_only = false): string {
    if (empty($property_data['id'])) {
      return 'skipped';
    }

    $property_id = $property_data['id'];

    $existing_property = Property::get_by_external_id($property_data['id']);

    if ($existing_property) {

      // Check if update is needed
      if (!$force_update) {
        $lastmod = get_post_meta($existing_property->ID, '_kato_sync_lastmod', true);
        $feed_lastmod = $property_data['last_updated'] ?? '';


        if ($lastmod && $feed_lastmod && $lastmod >= $feed_lastmod) {
          return 'skipped';
        } else {
        }
      } else {
      }
    } else {
    }

    $post_id = Property::create_or_update($property_data);

    if ($post_id && !is_wp_error($post_id)) {
      // Normalise and persist to custom tables and view-model
      try {
        $normalized = \KatoSync\Sync\Normalizer::normalize($property_data);
        \KatoSync\Repository\PropertyRepository::upsert($post_id, $normalized, $property_data);
      } catch (\Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
          \error_log('Kato Sync upsert failed for post ' . $post_id . ': ' . $e->getMessage());
        }
      }

      // Set taxonomies
      self::set_property_taxonomies($post_id, $property_data);

      // Process images
      \KatoSync\Sync\ImageProcessor::process_property_images($post_id, $property_data, $is_property_only);

      $result = $existing_property ? 'updated' : 'added';
      return $result;
    }

    return 'skipped';
  }

  /**
   * Set property taxonomies
   */
  private static function set_property_taxonomies(int $post_id, array $property_data): void {
    $property_id = $property_data['id'] ?? 'unknown';

    // Set property type taxonomy - handle multiple types
    if (!empty($property_data['types'])) {
      $type_values = array();

      if (is_array($property_data['types'])) {
        // Check if types contains a 'type' key with multiple type objects
        if (isset($property_data['types']['type']) && is_array($property_data['types']['type'])) {
          // Handle multiple type objects under types/type structure
          foreach ($property_data['types']['type'] as $type) {
            if (is_array($type) && isset($type['name'])) {
              // Type object with name/id structure
              $type_values[] = $type['name'];
            } elseif (is_string($type) && !empty($type)) {
              // Simple string type - skip if it's just a numeric ID
              if (!is_numeric($type) || strlen($type) > 3) {
                $type_values[] = $type;
              }
            }
          }
        } else {
          // Handle array of types directly
          foreach ($property_data['types'] as $type) {
            if (is_array($type) && isset($type['name'])) {
              // Type object with name/id structure
              $type_values[] = $type['name'];
            } elseif (is_string($type) && !empty($type)) {
              // Simple string type - skip if it's just a numeric ID
              if (!is_numeric($type) || strlen($type) > 3) {
                $type_values[] = $type;
              }
            }
          }
        }
      } else {
        // Handle string type
        $type_values = array($property_data['types']);
      }

      if (!empty($type_values)) {
        try {
          wp_set_object_terms($post_id, $type_values, 'kato_property_type');
        } catch (\Exception $e) {
        }
      }
    } elseif (!empty($property_data['property_type'])) {
      // Fallback to property_type field
      try {
        wp_set_object_terms($post_id, $property_data['property_type'], 'kato_property_type');
      } catch (\Exception $e) {
      }
    }

    // Set location taxonomy
    if (!empty($property_data['county'])) {
      try {
        wp_set_object_terms($post_id, $property_data['county'], 'kato_location');
      } catch (\Exception $e) {
      }
    }

    // Set availability taxonomy - handle multiple availabilities
    if (!empty($property_data['availabilities'])) {
      $availability_names = array();

      if (is_array($property_data['availabilities'])) {
        // Handle array of availabilities
        foreach ($property_data['availabilities'] as $availability) {
          if (is_array($availability) && isset($availability['name'])) {
            // Extract name from structured data
            $availability_names[] = $availability['name'];
          } elseif (is_string($availability) && !empty($availability)) {
            // Handle simple string values
            $availability_names[] = $availability;
          }
        }
      } else {
        // Handle string availability
        $availability_names = array($property_data['availabilities']);
      }

      // Clean and deduplicate availability names
      $availability_names = array_unique(array_filter($availability_names, function ($item) {
        return is_string($item) && !empty(trim($item));
      }));

      if (!empty($availability_names)) {
        try {
          wp_set_object_terms($post_id, $availability_names, 'kato_availability');
        } catch (\Exception $e) {
        }
      }
    }
  }

  /**
   * Parse comma-separated values into array
   */
  private static function parse_comma_separated_values($value): array {
    if (is_array($value)) {
      return $value;
    }

    if (is_string($value)) {
      return array_map('trim', explode(',', $value));
    }

    return array();
  }

  /**
   * Test feed connectivity
   */
  private static function test_feed(string $url): array {
    $start_time = microtime(true);


    $response = wp_remote_get($url, array(
      'timeout' => 30,
      'user-agent' => 'Kato Sync WordPress Plugin/' . KATO_SYNC_VERSION,
    ));

    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);

    if (is_wp_error($response)) {
      return array(
        'success' => false,
        'message' => $response->get_error_message(),
        'response_time' => $response_time,
      );
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);


    if ($status_code !== 200) {
      return array(
        'success' => false,
        'message' => sprintf(__('HTTP %d: %s', 'kato-sync'), $status_code, wp_remote_retrieve_response_message($response)),
        'response_time' => $response_time,
      );
    }

    if (empty($body)) {
      return array(
        'success' => false,
        'message' => __('Empty response received.', 'kato-sync'),
        'response_time' => $response_time,
      );
    }

    // Try to parse XML
    $xml = simplexml_load_string($body);
    if (!$xml) {
      return array(
        'success' => false,
        'message' => __('Invalid XML format.', 'kato-sync'),
        'response_time' => $response_time,
      );
    }

    return array(
      'success' => true,
      'message' => __('Feed is accessible and contains valid XML.', 'kato-sync'),
      'response_time' => $response_time,
      'content_length' => strlen($body),
    );
  }

  /**
   * Log sync results
   */
  private static function log_sync(array $log_entry): void {
    $logs = get_option('kato_sync_sync_logs', array());
    $logs[] = $log_entry;

    // Keep only the last 100 logs
    if (count($logs) > 100) {
      $logs = array_slice($logs, -100);
    }

    update_option('kato_sync_sync_logs', $logs);
  }
}
