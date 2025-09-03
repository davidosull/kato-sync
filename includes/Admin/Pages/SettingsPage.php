<?php

namespace KatoSync\Admin\Pages;

/**
 * Settings page
 */
class SettingsPage {

  /**
   * Render the settings page
   */
  public function render(): void {
    // Handle form submission
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['kato_sync_settings_nonce'], 'kato_sync_settings')) {
      $this->save_settings();
    }

    $settings = get_option('kato_sync_settings', array());
?>
    <div class="wrap kato-sync-settings-page">
      <h1><?php _e('Kato Sync Settings', 'kato-sync'); ?></h1>

      <form method="post" action="">
        <?php wp_nonce_field('kato_sync_settings', 'kato_sync_settings_nonce'); ?>

        <!-- Feed Configuration -->
        <div class="kato-sync-section">
          <h2><?php _e('Feed Configuration', 'kato-sync'); ?></h2>
          <p><?php _e('Configure your XML property feed connection and basic import settings.', 'kato-sync'); ?></p>

          <table class="form-table">
            <!-- Feed URL -->
            <tr>
              <th scope="row">
                <label for="feed_url"><?php _e('Feed URL', 'kato-sync'); ?></label>
              </th>
              <td>
                <input type="url"
                  id="feed_url"
                  name="feed_url"
                  value="<?php echo esc_attr($settings['feed_url'] ?? ''); ?>"
                  class="regular-text"
                  required />
                <p class="description">
                  <?php _e('The URL of your XML property feed.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>

            <!-- Request Timeout -->
            <tr>
              <th scope="row">
                <label for="request_timeout"><?php _e('Request Timeout', 'kato-sync'); ?></label>
              </th>
              <td>
                <input type="number"
                  id="request_timeout"
                  name="request_timeout"
                  value="<?php echo esc_attr($settings['request_timeout'] ?? 30); ?>"
                  min="10"
                  max="300"
                  class="small-text" />
                <span><?php _e('seconds', 'kato-sync'); ?></span>
                <p class="description">
                  <?php _e('Maximum time to wait for the XML feed to respond.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>

            <!-- Batch Size -->
            <tr>
              <th scope="row">
                <label for="batch_size"><?php _e('Batch Size', 'kato-sync'); ?></label>
              </th>
              <td>
                <input type="number"
                  id="batch_size"
                  name="batch_size"
                  value="<?php echo esc_attr($settings['batch_size'] ?? 50); ?>"
                  min="10"
                  max="200"
                  class="small-text" />
                <span><?php _e('properties per batch', 'kato-sync'); ?></span>
                <p class="description">
                  <?php _e('Number of properties to process in each batch to prevent server overload.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>
          </table>
        </div>

        <!-- Auto-Sync Configuration -->
        <div class="kato-sync-section">
          <h2><?php _e('Auto-Sync Configuration', 'kato-sync'); ?></h2>
          <p><?php _e('Configure automatic property synchronization from your XML feed.', 'kato-sync'); ?></p>

          <table class="form-table">
            <!-- Auto-Sync Settings -->
            <tr>
              <th scope="row"><?php _e('Auto-Sync Settings', 'kato-sync'); ?></th>
              <td>
                <fieldset>
                  <label>
                    <input type="checkbox"
                      name="auto_sync_enabled"
                      value="1"
                      <?php checked($settings['auto_sync_enabled'] ?? true); ?> />
                    <?php _e('Enable automatic syncing', 'kato-sync'); ?>
                  </label>
                </fieldset>

                <br>

                <label for="auto_sync_frequency"><?php _e('Frequency:', 'kato-sync'); ?></label>
                <select id="auto_sync_frequency" name="auto_sync_frequency">
                  <option value="15mins" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '15mins'); ?>>
                    <?php _e('15 mins', 'kato-sync'); ?>
                  </option>
                  <option value="30mins" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '30mins'); ?>>
                    <?php _e('30 mins', 'kato-sync'); ?>
                  </option>
                  <option value="1hour" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '1hour'); ?>>
                    <?php _e('1 hour', 'kato-sync'); ?>
                  </option>
                  <option value="3hours" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '3hours'); ?>>
                    <?php _e('3 hours', 'kato-sync'); ?>
                  </option>
                  <option value="6hours" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '6hours'); ?>>
                    <?php _e('6 hours', 'kato-sync'); ?>
                  </option>
                  <option value="12hours" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '12hours'); ?>>
                    <?php _e('12 hours', 'kato-sync'); ?>
                  </option>
                  <option value="24hours" <?php selected($settings['auto_sync_frequency'] ?? '1hour', '24hours'); ?>>
                    <?php _e('24 hours', 'kato-sync'); ?>
                  </option>
                </select>
                <p class="description">
                  <?php _e('How often to automatically sync properties from the XML feed.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>

            <!-- Force Update All -->
            <tr>
              <th scope="row"><?php _e('Force Update All', 'kato-sync'); ?></th>
              <td>
                <fieldset>
                  <label>
                    <input type="checkbox"
                      name="force_update_all"
                      value="1"
                      <?php checked($settings['force_update_all'] ?? false); ?> />
                    <?php _e('Bypass XML lastmod timestamp for full re-import', 'kato-sync'); ?>
                  </label>
                  <p class="description">
                    <?php _e('When enabled, all properties will be updated regardless of their lastmod timestamp.', 'kato-sync'); ?>
                  </p>
                </fieldset>
              </td>
            </tr>
          </table>
        </div>

        <!-- Property Configuration -->
        <div class="kato-sync-section">
          <h2><?php _e('Property Configuration', 'kato-sync'); ?></h2>
          <p><?php _e('Configure how properties are created and managed in WordPress.', 'kato-sync'); ?></p>

          <table class="form-table">
            <!-- URL Pattern -->
            <tr>
              <th scope="row">
                <label for="url_pattern"><?php _e('URL Pattern for Slugs', 'kato-sync'); ?></label>
              </th>
              <td>
                <input type="text"
                  id="url_pattern"
                  name="url_pattern"
                  value="<?php echo esc_attr($settings['url_pattern'] ?? '{name}-{address1}-{postcode}'); ?>"
                  class="regular-text" />
                <p class="description">
                  <?php _e('Pattern for generating property URLs. Use placeholders like {name}, {address1}, {postcode}.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>
          </table>
        </div>

        <!-- Image Processing Configuration -->
        <div class="kato-sync-section">
          <h2><?php _e('Image Processing Configuration', 'kato-sync'); ?></h2>
          <p><?php _e('Configure how property images are processed and stored.', 'kato-sync'); ?></p>

          <table class="form-table">
            <!-- Image Processing Mode -->
            <tr>
              <th scope="row"><?php _e('Image Processing Mode', 'kato-sync'); ?></th>
              <td>
                <fieldset>
                  <label>
                    <input type="radio"
                      name="image_mode"
                      value="local"
                      <?php checked($settings['image_mode'] ?? 'local', 'local'); ?> />
                    <?php _e('Import to Media Library (Recommended)', 'kato-sync'); ?>
                  </label>
                  <br>
                  <label>
                    <input type="radio"
                      name="image_mode"
                      value="external"
                      <?php checked($settings['image_mode'] ?? 'local', 'external'); ?> />
                    <?php _e('Serve from External URLs (Saves Storage)', 'kato-sync'); ?>
                  </label>
                </fieldset>
                <p class="description">
                  <?php _e('Local mode downloads images to your server for better performance and WordPress integration. External mode saves storage but relies on external servers.', 'kato-sync'); ?>
                  <br>
                  <strong><?php _e('Note:', 'kato-sync'); ?></strong> <?php _e('Images larger than 10MB will be skipped in local mode.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>
          </table>
        </div>

        <!-- Advanced Image Processing Settings -->
        <div class="kato-sync-section">
          <h2><?php _e('Advanced Image Processing', 'kato-sync'); ?></h2>
          <p><?php _e('Advanced settings for image processing performance and resource usage. Only modify if you understand the implications.', 'kato-sync'); ?></p>

          <div class="kato-sync-advanced-settings">
            <div class="kato-sync-warning-box">
              <p><strong><?php _e('⚠️ Advanced Users Only', 'kato-sync'); ?></strong></p>
              <p><?php _e('These settings control image processing performance and resource usage. Only modify if you understand the implications.', 'kato-sync'); ?></p>
            </div>

            <table class="form-table">
              <tr>
                <th scope="row">
                  <label for="image_batch_size"><?php _e('Image Batch Size', 'kato-sync'); ?></label>
                </th>
                <td>
                  <input type="number"
                    id="image_batch_size"
                    name="image_batch_size"
                    value="<?php echo esc_attr($settings['image_batch_size'] ?? 20); ?>"
                    min="5"
                    max="100"
                    class="small-text" />
                  <span><?php _e('images per batch', 'kato-sync'); ?></span>
                  <p class="description">
                    <?php _e('Number of images to process in each batch. Higher values may cause timeouts on slower servers.', 'kato-sync'); ?>
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="image_timeout"><?php _e('Image Download Timeout', 'kato-sync'); ?></label>
                </th>
                <td>
                  <input type="number"
                    id="image_timeout"
                    name="image_timeout"
                    value="<?php echo esc_attr($settings['image_timeout'] ?? 30); ?>"
                    min="10"
                    max="300"
                    class="small-text" />
                  <span><?php _e('seconds', 'kato-sync'); ?></span>
                  <p class="description">
                    <?php _e('Maximum time to wait for each image download. Increase for slower connections or larger images.', 'kato-sync'); ?>
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="image_retry_attempts"><?php _e('Image Retry Attempts', 'kato-sync'); ?></label>
                </th>
                <td>
                  <input type="number"
                    id="image_retry_attempts"
                    name="image_retry_attempts"
                    value="<?php echo esc_attr($settings['image_retry_attempts'] ?? 3); ?>"
                    min="1"
                    max="10"
                    class="small-text" />
                  <span><?php _e('attempts', 'kato-sync'); ?></span>
                  <p class="description">
                    <?php _e('Number of times to retry failed image downloads before marking as failed.', 'kato-sync'); ?>
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="image_max_size"><?php _e('Maximum Image Size', 'kato-sync'); ?></label>
                </th>
                <td>
                  <input type="number"
                    id="image_max_size"
                    name="image_max_size"
                    value="<?php echo esc_attr($settings['image_max_size'] ?? 10); ?>"
                    min="1"
                    max="50"
                    class="small-text" />
                  <span><?php _e('MB', 'kato-sync'); ?></span>
                  <p class="description">
                    <?php _e('Images larger than this size will be skipped to prevent server overload.', 'kato-sync'); ?>
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="image_cron_interval"><?php _e('Image Processing Interval', 'kato-sync'); ?></label>
                </th>
                <td>
                  <select id="image_cron_interval" name="image_cron_interval">
                    <option value="every_1_minute" <?php selected($settings['image_cron_interval'] ?? 'every_2_minutes', 'every_1_minute'); ?>>
                      <?php _e('Every 1 minute', 'kato-sync'); ?>
                    </option>
                    <option value="every_2_minutes" <?php selected($settings['image_cron_interval'] ?? 'every_2_minutes', 'every_2_minutes'); ?>>
                      <?php _e('Every 2 minutes', 'kato-sync'); ?>
                    </option>
                    <option value="every_5_minutes" <?php selected($settings['image_cron_interval'] ?? 'every_2_minutes', 'every_5_minutes'); ?>>
                      <?php _e('Every 5 minutes', 'kato-sync'); ?>
                    </option>
                    <option value="every_10_minutes" <?php selected($settings['image_cron_interval'] ?? 'every_2_minutes', 'every_10_minutes'); ?>>
                      <?php _e('Every 10 minutes', 'kato-sync'); ?>
                    </option>
                    <option value="every_15_minutes" <?php selected($settings['image_cron_interval'] ?? 'every_2_minutes', 'every_15_minutes'); ?>>
                      <?php _e('Every 15 minutes', 'kato-sync'); ?>
                    </option>
                    <option value="every_30_minutes" <?php selected($settings['image_cron_interval'] ?? 'every_2_minutes', 'every_30_minutes'); ?>>
                      <?php _e('Every 30 minutes', 'kato-sync'); ?>
                    </option>
                  </select>
                  <p class="description">
                    <?php _e('How often to process image batches. More frequent processing = faster completion but higher server load.', 'kato-sync'); ?>
                  </p>
                </td>
              </tr>
            </table>
          </div>
        </div>

        <!-- Maintenance Settings -->
        <div class="kato-sync-section">
          <h2><?php _e('Maintenance Settings', 'kato-sync'); ?></h2>
          <p><?php _e('Configure automatic maintenance and cleanup settings.', 'kato-sync'); ?></p>

          <table class="form-table">
            <!-- Cleanup Logs After Days -->
            <tr>
              <th scope="row">
                <label for="cleanup_logs_after_days"><?php _e('Cleanup Logs After', 'kato-sync'); ?></label>
              </th>
              <td>
                <input type="number"
                  id="cleanup_logs_after_days"
                  name="cleanup_logs_after_days"
                  value="<?php echo esc_attr($settings['cleanup_logs_after_days'] ?? 30); ?>"
                  min="7"
                  max="365"
                  class="small-text" />
                <span><?php _e('days', 'kato-sync'); ?></span>
                <p class="description">
                  <?php _e('Automatically delete sync logs older than this number of days.', 'kato-sync'); ?>
                </p>
              </td>
            </tr>

            <!-- Remove Data on Uninstall -->
            <tr>
              <th scope="row"><?php _e('Remove All Data on Uninstall', 'kato-sync'); ?></th>
              <td>
                <fieldset>
                  <label>
                    <input type="checkbox"
                      name="remove_data_on_uninstall"
                      value="1"
                      <?php checked($settings['remove_data_on_uninstall'] ?? false); ?> />
                    <?php _e('Delete all imported properties and settings when plugin is uninstalled', 'kato-sync'); ?>
                  </label>
                  <p class="description kato-sync-warning">
                    <strong><?php _e('Warning:', 'kato-sync'); ?></strong> <?php _e('This will permanently delete all imported properties and plugin data.', 'kato-sync'); ?>
                  </p>
                </fieldset>
              </td>
            </tr>
          </table>
        </div>

        <div class="kato-sync-form-actions">
          <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'kato-sync'); ?>" />
          <input type="submit" name="reset" id="reset" class="button button-secondary" value="<?php _e('Reset to Defaults', 'kato-sync'); ?>" />
        </div>
      </form>
    </div>
<?php
  }

  /**
   * Save settings
   */
  private function save_settings(): void {
    $settings = array(
      'feed_url' => esc_url_raw($_POST['feed_url'] ?? ''),
      'request_timeout' => intval($_POST['request_timeout'] ?? 30),
      'batch_size' => intval($_POST['batch_size'] ?? 50),
      'auto_sync_enabled' => !empty($_POST['auto_sync_enabled']),
      'auto_sync_frequency' => sanitize_text_field($_POST['auto_sync_frequency'] ?? '24hours'),
      'url_pattern' => sanitize_text_field($_POST['url_pattern'] ?? '{name}-{address1}-{postcode}'),
      'force_update_all' => !empty($_POST['force_update_all']),
      'remove_data_on_uninstall' => !empty($_POST['remove_data_on_uninstall']),
      'image_mode' => sanitize_text_field($_POST['image_mode'] ?? 'local'),
      'image_batch_size' => intval($_POST['image_batch_size'] ?? 20),
      'image_timeout' => intval($_POST['image_timeout'] ?? 30),
      'image_retry_attempts' => intval($_POST['image_retry_attempts'] ?? 3),
      'image_max_size' => intval($_POST['image_max_size'] ?? 10),
      'image_cron_interval' => sanitize_text_field($_POST['image_cron_interval'] ?? 'every_2_minutes'),
      'cleanup_logs_after_days' => intval($_POST['cleanup_logs_after_days'] ?? 30),
    );

    update_option('kato_sync_settings', $settings);

    // Update cron schedule if auto-sync settings changed
    if ($settings['auto_sync_enabled']) {
      $this->update_cron_schedule($settings);
    } else {
      wp_clear_scheduled_hook('kato_sync_auto_sync');
    }

    // Update image processing cron job if interval changed
    $this->update_image_cron_schedule($settings);

    add_action('admin_notices', function () {
      echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'kato-sync') . '</p></div>';
    });
  }

  /**
   * Update cron schedule
   */
  private function update_cron_schedule(array $settings): void {

    // Clear existing cron job
    $cleared = wp_clear_scheduled_hook('kato_sync_auto_sync');

    $frequency = $settings['auto_sync_frequency'];
    $current_time = current_time('timestamp');


    // Validate frequency is supported
    $supported_frequencies = array('15mins', '30mins', '1hour', '3hours', '6hours', '12hours', '24hours');
    if (!in_array($frequency, $supported_frequencies)) {
      $frequency = '1hour'; // Fallback to default
    }

    // Check if custom cron intervals are registered
    $schedules = wp_get_schedules();

    if (!isset($schedules[$frequency])) {
    }

    // Calculate next run time based on frequency
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
        // Default to 24 hours if frequency is not recognized
        $next_run = $current_time + 86400; // 24 hours from now
        break;
    }


    // Schedule the cron job
    $scheduled = wp_schedule_event($next_run, $frequency, 'kato_sync_auto_sync');

    if ($scheduled === false) {

      // Try with a built-in interval as fallback
      if ($frequency !== 'hourly') {
        $scheduled = wp_schedule_event($next_run, 'hourly', 'kato_sync_auto_sync');
      }
    } else {
      update_option('kato_sync_next_sync', $next_run);
    }

    // Verify the cron job was actually scheduled
    $next_scheduled = wp_next_scheduled('kato_sync_auto_sync');
    if (!$next_scheduled) {
    } else {
    }
  }

  /**
   * Update image processing cron schedule
   */
  private function update_image_cron_schedule(array $settings): void {
    // Clear existing image processing cron job
    wp_clear_scheduled_hook('kato_sync_process_images');

    $interval = $settings['image_cron_interval'] ?? 'every_2_minutes';

    // Validate interval is supported
    $supported_intervals = array('every_1_minute', 'every_2_minutes', 'every_5_minutes', 'every_10_minutes', 'every_15_minutes', 'every_30_minutes');
    if (!in_array($interval, $supported_intervals)) {
      $interval = 'every_2_minutes'; // Fallback to default
    }

    // Schedule the image processing cron job
    $scheduled = wp_schedule_event(time(), $interval, 'kato_sync_process_images');

    if ($scheduled === false) {
      // Try with fallback interval
      $scheduled = wp_schedule_event(time(), 'every_2_minutes', 'kato_sync_process_images');
      if ($scheduled === false) {
      }
    }

    // Verify the cron job was actually scheduled
    $next_run = wp_next_scheduled('kato_sync_process_images');
    if (!$next_run) {
    } else {
    }
  }
}
