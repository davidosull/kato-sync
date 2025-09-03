<?php

namespace KatoSync\Sync;

use KatoSync\PostTypes\Property;

/**
 * Image processor for handling property images in both local and external modes
 */
class ImageProcessor {

  // Default limits - will be overridden by settings
  const DEFAULT_BATCH_SIZE = 20;
  const DEFAULT_MAX_CONCURRENT_DOWNLOADS = 3;
  const DEFAULT_DOWNLOAD_TIMEOUT = 30;
  const DEFAULT_MAX_FILE_SIZE = 10485760; // 10MB
  const DEFAULT_MAX_RETRIES = 3;
  const MAX_RETRIES = 3;

  /**
   * Process images for a property based on the current mode
   */
  public static function process_property_images(int $post_id, array $property_data, bool $is_property_only_import = false): void {
    $settings = get_option('kato_sync_settings', array());
    $image_mode = $settings['image_mode'] ?? 'local';

    // Extract images from property data
    $images = self::extract_images_from_property($property_data);

    if (empty($images)) {
      return;
    }

    if ($image_mode === 'local') {
      // Store external URLs and queue for local import
      self::store_external_images($post_id, $images);

      // Only queue images if this is not a property-only import
      if (!$is_property_only_import) {
        self::queue_images_for_import($post_id, $images);
      }
    } else {
      // External mode - just store URLs for direct serving
      self::store_external_images($post_id, $images);
    }
  }

  /**
   * Get image processing settings
   */
  private static function get_image_settings(): array {
    $settings = get_option('kato_sync_settings', array());

    return array(
      'batch_size' => intval($settings['image_batch_size'] ?? self::DEFAULT_BATCH_SIZE),
      'timeout' => intval($settings['image_timeout'] ?? self::DEFAULT_DOWNLOAD_TIMEOUT),
      'max_retries' => intval($settings['image_retry_attempts'] ?? self::DEFAULT_MAX_RETRIES),
      'max_file_size' => intval($settings['image_max_size'] ?? 10) * 1024 * 1024, // Convert MB to bytes
      'max_concurrent_downloads' => self::DEFAULT_MAX_CONCURRENT_DOWNLOADS,
    );
  }

  /**
   * Extract images from property data
   */
  private static function extract_images_from_property(array $property_data): array {
    $images = array();

    // Check for original_images first (preferred structure)
    if (isset($property_data['original_images']) && is_array($property_data['original_images'])) {


      foreach ($property_data['original_images'] as $image) {
        if (is_array($image)) {
          // Handle structured image data
          if (isset($image['url']) && !empty($image['url'])) {
            $images[] = array(
              'url' => $image['url'],
              'name' => $image['name'] ?? basename($image['url'])
            );
          }
        } elseif (is_string($image) && !empty($image)) {
          // Handle string URLs
          $images[] = array(
            'url' => $image,
            'name' => basename($image)
          );
        }
      }
    } else {
      // Fallback to regular images

      if (isset($property_data['images']) && is_array($property_data['images'])) {
        foreach ($property_data['images'] as $image) {
          if (is_array($image)) {
            if (isset($image['url']) && !empty($image['url'])) {
              $images[] = array(
                'url' => $image['url'],
                'name' => $image['name'] ?? basename($image['url'])
              );
            }
          } elseif (is_string($image) && !empty($image)) {
            $images[] = array(
              'url' => $image,
              'name' => basename($image)
            );
          }
        }
      }
    }



    return $images;
  }

  /**
   * Store external image URLs in post meta
   */
  private static function store_external_images(int $post_id, array $images): void {
    update_post_meta($post_id, '_kato_sync_external_images', $images);
    update_post_meta($post_id, '_kato_sync_image_mode', 'external');
  }

  /**
   * Queue images for local import
   */
  private static function queue_images_for_import(int $post_id, array $images, bool $bypass_existing = false): void {
    $queue = get_option('kato_sync_image_queue', array());
    $original_queue_size = count($queue);
    $original_images_count = count($images);

    self::log_image_operation('queue_images_start', array(
      'post_id' => $post_id,
      'original_images_count' => $original_images_count,
      'current_queue_size' => $original_queue_size,
      'bypass_existing' => $bypass_existing
    ), 'info');

    if ($bypass_existing) {
      // Remove existing images for this property from queue
      $before_removal = count($queue);
      $queue = array_filter($queue, function ($item) use ($post_id) {
        return $item['post_id'] != $post_id;
      });
      $removed_count = $before_removal - count($queue);

      self::log_image_operation('queue_bypass_existing', array(
        'post_id' => $post_id,
        'removed_from_queue' => $removed_count
      ), 'info');
    } else {
      // Check if images for this property are already in queue OR already imported
      $existing_images = array();
      foreach ($queue as $item) {
        if ($item['post_id'] == $post_id) {
          $existing_images[] = $item['image_url'];
        }
      }

      // Also check for already imported images
      $local_images = get_post_meta($post_id, '_kato_sync_local_images', true);
      $imported_images = array();
      if (!empty($local_images) && is_array($local_images)) {
        foreach ($local_images as $local_image) {
          if (isset($local_image['name'])) {
            // Match by image name since URLs might differ
            $imported_images[] = $local_image['name'];
          }
        }
      }

      self::log_image_operation('queue_duplicate_check', array(
        'post_id' => $post_id,
        'already_queued_count' => count($existing_images),
        'already_imported_count' => count($imported_images),
        'total_to_check' => count($images)
      ), 'info');

      // Filter and log what gets skipped
      $skipped_queued = 0;
      $skipped_imported = 0;
      $images = array_filter($images, function ($image) use ($existing_images, $imported_images, &$skipped_queued, &$skipped_imported) {
        $already_queued = in_array($image['url'], $existing_images);
        $already_imported = in_array($image['name'], $imported_images);

        if ($already_queued) $skipped_queued++;
        if ($already_imported) $skipped_imported++;

        return !$already_queued && !$already_imported;
      });

      self::log_image_operation('queue_duplicates_filtered', array(
        'post_id' => $post_id,
        'skipped_already_queued' => $skipped_queued,
        'skipped_already_imported' => $skipped_imported,
        'remaining_to_queue' => count($images)
      ), $skipped_queued > 0 || $skipped_imported > 0 ? 'warning' : 'info');
    }

    // Add new images to queue
    $added_count = 0;
    foreach ($images as $image) {
      $queue[] = array(
        'post_id' => $post_id,
        'image_name' => $image['name'],
        'image_url' => $image['url'],
        'attempts' => 0,
        'status' => 'pending',
        'added_at' => current_time('mysql')
      );
      $added_count++;
    }

    update_option('kato_sync_image_queue', $queue);

    self::log_image_operation('queue_images_complete', array(
      'post_id' => $post_id,
      'original_images' => $original_images_count,
      'images_added_to_queue' => $added_count,
      'final_queue_size' => count($queue),
      'queue_size_change' => count($queue) - $original_queue_size
    ), 'info');
  }

