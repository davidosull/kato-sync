<?php

namespace KatoSync;

/**
 * Main plugin class
 */
class Plugin {

  /**
   * Initialize the plugin
   */
  public static function init(): void {
    self::load_dependencies();
    self::register_hooks();
    self::maybe_flush_rewrite_rules();

    // Run migrations on version change
    \add_action('init', ['\\KatoSync\\DB\\Migrations', 'maybe_migrate']);
  }

  /**
   * Load required files
   */
  private static function load_dependencies(): void {
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/PostTypes/Property.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/PostTypes/Kato_Property.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Admin/Admin.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Sync/SyncManager.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Sync/ImageProcessor.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Utils/ImageDisplay.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Utils/Tools.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/DB/Migrations.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Sync/Normalizer.php';
    require_once KATO_SYNC_PLUGIN_DIR . 'includes/Repository/PropertyRepository.php';
  }

  /**
   * Register WordPress hooks
   */
  private static function register_hooks(): void {
    // Register post types
    \add_action('init', [PostTypes\Property::class, 'register']);

    // Activation hook (must be registered from main plugin file normally)
    if (\function_exists('register_activation_hook')) {
      \register_activation_hook(KATO_SYNC_PLUGIN_BASENAME, ['\\KatoSync\\DB\\Migrations', 'activate']);
    }

    // Initialize admin interface
    if (\is_admin()) {
      new Admin\Admin();
    }

    // Register cron hooks
    \add_action('kato_sync_process_images', [Sync\ImageProcessor::class, 'process_image_queue']);

    // Add daily maintenance
    \add_action('kato_sync_daily_maintenance', [Sync\ImageProcessor::class, 'daily_maintenance']);

    // Global helper functions - register early
    \add_action('plugins_loaded', [self::class, 'register_global_functions']);
  }

  /**
   * Register global helper functions for themes
   */
  public static function register_global_functions(): void {
    if (!function_exists('kato_get_card_image')) {
      /**
       * Get the first property image for use in cards
       *
       * This function returns the HTML for the first image of a property.
       * Supports theme override for image size via 'kato_sync_card_image_size' filter.
       *
       * @param int|null $post_id Property post ID (auto-detects if null)
       * @param string $size Default image size
       * @param array $attributes Additional HTML attributes
       * @return string HTML img tag or empty string
       */
      function kato_get_card_image(?int $post_id = null, string $size = 'medium', array $attributes = array()): string {
        if ($post_id === null) {
          $post_id = \get_the_ID();
          if (!$post_id) {
            return '';
          }
        }

        return \KatoSync\Utils\ImageDisplay::get_card_image_html($post_id, $size, $attributes);
      }
    }

    if (!function_exists('kato_get_card_image_url')) {
      /**
       * Get the first property image URL for use in cards
       *
       * @param int|null $post_id Property post ID (auto-detects if null)
       * @param string $size Default image size
       * @return string|null Image URL or null
       */
      function kato_get_card_image_url(?int $post_id = null, string $size = 'medium'): ?string {
        if ($post_id === null) {
          $post_id = \get_the_ID();
          if (!$post_id) {
            return null;
          }
        }

        return \KatoSync\Utils\ImageDisplay::get_card_image_url($post_id, $size);
      }
    }
  }

  /**
   * Maybe flush rewrite rules
   */
  private static function maybe_flush_rewrite_rules(): void {
    // Add any logic you want to execute when rewrite rules are flushed
  }
}
