<?php

namespace KatoSync\Admin;

/**
 * Main admin class
 */
class Admin {

  /**
   * Constructor
   */
  public function __construct() {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    $this->register_ajax_actions();
  }

  /**
   * Add admin menu
   */
  public function add_admin_menu(): void {
    // Add top-level menu
    add_menu_page(
      __('Kato Sync', 'kato-sync'),
      __('Kato Sync', 'kato-sync'),
      'manage_options',
      'kato-sync',
      array($this, 'properties_page'),
      'dashicons-building',
      30
    );

    // Add submenu pages
    add_submenu_page(
      'kato-sync',
      __('Properties', 'kato-sync'),
      __('Properties', 'kato-sync'),
      'manage_options',
      'kato-sync',
      array($this, 'properties_page')
    );

    add_submenu_page(
      'kato-sync',
      __('Import', 'kato-sync'),
      __('Import', 'kato-sync'),
      'manage_options',
      'kato-sync-import',
      array($this, 'unified_import_page')
    );

    add_submenu_page(
      'kato-sync',
      __('Settings', 'kato-sync'),
      __('Settings', 'kato-sync'),
      'manage_options',
      'kato-sync-settings',
      array($this, 'settings_page')
    );

    add_submenu_page(
      'kato-sync',
      __('Tools', 'kato-sync'),
      __('Tools', 'kato-sync'),
      'manage_options',
      'kato-sync-tools',
      array($this, 'tools_page')
    );

    add_submenu_page(
      'kato-sync',
      __('Property Debug', 'kato-sync'),
      __('Property Debug', 'kato-sync'),
      'manage_options',
      'kato-sync-debug',
      array($this, 'debug_page')
    );

    // Initialize range test page
    Pages\RangeTestPage::init();
  }

  /**
   * Enqueue admin scripts and styles
   */
  public function enqueue_admin_scripts(string $hook): void {
    // Only load on our plugin pages
    if (strpos($hook, 'kato-sync') === false) {
      return;
    }

    wp_enqueue_style(
      'kato-sync-admin',
      KATO_SYNC_PLUGIN_URL . 'dist/styles/admin-style.css',
      array(),
      KATO_SYNC_VERSION
    );

    wp_enqueue_script(
      'kato-sync-admin',
      KATO_SYNC_PLUGIN_URL . 'dist/scripts/admin.js',
      array(),
      KATO_SYNC_VERSION,
      true
    );

    // Localize script for AJAX
    wp_localize_script('kato-sync-admin', 'katoSyncAjax', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('kato_sync_nonce'),
      'strings' => array(
        'confirmRemoveAll' => __('Are you sure you want to remove all properties? This action cannot be undone.', 'kato-sync'),
        'confirmCleanupLogs' => __('Are you sure you want to cleanup old logs?', 'kato-sync'),
        'confirmResetSync' => __('Are you sure you want to reset sync status?', 'kato-sync'),
      )
    ));
  }

  /**
   * Properties page
   */
  public function properties_page(): void {
    $properties_page = new Pages\PropertiesPage();
    $properties_page->render();
  }

  /**
   * Unified import page
   */
  public function unified_import_page(): void {
    $unified_import_page = new Pages\UnifiedImportPage();
    $unified_import_page->render();
  }

  /**
   * Settings page
   */
  public function settings_page(): void {
    $settings_page = new Pages\SettingsPage();
    $settings_page->render();
  }

  /**
   * Tools page
   */
  public function tools_page(): void {
    $tools_page = new Pages\ToolsPage();
    $tools_page->render();
  }

  /**
   * Property Debug page
   */
  public function debug_page(): void {
    $debug_page = new Pages\PropertyDebugPage();
    $debug_page->render();
  }

  /**
   * Register AJAX actions
   */
  private function register_ajax_actions(): void {
    // Import/sync actions
    add_action('wp_ajax_kato_sync_manual_sync', array('\KatoSync\Sync\SyncManager', 'ajax_manual_sync'));
    add_action('wp_ajax_kato_sync_test_feed', array('\KatoSync\Sync\SyncManager', 'ajax_test_feed'));
    add_action('wp_ajax_kato_sync_get_property_data', array('\KatoSync\Utils\Tools', 'ajax_get_property_data'));

    // Tools and maintenance actions
    add_action('wp_ajax_kato_sync_remove_all_properties', array('\KatoSync\Utils\Tools', 'ajax_remove_all_properties'));
    add_action('wp_ajax_kato_sync_remove_all_images', array('\KatoSync\Sync\ImageProcessor', 'ajax_remove_all_images'));
    add_action('wp_ajax_kato_sync_cleanup_logs', array('\KatoSync\Utils\Tools', 'ajax_cleanup_logs'));
    add_action('wp_ajax_kato_sync_reset_sync_status', array('\KatoSync\Utils\Tools', 'ajax_reset_sync_status'));


    // Settings actions
    add_action('wp_ajax_kato_sync_export_settings', array('\KatoSync\Utils\Tools', 'ajax_export_settings'));
    add_action('wp_ajax_kato_sync_import_settings', array('\KatoSync\Utils\Tools', 'ajax_import_settings'));

    // Diagnostics actions
    add_action('wp_ajax_kato_sync_check_auto_sync', array('\KatoSync\Utils\Tools', 'ajax_check_auto_sync'));
    add_action('wp_ajax_kato_sync_clear_sync_locks', array('\KatoSync\Utils\Tools', 'ajax_clear_sync_locks'));
    add_action('wp_ajax_kato_sync_run_auto_sync_diagnostics', array('\KatoSync\Utils\Tools', 'ajax_run_auto_sync_diagnostics'));
    add_action('wp_ajax_kato_sync_reset_auto_sync_settings', array('\KatoSync\Utils\Tools', 'ajax_reset_auto_sync_settings'));
    add_action('wp_ajax_kato_sync_backfill_attachment_ids', array('\KatoSync\Utils\Tools', 'ajax_backfill_attachment_ids'));
  }
}