  /**
   * Get images for a property (works in both modes)
   */
  public static function get_property_images(int $post_id): array {
    $settings = get_option('kato_sync_settings', array());
    $image_mode = $settings['image_mode'] ?? 'local';

    if ($image_mode === 'local') {
      // Try local images first, fallback to external
      $local_images = get_post_meta($post_id, '_kato_sync_local_images', true);
      if (!empty($local_images)) {
        // Convert old format to new format if needed
        $local_images = self::convert_to_new_format($local_images);
        return $local_images;
      }
    }

    // Fallback to external images
    $external_images = get_post_meta($post_id, '_kato_sync_external_images', true);
    return $external_images ?: array();
  }

  /**
   * Convert old image format to new format (clean structure with only sizes array)
   */
  private static function convert_to_new_format(array $images): array {
    $new_images = array();
    $needs_update = false;

    foreach ($images as $image) {
      if (isset($image['attachment_id'])) {
        // Check if this image is in old format (has url, thumbnail, medium, large fields)
        if (isset($image['url']) || isset($image['thumbnail']) || isset($image['medium']) || isset($image['large'])) {
          $needs_update = true;
        }

        // This is a local image
        $new_image = array(
          'attachment_id' => $image['attachment_id'],
          'name' => $image['name']
        );

        // Generate all available sizes
        $image_sizes = array();
        $available_sizes = get_intermediate_image_sizes();
        $available_sizes[] = 'full';

        foreach ($available_sizes as $size) {
          $image_url = wp_get_attachment_image_url($image['attachment_id'], $size);
          if ($image_url) {
            $image_sizes[$size] = $image_url;
          }
        }

        $new_image['sizes'] = $image_sizes;
        $new_images[] = $new_image;
      } else {
        // This is an external image, keep as is
        $new_images[] = $image;
      }
    }

    return $new_images;
  }



  /**
   * Process image queue (called by cron)
   */
  public static function process_image_queue(): array {
    return self::process_image_batch();
  }

  /**
   * Process a single batch of images
   */
  public static function process_image_batch(): array {
    $queue = get_option('kato_sync_image_queue', array());
    $processed = 0;
    $failed = 0;
    $errors = array();

    if (empty($queue)) {
      return array(
        'processed' => 0,
        'failed' => 0,
        'errors' => array(),
        'remaining' => 0
      );
    }

    // Get settings for batch processing
    $settings = self::get_image_settings();

    // Process only batch_size images per run
    $batch = array_slice($queue, 0, $settings['batch_size']);

    foreach ($batch as $index => $item) {
      $result = self::process_single_image($item);

      if ($result['success']) {
        $processed++;
        // Remove from queue
        unset($queue[$index]);
      } else {
        $failed++;
        $errors[] = $result['error'];
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        // Update attempt count
        $queue[$index]['attempts']++;
        $queue[$index]['status'] = $queue[$index]['attempts'] >= self::MAX_RETRIES ? 'failed' : 'pending';
      }
    }

    // Re-index array and save
    $queue = array_values($queue);
    update_option('kato_sync_image_queue', $queue);

    return array(
      'processed' => $processed,
      'failed' => $failed,
      'errors' => $errors,
      'remaining' => count($queue)
    );
  }

  /**
   * Process images continuously until queue is empty or max time reached
   */
  public static function process_images_continuously(int $max_minutes = 10): array {
    $start_time = time();
    $max_seconds = $max_minutes * 60;
    $total_processed = 0;
    $total_failed = 0;
    $total_errors = array();

    while (time() - $start_time < $max_seconds) {
      $queue = get_option('kato_sync_image_queue', array());

      if (empty($queue)) {
        break; // Queue is empty, we're done
      }

      $result = self::process_image_batch();
      $total_processed += $result['processed'];
      $total_failed += $result['failed'];
      $total_errors = array_merge($total_errors, $result['errors']);

      // If no images were processed in this batch, break to avoid infinite loop
      if ($result['processed'] === 0 && $result['failed'] === 0) {
        break;
      }

      // Small delay to prevent overwhelming the server
      usleep(100000); // 0.1 second delay
    }

    return array(
      'processed' => $total_processed,
      'failed' => $total_failed,
      'errors' => $total_errors,
      'remaining' => count(get_option('kato_sync_image_queue', array())),
      'time_elapsed' => time() - $start_time
    );
  }

