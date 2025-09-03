<?php

namespace KatoSync\Admin\Pages;

/**
 * Import page
 */
class ImportPage {

  /**
   * Render the import page
   */
  public function render(): void {
    $settings = get_option('kato_sync_settings', array());
    $sync_logs = $this->get_sync_logs();
    $last_sync = get_option('kato_sync_last_sync');
    $next_sync = get_option('kato_sync_next_sync');
?>
    <div class="wrap">
      <h1><?php _e('Import', 'kato-sync'); ?></h1>

      <!-- Manual Import Section -->
      <div class="kato-sync-section">
        <h2><?php _e('Manual Import', 'kato-sync'); ?></h2>
        <p><?php _e('Click the button below to manually trigger a sync from your XML feed.', 'kato-sync'); ?></p>
        <button type="button" id="kato-sync-manual-import" class="button button-primary">
          <?php _e('Start Manual Import', 'kato-sync'); ?>
        </button>
        <div id="kato-sync-import-progress" style="display: none;">
          <div class="kato-sync-progress-bar">
            <div class="kato-sync-progress-fill"></div>
          </div>
          <p id="kato-sync-import-status"><?php _e('Starting import...', 'kato-sync'); ?></p>
        </div>
      </div>

      <!-- Auto-Sync Details Section -->
      <div class="kato-sync-section">
        <h2><?php _e('Auto-Sync Details', 'kato-sync'); ?></h2>
        <table class="form-table">
          <tr>
            <th><?php _e('Status:', 'kato-sync'); ?></th>
            <td>
              <?php if ($settings['auto_sync_enabled']): ?>
                <span class="kato-sync-status kato-sync-status-success"><?php _e('Enabled', 'kato-sync'); ?></span>
              <?php else: ?>
                <span class="kato-sync-status kato-sync-status-error"><?php _e('Disabled', 'kato-sync'); ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th><?php _e('Frequency:', 'kato-sync'); ?></th>
            <td>
              <?php
              $frequency = $settings['auto_sync_frequency'] ?? '1hour';
              $frequency_labels = array(
                '15mins' => __('Every 15 minutes', 'kato-sync'),
                '30mins' => __('Every 30 minutes', 'kato-sync'),
                '1hour' => __('Every hour', 'kato-sync'),
                '3hours' => __('Every 3 hours', 'kato-sync'),
                '6hours' => __('Every 6 hours', 'kato-sync'),
                '12hours' => __('Every 12 hours', 'kato-sync'),
                '24hours' => __('Every 24 hours', 'kato-sync'),
              );
              echo esc_html($frequency_labels[$frequency] ?? $frequency);
              ?>
            </td>
          </tr>
          <tr>
            <th><?php _e('Last Sync:', 'kato-sync'); ?></th>
            <td>
              <?php
              if ($last_sync) {
                echo esc_html(human_time_diff($last_sync, current_time('timestamp'))) . ' ago';
                echo ' <small>(' . esc_html(date('Y-m-d H:i:s T', $last_sync)) . ')</small>';
              } else {
                echo esc_html__('Never', 'kato-sync');
              }
              ?>
            </td>
          </tr>
          <tr>
            <th><?php _e('Next Sync:', 'kato-sync'); ?></th>
            <td>
              <?php
              if ($next_sync) {
                echo esc_html(human_time_diff(current_time('timestamp'), $next_sync)) . ' from now';
                echo ' <small>(' . esc_html(date('Y-m-d H:i:s T', $next_sync)) . ')</small>';
              } else {
                echo esc_html__('Not scheduled', 'kato-sync');
              }
              ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- Sync Summary Table -->
      <div class="kato-sync-section">
        <h2><?php _e('Sync History', 'kato-sync'); ?></h2>
        <?php if (empty($sync_logs)): ?>
          <p><?php _e('No sync history available.', 'kato-sync'); ?></p>
        <?php else: ?>
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th><?php _e('Status', 'kato-sync'); ?></th>
                <th><?php _e('Type', 'kato-sync'); ?></th>
                <th><?php _e('Timestamp', 'kato-sync'); ?></th>
                <th><?php _e('Total Properties', 'kato-sync'); ?></th>
                <th><?php _e('Added', 'kato-sync'); ?></th>
                <th><?php _e('Updated', 'kato-sync'); ?></th>
                <th><?php _e('Removed', 'kato-sync'); ?></th>
                <th><?php _e('Skipped', 'kato-sync'); ?></th>
                <th><?php _e('Duration', 'kato-sync'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sync_logs as $log): ?>
                <tr>
                  <td>
                    <span class="kato-sync-status kato-sync-status-<?php echo esc_attr($log['status']); ?>">
                      <?php echo esc_html(ucfirst($log['status'])); ?>
                    </span>
                  </td>
                  <td><?php echo esc_html(ucfirst($log['type'])); ?></td>
                  <td>
                    <?php
                    echo esc_html(human_time_diff($log['timestamp'], current_time('timestamp'))) . ' ago';
                    echo '<br><small>(' . esc_html(date('Y-m-d H:i:s T', $log['timestamp'])) . ')</small>';
                    ?>
                  </td>
                  <td><?php echo esc_html(number_format($log['total_properties'])); ?></td>
                  <td><?php echo esc_html(number_format($log['added'])); ?></td>
                  <td><?php echo esc_html(number_format($log['updated'])); ?></td>
                  <td><?php echo esc_html(number_format($log['removed'])); ?></td>
                  <td><?php echo esc_html(number_format($log['skipped'])); ?></td>
                  <td><?php echo esc_html($this->format_duration($log['duration'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
<?php
  }

  /**
   * Get sync logs
   */
  private function get_sync_logs(): array {
    $logs = get_option('kato_sync_sync_logs', array());

    // Sort by timestamp (newest first)
    usort($logs, function ($a, $b) {
      return $b['timestamp'] - $a['timestamp'];
    });

    // Limit to 50 entries
    return array_slice($logs, 0, 50);
  }

  /**
   * Format duration in seconds to human readable format
   */
  private function format_duration(int $seconds): string {
    if ($seconds < 60) {
      return $seconds . ' sec';
    }

    $minutes = floor($seconds / 60);
    $remaining_seconds = $seconds % 60;

    if ($remaining_seconds === 0) {
      return $minutes . ' min';
    }

    return $minutes . ' min ' . $remaining_seconds . ' sec';
  }
}
