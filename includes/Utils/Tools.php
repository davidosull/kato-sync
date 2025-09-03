<?php

namespace KatoSync\Utils;

/**
 * Utility tools for the Kato Sync plugin
 */
class Tools {

  /**
   * Get property meta data for filtering
   */
  public static function get_property_meta_data(int $post_id): array {
    $meta_data = array();

    // Basic meta fields
    $meta_fields = array(
      'types',
      'location',
      'specifications',
      'featured',
      'availabilities',
      'total_size_sqft',
      'price_per_sqft_min',
      'price_per_sqft_max',
      'total_monthly_min',
      'total_monthly_max',
      'total_yearly_min',
      'total_yearly_max',
      'available_units_count',
      'floor_units',
      // Price fields
      'sale_price',
      'sale_price_numeric',
      'sale_price_on_application',
      'price_components',
      'rent',
      'rent_numeric',
      'rent_on_application',
      'rent_components',
      'service_charge'
    );

    foreach ($meta_fields as $field) {
      $value = \get_post_meta($post_id, '_kato_sync_' . $field, true);
      if (!empty($value)) {
        // Unserialize arrays and objects
        if (\is_serialized($value)) {
          $value = \maybe_unserialize($value);
        }
        $meta_data[$field] = $value;
      }
    }

    return $meta_data;
  }

  /**
   * Get all unique values for a specific meta field across all properties
   */
  public static function get_unique_meta_values(string $meta_key): array {
    global $wpdb;

    $results = $wpdb->get_col($wpdb->prepare(
      "SELECT DISTINCT meta_value
       FROM {$wpdb->postmeta}
       WHERE meta_key = %s
       AND meta_value != ''
       AND meta_value IS NOT NULL",
      '_kato_sync_' . $meta_key
    ));

    $values = array();
    foreach ($results as $result) {
      // Handle both old serialized data and new natural arrays
      if (\is_serialized($result)) {
        // Old format: serialized data
        $unserialized = \maybe_unserialize($result);
        if (is_array($unserialized)) {
          foreach ($unserialized as $item) {
            if (is_string($item) && $item !== '') {
              $values[] = $item;
            }
          }
        } elseif (is_string($unserialized) && $unserialized !== '') {
          $values[] = $unserialized;
        }
      } elseif (is_array($result)) {
        // New format: natural arrays
        foreach ($result as $item) {
          if (is_string($item) && $item !== '') {
            $values[] = $item;
          }
        }
      } elseif (is_string($result) && $result !== '') {
        $values[] = $result;
      }
    }

    $unique_values = array_unique($values);
    sort($unique_values, SORT_NATURAL | SORT_FLAG_CASE);
    return $unique_values;
  }

  /**
   * Get properties filtered by meta criteria
   */
  public static function get_filtered_properties(array $filters = array()): array {
    $args = array(
      'post_type' => 'kato-property',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'meta_query' => array(),
    );

    // Type filter
    if (!empty($filters['types'])) {
      $args['meta_query'][] = array(
        'key' => '_kato_sync_types',
        'value' => $filters['types'],
        'compare' => 'LIKE',
      );
    }

    // Location filter
    if (!empty($filters['location'])) {
      $args['meta_query'][] = array(
        'key' => '_kato_sync_location',
        'value' => $filters['location'],
        'compare' => '=',
      );
    }

    // Availability filter
    if (!empty($filters['availabilities'])) {
      $args['meta_query'][] = array(
        'key' => '_kato_sync_availabilities',
        'value' => $filters['availabilities'],
        'compare' => 'LIKE',
      );
    }

    // Featured filter
    if (isset($filters['featured'])) {
      $args['meta_query'][] = array(
        'key' => '_kato_sync_featured',
        'value' => $filters['featured'] ? '1' : '0',
        'compare' => '=',
      );
    }

    // Size range filter
    if (!empty($filters['size_min']) || !empty($filters['size_max'])) {
      $size_query = array('key' => '_kato_sync_total_size_sqft');
      if (!empty($filters['size_min'])) {
        $size_query['value'] = $filters['size_min'];
        $size_query['compare'] = '>=';
        $size_query['type'] = 'NUMERIC';
      }
      if (!empty($filters['size_max'])) {
        $size_query['value'] = $filters['size_max'];
        $size_query['compare'] = '<=';
        $size_query['type'] = 'NUMERIC';
      }
      $args['meta_query'][] = $size_query;
    }

    // Price range filter
    if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
      $price_query = array('key' => '_kato_sync_price_per_sqft_min');
      if (!empty($filters['price_min'])) {
        $price_query['value'] = $filters['price_min'];
        $price_query['compare'] = '>=';
        $price_query['type'] = 'DECIMAL';
      }
      if (!empty($filters['price_max'])) {
        $price_query['value'] = $filters['price_max'];
        $price_query['compare'] = '<=';
        $price_query['type'] = 'DECIMAL';
      }
      $args['meta_query'][] = $price_query;
    }

    // Available units filter
    if (!empty($filters['available_units_min'])) {
      $args['meta_query'][] = array(
        'key' => '_kato_sync_available_units_count',
        'value' => $filters['available_units_min'],
        'compare' => '>=',
        'type' => 'NUMERIC',
      );
    }

    // Set relation for multiple meta queries
    if (count($args['meta_query']) > 1) {
      $args['meta_query']['relation'] = 'AND';
    }