  /**
   * Process images with specific settings (for import process)
   */
  public static function process_images_with_settings(int $batch_size, int $timeout, int $max_retries): array {
    $queue = get_option('kato_sync_image_queue', array());
    $processed = 0;
    $failed = 0;
    $errors = array();

    if (empty($queue)) {
      return array(
        'processed' => 0,
        'failed' => 0,
        'errors' => array(),
        'remaining' => 0
      );
    }

    // Process only the specified batch size
    $batch = array_slice($queue, 0, $batch_size);

    foreach ($batch as $index => $item) {
      $result = self::process_single_image_with_settings($item, $timeout, $max_retries);

      if ($result['success']) {
        $processed++;
        // Remove from queue
        unset($queue[$index]);
      } else {
        $failed++;
        $errors[] = $result['error'];
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        // Update attempt count
        $queue[$index]['attempts']++;
        $queue[$index]['status'] = $queue[$index]['attempts'] >= $max_retries ? 'failed' : 'pending';
      }
    }

    // Re-index array and save
    $queue = array_values($queue);
    update_option('kato_sync_image_queue', $queue);

    return array(
      'processed' => $processed,
      'failed' => $failed,
      'errors' => $errors,
      'remaining' => count($queue)
    );
  }

  /**
   * Process a single image with specific settings
   */
  private static function process_single_image_with_settings(array $item, int $timeout, int $max_retries): array {
    $post_id = $item['post_id'];
    $image_url = $item['image_url'];
    $image_name = $item['image_name'];

    // Validate URL
    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
      return array('success' => false, 'error' => 'Invalid URL: ' . $image_url);
    }

    // Get image settings
    $settings = get_option('kato_sync_settings', array());
    $max_file_size = intval($settings['image_max_size'] ?? 10) * 1024 * 1024; // Convert MB to bytes

    // Check file size before downloading
    $headers = wp_remote_head($image_url, array('timeout' => 10));
    if (is_wp_error($headers)) {
      return array('success' => false, 'error' => 'Failed to check file size: ' . $headers->get_error_message());
    }

    $file_size = wp_remote_retrieve_header($headers, 'content-length');
    if ($file_size && $file_size > $max_file_size) {
      return array('success' => false, 'error' => 'File too large: ' . $image_url . ' (' . size_format($file_size) . ')');
    }

