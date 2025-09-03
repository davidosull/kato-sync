<?php

namespace KatoSync\Admin\Pages;

/**
 * Tools page
 */
class ToolsPage {

  /**
   * Render the tools page
   */
  public function render(): void {
?>
    <div class="wrap kato-sync-tools-page">
      <h1><?php _e('Kato Sync Tools', 'kato-sync'); ?></h1>

      <!-- System Status -->
      <div class="kato-sync-section">
        <h2><?php _e('System Status', 'kato-sync'); ?></h2>
        <table class="form-table">
          <tr>
            <th><?php _e('PHP Version:', 'kato-sync'); ?></th>
            <td><?php echo esc_html(PHP_VERSION); ?></td>
          </tr>
          <tr>
            <th><?php _e('WordPress Version:', 'kato-sync'); ?></th>
            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
          </tr>
          <tr>
            <th><?php _e('Plugin Version:', 'kato-sync'); ?></th>
            <td><?php echo esc_html(KATO_SYNC_VERSION); ?></td>
          </tr>
          <tr>
            <th><?php _e('Memory Limit:', 'kato-sync'); ?></th>
            <td><?php echo esc_html(WP_MEMORY_LIMIT); ?></td>
          </tr>
          <tr>
            <th><?php _e('Current Timezone:', 'kato-sync'); ?></th>
            <td><?php echo esc_html(wp_timezone_string()); ?></td>
          </tr>
          <tr>
            <th><?php _e('Total Properties:', 'kato-sync'); ?></th>
            <td><?php echo esc_html(wp_count_posts('kato-property')->publish); ?></td>
          </tr>
        </table>
      </div>

      <!-- Auto-Sync Diagnostics -->
      <div class="kato-sync-section">
        <h2><?php _e('Auto-Sync Diagnostics', 'kato-sync'); ?></h2>
        <p><?php _e('Check the status of your auto-sync configuration and identify potential issues.', 'kato-sync'); ?></p>

        <div class="kato-sync-button-group">
          <button type="button" id="kato-sync-check-auto-sync" class="button button-secondary">
            <?php _e('Check Auto-Sync Status', 'kato-sync'); ?>
          </button>

          <button type="button" id="kato-sync-run-auto-sync-diagnostics" class="button button-secondary">
            <?php _e('Run Auto-Sync Diagnostics', 'kato-sync'); ?>
          </button>

          <button type="button" id="kato-sync-clear-sync-locks" class="button button-secondary">
            <?php _e('Clear Sync Locks', 'kato-sync'); ?>
          </button>

          <button type="button" id="kato-sync-reset-auto-sync" class="button button-warning">
            <?php _e('Reset Auto-Sync Settings', 'kato-sync'); ?>
          </button>
        </div>

        <div id="kato-sync-auto-sync-results" style="display: none; margin-top: 10px;">
          <div class="kato-sync-auto-sync-content"></div>
        </div>
      </div>

      <!-- System Diagnostics -->
      <div class="kato-sync-section">
        <h2><?php _e('System Diagnostics', 'kato-sync'); ?></h2>
        <p><?php _e('Run comprehensive system diagnostics to check for potential issues.', 'kato-sync'); ?></p>

        <button type="button" id="kato-sync-run-diagnostics" class="button button-secondary">
          <?php _e('Run Diagnostics', 'kato-sync'); ?>
        </button>

        <div id="kato-sync-diagnostics-results" style="display: none; margin-top: 10px;">
          <div class="kato-sync-diagnostics-content"></div>
        </div>
      </div>

      <!-- XML Feed Test -->
      <div class="kato-sync-section">
        <h2><?php _e('XML Feed Test', 'kato-sync'); ?></h2>
        <p><?php _e('Test the connectivity and response time of your configured XML feed.', 'kato-sync'); ?></p>

        <button type="button" id="kato-sync-test-feed" class="button button-secondary">
          <?php _e('Test Feed', 'kato-sync'); ?>
        </button>

        <div id="kato-sync-feed-test-results" style="display: none; margin-top: 10px;">
          <div class="kato-sync-feed-test-content"></div>
        </div>
      </div>

      <!-- Database Tools -->
      <div class="kato-sync-section">
        <h2><?php _e('Database Tools', 'kato-sync'); ?></h2>
        <p><?php _e('Maintenance tools for cleaning up and managing your property data.', 'kato-sync'); ?></p>

        <div class="kato-sync-tool-item">
          <h3><?php _e('Cleanup Old Logs', 'kato-sync'); ?></h3>
          <p><?php _e('Delete sync logs older than the configured number of days.', 'kato-sync'); ?></p>
          <button type="button" id="kato-sync-cleanup-logs" class="button button-secondary">
            <?php _e('Cleanup Logs', 'kato-sync'); ?>
          </button>
        </div>

        <div class="kato-sync-tool-item">
          <h3><?php _e('Reset Sync Status', 'kato-sync'); ?></h3>
          <p><?php _e('Clear last sync and next sync timestamps.', 'kato-sync'); ?></p>
          <button type="button" id="kato-sync-reset-sync-status" class="button button-secondary">
            <?php _e('Reset Sync Status', 'kato-sync'); ?>
          </button>
        </div>

        <div class="kato-sync-tool-item">
          <h3><?php _e('Fix Image Import Status', 'kato-sync'); ?></h3>
          <p><?php _e('Updates the database for images that have been imported but are still showing as pending.', 'kato-sync'); ?></p>
          <button type="button" id="kato-sync-backfill-attachment-ids" class="button button-secondary">
            <?php _e('Fix Image Status', 'kato-sync'); ?>
          </button>
        </div>
      </div>

      <!-- Import/Export Settings -->
      <div class="kato-sync-section">
        <h2><?php _e('Import/Export Settings', 'kato-sync'); ?></h2>
        <p><?php _e('Backup and restore your plugin configuration.', 'kato-sync'); ?></p>

        <div class="kato-sync-tool-item">
          <h3><?php _e('Export Settings', 'kato-sync'); ?></h3>
          <p><?php _e('Download all plugin settings as a JSON file.', 'kato-sync'); ?></p>
          <button type="button" id="kato-sync-export-settings" class="button button-secondary">
            <?php _e('Export Settings', 'kato-sync'); ?>
          </button>
        </div>

        <div class="kato-sync-tool-item">
          <h3><?php _e('Import Settings', 'kato-sync'); ?></h3>
          <p><?php _e('Upload and import settings from a JSON file.', 'kato-sync'); ?></p>
          <input type="file" id="kato-sync-import-file" accept=".json" style="margin-bottom: 10px;" />
          <br>
          <button type="button" id="kato-sync-import-settings" class="button button-secondary">
            <?php _e('Import Settings', 'kato-sync'); ?>
          </button>
        </div>
      </div>

      <!-- Remove Data -->
      <div class="kato-sync-section">
        <h2><?php _e('Remove Data', 'kato-sync'); ?></h2>
        <p><?php _e('Permanently delete imported data. These actions cannot be undone.', 'kato-sync'); ?></p>

        <div class="kato-sync-advanced-settings">
          <div class="kato-sync-warning-box">
            <p><strong><?php _e('⚠️ Dangerous Operations', 'kato-sync'); ?></strong></p>
            <p><?php _e('These actions will permanently delete data and cannot be undone. Please ensure you have backups before proceeding.', 'kato-sync'); ?></p>
          </div>

          <div class="kato-sync-tool-item">
            <h3><?php _e('Remove All Properties', 'kato-sync'); ?></h3>
            <p><?php _e('This action will permanently delete all imported properties and reset the plugin data. This cannot be undone.', 'kato-sync'); ?></p>
            <button type="button" id="kato-sync-remove-all-properties" class="button button-danger">
              <?php _e('Remove All Properties', 'kato-sync'); ?>
            </button>
          </div>

          <div class="kato-sync-tool-item">
            <h3><?php _e('Remove All Images', 'kato-sync'); ?></h3>
            <p><?php _e('This action will permanently delete all imported images from the media library and clear the image queue. This cannot be undone.', 'kato-sync'); ?></p>
            <button type="button" id="kato-sync-remove-all-images" class="button button-danger">
              <?php _e('Remove All Images', 'kato-sync'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>
<?php
  }
}
