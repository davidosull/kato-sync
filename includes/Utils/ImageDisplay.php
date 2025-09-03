<?php

namespace KatoSync\Utils;

use KatoSync\Sync\ImageProcessor;

/**
 * Utility class for displaying property images
 */
class ImageDisplay {

  /**
   * Get images for a property with fallback support
   */
  public static function get_property_images(int $post_id): array {
    return ImageProcessor::get_property_images($post_id);
  }

  /**
   * Get the first image URL for a property (useful for thumbnails)
   */
  public static function get_first_image_url(int $post_id, string $size = 'medium'): ?string {
    $images = self::get_property_images($post_id);

    if (empty($images)) {
      return null;
    }

    $first_image = $images[0];

    // Handle local images (WordPress media library)
    if (isset($first_image['attachment_id'])) {
      // Check if we have the specific size in our sizes array
      if (isset($first_image['sizes'][$size])) {
        return $first_image['sizes'][$size];
      }

      // Fallback to WordPress function
      return \wp_get_attachment_image_url($first_image['attachment_id'], $size);
    }

    // Handle external images
    if (isset($first_image['url'])) {
      return $first_image['url'];
    }

    return null;
  }

  /**
   * Display property images in admin interface
   */
  public static function display_property_images_admin(int $post_id): void {
    $images = self::get_property_images($post_id);
    $settings = \get_option('kato_sync_settings', array());
    $image_mode = $settings['image_mode'] ?? 'local';

    // Get queue status for this specific property
    $queue = \get_option('kato_sync_image_queue', array());
    $property_queue_items = array_filter($queue, function ($item) use ($post_id) {
      return $item['post_id'] == $post_id;
    });

    $pending_count = count(array_filter($property_queue_items, function ($item) {
      return $item['status'] !== 'failed';
    }));
    $failed_count = count(array_filter($property_queue_items, function ($item) {
      return $item['status'] === 'failed';
    }));

    if (empty($images) && $pending_count === 0) {
      echo '<p>' . \__('No images available for this property.', 'kato-sync') . '</p>';
      return;
    }

    echo '<div class="kato-sync-images">';
    echo '<h4>' . \__('Property Images', 'kato-sync') . '</h4>';

    // Show processing status if there are pending images
    if ($image_mode === 'local' && $pending_count > 0) {
      echo '<div class="notice notice-info">';
      echo '<p>' . sprintf(
        __('%d images are queued for processing (%d pending, %d failed).', 'kato-sync'),
        count($property_queue_items),
        $pending_count,
        $failed_count
      ) . '</p>';
      echo '<p><a href="' . admin_url('admin.php?page=kato-sync-images') . '">' .
        __('View Image Management', 'kato-sync') . '</a></p>';
      echo '</div>';
    }

    // Show available images (either local or external)
    if (!empty($images)) {
      echo '<div class="kato-sync-image-grid">';
      foreach ($images as $index => $image) {
        $image_url = null;
        $image_title = '';
        $image_status = '';

        if (isset($image['attachment_id'])) {
          // Local image - check if we have the specific size in our sizes array
          if (isset($image['sizes']['medium'])) {
            $image_url = $image['sizes']['medium'];
          } else {
            // Fallback to WordPress function
            $image_url = wp_get_attachment_image_url($image['attachment_id'], 'medium');
          }
          $image_title = $image['name'] ?? '';
          $image_status = ' (Imported)';
        } elseif (isset($image['url'])) {
          // External image
          $image_url = $image['url'];
          $image_title = $image['name'] ?? basename($image['url']);
          $image_status = ' (External)';
        }

        if ($image_url) {
          echo '<div class="kato-sync-image-item">';
          echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_title) . '" />';
          echo '<p class="image-title">' . esc_html($image_title . $image_status) . '</p>';
          echo '</div>';
        }
      }
      echo '</div>';
    }