    // Download image with specified timeout
    $response = wp_remote_get($image_url, array(
      'timeout' => $timeout,
      'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ));

    if (is_wp_error($response)) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
      return array('success' => false, 'error' => 'Download failed: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);

    if ($response_code !== 200) {
      return array('success' => false, 'error' => 'HTTP ' . $response_code . ' response from: ' . $image_url);
    }

    $body = wp_remote_retrieve_body($response);

    if (empty($body)) {
      return array('success' => false, 'error' => 'Empty response from: ' . $image_url);
    }

    // Validate image type
    $image_info = getimagesizefromstring($body);
    if (!$image_info) {
      return array('success' => false, 'error' => 'Invalid image format: ' . $image_url);
    }

    // Create upload directory
    $upload_dir = wp_upload_dir();
    $kato_dir = $upload_dir['basedir'] . '/kato-sync/property-' . $post_id;
    if (!wp_mkdir_p($kato_dir)) {
      return array('success' => false, 'error' => 'Failed to create directory: ' . $kato_dir);
    }

    // Sanitize filename
    $filename = sanitize_file_name($image_name);
    $file_path = $kato_dir . '/' . $filename;

    // Save file
    if (file_put_contents($file_path, $body) === false) {
      return array('success' => false, 'error' => 'Failed to save file: ' . $file_path);
    }

    // Add to WordPress media library
    $attachment_id = self::add_to_media_library($file_path, $post_id, $image_name);
    if (!$attachment_id) {
      return array('success' => false, 'error' => 'Failed to add to media library: ' . $filename);
    }

    // Update property with local image
    self::update_property_local_images($post_id, $attachment_id, $image_name);

    return array('success' => true);
  }

  /**
   * Process a single image download
   */
  private static function process_single_image(array $item): array {
    $post_id = $item['post_id'];
    $image_url = $item['image_url'];
    $image_name = $item['image_name'];

    // Validate URL
    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
      return array('success' => false, 'error' => 'Invalid URL: ' . $image_url);
    }

    // Get image settings
    $image_settings = self::get_image_settings();

    // Check file size before downloading
    $headers = wp_remote_head($image_url, array('timeout' => 10));
    if (is_wp_error($headers)) {
      return array('success' => false, 'error' => 'Failed to check file size: ' . $headers->get_error_message());
    }

    $file_size = wp_remote_retrieve_header($headers, 'content-length');
    if ($file_size && $file_size > $image_settings['max_file_size']) {
      return array('success' => false, 'error' => 'File too large: ' . $image_url . ' (' . size_format($file_size) . ')');
    }

    // Download image - using simple approach that worked in earlier versions
    $response = wp_remote_get($image_url, array(
      'timeout' => $image_settings['timeout'],
      'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ));

    if (is_wp_error($response)) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
      return array('success' => false, 'error' => 'Download failed: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);

    if ($response_code !== 200) {
      return array('success' => false, 'error' => 'HTTP ' . $response_code . ' response from: ' . $image_url);
    }

    $body = wp_remote_retrieve_body($response);

    if (empty($body)) {

      // Try alternative approach - simplest possible
      $alt_response = wp_remote_get($image_url, array(
        'timeout' => $image_settings['timeout']
      ));

      if (!is_wp_error($alt_response)) {
        $alt_body = wp_remote_retrieve_body($alt_response);

        if (!empty($alt_body)) {
          $body = $alt_body;
        } else {
          return array('success' => false, 'error' => 'Empty response from: ' . $image_url);
        }
      } else {
        return array('success' => false, 'error' => 'Empty response from: ' . $image_url);
      }
    }

    // Validate image type
    $image_info = getimagesizefromstring($body);
    if (!$image_info) {
      return array('success' => false, 'error' => 'Invalid image format: ' . $image_url);
    }

    // Create upload directory
    $upload_dir = wp_upload_dir();
    $kato_dir = $upload_dir['basedir'] . '/kato-sync/property-' . $post_id;
    if (!wp_mkdir_p($kato_dir)) {
      return array('success' => false, 'error' => 'Failed to create directory: ' . $kato_dir);
    }

    // Sanitize filename
    $filename = sanitize_file_name($image_name);
    $file_path = $kato_dir . '/' . $filename;

    // Save file
    if (file_put_contents($file_path, $body) === false) {
      return array('success' => false, 'error' => 'Failed to save file: ' . $file_path);
    }

    // Add to WordPress media library
    $attachment_id = self::add_to_media_library($file_path, $post_id, $image_name);
    if (!$attachment_id) {
      return array('success' => false, 'error' => 'Failed to add to media library: ' . $filename);
    }

    // Update property with local image
    self::update_property_local_images($post_id, $attachment_id, $image_name);

    return array('success' => true);
  }

  /**
   * Add image to WordPress media library
   */
  private static function add_to_media_library(string $file_path, int $post_id, string $image_name): ?int {
    $upload_dir = wp_upload_dir();
    $relative_path = str_replace($upload_dir['basedir'] . '/', '', $file_path);

    $attachment = array(
      'post_mime_type' => mime_content_type($file_path),
      'post_title' => sanitize_text_field($image_name),
      'post_content' => '',
      'post_status' => 'inherit'
    );

    $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
    if (is_wp_error($attachment_id)) {
      return null;
    }

    // Generate image sizes
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attachment_data);

    return $attachment_id;
  }

  /**
   * Update property with local image information
   */
  private static function update_property_local_images(int $post_id, int $attachment_id, string $image_name): void {
    // Update postmeta (existing functionality)
    $local_images = get_post_meta($post_id, '_kato_sync_local_images', true) ?: array();

    // Get all available image sizes for this attachment
    $image_sizes = array();

    // Get all registered image sizes
    $available_sizes = get_intermediate_image_sizes();

    // Add full size
    $available_sizes[] = 'full';

    // Generate URLs for all available sizes
    foreach ($available_sizes as $size) {
      $image_url = wp_get_attachment_image_url($attachment_id, $size);
      if ($image_url) {
        $image_sizes[$size] = $image_url;
      }
    }

    $local_images[] = array(
      'attachment_id' => $attachment_id,
      'name' => $image_name,
      'sizes' => $image_sizes
    );

    update_post_meta($post_id, '_kato_sync_local_images', $local_images);
    update_post_meta($post_id, '_kato_sync_image_mode', 'local');

    // NEW: Update the wp_kato_property_media table with attachment_id
    self::update_media_table_with_attachment($post_id, $attachment_id, $image_name);
  }

  /**
   * Update wp_kato_property_media table with attachment_id after successful import
   */
  private static function update_media_table_with_attachment(int $post_id, int $attachment_id, string $image_name): void {
    global $wpdb;

    $table_media = $wpdb->prefix . 'kato_property_media';

    // Get the WordPress attachment URL to match against stored URLs
    $attachment_url = wp_get_attachment_url($attachment_id);

    if (!$attachment_url) {
      return;
    }

    // Try to find the media record by matching the original URL pattern
    // We'll look for records with this post_id that don't have an attachment_id yet
    $media_records = $wpdb->get_results($wpdb->prepare(
      "SELECT id, url FROM {$table_media}
       WHERE post_id = %d
       AND type = 'image'
       AND attachment_id IS NULL",
      $post_id
    ));

    if (!$media_records) {
      return;
    }

    // Try to match by filename or image name
    $attachment_filename = basename($attachment_url);
    $sanitized_name = sanitize_file_name($image_name);

    foreach ($media_records as $record) {
      $record_filename = basename($record->url);

      // Get the base filename without extension for both files
      $record_base = basename($record->url, '.' . pathinfo($record->url, PATHINFO_EXTENSION));
      $attachment_base = basename($attachment_filename, '.' . pathinfo($attachment_filename, PATHINFO_EXTENSION));
      $sanitized_base = basename($sanitized_name, '.' . pathinfo($sanitized_name, PATHINFO_EXTENSION));

      // Match by various strategies:
      // 1. Exact filename match
      // 2. WordPress often adds hash prefixes, so check if attachment contains original
      // 3. Check if original URL contains part of the attachment name
      // 4. Match by image_name parameter
      if (
        $record_filename === $attachment_filename ||
        strpos($attachment_base, $record_base) !== false ||
        strpos($record_base, $attachment_base) !== false ||
        $record_base === $sanitized_base ||
        strpos($attachment_base, $sanitized_base) !== false ||
        strpos($sanitized_base, $record_base) !== false
      ) {
        // Update this record with the attachment_id
        $wpdb->update(
          $table_media,
          ['attachment_id' => $attachment_id],
          ['id' => $record->id],
          ['%d'],
          ['%d']
        );
        break; // Only update the first match
      }
    }
  }

  /**
   * Get queue status
   */
  public static function get_queue_status(): array {
    $queue = get_option('kato_sync_image_queue', array());

    $pending = 0;
    $failed = 0;

    foreach ($queue as $item) {
      if ($item['status'] === 'failed') {
        $failed++;
      } else {
        $pending++;
      }
    }

    return array(
      'pending' => $pending,
      'failed' => $failed,
      'total' => count($queue)
    );
  }

  /**
   * Get total images count from all properties
   */
  public static function get_total_images_count(): int {
    global $wpdb;

    // Count all original_images from all properties (this is the feed data)
    $total_count = 0;

    // First try original_images
    $original_images_meta = $wpdb->get_col($wpdb->prepare(
      "SELECT meta_value FROM {$wpdb->postmeta}
       WHERE meta_key = %s",
      '_kato_sync_original_images'
    ));

    foreach ($original_images_meta as $meta_value) {
      $images = maybe_unserialize($meta_value);
      if (is_array($images)) {
        $total_count += count($images);
      }
    }

    // If no original_images found, try external_images as fallback
    if ($total_count === 0) {
      $external_images_meta = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta}
         WHERE meta_key = %s",
        '_kato_sync_external_images'
      ));

      foreach ($external_images_meta as $meta_value) {
        $images = maybe_unserialize($meta_value);
        if (is_array($images)) {
          $total_count += count($images);
        }
      }
    }

    return $total_count;
  }