    $query = new \WP_Query($args);
    return $query->posts;
  }

  /**
   * Format price for display
   */
  public static function format_price(float $price, string $currency = '£'): string {
    return $currency . number_format($price, 2);
  }

  /**
   * Format size for display
   */
  public static function format_size(int $size, string $unit = 'sqft'): string {
    return number_format($size) . ' ' . $unit;
  }

  /**
   * Get price range string
   */
  public static function get_price_range(float $min, float $max, string $currency = '£'): string {
    if ($min === $max) {
      return self::format_price($min, $currency) . '/sqft';
    }
    return self::format_price($min, $currency) . ' - ' . self::format_price($max, $currency) . '/sqft';
  }



  /**
   * AJAX handler for removing all properties
   */
  public static function ajax_remove_all_properties(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      // Get all property posts with detailed status breakdown
      $all_properties = get_posts(array(
        'post_type' => 'kato-property',
        'numberposts' => -1,
        'post_status' => 'any'
      ));

      $status_counts = array();
      $deleted_count = 0;
      $failed_count = 0;

      foreach ($all_properties as $property) {
        $status = $property->post_status;
        if (!isset($status_counts[$status])) {
          $status_counts[$status] = 0;
        }
        $status_counts[$status]++;

        $result = wp_delete_post($property->ID, true);
        if ($result) {
          $deleted_count++;
        } else {
          $failed_count++;
        }
      }

      // Reset sync status
      delete_option('kato_sync_last_sync');
      delete_option('kato_sync_next_sync');

      // Clear sync logs
      $logs = get_option('kato_sync_sync_logs', array());
      $logs_count = count($logs);
      delete_option('kato_sync_sync_logs');

      // Create detailed message
      $status_details = array();
      foreach ($status_counts as $status => $count) {
        $status_details[] = sprintf('%d %s', $count, $status);
      }

      $message = sprintf(
        __('Successfully removed %d properties (%s), cleared %d sync logs, and reset sync status.', 'kato-sync'),
        $deleted_count,
        implode(', ', $status_details),
        $logs_count
      );

      if ($failed_count > 0) {
        $message .= sprintf(' %d properties failed to delete.', $failed_count);
      }

      wp_send_json_success(array(
        'message' => $message,
        'deleted_count' => $deleted_count,
        'failed_count' => $failed_count,
        'status_breakdown' => $status_counts
      ));
    } catch (\Exception $e) {
      wp_send_json_error(__('Error removing properties: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * AJAX handler for cleaning up logs
   */
  public static function ajax_cleanup_logs(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $settings = get_option('kato_sync_settings', array());
      $days_to_keep = $settings['cleanup_logs_after_days'] ?? 30;
      $cutoff_time = time() - ($days_to_keep * 24 * 60 * 60);

      $logs = get_option('kato_sync_sync_logs', array());
      $original_count = count($logs);
      $logs = array_filter($logs, function ($log) use ($cutoff_time) {
        return ($log['timestamp'] ?? 0) >= $cutoff_time;
      });

      update_option('kato_sync_sync_logs', $logs);
      $deleted_count = $original_count - count($logs);

      wp_send_json_success(sprintf(
        __('Successfully cleaned up %d old log entries.', 'kato-sync'),
        $deleted_count
      ));
    } catch (\Exception $e) {
      wp_send_json_error(__('Error cleaning up logs: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * AJAX handler for resetting sync status
   */
  public static function ajax_reset_sync_status(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      delete_option('kato_sync_last_sync');
      delete_option('kato_sync_next_sync');

      wp_send_json_success(__('Sync status has been reset successfully.', 'kato-sync'));
    } catch (\Exception $e) {
      wp_send_json_error(__('Error resetting sync status: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * AJAX handler for exporting settings
   */
  public static function ajax_export_settings(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $settings = get_option('kato_sync_settings', array());
      $export_data = json_encode($settings, JSON_PRETTY_PRINT);

      wp_send_json_success($export_data);
    } catch (\Exception $e) {
      wp_send_json_error(__('Error exporting settings: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * AJAX handler for importing settings
   */
  public static function ajax_import_settings(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(__('No file uploaded or upload error occurred.', 'kato-sync'));
      }

      $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
      $settings = json_decode($file_content, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(__('Invalid JSON file format.', 'kato-sync'));
      }

      update_option('kato_sync_settings', $settings);

      wp_send_json_success(__('Settings imported successfully.', 'kato-sync'));
    } catch (\Exception $e) {
      wp_send_json_error(__('Error importing settings: ', 'kato-sync') . $e->getMessage());
    }
  }



  /**
   * Check auto-sync cron status and diagnose issues
   */
  public static function check_auto_sync_status(): array {
    $settings = get_option('kato_sync_settings', array());
    $next_scheduled = wp_next_scheduled('kato_sync_auto_sync');
    $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    $last_sync = get_option('kato_sync_last_sync');
    $next_sync_option = get_option('kato_sync_next_sync');

    $status = array(
      'auto_sync_enabled' => !empty($settings['auto_sync_enabled']),
      'frequency' => $settings['auto_sync_frequency'] ?? '1hour',
      'feed_url_configured' => !empty($settings['feed_url']),
      'cron_disabled' => $cron_disabled,
      'next_scheduled' => $next_scheduled,
      'last_sync' => $last_sync,
      'next_sync_option' => $next_sync_option,
      'issues' => array()
    );

    // Check for issues
    if ($status['auto_sync_enabled'] && !$status['feed_url_configured']) {
      $status['issues'][] = 'Auto-sync is enabled but no feed URL is configured';
    }

    if ($status['auto_sync_enabled'] && $status['cron_disabled']) {
      $status['issues'][] = 'WP Cron is disabled - auto-sync will not run automatically';
    }

    if ($status['auto_sync_enabled'] && !$next_scheduled) {
      $status['issues'][] = 'Auto-sync is enabled but no cron job is scheduled';
    }

    if ($next_scheduled && $next_scheduled < time()) {
      $status['issues'][] = 'Next scheduled sync is in the past - cron may not be running';
    }

    return $status;
  }

  /**
   * AJAX handler for checking auto-sync status
   */
  public static function ajax_check_auto_sync(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $status = self::check_auto_sync_status();
      wp_send_json_success($status);
    } catch (\Exception $e) {
      wp_send_json_error(__('Error checking auto-sync status: ', 'kato-sync') . $e->getMessage());
    }
  }





  /**
   * Get property data for display in modal
   */
  public static function ajax_get_property_data(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    $property_id = intval($_POST['property_id'] ?? 0);

    if (!$property_id) {
      wp_send_json_error(__('Invalid property ID.', 'kato-sync'));
    }

    try {
      $property = get_post($property_id);

      if (!$property || $property->post_type !== 'kato-property') {
        wp_send_json_error(__('Property not found.', 'kato-sync'));
      }

      $view_json = get_post_meta($property_id, '_kato_view', true);
      $all_data = is_string($view_json) ? json_decode($view_json, true) : [];

      // Formatting helpers
      $dash = function () {
        return '—';
      };
      $fmt_scalar = function ($v) use ($dash) {
        if ($v === null || $v === '') return $dash();
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        // Clean up any Unicode currency symbols in string values
        if (is_string($v)) {
          $v = str_replace(['u00a3', '\\u00a3', '\u00a3', 'u00A3', '\\u00A3', '\u00A3'], '£', $v);
          // Handle double newlines and convert to proper line breaks for display
          $v = str_replace('nn', "\n\n", $v);
          $v = nl2br($v);
        }
        return (string)$v;
      };
      $fmt_currency = function ($v) use ($dash) {
        if ($v === null || $v === '') return $dash();
        // If it's already a formatted string with currency symbol, return as-is
        if (is_string($v) && (strpos($v, '£') !== false || strpos($v, 'POA') !== false)) {
          return $v;
        }
        // If it's a numeric value, format it
        if (is_numeric($v)) {
          return '£' . number_format((float)$v, 2);
        }
        return $dash();
      };
      $fmt_array = function ($v) use ($dash) {
        if (empty($v) || !is_array($v)) return $dash();
        $items = [];
        foreach ($v as $item) {
          if (is_scalar($item)) $items[] = (string)$item;
          elseif (is_array($item)) $items[] = implode(' ', array_map('strval', array_filter($item, 'is_scalar')));
        }
        $items = array_filter($items, function ($s) {
          return $s !== '';
        });
        return empty($items) ? $dash() : implode(', ', $items);
      };

      // Humanise price type tokens
      $humanise_price_type = function ($v) use ($dash) {
        if ($v === null || $v === '') return $dash();
        $map = array(
          'per_sqft' => 'per sq ft',
          'per_annum' => 'per annum',
          'total' => 'total',
          'poa' => 'POA',
          'per desk' => 'per desk',
          'per month' => 'per month',
          'per year' => 'per year'
        );
        $v = (string)$v;
        return $map[$v] ?? $v;
      };

      // Derive title for modal header
      $modal_title = \get_the_title($property_id);
      if (!$modal_title && !empty($all_data['location']['name'])) {
        $modal_title = $all_data['location']['name'];
      } elseif (!$modal_title && !empty($all_data['location'])) {
        $loc = $all_data['location'];
        $parts = array_filter(array($loc['address1'] ?? null, $loc['postcode'] ?? null));
        $modal_title = implode(' ', $parts);
      }

      // Build modal content
      $html = '<div class="kato-sync-property-data">';

      // Kato Meta
      if (!empty($all_data['kato_meta'])) {
        $km = $all_data['kato_meta'];
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Kato Meta', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';
        $html .= '<tr><td><strong>' . __('ID', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($km['external_id'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Created', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($km['created_at'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Imported', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($km['imported_at'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Updated', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($km['last_updated'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Featured', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($km['is_featured'] ?? null)) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
      }

      // Property Meta
      if (!empty($all_data['property'])) {
        $p = $all_data['property'];
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Property Details', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';
        $html .= '<tr><td><strong>' . __('Status', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['status'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Type', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_array($p['types'] ?? [])) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Property Type', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['property_type'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Availabilities', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_array($p['availabilities'] ?? [])) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Summary', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['specification_summary'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Promo', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['specification_promo'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Description', 'kato-sync') . '</strong></td><td>' . $fmt_scalar($p['description'] ?? null) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Features', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['features'] ?? null)) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Fitted', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['fitted'] ?? null)) . '</td></tr>';
        if (!empty($p['fitted_comment'])) {
          $html .= '<tr><td><strong>' . __('Fitted Comment', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($p['fitted_comment'])) . '</td></tr>';
        }
        if (!empty($p['url'])) {
          $html .= '<tr><td><strong>' . __('Property URL', 'kato-sync') . '</strong></td><td><a href="' . esc_url($p['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($p['url']) . '</a></td></tr>';
        }
        if (!empty($p['particulars_url'])) {
          $html .= '<tr><td><strong>' . __('Particulars URL', 'kato-sync') . '</strong></td><td><a href="' . esc_url($p['particulars_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($p['particulars_url']) . '</a></td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
      }

      // Location
      if (!empty($all_data['location'])) {
        $l = $all_data['location'];
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Location', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';

        // Show name above address1 if different from address1
        if (!empty($l['name']) && $l['name'] !== ($l['address1'] ?? '')) {
          $html .= '<tr><td><strong>' . __('Name', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($l['name'])) . '</td></tr>';
        }

        foreach (['address1', 'address2', 'city', 'town', 'county', 'postcode'] as $k) {
          $value = $l[$k] ?? null;
          // Show all fields, using em-dash for empty values
          $html .= '<tr><td><strong>' . esc_html(ucwords(str_replace('_', ' ', $k))) . '</strong></td><td>' . esc_html($fmt_scalar($value)) . '</td></tr>';
        }

        // Coordinates
        if (!empty($l['lat']) || !empty($l['lng'])) {
          $lat = $fmt_scalar($l['lat'] ?? null);
          $lng = $fmt_scalar($l['lng'] ?? null);
          $html .= '<tr><td><strong>' . __('Coordinates', 'kato-sync') . '</strong></td><td>' . esc_html($lat . ', ' . $lng) . '</td></tr>';
        }

        // Location text (CDATA long text)
        if (!empty($l['location_text'])) {
          $html .= '<tr><td><strong>' . __('Location Description', 'kato-sync') . '</strong></td><td>' . $fmt_scalar($l['location_text']) . '</td></tr>';
        } elseif (!empty($l['location'])) {
          // Check for location field in normalized data
          $html .= '<tr><td><strong>' . __('Location Description', 'kato-sync') . '</strong></td><td>' . $fmt_scalar($l['location']) . '</td></tr>';
        } else {
          // Check raw data for location field
          $raw_json = \get_post_meta($property_id, '_kato_raw_original', true);
          $raw_data = is_string($raw_json) ? json_decode($raw_json, true) : array();
          $raw_location = $raw_data['location'] ?? null;
          if (!empty($raw_location)) {
            $html .= '<tr><td><strong>' . __('Location Description', 'kato-sync') . '</strong></td><td>' . $fmt_scalar($raw_location) . '</td></tr>';
          }
        }

        // Travel times
        if (!empty($l['travel_times'])) {
          $html .= '<tr><td><strong>' . __('Travel Times', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($l['travel_times'])) . '</td></tr>';
        }

        // Street view grid
        if (!empty($l['street_view']) && is_array($l['street_view'])) {
          $sv = $l['street_view'];
          $grid = '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 5px;">';
          $map = array(
            'lat' => __('Latitude:', 'kato-sync'),
            'long' => __('Longitude:', 'kato-sync'),
            'pano' => __('Panorama:', 'kato-sync'),
            'zoom' => __('Zoom:', 'kato-sync'),
            'pitch' => __('Pitch:', 'kato-sync'),
            'heading' => __('Heading:', 'kato-sync'),
          );
          foreach ($map as $key => $label) {
            if (isset($sv[$key]) && $sv[$key] !== '') {
              $grid .= '<div><strong>' . $label . '</strong></div><div>' . esc_html((string)$sv[$key]) . '</div>';
            }
          }
          $grid .= '</div>';
          $html .= '<tr><td><strong>' . __('Street View', 'kato-sync') . '</strong></td><td>' . $grid . '</td></tr>';
        }

        // Submarkets (comma list)
        if (!empty($l['submarkets'])) {
          $html .= '<tr><td><strong>' . __('Submarkets', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_array($l['submarkets'])) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
      }

      // Pricing
      if (!empty($all_data['pricing'])) {
        $pr = $all_data['pricing'];
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Pricing', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';

        // Determine rent-only from availability or pricing data
        $has_rent_data = !empty($pr['rent']['from']) || !empty($pr['rent']['to']);
        $has_sale_data = !empty($pr['price_min']) || !empty($pr['price_max']) || !empty($pr['price']);
        $is_rent_only = $has_rent_data && !$has_sale_data;

        // Check if we have rent data from raw XML
        $raw_json = \get_post_meta($property_id, '_kato_raw_original', true);
        $raw_data = is_string($raw_json) ? json_decode($raw_json, true) : array();
        $raw_rent_display = $raw_data['rent'] ?? null;
        $has_raw_rent = !empty($raw_rent_display) && is_string($raw_rent_display);

        // Price type - show the actual price with methodology when available
        $price_type_to_display = $pr['price_type'] ?? null;

        // If we have raw rent data, use that
        if ($has_raw_rent) {
          $price_type_label = $fmt_scalar($raw_rent_display);
        } elseif (!empty($pr['rent']['metric']) && !empty($pr['rent']['from'])) {
          // Show actual price with metric
          $price_type_label = '£' . number_format((float)$pr['rent']['from'], 0) . ' per ' . $pr['rent']['metric'];
        } elseif (!empty($pr['rent']['metric'])) {
          // Fallback to just the metric
          $price_type_label = $humanise_price_type('per ' . $pr['rent']['metric']);
        } else {
          $price_type_label = $humanise_price_type($price_type_to_display);
        }

        $html .= '<tr><td><strong>' . __('Price Type', 'kato-sync') . '</strong></td><td>' . esc_html($price_type_label) . '</td></tr>';

        // Only show sale prices if we don't have rent data or if we have both
        if (!$is_rent_only && !$has_raw_rent) {
          if (!empty($pr['price'])) {
            $html .= '<tr><td><strong>' . __('Price', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['price'])) . '</td></tr>';
          }
          if (!empty($pr['price_min']) || !empty($pr['price_max'])) {
            // Use new range formatting logic
            $price_type = $pr['price_type'] ?? 'per_sqft';
            $price_range = \KatoSync\Utils\RangeFormatter::format_price_range($pr['price_min'], $pr['price_max'], $price_type);
            $html .= '<tr><td><strong>' . __('Price Range', 'kato-sync') . '</strong></td><td>' . esc_html($price_range['display']) . '</td></tr>';
          }
          if (!empty($pr['total_price'])) {
            $html .= '<tr><td><strong>' . __('Total Price', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['total_price'])) . '</td></tr>';
          }
        }

        // Price per sqft
        if (!empty($pr['price_per_sqft'])) {
          $html .= '<tr><td><strong>' . __('Price per sq ft', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['price_per_sqft'])) . '</td></tr>';
        }
        if (!empty($pr['price_per_sqft_min']) || !empty($pr['price_per_sqft_max'])) {
          $html .= '<tr><td><strong>' . __('Price per sq ft Range', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['price_per_sqft_min']) . ' - ' . $fmt_currency($pr['price_per_sqft_max'])) . '</td></tr>';
        }

        // Monthly/Yearly totals
        if (!empty($pr['total_monthly_min']) || !empty($pr['total_monthly_max'])) {
          $html .= '<tr><td><strong>' . __('Monthly Range', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['total_monthly_min']) . ' - ' . $fmt_currency($pr['total_monthly_max'])) . '</td></tr>';
        }
        if (!empty($pr['total_yearly_min']) || !empty($pr['total_yearly_max'])) {
          $html .= '<tr><td><strong>' . __('Yearly Range', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['total_yearly_min']) . ' - ' . $fmt_currency($pr['total_yearly_max'])) . '</td></tr>';
        }

        // Rent details - check for raw rent string first
        if ($has_raw_rent) {
          // Use the raw rent string from XML if available and clean up unicode
          $cleaned_rent = $fmt_scalar($raw_rent_display);
          $html .= '<tr><td><strong>' . __('Rent Range', 'kato-sync') . '</strong></td><td>' . $cleaned_rent . '</td></tr>';
        } elseif (!empty($pr['rent'])) {
          $r = $pr['rent'];
          if (!empty($r['from']) || !empty($r['to'])) {
            // Build rent display with proper metric
            $rent_display = '';
            if (!empty($r['from']) && !empty($r['to']) && $r['from'] !== $r['to']) {
              $rent_display = '£' . number_format((float)$r['from'], 2) . ' - £' . number_format((float)$r['to'], 2);
            } elseif (!empty($r['from'])) {
              $rent_display = '£' . number_format((float)$r['from'], 2);
            } elseif (!empty($r['to'])) {
              $rent_display = '£' . number_format((float)$r['to'], 2);
            }

            // Add metric if available
            if (!empty($r['metric']) && $rent_display) {
              $rent_display .= '/' . $r['metric'];
            }

            if ($rent_display) {
              $html .= '<tr><td><strong>' . __('Rent Range', 'kato-sync') . '</strong></td><td>' . esc_html($rent_display) . '</td></tr>';
            }
          }
        }

        // Only show rent metric if it's different from what's already shown in price type
        if (!empty($pr['rent']['metric']) && !$has_raw_rent && $price_type_to_display !== 'per ' . $pr['rent']['metric']) {
          $html .= '<tr><td><strong>' . __('Rent Metric', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['rent']['metric'])) . '</td></tr>';
        }
        if (!empty($pr['rent']['rates'])) {
          $html .= '<tr><td><strong>' . __('Rent Rates', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['rent']['rates'])) . '</td></tr>';
        }
        if (!empty($pr['rent']['comment'])) {
          $html .= '<tr><td><strong>' . __('Rent Comment', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['rent']['comment'])) . '</td></tr>';
        }
        if (isset($pr['rent']['on_application'])) {
          $html .= '<tr><td><strong>' . __('On Application', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['rent']['on_application'])) . '</td></tr>';
        }

        // Service charge
        if (!empty($pr['service_charge'])) {
          $sc = $pr['service_charge'];
          if (!empty($sc['amount'])) {
            $html .= '<tr><td><strong>' . __('Service Charge', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($sc['amount'])) . '</td></tr>';
          }
          if (!empty($sc['period'])) {
            $html .= '<tr><td><strong>' . __('SC Period', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($sc['period'])) . '</td></tr>';
          }
          if (!empty($sc['text'])) {
            $html .= '<tr><td><strong>' . __('SC Text', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($sc['text'])) . '</td></tr>';
          }
          if (!empty($sc['rates'])) {
            $html .= '<tr><td><strong>' . __('SC Rates', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($sc['rates'])) . '</td></tr>';
          }
        }

        // Other pricing fields
        if (!empty($pr['initial_yield'])) {
          $html .= '<tr><td><strong>' . __('Initial Yield', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['initial_yield'])) . '%</td></tr>';
        }
        if (!empty($pr['premium'])) {
          $html .= '<tr><td><strong>' . __('Premium', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($pr['premium'])) . '</td></tr>';
        }
        if (isset($pr['premium_nil'])) {
          $html .= '<tr><td><strong>' . __('Premium Nil', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['premium_nil'])) . '</td></tr>';
        }
        if (!empty($pr['parking_ratio'])) {
          $html .= '<tr><td><strong>' . __('Parking Ratio', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pr['parking_ratio'])) . '</td></tr>';
        }

        $html .= '</table>';
        $html .= '</div>';
      }

      // Business & Financial
      if (!empty($all_data['business_financial'])) {
        $bf = $all_data['business_financial'];
        $has_data = array_filter($bf, function ($v) {
          return $v !== null && $v !== '';
        });
        if (!empty($has_data)) {
          $html .= '<div class="kato-sync-data-section">';
          $html .= '<h3>' . __('Business & Financial', 'kato-sync') . '</h3>';
          $html .= '<table class="kato-sync-data-table">';
          if (!empty($bf['turnover'])) {
            $html .= '<tr><td><strong>' . __('Turnover', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($bf['turnover'])) . '</td></tr>';
          }
          if (!empty($bf['turnover_pa'])) {
            $html .= '<tr><td><strong>' . __('Turnover PA', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($bf['turnover_pa'])) . '</td></tr>';
          }
          if (!empty($bf['profit_gross'])) {
            $html .= '<tr><td><strong>' . __('Gross Profit', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($bf['profit_gross'])) . '</td></tr>';
          }
          if (!empty($bf['profit_net'])) {
            $html .= '<tr><td><strong>' . __('Net Profit', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($bf['profit_net'])) . '</td></tr>';
          }
          if (!empty($bf['tenancy_passing_giy'])) {
            $html .= '<tr><td><strong>' . __('Tenancy Passing GIY', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($bf['tenancy_passing_giy'])) . '</td></tr>';
          }
          if (!empty($bf['tenancy_passing_niy'])) {
            $html .= '<tr><td><strong>' . __('Tenancy Passing NIY', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_currency($bf['tenancy_passing_niy'])) . '</td></tr>';
          }
          if (!empty($bf['tenancy_status'])) {
            $html .= '<tr><td><strong>' . __('Tenancy Status', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($bf['tenancy_status'])) . '</td></tr>';
          }
          $html .= '</table>';
          $html .= '</div>';
        }
      }

      // Legal & Regulatory
      if (!empty($all_data['legal_regulatory'])) {
        $lr = $all_data['legal_regulatory'];
        $has_data = array_filter($lr, function ($v) {
          return $v !== null && $v !== '' && (!is_array($v) || !empty($v));
        });
        if (!empty($has_data)) {
          $html .= '<div class="kato-sync-data-section">';
          $html .= '<h3>' . __('Legal & Regulatory', 'kato-sync') . '</h3>';
          $html .= '<table class="kato-sync-data-table">';
          if (!empty($lr['sale_type'])) {
            $html .= '<tr><td><strong>' . __('Sale Type', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['sale_type'])) . '</td></tr>';
          }
          if (!empty($lr['class_of_use'])) {
            $html .= '<tr><td><strong>' . __('Class of Use', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['class_of_use'])) . '</td></tr>';
          }
          if (isset($lr['legal_fees_applicable'])) {
            $html .= '<tr><td><strong>' . __('Legal Fees Applicable', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['legal_fees_applicable'])) . '</td></tr>';
          }
          if (!empty($lr['lease_length'])) {
            $html .= '<tr><td><strong>' . __('Lease Length', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['lease_length'])) . '</td></tr>';
          }
          if (isset($lr['protected_act'])) {
            $html .= '<tr><td><strong>' . __('Protected Act', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['protected_act'])) . '</td></tr>';
          }
          if (!empty($lr['insurance_type'])) {
            $html .= '<tr><td><strong>' . __('Insurance Type', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['insurance_type'])) . '</td></tr>';
          }
          if (!empty($lr['availability_reasons'])) {
            $html .= '<tr><td><strong>' . __('Availability Reasons', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['availability_reasons'])) . '</td></tr>';
          }
          if (isset($lr['togc'])) {
            $html .= '<tr><td><strong>' . __('TOGC', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($lr['togc'])) . '</td></tr>';
          }
          $html .= '</table>';
          $html .= '</div>';
        }
      }

      // Physical Features
      if (!empty($all_data['physical_features'])) {
        $pf = $all_data['physical_features'];
        $has_data = array_filter($pf, function ($v) {
          return $v !== null && $v !== '';
        });
        if (!empty($has_data)) {
          $html .= '<div class="kato-sync-data-section">';
          $html .= '<h3>' . __('Physical Features', 'kato-sync') . '</h3>';
          $html .= '<table class="kato-sync-data-table">';

          // Shop frontage
          $frontage_parts = [];
          if (!empty($pf['shop_frontage_ft'])) $frontage_parts[] = $pf['shop_frontage_ft'] . ' ft';
          if (!empty($pf['shop_frontage_m'])) $frontage_parts[] = $pf['shop_frontage_m'] . ' m';
          if (!empty($pf['shop_frontage_inches'])) $frontage_parts[] = $pf['shop_frontage_inches'] . ' inches';
          if (!empty($frontage_parts)) {
            $html .= '<tr><td><strong>' . __('Shop Frontage', 'kato-sync') . '</strong></td><td>' . esc_html(implode(', ', $frontage_parts)) . '</td></tr>';
          }

          // Land size
          if (!empty($pf['land_size_from']) || !empty($pf['land_size_to'])) {
            $land_size = '';
            if (!empty($pf['land_size_from']) && !empty($pf['land_size_to'])) {
              $land_size = $fmt_scalar($pf['land_size_from']) . ' - ' . $fmt_scalar($pf['land_size_to']);
            } elseif (!empty($pf['land_size_from'])) {
              $land_size = $fmt_scalar($pf['land_size_from']);
            } else {
              $land_size = $fmt_scalar($pf['land_size_to']);
            }
            if (!empty($pf['land_size_metric'])) {
              $land_size .= ' ' . $pf['land_size_metric'];
            }
            $html .= '<tr><td><strong>' . __('Land Size', 'kato-sync') . '</strong></td><td>' . esc_html($land_size) . '</td></tr>';
          }

          if (!empty($pf['total_property_size'])) {
            $size_text = $fmt_scalar($pf['total_property_size']);
            if (!empty($pf['total_property_size_metric'])) {
              $size_text .= ' ' . $pf['total_property_size_metric'];
            }
            $html .= '<tr><td><strong>' . __('Total Property Size', 'kato-sync') . '</strong></td><td>' . esc_html($size_text) . '</td></tr>';
          }

          if (!empty($pf['area_size_type'])) {
            $html .= '<tr><td><strong>' . __('Area Size Type', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pf['area_size_type'])) . '</td></tr>';
          }
          if (!empty($pf['size_measure'])) {
            $html .= '<tr><td><strong>' . __('Size Measure', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($pf['size_measure'])) . '</td></tr>';
          }

          $html .= '</table>';
          $html .= '</div>';
        }
      }

      // Size Data
      if (!empty($all_data['size']) || !empty($all_data['units'])) {
        $sz = $all_data['size'] ?? array();
        $units = $all_data['units'] ?? array();
        $unit_sizes = array();
        foreach ($units as $u) {
          if (!empty($u['size_sqft'])) {
            $unit_sizes[] = (int)$u['size_sqft'];
          }
        }
        sort($unit_sizes, SORT_NUMERIC);
        $unit_range = (!empty($unit_sizes)) ? (min($unit_sizes) . ' - ' . max($unit_sizes) . ' sq ft') : $dash();
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Size Data', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';
        $html .= '<tr><td><strong>' . __('Unit Size Range', 'kato-sync') . '</strong></td><td>' . esc_html($unit_range) . '</td></tr>';
        if (!empty($sz['total_size_sqft'])) {
          $html .= '<tr><td><strong>' . __('Total Size', 'kato-sync') . '</strong></td><td>' . esc_html($fmt_scalar($sz['total_size_sqft'])) . ' ' . esc_html($fmt_scalar($sz['area_size_unit'] ?? 'sq ft')) . '</td></tr>';
        }

        // Get property size range from raw data
        $raw_json = \get_post_meta($property_id, '_kato_raw_original', true);
        $raw_data = is_string($raw_json) ? json_decode($raw_json, true) : array();
        $size_from = $raw_data['size_from'] ?? null;
        $size_to = $raw_data['size_to'] ?? null;

        if (!empty($size_from) || !empty($size_to)) {
          $property_size_range = '';
          if (!empty($size_from) && !empty($size_to)) {
            $property_size_range = $fmt_scalar($size_from) . ' - ' . $fmt_scalar($size_to);
          } elseif (!empty($size_from)) {
            $property_size_range = $fmt_scalar($size_from);
          } else {
            $property_size_range = $fmt_scalar($size_to);
          }
          $property_size_range .= ' sq ft';
          $html .= '<tr><td><strong>' . __('Property Size Range', 'kato-sync') . '</strong></td><td>' . esc_html($property_size_range) . '</td></tr>';
        } elseif (!empty($sz['size_min']) || !empty($sz['size_max'])) {
          $size_range = '';
          if (!empty($sz['size_min']) && !empty($sz['size_max'])) {
            $size_range = $fmt_scalar($sz['size_min']) . ' - ' . $fmt_scalar($sz['size_max']);
          } elseif (!empty($sz['size_min'])) {
            $size_range = $fmt_scalar($sz['size_min']);
          } else {
            $size_range = $fmt_scalar($sz['size_max']);
          }
          $size_range .= ' ' . $fmt_scalar($sz['area_size_unit'] ?? 'sq ft');
          $html .= '<tr><td><strong>' . __('Property Size Range', 'kato-sync') . '</strong></td><td>' . esc_html($size_range) . '</td></tr>';
        }

        if (!empty($sz['size_from_sqft']) || !empty($sz['size_to_sqft'])) {
          $sqft_range = '';
          if (!empty($sz['size_from_sqft']) && !empty($sz['size_to_sqft'])) {
            $sqft_range = $fmt_scalar($sz['size_from_sqft']) . ' - ' . $fmt_scalar($sz['size_to_sqft']);
          } elseif (!empty($sz['size_from_sqft'])) {
            $sqft_range = $fmt_scalar($sz['size_from_sqft']);
          } else {
            $sqft_range = $fmt_scalar($sz['size_to_sqft']);
          }
          $sqft_range .= ' sq ft';
          $html .= '<tr><td><strong>' . __('Size Range (sq ft)', 'kato-sync') . '</strong></td><td>' . esc_html($sqft_range) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
      }

      // Key Selling Points & Amenities
      if (!empty($all_data['selling_points'])) {
        $sp = $all_data['selling_points'];
        $has_ksp = !empty($sp['key_selling_points']);
        $has_amenities = !empty($sp['amenities_specifications']);

        if ($has_ksp || $has_amenities) {
          $html .= '<div class="kato-sync-data-section">';
          $html .= '<h3>' . __('Selling Points & Amenities', 'kato-sync') . '</h3>';
          $html .= '<table class="kato-sync-data-table">';

          if ($has_ksp) {
            $ksp_list = '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 5px;">';
            foreach ($sp['key_selling_points'] as $index => $ksp) {
              $ksp_list .= '<div><strong>Point ' . ($index + 1) . ':</strong></div><div>' . esc_html($ksp) . '</div>';
            }
            $ksp_list .= '</div>';
            $html .= '<tr><td><strong>' . __('Key Selling Points', 'kato-sync') . '</strong></td><td>' . $ksp_list . '</td></tr>';
          }

          if ($has_amenities) {
            $amenities_table = '<table style="width: 100%; border-collapse: collapse;">';
            foreach ($sp['amenities_specifications'] as $spec) {
              $amenities_table .= '<tr><td style="border: 1px solid #ddd; padding: 4px; font-weight: bold;">' . esc_html($spec['label']) . '</td><td style="border: 1px solid #ddd; padding: 4px;">' . esc_html($spec['value']) . '</td></tr>';
            }
            $amenities_table .= '</table>';
            $html .= '<tr><td><strong>' . __('Amenities & Specifications', 'kato-sync') . '</strong></td><td>' . $amenities_table . '</td></tr>';
          }

          $html .= '</table>';
          $html .= '</div>';
        }
      }

      // Units
      if (!empty($all_data['units'])) {
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Floor Units Data', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';
        $count_units = is_array($all_data['units']) ? count($all_data['units']) : 0;
        $html .= '<tr><td><strong>' . __('Floor Units', 'kato-sync') . '</strong></td><td>' . ($count_units > 0 ? sprintf(__('%d units available', 'kato-sync'), $count_units) : '—') . '</td></tr>';
        $index = 0;
        foreach ($all_data['units'] as $u) {
          $index++;
          $html .= '<tr><td><strong>' . sprintf(__('Unit %d', 'kato-sync'), $index) . '</strong></td><td>';
          $html .= '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 5px;">';
          $raw = is_array($u['raw'] ?? null) ? $u['raw'] : array();
          $rent_sqft = $raw['rent_sqft'] ?? null;
          $total_sqft_text = $raw['total_sqft'] ?? null;
          $total_month = $raw['total_month'] ?? null;
          $total_year = $raw['total_year'] ?? null;
          $html .= '<div><strong>' . __('Floor:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($u['floor_label'] ?? null)) . '</div>';
          $html .= '<div><strong>' . __('Size:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($u['size_sqft'] ?? null)) . '</div>';

          // Show rent min/max if available
          $rent_display = '';
          if (!empty($u['rent_min']) && !empty($u['rent_max']) && $u['rent_min'] !== $u['rent_max']) {
            $rent_display = $fmt_currency($u['rent_min']) . ' - ' . $fmt_currency($u['rent_max']);
          } elseif (!empty($u['rent_min'])) {
            $rent_display = $fmt_currency($u['rent_min']);
          } elseif (!empty($u['rent_max'])) {
            $rent_display = $fmt_currency($u['rent_max']);
          }
          if ($rent_display) {
            $html .= '<div><strong>' . __('Rent:', 'kato-sync') . '</strong></div><div>' . esc_html($rent_display) . '</div>';
          }

          if (!empty($rent_sqft)) {
            $html .= '<div><strong>' . __('Rent per sq ft:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($rent_sqft)) . '</div>';
          }
          if (!empty($u['rent_metric'])) {
            $html .= '<div><strong>' . __('Rent Metric:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($u['rent_metric'])) . '</div>';
          }
          if (!empty($u['rates_text'])) {
            $html .= '<div><strong>' . __('Rates:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($u['rates_text'])) . '</div>';
          }
          if (!empty($total_sqft_text)) {
            $html .= '<div><strong>' . __('Total per sq ft:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($total_sqft_text)) . '</div>';
          }
          if (!empty($total_month)) {
            $html .= '<div><strong>' . __('Total Monthly:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($total_month)) . '</div>';
          }
          if (!empty($total_year)) {
            $html .= '<div><strong>' . __('Total Yearly:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($total_year)) . '</div>';
          }
          $html .= '<div><strong>' . __('Status:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($u['status'] ?? null)) . '</div>';
          if (!empty($u['availability_date'])) {
            $html .= '<div><strong>' . __('Available:', 'kato-sync') . '</strong></div><div>' . esc_html($fmt_scalar($u['availability_date'])) . '</div>';
          }
          $html .= '</div>';
          $html .= '</td></tr>';
          if ($index < $count_units) {
            $html .= '<tr><td colspan="2"><hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;"></td></tr>';
          }
        }
        $html .= '</table>';
        $html .= '</div>';
      }

      // Media
      if (!empty($all_data['media'])) {
        $m = $all_data['media'];
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Media Files', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';

        // Get live image data with current import status
        $images_with_status = [];
        $import_status = [];

        if (class_exists('\\KatoSync\\Utils\\ImageDisplay')) {
          $images_with_status = \KatoSync\Utils\ImageDisplay::get_property_images_with_import_status($property_id);
          $import_status = \KatoSync\Utils\ImageDisplay::get_import_status($property_id);
        }

        // If we have live data, use it; otherwise fallback to view model data
        if (!empty($images_with_status)) {
          $images_array = $images_with_status;
          $image_count = count($images_array);
        } else {
          // Fallback to old logic for backward compatibility
          $images_array = (isset($m['images']) && is_array($m['images'])) ? $m['images'] : array();
          $image_count = count($images_array);

          if ($image_count === 0) {
            $raw_json = \get_post_meta($property_id, '_kato_raw_original', true);
            $raw_arr = is_string($raw_json) ? json_decode($raw_json, true) : array();
            // Navigate to images->image and flatten
            if (isset($raw_arr['images'])) {
              $raw_images = $raw_arr['images'];
              if (isset($raw_images['image'])) {
                $raw_images = $raw_images['image'];
              }
              foreach ((array)$raw_images as $ri) {
                if (is_string($ri) && filter_var($ri, FILTER_VALIDATE_URL)) {
                  $images_array[] = array('url' => $ri, 'alt' => null, 'is_imported' => false);
                } elseif (is_array($ri)) {
                  $url = $ri['url'] ?? (is_string(($ri['name'] ?? '')) && filter_var($ri['name'], FILTER_VALIDATE_URL) ? $ri['name'] : null);
                  if ($url) {
                    $images_array[] = array('url' => $url, 'alt' => ($ri['name'] ?? null), 'is_imported' => false);
                  }
                }
              }
              $image_count = count($images_array);
            }
          }
        }

        $thumbs_html = '';
        if ($image_count > 0) {
          $thumbs_html .= '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">';
          $i = 0;
          $max = 6;
          foreach ($images_array as $img) {
            if (!empty($img['url'])) {
              // Use imported image URL if available, otherwise use original URL
              $image_url = $img['url'];
              if (!empty($img['is_imported']) && !empty($img['attachment_id'])) {
                // Try to get a thumbnail size if available
                if (!empty($img['sizes']['thumbnail'])) {
                  $image_url = $img['sizes']['thumbnail'];
                } elseif (!empty($img['sizes']['medium'])) {
                  $image_url = $img['sizes']['medium'];
                }
              }

              $thumbs_html .= '<img src="' . \esc_url($image_url) . '" alt="' . \esc_attr($img['alt'] ?? 'Image') . '" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">';
              $i++;
              if ($i >= $max) break;
            }
          }
          if ($image_count > $max) {
            $thumbs_html .= '<div style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">+' . ($image_count - $max) . ' more</div>';
          }
          $thumbs_html .= '</div>';
        }
        // Build image status text
        $image_status_text = '';
        if ($image_count > 0) {
          if (!empty($import_status)) {
            if ($import_status['all_imported']) {
              $image_status_text = sprintf(__('%d images (all imported)', 'kato-sync'), $image_count);
            } elseif ($import_status['imported_images'] > 0) {
              $image_status_text = sprintf(__('%d images (%d imported, %d pending)', 'kato-sync'), $image_count, $import_status['imported_images'], $import_status['pending_images']);
            } else {
              $image_status_text = sprintf(__('%d images (pending import)', 'kato-sync'), $image_count);
            }
          } else {
            $image_status_text = sprintf(__('%d images', 'kato-sync'), $image_count);
          }
          $image_status_text .= $thumbs_html;
        } else {
          $image_status_text = '—';
        }

        $html .= '<tr><td><strong>' . __('Images', 'kato-sync') . '</strong></td><td>' . $image_status_text . '</td></tr>';

        // Files (combine brochures, floor plans, and other files)
        $all_files = [];

        // Collect brochures (type 11)
        $brochures = $m['brochures'] ?? [];
        foreach ($brochures as $b) {
          $description = $b['raw']['description'] ?? 'Brochure';
          $name = $b['title'] ?? basename($b['url'] ?? '');
          $all_files[] = [
            'description' => $description,
            'name' => $name,
            'url' => $b['url'] ?? null
          ];
        }

        // Collect floor plans (type 15)
        $floorplans = $m['floorplans'] ?? [];
        foreach ($floorplans as $f) {
          $description = $f['raw']['description'] ?? 'Floor Plan';
          $name = $f['title'] ?? basename($f['url'] ?? '');
          $all_files[] = [
            'description' => $description,
            'name' => $name,
            'url' => $f['url'] ?? null
          ];
        }

        if (!empty($all_files)) {
          $files_list = '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 5px;">';
          foreach ($all_files as $file) {
            $description = esc_html($file['description']);
            $name = esc_html($file['name']);
            if (!empty($file['url'])) {
              $files_list .= '<div><strong>' . $description . ':</strong></div><div><a href="' . esc_url($file['url']) . '" target="_blank" rel="noopener noreferrer">' . $name . '</a></div>';
            } else {
              $files_list .= '<div><strong>' . $description . ':</strong></div><div>' . $name . '</div>';
            }
          }
          $files_list .= '</div>';
          $html .= '<tr><td><strong>' . __('Files', 'kato-sync') . '</strong></td><td>' . $files_list . '</td></tr>';
        } else {
          $html .= '<tr><td><strong>' . __('Files', 'kato-sync') . '</strong></td><td>' . $dash() . '</td></tr>';
        }

        // Videos
        $videos = $m['videos'] ?? [];
        $videos_detail = $m['videos_detail'] ?? [];

        // Filter out empty videos
        $valid_videos = array_filter($videos, function ($v) {
          return !empty($v['url']) && trim($v['url']) !== '';
        });
        $valid_videos_detail = array_filter($videos_detail, function ($vd) {
          return !empty($vd) && trim($vd) !== '';
        });

        if (!empty($valid_videos) || !empty($valid_videos_detail)) {
          $video_list = '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 5px;">';
          $video_count = 1;
          foreach ($valid_videos as $v) {
            $title = $v['title'] ?? basename($v['url'] ?? '');
            if (!empty($v['url'])) {
              $video_list .= '<div><strong>Video ' . $video_count . ':</strong></div><div><a href="' . esc_url($v['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($title) . '</a></div>';
            } else {
              $video_list .= '<div><strong>Video ' . $video_count . ':</strong></div><div>' . esc_html($title) . '</div>';
            }
            $video_count++;
          }
          foreach ($valid_videos_detail as $vd) {
            $video_list .= '<div><strong>Detail ' . ($video_count - count($valid_videos)) . ':</strong></div><div>' . esc_html($vd) . '</div>';
            $video_count++;
          }
          $video_list .= '</div>';
          $html .= '<tr><td><strong>' . __('Videos', 'kato-sync') . '</strong></td><td>' . $video_list . '</td></tr>';
        } else {
          $html .= '<tr><td><strong>' . __('Videos', 'kato-sync') . '</strong></td><td>' . $dash() . '</td></tr>';
        }

        $html .= '</table>';
        $html .= '</div>';
      }

      // Marketing Content
      if (!empty($all_data['marketing'])) {
        $mk = $all_data['marketing'];
        $has_data = array_filter($mk, function ($v) {
          return $v !== null && $v !== '';
        });
        if (!empty($has_data)) {
          $html .= '<div class="kato-sync-data-section">';
          $html .= '<h3>' . __('Marketing Content', 'kato-sync') . '</h3>';
          $html .= '<table class="kato-sync-data-table">';

          for ($i = 1; $i <= 5; $i++) {
            $title_key = "marketing_title_$i";
            $text_key = "marketing_text_$i";
            if (!empty($mk[$title_key]) || !empty($mk[$text_key])) {
              if (!empty($mk[$title_key])) {
                $html .= '<tr><td><strong>' . sprintf(__('Marketing Title %d', 'kato-sync'), $i) . '</strong></td><td>' . esc_html($mk[$title_key]) . '</td></tr>';
              }
              if (!empty($mk[$text_key])) {
                $html .= '<tr><td><strong>' . sprintf(__('Marketing Text %d', 'kato-sync'), $i) . '</strong></td><td>' . esc_html($mk[$text_key]) . '</td></tr>';
              }
            }
          }

          if (!empty($mk['marketing_title_transport'])) {
            $html .= '<tr><td><strong>' . __('Transport Title', 'kato-sync') . '</strong></td><td>' . esc_html($mk['marketing_title_transport']) . '</td></tr>';
          }
          if (!empty($mk['marketing_text_transport'])) {
            $html .= '<tr><td><strong>' . __('Transport Info', 'kato-sync') . '</strong></td><td>' . esc_html($mk['marketing_text_transport']) . '</td></tr>';
          }

          $html .= '</table>';
          $html .= '</div>';
        }
      }

      // Certifications & Tags
      if (!empty($all_data['certifications'])) {
        $cert = $all_data['certifications'];
        $has_data = array_filter($cert, function ($v) {
          return $v !== null && $v !== '' && (!is_array($v) || !empty($v));
        });
        if (!empty($has_data)) {
          $html .= '<div class="kato-sync-data-section">';
          $html .= '<h3>' . __('Certifications & Tags', 'kato-sync') . '</h3>';
          $html .= '<table class="kato-sync-data-table">';

          if (!empty($cert['epcs'])) {
            $epc_list = is_array($cert['epcs']) ? implode(', ', $cert['epcs']) : $cert['epcs'];
            $html .= '<tr><td><strong>' . __('EPCs', 'kato-sync') . '</strong></td><td>' . esc_html($epc_list) . '</td></tr>';
          }

          if (!empty($cert['tags'])) {
            $tags_display = is_array($cert['tags']) ? implode(', ', $cert['tags']) : $cert['tags'];
            $html .= '<tr><td><strong>' . __('Tags', 'kato-sync') . '</strong></td><td>' . esc_html($tags_display) . '</td></tr>';
          }

          $html .= '</table>';
          $html .= '</div>';
        }
      }

      // Contacts
      if (!empty($all_data['contacts'])) {
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('Agent Data', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';
        foreach ($all_data['contacts'] as $c) {
          $grid = '<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 5px;">';
          if (!empty($c['name'])) {
            $grid .= '<div><strong>' . __('Name:', 'kato-sync') . '</strong></div><div>' . \esc_html($c['name']) . '</div>';
          }
          if (!empty($c['email'])) {
            $grid .= '<div><strong>' . __('Email:', 'kato-sync') . '</strong></div><div><a href="mailto:' . \esc_attr($c['email']) . '">' . \esc_html($c['email']) . '</a></div>';
          }
          if (!empty($c['phone'])) {
            $grid .= '<div><strong>' . __('Phone:', 'kato-sync') . '</strong></div><div><a href="tel:' . \esc_attr(preg_replace('/\s+/', '', $c['phone'])) . '">' . \esc_html($c['phone']) . '</a></div>';
          }
          if (!empty($c['company'])) {
            $grid .= '<div><strong>' . __('Office:', 'kato-sync') . '</strong></div><div>' . \esc_html($c['company']) . '</div>';
          }
          $grid .= '</div>';
          $html .= '<tr><td><strong>' . \esc_html($fmt_scalar($c['role'] ?? 'agent')) . '</strong></td><td>' . $grid . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
      }

      // WordPress Meta
      if ($property) {
        $author_name = '';
        if ($property->post_author) {
          $user = \get_userdata((int)$property->post_author);
          $author_name = $user ? $user->user_nicename : '';
        }
        $html .= '<div class="kato-sync-data-section">';
        $html .= '<h3>' . __('WordPress Meta', 'kato-sync') . '</h3>';
        $html .= '<table class="kato-sync-data-table">';
        $html .= '<tr><td><strong>' . __('Post Title', 'kato-sync') . '</strong></td><td>' . \esc_html($property->post_title) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post Type', 'kato-sync') . '</strong></td><td>' . \esc_html($property->post_type) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post Status', 'kato-sync') . '</strong></td><td>' . \esc_html($property->post_status) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post Slug', 'kato-sync') . '</strong></td><td>' . \esc_html($property->post_name) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post ID', 'kato-sync') . '</strong></td><td>' . (int)$property->ID . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post Date', 'kato-sync') . '</strong></td><td>' . \esc_html($property->post_date) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post Modified', 'kato-sync') . '</strong></td><td>' . \esc_html($property->post_modified) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Post Author', 'kato-sync') . '</strong></td><td>' . \esc_html($author_name) . '</td></tr>';
        $excerpt_len = \strlen((string)$property->post_excerpt);
        $html .= '<tr><td><strong>' . __('Excerpt Length', 'kato-sync') . '</strong></td><td>' . $excerpt_len . ' ' . __('characters', 'kato-sync') . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Comment Status', 'kato-sync') . '</strong></td><td>' . \esc_html($property->comment_status) . '</td></tr>';
        $html .= '<tr><td><strong>' . __('Ping Status', 'kato-sync') . '</strong></td><td>' . \esc_html($property->ping_status) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
      }

      $html .= '</div>';

      wp_send_json_success(['title' => $modal_title, 'html' => $html]);
    } catch (\Exception $e) {
      wp_send_json_error(__('Error loading property data: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * Get clean property type options for filtering
   */
  public static function get_property_type_options(): array {
    $raw_types = self::get_unique_meta_values('types');
    $unique_types = array();

    foreach ($raw_types as $raw_type) {
      // Skip empty or invalid entries
      if (empty($raw_type) || $raw_type === '[""]' || $raw_type === '[]') {
        continue;
      }

      // Skip entries with numeric codes (like "1", "31")
      if (preg_match('/"1"|"31"/', $raw_type)) {
        continue;
      }

      // Decode the JSON array and extract individual types
      $types_array = json_decode($raw_type, true);
      if (is_array($types_array) && !empty($types_array)) {
        foreach ($types_array as $type) {
          if (is_string($type) && !empty(trim($type))) {
            $unique_types[] = trim($type);
          }
        }
      }
    }

    // Remove duplicates and sort
    $unique_types = array_unique($unique_types);
    sort($unique_types, SORT_NATURAL | SORT_FLAG_CASE);

    // Create clean options array
    $clean_types = array();
    foreach ($unique_types as $type) {
      $clean_types[$type] = $type;
    }

    return $clean_types;
  }

  /**
   * Get clean availability options for filtering
   */
  public static function get_property_availability_options(): array {
    $raw_availabilities = self::get_unique_meta_values('availabilities');
    $unique_availabilities = array();

    foreach ($raw_availabilities as $raw_availability) {
      // Skip empty or invalid entries
      if (empty($raw_availability) || $raw_availability === '[""]' || $raw_availability === '[]') {
        continue;
      }

      // Decode the JSON array and extract individual availabilities
      $availabilities_array = json_decode($raw_availability, true);
      if (is_array($availabilities_array) && !empty($availabilities_array)) {
        foreach ($availabilities_array as $availability) {
          if (is_string($availability) && !empty(trim($availability))) {
            $unique_availabilities[] = trim($availability);
          }
        }
      }
    }

    // Remove duplicates and sort
    $unique_availabilities = array_unique($unique_availabilities);
    sort($unique_availabilities, SORT_NATURAL | SORT_FLAG_CASE);

    // Create clean options array
    $clean_availabilities = array();
    foreach ($unique_availabilities as $availability) {
      $clean_availabilities[$availability] = $availability;
    }

    return $clean_availabilities;
  }

  /**
   * Comprehensive diagnostic function for auto-sync issues
   */
  public static function diagnose_auto_sync_issues(): array {

    $settings = get_option('kato_sync_settings', array());
    $next_scheduled = wp_next_scheduled('kato_sync_auto_sync');
    $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    $last_sync = get_option('kato_sync_last_sync');
    $next_sync_option = get_option('kato_sync_next_sync');
    $sync_lock = get_transient('kato_sync_running');


    // Check available cron schedules
    $schedules = wp_get_schedules();

    // Check if custom intervals are registered
    $custom_intervals = array('15mins', '30mins', '1hour', '3hours', '6hours', '12hours', '24hours');
    $missing_intervals = array();
    foreach ($custom_intervals as $interval) {
      if (!isset($schedules[$interval])) {
        $missing_intervals[] = $interval;
      }
    }

    if (!empty($missing_intervals)) {
    }

    // Check all scheduled cron jobs
    $all_crons = _get_cron_array();

    $auto_sync_crons = 0;
    foreach ($all_crons as $timestamp => $cron_jobs) {
      foreach ($cron_jobs as $hook => $callbacks) {
        if ($hook === 'kato_sync_auto_sync') {
          $auto_sync_crons++;
        }
      }
    }


    $diagnosis = array(
      'settings' => $settings,
      'next_scheduled' => $next_scheduled,
      'cron_disabled' => $cron_disabled,
      'last_sync' => $last_sync,
      'next_sync_option' => $next_sync_option,
      'sync_lock' => $sync_lock,
      'available_schedules' => array_keys($schedules),
      'missing_intervals' => $missing_intervals,
      'total_cron_jobs' => count($all_crons),
      'auto_sync_cron_count' => $auto_sync_crons,
      'issues' => array()
    );

    // Identify issues
    if ($diagnosis['auto_sync_cron_count'] > 1) {
      $diagnosis['issues'][] = 'Multiple auto-sync cron jobs scheduled (' . $diagnosis['auto_sync_cron_count'] . ')';
    }

    if (!empty($diagnosis['missing_intervals'])) {
      $diagnosis['issues'][] = 'Custom cron intervals not registered: ' . implode(', ', $diagnosis['missing_intervals']);
    }

    if ($diagnosis['cron_disabled']) {
      $diagnosis['issues'][] = 'WordPress cron is disabled';
    }

    if (!$diagnosis['next_scheduled'] && !empty($settings['auto_sync_enabled'])) {
      $diagnosis['issues'][] = 'Auto-sync enabled but no cron job scheduled';
    }

    if ($diagnosis['sync_lock']) {
      $diagnosis['issues'][] = 'Sync lock exists - previous sync may have failed';
    }

    foreach ($diagnosis['issues'] as $issue) {
    }

    return $diagnosis;
  }

  /**
   * AJAX handler for running auto-sync diagnostics
   */
  public static function ajax_run_auto_sync_diagnostics(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $diagnosis = self::diagnose_auto_sync_issues();
      wp_send_json_success($diagnosis);
    } catch (\Exception $e) {
      wp_send_json_error(__('Error running diagnostics: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * Clean up availability taxonomy terms to only use name field
   */
  public static function cleanup_availability_terms(): array {
    $results = array(
      'cleaned' => 0,
      'errors' => 0,
      'messages' => array()
    );

    // Get all availability terms
    $terms = get_terms(array(
      'taxonomy' => 'kato_availability',
      'hide_empty' => false,
    ));

    // First, identify terms that are IDs (like "tolet", "forsale")
    $id_terms = array();
    $name_terms = array();

    foreach ($terms as $term) {
      $term_name = $term->name;

      // Check if this looks like an ID (lowercase, no spaces, short)
      if (strlen($term_name) <= 10 && strtolower($term_name) === $term_name && strpos($term_name, ' ') === false) {
        $id_terms[] = $term;
      } else {
        $name_terms[] = $term;
      }
    }

    // For each ID term, find if there's a corresponding name term
    foreach ($id_terms as $id_term) {
      $id_name = $id_term->name;

      // Map IDs to expected names
      $id_to_name_map = array(
        'tolet' => 'To Let',
        'forsale' => 'For Sale',
        'sale' => 'For Sale',
        'let' => 'To Let',
        'rent' => 'To Let',
        'rental' => 'To Let'
      );

      $expected_name = $id_to_name_map[$id_name] ?? null;

      if ($expected_name) {
        // Check if the name term already exists
        $existing_name_term = get_term_by('name', $expected_name, 'kato_availability');

        if ($existing_name_term) {
          // Move all posts from ID term to name term
          $posts_with_id_term = get_objects_in_term($id_term->term_id, 'kato_availability');

          foreach ($posts_with_id_term as $post_id) {
            wp_set_object_terms($post_id, $expected_name, 'kato_availability', true);
          }

          // Delete the ID term
          wp_delete_term($id_term->term_id, 'kato_availability');

          $results['cleaned']++;
          $results['messages'][] = 'Merged term "' . $id_name . '" into "' . $expected_name . '"';
        } else {
          // Rename the ID term to the name
          $update_result = wp_update_term($id_term->term_id, 'kato_availability', array(
            'name' => $expected_name,
            'slug' => sanitize_title($expected_name)
          ));

          if (is_wp_error($update_result)) {
            $results['errors']++;
            $results['messages'][] = 'Failed to update term "' . $id_name . '": ' . $update_result->get_error_message();
          } else {
            $results['cleaned']++;
            $results['messages'][] = 'Updated term "' . $id_name . '" to "' . $expected_name . '"';
          }
        }
      }
    }

    return $results;
  }





  /**
   * Reset and fix auto-sync settings
   */
  public static function reset_auto_sync_settings(): array {

    // Get current settings
    $settings = get_option('kato_sync_settings', array());

    // Clear all existing cron jobs
    wp_clear_scheduled_hook('kato_sync_auto_sync');

    // Fix the frequency if it's invalid
    $current_frequency = $settings['auto_sync_frequency'] ?? '1hour';
    $supported_frequencies = array('15mins', '30mins', '1hour', '3hours', '6hours', '12hours', '24hours');

    if (!in_array($current_frequency, $supported_frequencies)) {
      $settings['auto_sync_frequency'] = '24hours';
    }

    // Ensure auto-sync is enabled if it was previously enabled
    if (!empty($settings['auto_sync_enabled'])) {
      $settings['auto_sync_enabled'] = true;
    }

    // Update settings
    update_option('kato_sync_settings', $settings);

    // Reschedule cron job if auto-sync is enabled
    if (!empty($settings['auto_sync_enabled'])) {
      $frequency = $settings['auto_sync_frequency'];
      $current_time = current_time('timestamp');

      // Calculate next run time
      switch ($frequency) {
        case '15mins':
          $next_run = $current_time + 900;
          break;
        case '30mins':
          $next_run = $current_time + 1800;
          break;
        case '1hour':
          $next_run = $current_time + 3600;
          break;
        case '3hours':
          $next_run = $current_time + 10800;
          break;
        case '6hours':
          $next_run = $current_time + 21600;
          break;
        case '12hours':
          $next_run = $current_time + 43200;
          break;
        case '24hours':
          $next_run = $current_time + 86400;
          break;
        default:
          $next_run = $current_time + 86400;
          break;
      }

      $scheduled = wp_schedule_event($next_run, $frequency, 'kato_sync_auto_sync');

      update_option('kato_sync_next_sync', $next_run);
    }

    // Clear any existing sync locks
    delete_transient('kato_sync_running');

    $result = array(
      'success' => true,
      'old_frequency' => $current_frequency,
      'new_frequency' => $settings['auto_sync_frequency'],
      'next_scheduled' => wp_next_scheduled('kato_sync_auto_sync'),
      'message' => 'Auto-sync settings reset successfully'
    );

    return $result;
  }

  /**
   * AJAX handler for resetting auto-sync settings
   */
  public static function ajax_reset_auto_sync_settings(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $result = self::reset_auto_sync_settings();
      wp_send_json_success($result);
    } catch (\Exception $e) {
      wp_send_json_error(__('Error resetting auto-sync settings: ', 'kato-sync') . $e->getMessage());
    }
  }







  /**
   * Clear any stale sync locks
   */
  public static function ajax_clear_sync_locks(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $locks_cleared = 0;

      // Check and clear sync running lock
      $sync_lock = get_transient('kato_sync_running');
      if ($sync_lock) {
        delete_transient('kato_sync_running');
        $locks_cleared++;
      }

      // Check and clear sync running time lock
      $sync_lock_time = get_transient('kato_sync_running_time');
      if ($sync_lock_time) {
        delete_transient('kato_sync_running_time');
        $locks_cleared++;
      }

      if ($locks_cleared > 0) {
        wp_send_json_success(sprintf(__('Cleared %d stale sync lock(s). Auto-sync should now work properly.', 'kato-sync'), $locks_cleared));
      } else {
        wp_send_json_success(__('No stale sync locks found.', 'kato-sync'));
      }
    } catch (\Exception $e) {
      wp_send_json_error(__('Error clearing sync locks: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * AJAX handler for backfilling attachment IDs in media table
   */
  public static function ajax_backfill_attachment_ids(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $results = \KatoSync\Sync\ImageProcessor::backfill_attachment_ids();

      $message = sprintf(
        __('Backfill complete: %d images checked, %d records updated', 'kato-sync'),
        $results['total_checked'],
        $results['updated']
      );

      if (!empty($results['errors'])) {
        $message .= '. Errors: ' . implode(', ', array_slice($results['errors'], 0, 3));
        if (count($results['errors']) > 3) {
          $message .= sprintf(' (+%d more)', count($results['errors']) - 3);
        }
      }

      wp_send_json_success([
        'message' => $message,
        'results' => $results
      ]);
    } catch (\Exception $e) {
      wp_send_json_error(__('Error running backfill: ', 'kato-sync') . $e->getMessage());
    }
  }
}