    // Show queue status if no images are displayed but there are pending items
    if (empty($images) && $pending_count > 0) {
      echo '<div class="notice notice-warning">';
      echo '<p>' . sprintf(
        __('%d images are queued for processing. They will appear here once processed.', 'kato-sync'),
        $pending_count
      ) . '</p>';
      echo '</div>';
    }

    echo '</div>';
  }

  /**
   * Display property images in frontend
   */
  public static function display_frontend_images(int $post_id, string $size = 'medium'): void {
    $images = self::get_property_images($post_id);

    if (empty($images)) {
      return;
    }

    echo '<div class="kato-sync-property-images">';
    foreach ($images as $index => $image) {
      $image_url = null;
      $image_alt = '';

      if (isset($image['attachment_id'])) {
        // Local image - check if we have the specific size in our sizes array
        if (isset($image['sizes'][$size])) {
          $image_url = $image['sizes'][$size];
        } else {
          // Fallback to WordPress function
          $image_url = wp_get_attachment_image_url($image['attachment_id'], $size);
        }
        $image_alt = $image['name'] ?? '';
      } elseif (isset($image['url'])) {
        // External image
        $image_url = $image['url'];
        $image_alt = $image['name'] ?? basename($image['url']);
      }

      if ($image_url) {
        echo '<div class="property-image">';
        echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" />';
        echo '</div>';
      }
    }
    echo '</div>';
  }

  /**
   * Get image count for a property
   */
  public static function get_image_count(int $post_id): int {
    $images = self::get_property_images($post_id);
    return count($images);
  }

  /**
   * Check if property has images
   */
  public static function has_images(int $post_id): bool {
    return self::get_image_count($post_id) > 0;
  }

  /**
   * Get card image HTML for a property (first image with theme-configurable size)
   *
   * This function displays the first image from the property's image array.
   * Before import: shows simple <img> tag with URL from feed
   * After import: uses WordPress responsive image functions
   *
   * @param int $post_id Property post ID
   * @param string $default_size Default image size (medium)
   * @param array $attributes Additional HTML attributes for the img tag
   * @return string HTML img tag or empty string if no image
   */
  public static function get_card_image_html(int $post_id, string $default_size = 'medium', array $attributes = array()): string {
    $images = self::get_property_images($post_id);

    if (empty($images)) {
      return '';
    }

    $first_image = $images[0];

    // Allow theme to override the image size
    $image_size = \apply_filters('kato_sync_card_image_size', $default_size, $post_id);

    // Set up default attributes
    $default_attributes = array(
      'class' => 'kato-sync-card-image',
      'loading' => 'lazy'
    );

    $attributes = \wp_parse_args($attributes, $default_attributes);

    // Handle local images (imported to WordPress media library)
    if (isset($first_image['attachment_id'])) {
      $attachment_id = $first_image['attachment_id'];
      $image_alt = $first_image['name'] ?? \get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

      // Use WordPress responsive image function for proper srcset and sizes
      $image_html = \wp_get_attachment_image($attachment_id, $image_size, false, array_merge($attributes, array(
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

  /**
   * Get card image URL for a property (first image with theme-configurable size)
   *
   * @param int $post_id Property post ID
   * @param string $default_size Default image size (medium)
   * @return string|null Image URL or null if no image
   */
  public static function get_card_image_url(int $post_id, string $default_size = 'medium'): ?string {
    $images = self::get_property_images($post_id);

    if (empty($images)) {
      return null;
    }

    $first_image = $images[0];

    // Allow theme to override the image size
    $image_size = \apply_filters('kato_sync_card_image_size', $default_size, $post_id);

    // Handle local images (imported to WordPress media library)
    if (isset($first_image['attachment_id'])) {
      // Check if we have the specific size in our sizes array
      if (isset($first_image['sizes'][$image_size])) {
        return $first_image['sizes'][$image_size];
      }

      // Fallback to WordPress function
      return \wp_get_attachment_image_url($first_image['attachment_id'], $image_size);
    }

    // Handle external images (not yet imported)
    if (isset($first_image['url'])) {
      return $first_image['url'];
    }

    return null;
  }

  /**
   * Simplified card image function
   *
   * Checks if image is imported to media library, if so returns WordPress image.
   * If not imported, returns the stored XML feed URL with alt text.
   *
   * @param int $post_id Property post ID
   * @param string $size Image size (default: 'medium')
   * @param string $class CSS class (default: 'property-card__img')
   * @param array $atts Additional HTML attributes
   * @return string HTML img tag or empty string
   */
  public static function get_simple_card_image(int $post_id, string $size = 'medium', string $class = 'property-card__img', array $atts = array()): string {
    $images = self::get_property_images($post_id);

    if (empty($images)) {
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

      // Use WordPress responsive image function for proper srcset and sizes
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

  /**
   * Get property images with import status
   */
  public static function get_property_images_with_import_status(int $post_id): array {
    return ImageProcessor::get_property_images_with_status($post_id);
  }

  /**
   * Get import status for a property's images
   */
  public static function get_import_status(int $post_id): array {
    return ImageProcessor::get_property_image_import_status($post_id);
  }

  /**
   * Check if all images have been imported
   */
  public static function are_all_images_imported(int $post_id): bool {
    return ImageProcessor::are_all_images_imported($post_id);
  }

  /**
   * Get enhanced card image with import status
   * Returns WordPress responsive image if imported, otherwise original URL
   */
  public static function get_enhanced_card_image(int $post_id, string $size = 'medium', array $attributes = []): string {
    $images = self::get_property_images_with_import_status($post_id);

    if (empty($images)) {
      return '';
    }

    $first_image = $images[0];

    // Allow theme to override the image size
    $image_size = \apply_filters('kato_sync_card_image_size', $size, $post_id);

    // Set up default attributes
    $default_attributes = [
      'class' => 'kato-sync-card-image',
      'loading' => 'lazy'
    ];

    $attributes = \wp_parse_args($attributes, $default_attributes);

    // Add import status class
    if ($first_image['is_imported']) {
      $attributes['class'] .= ' kato-sync-imported';
    } else {
      $attributes['class'] .= ' kato-sync-pending';
    }

    // Handle imported images (use WordPress responsive image functions)
    if ($first_image['is_imported'] && !empty($first_image['attachment_id'])) {
      $image_alt = $first_image['alt'] ?: \get_post_meta($first_image['attachment_id'], '_wp_attachment_image_alt', true);

      return \wp_get_attachment_image($first_image['attachment_id'], $image_size, false, array_merge($attributes, [
        'alt' => $image_alt,
        'data-import-status' => 'imported'
      ]));
    }

    // Handle external images (not yet imported)
    if (!empty($first_image['url'])) {
      $image_alt = $first_image['alt'] ?: basename($first_image['url']);

      // Build attributes string
      $attr_string = '';
      foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . \esc_attr($key) . '="' . \esc_attr($value) . '"';
      }

      return sprintf(
        '<img src="%s" alt="%s" data-import-status="pending"%s>',
        \esc_url($first_image['url']),
        \esc_attr($image_alt),
        $attr_string
      );
    }

    return '';
  }

  /**
   * Display import status indicator for admin
   */
  public static function display_import_status_indicator(int $post_id): string {
    $status = self::get_import_status($post_id);

    if ($status['total_images'] === 0) {
      return '<span class="kato-sync-status no-images">📷 No images</span>';
    }

    if ($status['all_imported']) {
      return sprintf(
        '<span class="kato-sync-status all-imported">✅ All imported (%d)</span>',
        $status['imported_images']
      );
    }

    if ($status['imported_images'] === 0) {
      return sprintf(
        '<span class="kato-sync-status pending">⏳ Pending (%d)</span>',
        $status['total_images']
      );
    }

    // Partial import
    return sprintf(
      '<span class="kato-sync-status partial">🔄 %d/%d (%d%%)</span>',
      $status['imported_images'],
      $status['total_images'],
      $status['import_percentage']
    );
  }
}