  /**
   * Get next batch timing
   */
  public static function get_next_batch_timing(): string {
    $next_cron = wp_next_scheduled('kato_sync_process_images');

    if (!$next_cron) {
      return 'Not scheduled';
    }

    $time_diff = $next_cron - time();

    if ($time_diff <= 0) {
      return 'Due now';
    } elseif ($time_diff < 60) {
      return 'Starting in ' . $time_diff . ' seconds';
    } elseif ($time_diff < 3600) {
      $minutes = floor($time_diff / 60);
      return 'Starting in ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    } else {
      $hours = floor($time_diff / 3600);
      return 'Starting in ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
    }
  }

  /**
   * Retry failed images
   */
  public static function retry_failed_images(): array {
    $queue = get_option('kato_sync_image_queue', array());
    $retried = 0;

    foreach ($queue as $index => $item) {
      if ($item['status'] === 'failed') {
        $queue[$index]['attempts'] = 0;
        $queue[$index]['status'] = 'pending';
        $retried++;
      }
    }

    update_option('kato_sync_image_queue', $queue);

    return array('retried' => $retried);
  }

  /**
   * Clear image queue
   */
  public static function clear_image_queue(): void {
    update_option('kato_sync_image_queue', array());
  }

  /**
   * Get failed images for admin display
   */
  public static function get_failed_images(): array {
    $queue = get_option('kato_sync_image_queue', array());
    $failed = array();

    foreach ($queue as $item) {
      if ($item['status'] === 'failed') {
        $failed[] = $item;
      }
    }

    return $failed;
  }

  /**
   * Get the raw image queue for debugging
   */
  public static function get_image_queue(): array {
    return get_option('kato_sync_image_queue', array());
  }

  /**
   * Get detailed queue information for debugging
   */
  public static function get_queue_details(): array {
    $queue = get_option('kato_sync_image_queue', array());
    $details = array(
      'total_items' => count($queue),
      'pending_items' => 0,
      'failed_items' => 0,
      'unique_properties' => array(),
      'property_counts' => array(),
      'sample_items' => array()
    );

    foreach ($queue as $item) {
      if ($item['status'] === 'failed') {
        $details['failed_items']++;
      } else {
        $details['pending_items']++;
      }

      $post_id = $item['post_id'];
      if (!in_array($post_id, $details['unique_properties'])) {
        $details['unique_properties'][] = $post_id;
      }

      if (!isset($details['property_counts'][$post_id])) {
        $details['property_counts'][$post_id] = 0;
      }
      $details['property_counts'][$post_id]++;

      // Store first 5 items as samples
      if (count($details['sample_items']) < 5) {
        $details['sample_items'][] = array(
          'post_id' => $post_id,
          'image_name' => $item['image_name'],
          'status' => $item['status'],
          'attempts' => $item['attempts']
        );
      }
    }

    return $details;
  }

  /**
   * Test image URLs to check accessibility
   */
  public static function test_image_urls(): array {
    $queue = get_option('kato_sync_image_queue', array());

    if (empty($queue)) {
      return array('success' => false, 'error' => 'No images in queue to test');
    }

    // Test first 5 images
    $test_batch = array_slice($queue, 0, 5);
    $accessible = 0;
    $inaccessible = 0;
    $results = array();

    foreach ($test_batch as $item) {
      $image_url = $item['image_url'];

      // Test with HEAD request first
      $headers = wp_remote_head($image_url, array(
        'timeout' => 10,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
      ));

      if (is_wp_error($headers)) {
        $inaccessible++;
        $results[] = array(
          'url' => $image_url,
          'status' => 'error',
          'message' => $headers->get_error_message()
        );
        continue;
      }

      $response_code = wp_remote_retrieve_response_code($headers);
      $content_length = wp_remote_retrieve_header($headers, 'content-length');
      $content_type = wp_remote_retrieve_header($headers, 'content-type');

      if ($response_code === 200 && $content_length > 0) {
        $accessible++;
        $results[] = array(
          'url' => $image_url,
          'status' => 'accessible',
          'response_code' => $response_code,
          'content_length' => $content_length,
          'content_type' => $content_type
        );
      } else {
        $inaccessible++;
        $results[] = array(
          'url' => $image_url,
          'status' => 'inaccessible',
          'response_code' => $response_code,
          'content_length' => $content_length,
          'content_type' => $content_type
        );
      }
    }

    // Log results for debugging
    foreach ($results as $result) {
    }

    return array(
      'success' => true,
      'total' => count($test_batch),
      'accessible' => $accessible,
      'inaccessible' => $inaccessible,
      'results' => $results
    );
  }

  /**
   * Get the count of successfully imported images
   */
  public static function get_imported_images_count(): int {
    global $wpdb;

    // Count all image attachments that are attached to kato-property posts
    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->posts} p
       INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
       WHERE p.post_type = 'attachment'
       AND p.post_mime_type LIKE 'image/%'
       AND pm.meta_key = %s
       AND pm.meta_value LIKE %s",
      '_wp_attached_file',
      '%kato-sync%'
    ));

    return (int) $count;
  }

  /**
   * Check if a property has any imported images
   */
  public static function property_has_imported_images(int $post_id): bool {
    $local_images = get_post_meta($post_id, '_kato_sync_local_images', true);
    return !empty($local_images) && is_array($local_images);
  }

  /**
   * Get the count of imported images for a property
   */
  public static function get_property_imported_image_count(int $post_id): int {
    $local_images = get_post_meta($post_id, '_kato_sync_local_images', true);
    return !empty($local_images) && is_array($local_images) ? count($local_images) : 0;
  }

  /**
   * Get detailed import status for a property's images
   */
  public static function get_property_image_import_status(int $post_id): array {
    global $wpdb;

    $table_media = $wpdb->prefix . 'kato_property_media';

    $results = $wpdb->get_results($wpdb->prepare(
      "SELECT
         COUNT(*) as total_images,
         COUNT(attachment_id) as imported_images,
         COUNT(*) - COUNT(attachment_id) as pending_images
       FROM {$table_media}
       WHERE post_id = %d AND type = 'image'",
      $post_id
    ));

    if (empty($results)) {
      return [
        'total_images' => 0,
        'imported_images' => 0,
        'pending_images' => 0,
        'import_percentage' => 0,
        'all_imported' => false
      ];
    }

    $result = $results[0];
    $total = (int)$result->total_images;
    $imported = (int)$result->imported_images;
    $pending = (int)$result->pending_images;

    return [
      'total_images' => $total,
      'imported_images' => $imported,
      'pending_images' => $pending,
      'import_percentage' => $total > 0 ? round(($imported / $total) * 100) : 0,
      'all_imported' => $total > 0 && $pending === 0
    ];
  }

  /**
   * Check if all images for a property have been imported
   */
  public static function are_all_images_imported(int $post_id): bool {
    $status = self::get_property_image_import_status($post_id);
    return $status['all_imported'];
  }

  /**
   * Get property images with import status flags
   */
  public static function get_property_images_with_status(int $post_id): array {
    global $wpdb;

    $table_media = $wpdb->prefix . 'kato_property_media';

    $media_records = $wpdb->get_results($wpdb->prepare(
      "SELECT url, attachment_id, alt, sort_order, raw_json
       FROM {$table_media}
       WHERE post_id = %d AND type = 'image'
       ORDER BY sort_order ASC, id ASC",
      $post_id
    ));

    $images = [];

    foreach ($media_records as $record) {
      $is_imported = !is_null($record->attachment_id);

      $image_data = [
        'url' => $record->url,
        'alt' => $record->alt,
        'is_imported' => $is_imported,
        'attachment_id' => $record->attachment_id,
        'sort_order' => $record->sort_order
      ];

      // If imported, get WordPress image sizes
      if ($is_imported) {
        $image_data['sizes'] = [];
        $available_sizes = get_intermediate_image_sizes();
        $available_sizes[] = 'full';

        foreach ($available_sizes as $size) {
          $image_url = wp_get_attachment_image_url($record->attachment_id, $size);
          if ($image_url) {
            $image_data['sizes'][$size] = $image_url;
          }
        }

        $image_data['name'] = get_post_meta($record->attachment_id, '_wp_attachment_image_alt', true) ?: basename($record->url);
      } else {
        // Not imported yet, use original URL
        $image_data['name'] = basename($record->url);
      }

      $images[] = $image_data;
    }

    return $images;
  }

  /**
   * Enhanced logging for image operations (admin only - no debug.log)
   */
  public static function log_image_operation(string $operation, array $details = array(), string $level = 'info'): void {
    // Check if logging is disabled
    if (defined('KATO_SYNC_DISABLE_LOGGING') && KATO_SYNC_DISABLE_LOGGING) {
      return;
    }

    // Only log to admin options, not debug.log
    $timestamp = current_time('Y-m-d H:i:s');
    $log_entry = array(
      'timestamp' => $timestamp,
      'operation' => $operation,
      'level' => $level,
      'details' => $details,
      'memory_usage' => size_format(memory_get_usage(true)),
      'peak_memory' => size_format(memory_get_peak_usage(true))
    );

    // Store in option for admin viewing only
    $log_history = get_option('kato_sync_image_operation_logs', array());

    // Keep only last 50 entries to prevent bloat (reduced from 100)
    if (count($log_history) >= 50) {
      $log_history = array_slice($log_history, -49);
    }

    $log_history[] = $log_entry;
    update_option('kato_sync_image_operation_logs', $log_history);
  }

  /**
   * Get queue health status with detailed diagnostics
   */
  public static function get_queue_health_status(): array {
    $queue = get_option('kato_sync_image_queue', array());
    $total_items = count($queue);

    if ($total_items === 0) {
      return array(
        'status' => 'empty',
        'total_items' => 0,
        'message' => 'Queue is empty',
        'health_score' => 100
      );
    }

    $pending_count = 0;
    $failed_count = 0;
    $stuck_count = 0;
    $properties_with_items = array();
    $oldest_item_age = 0;
    $failed_items = array();

    $current_time = time();

    foreach ($queue as $item) {
      $post_id = $item['post_id'];
      $properties_with_items[$post_id] = ($properties_with_items[$post_id] ?? 0) + 1;

      // Check item age
      $added_time = strtotime($item['added_at']);
      $age_hours = ($current_time - $added_time) / 3600;
      $oldest_item_age = max($oldest_item_age, $age_hours);

      // Check status
      if ($item['status'] === 'failed') {
        $failed_count++;
        $failed_items[] = array(
          'post_id' => $post_id,
          'image_name' => $item['image_name'],
          'attempts' => $item['attempts'],
          'age_hours' => round($age_hours, 1)
        );
      } elseif ($item['status'] === 'pending') {
        $pending_count++;

        // Check if stuck (pending for more than 2 hours)
        if ($age_hours > 2) {
          $stuck_count++;
        }
      }
    }

    // Calculate health score
    $health_score = 100;
    if ($failed_count > 0) $health_score -= ($failed_count / $total_items) * 50;
    if ($stuck_count > 0) $health_score -= ($stuck_count / $total_items) * 30;
    if ($oldest_item_age > 24) $health_score -= 20; // Very old items

    $health_score = max(0, $health_score);

    $status = 'healthy';
    if ($health_score < 50) $status = 'critical';
    elseif ($health_score < 75) $status = 'warning';
    elseif ($stuck_count > 0 || $failed_count > 0) $status = 'attention';

    $result = array(
      'status' => $status,
      'health_score' => round($health_score),
      'total_items' => $total_items,
      'pending_count' => $pending_count,
      'failed_count' => $failed_count,
      'stuck_count' => $stuck_count,
      'properties_count' => count($properties_with_items),
      'oldest_item_hours' => round($oldest_item_age, 1),
      'failed_items' => array_slice($failed_items, 0, 10), // Show up to 10 failed items
      'properties_breakdown' => $properties_with_items
    );

    self::log_image_operation('queue_health_check', $result, 'info');

    return $result;
  }

  /**
   * Validate image settings for consistency
   */
  public static function validate_image_settings(array $settings): array {
    $errors = array();
    $warnings = array();
    $valid_settings = $settings;

    // Check image mode
    if (!isset($settings['image_mode']) || !in_array($settings['image_mode'], ['local', 'external'])) {
      $errors[] = 'Invalid image mode. Must be "local" or "external"';
      $valid_settings['image_mode'] = 'local'; // Default fallback
    }

    // Check batch size
    if (isset($settings['batch_size'])) {
      $batch_size = intval($settings['batch_size']);
      if ($batch_size < 1 || $batch_size > 100) {
        $warnings[] = 'Batch size should be between 1 and 100. Using default.';
        $valid_settings['batch_size'] = self::DEFAULT_BATCH_SIZE;
      }
    }

    // Check timeout
    if (isset($settings['download_timeout'])) {
      $timeout = intval($settings['download_timeout']);
      if ($timeout < 5 || $timeout > 300) {
        $warnings[] = 'Download timeout should be between 5 and 300 seconds. Using default.';
        $valid_settings['download_timeout'] = self::DEFAULT_DOWNLOAD_TIMEOUT;
      }
    }

    // Check max file size
    if (isset($settings['max_file_size'])) {
      $max_size = intval($settings['max_file_size']);
      if ($max_size < 1048576 || $max_size > 52428800) { // 1MB to 50MB
        $warnings[] = 'Max file size should be between 1MB and 50MB. Using default.';
        $valid_settings['max_file_size'] = self::DEFAULT_MAX_FILE_SIZE;
      }
    }

    $result = array(
      'is_valid' => empty($errors),
      'errors' => $errors,
      'warnings' => $warnings,
      'validated_settings' => $valid_settings
    );

    if (!empty($errors) || !empty($warnings)) {
      self::log_image_operation('settings_validation', $result, empty($errors) ? 'warning' : 'error');
    }

    return $result;
  }

  /**
   * Daily maintenance cleanup
   */
  public static function daily_maintenance(): array {
    $results = array(
      'queue_cleanup' => array(),
      'orphaned_files' => array(),
      'failed_items_removed' => 0,
      'old_logs_removed' => 0
    );

    self::log_image_operation('daily_maintenance_start', array(), 'info');

    // 1. Clean up the queue
    $results['queue_cleanup'] = self::cleanup_image_queue();

    // 2. Remove failed items older than 7 days
    $queue = get_option('kato_sync_image_queue', array());
    $cleaned_queue = array();
    $failed_removed = 0;
    $week_ago = time() - (7 * 24 * 3600);

    foreach ($queue as $item) {
      $item_time = strtotime($item['added_at']);
      if ($item['status'] === 'failed' && $item_time < $week_ago) {
        $failed_removed++;
        self::log_image_operation('maintenance_remove_old_failed', array(
          'post_id' => $item['post_id'],
          'image_name' => $item['image_name'],
          'age_days' => round((time() - $item_time) / 86400, 1)
        ), 'info');
      } else {
        $cleaned_queue[] = $item;
      }
    }

    if ($failed_removed > 0) {
      update_option('kato_sync_image_queue', $cleaned_queue);
      $results['failed_items_removed'] = $failed_removed;
    }

    // 3. Clean up old logs (keep only last 7 days)
    $logs = get_option('kato_sync_image_operation_logs', array());
    $week_ago_formatted = date('Y-m-d H:i:s', $week_ago);
    $cleaned_logs = array_filter($logs, function ($log) use ($week_ago_formatted) {
      return $log['timestamp'] >= $week_ago_formatted;
    });

    $old_logs_removed = count($logs) - count($cleaned_logs);
    if ($old_logs_removed > 0) {
      update_option('kato_sync_image_operation_logs', array_values($cleaned_logs));
      $results['old_logs_removed'] = $old_logs_removed;
    }

    // 4. Check for orphaned files
    $upload_dir = wp_upload_dir();
    $kato_sync_dir = $upload_dir['basedir'] . '/kato-sync';

    if (file_exists($kato_sync_dir)) {
      $property_folders = glob($kato_sync_dir . '/property-*', GLOB_ONLYDIR);
      $orphaned_folders = array();

      foreach ($property_folders as $folder) {
        $folder_name = basename($folder);
        $post_id = str_replace('property-', '', $folder_name);

        if (is_numeric($post_id)) {
          $post = get_post($post_id);
          if (!$post || !in_array($post->post_type, ['kato_property', 'kato-property'])) {
            $orphaned_folders[] = $folder;
          }
        }
      }

      $results['orphaned_files'] = $orphaned_folders;
    }

    self::log_image_operation('daily_maintenance_complete', $results, 'info');

    return $results;
  }

  /**
   * Clean up the image queue by removing items that have already been successfully imported
   */
  public static function cleanup_image_queue(): array {
    $queue = get_option('kato_sync_image_queue', array());
    $original_count = count($queue);
    $removed_count = 0;
    $cleaned_queue = array();

    foreach ($queue as $item) {
      $post_id = $item['post_id'];
      $image_name = $item['image_name'];

      // Check if this image has already been imported
      $local_images = get_post_meta($post_id, '_kato_sync_local_images', true);
      $already_imported = false;

      if (!empty($local_images) && is_array($local_images)) {
        foreach ($local_images as $local_image) {
          if (isset($local_image['name']) && $local_image['name'] === $image_name) {
            $already_imported = true;
            break;
          }
        }
      }

      if ($already_imported) {
        $removed_count++;
      } else {
        $cleaned_queue[] = $item;
      }
    }

    if ($removed_count > 0) {
      update_option('kato_sync_image_queue', $cleaned_queue);
    }

    return array(
      'original_count' => $original_count,
      'removed_count' => $removed_count,
      'remaining_count' => count($cleaned_queue)
    );
  }

  /**
   * AJAX handler for removing all imported images
   */
  public static function ajax_remove_all_images(): void {
    check_ajax_referer('kato_sync_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'kato-sync'));
    }

    try {
      $removed_count = 0;
      $failed_count = 0;
      $folder_removed = false;
      $attachments_removed = 0;

      // 1. Remove the physical kato-sync folder from uploads
      $upload_dir = wp_upload_dir();
      $kato_dir = $upload_dir['basedir'] . '/kato-sync';

      if (is_dir($kato_dir)) {
        $folder_removed = self::remove_directory_recursive($kato_dir);
      }

      // 2. Find and remove any orphaned Kato Sync attachments from media library
      $kato_attachments = get_posts(array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_status' => 'any',
        'meta_query' => array(
          'relation' => 'OR',
          array(
            'key' => '_wp_attached_file',
            'value' => 'kato-sync',
            'compare' => 'LIKE'
          )
        )
      ));

      foreach ($kato_attachments as $attachment) {
        $result = wp_delete_attachment($attachment->ID, true);
        if ($result) {
          $attachments_removed++;
        } else {
          $failed_count++;
        }
      }

      // 3. Clear all image-related options and queues
      delete_option('kato_sync_image_queue');
      delete_option('kato_sync_failed_images');
      delete_option('kato_sync_image_processing_lock');
      delete_option('kato_sync_last_image_batch');

      // 4. Build result message
      $message_parts = array();

      if ($folder_removed) {
        $message_parts[] = 'Removed kato-sync folder from uploads';
      }

      if ($attachments_removed > 0) {
        $message_parts[] = sprintf('Removed %d orphaned attachments from media library', $attachments_removed);
      }

      $message_parts[] = 'Cleared image queue and processing data';

      $message = 'Successfully: ' . implode(', ', $message_parts) . '.';

      if ($failed_count > 0) {
        $message .= sprintf(' %d attachments failed to delete.', $failed_count);
      }

      wp_send_json_success(array(
        'message' => $message,
        'folder_removed' => $folder_removed,
        'attachments_removed' => $attachments_removed,
        'failed_count' => $failed_count,
        'queues_cleared' => true
      ));
    } catch (\Exception $e) {
      wp_send_json_error(__('Error removing images: ', 'kato-sync') . $e->getMessage());
    }
  }

  /**
   * Backfill attachment_id values for existing imported images
   * This fixes cases where images were imported but the media table wasn't updated
   */
  public static function backfill_attachment_ids(): array {
    global $wpdb;

    $results = [
      'total_checked' => 0,
      'updated' => 0,
      'errors' => []
    ];

    $table_media = $wpdb->prefix . 'kato_property_media';

    // Get all kato-property posts that have local images
    $posts_with_images = $wpdb->get_results("
      SELECT p.ID, pm.meta_value
      FROM {$wpdb->posts} p
      INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
      WHERE p.post_type = 'kato-property'
      AND pm.meta_key = '_kato_sync_local_images'
      AND pm.meta_value != ''
      AND pm.meta_value != 'a:0:{}'
    ");

    foreach ($posts_with_images as $post_data) {
      $post_id = (int)$post_data->ID;
      $local_images = maybe_unserialize($post_data->meta_value);

      if (!is_array($local_images) || empty($local_images)) {
        continue;
      }

      foreach ($local_images as $local_image) {
        if (empty($local_image['attachment_id']) || empty($local_image['name'])) {
          continue;
        }

        $attachment_id = (int)$local_image['attachment_id'];
        $image_name = $local_image['name'];

        // Check if this attachment actually exists in WordPress
        if (!get_post($attachment_id)) {
          continue;
        }

        $results['total_checked']++;

        // Try to update media table records for this image
        $attachment_url = wp_get_attachment_url($attachment_id);
        if (!$attachment_url) {
          continue;
        }

        // Find media records that need updating
        $media_records = $wpdb->get_results($wpdb->prepare(
          "SELECT id, url FROM {$table_media}
           WHERE post_id = %d
           AND type = 'image'
           AND attachment_id IS NULL",
          $post_id
        ));

        if (!$media_records) {
          continue;
        }

        // Try to match this attachment to a media record
        $attachment_filename = basename($attachment_url);
        $sanitized_name = sanitize_file_name($image_name);

        foreach ($media_records as $record) {
          $record_filename = basename($record->url);

          // Get the base filename without extension for both files
          $record_base = basename($record->url, '.' . pathinfo($record->url, PATHINFO_EXTENSION));
          $attachment_base = basename($attachment_filename, '.' . pathinfo($attachment_filename, PATHINFO_EXTENSION));
          $sanitized_base = basename($sanitized_name, '.' . pathinfo($sanitized_name, PATHINFO_EXTENSION));

          // Match by various strategies
          if (
            $record_filename === $attachment_filename ||
            strpos($attachment_base, $record_base) !== false ||
            strpos($record_base, $attachment_base) !== false ||
            $record_base === $sanitized_base ||
            strpos($attachment_base, $sanitized_base) !== false ||
            strpos($sanitized_base, $record_base) !== false
          ) {
            // Update this record with the attachment_id
            $update_result = $wpdb->update(
              $table_media,
              ['attachment_id' => $attachment_id],
              ['id' => $record->id],
              ['%d'],
              ['%d']
            );

            if ($update_result !== false) {
              $results['updated']++;
            } else {
              $results['errors'][] = "Failed to update media record {$record->id} for post {$post_id}";
            }
            break; // Only update the first match
          }
        }
      }
    }

    return $results;
  }

  /**
   * Recursively remove a directory and all its contents
   */
  private static function remove_directory_recursive(string $dir): bool {
    if (!is_dir($dir)) {
      return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
      $path = $dir . '/' . $file;
      if (is_dir($path)) {
        self::remove_directory_recursive($path);
      } else {
        unlink($path);
      }
    }

    return rmdir($dir);
  }
}
