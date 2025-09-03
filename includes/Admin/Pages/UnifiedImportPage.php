<?php

namespace KatoSync\Admin\Pages;

use KatoSync\Sync\SyncManager;
use KatoSync\Sync\ImageProcessor;

/**
 * Unified import page for properties and images
 */
class UnifiedImportPage {

  /**
   * Render the unified import page
   */
  public function render(): void {

    // Handle form submissions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['kato_sync_unified_nonce'], 'kato_sync_unified_action')) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
      $this->handle_form_submission();
    } else if (isset($_POST['action'])) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    }

    $settings = get_option('kato_sync_settings', array());
    $image_mode = $settings['image_mode'] ?? 'local';

    $queue_status = ImageProcessor::get_queue_status();
    $total_images_count = ImageProcessor::get_total_images_count();
    $next_batch_timing = $this->get_next_batch_timing();
    $last_sync = get_option('kato_sync_last_sync', '');
    $next_sync = wp_next_scheduled('kato_sync_auto_sync');
    $last_import_mode = get_option('kato_sync_last_import_mode', '');

    // Calculate relative time for last sync
    $last_sync_relative = '';
    if ($last_sync) {
      $time_diff = time() - $last_sync;
      if ($time_diff < 60) {
        $last_sync_relative = 'just now';
      } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        $last_sync_relative = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
      } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        $last_sync_relative = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
      } else {
        $days = floor($time_diff / 86400);
        $last_sync_relative = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
      }
    }

    // Calculate ETA for pending images
    $eta = '';
    $total_from_feed = ImageProcessor::get_total_images_count();
    $imported_count = ImageProcessor::get_imported_images_count();
    $failed_count = $queue_status['failed'];
    $calculated_pending = max(0, $total_from_feed - $imported_count - $failed_count);

    if ($calculated_pending > 0) {
      $images_per_hour = 600; // 20 images per 2 minutes = 600 per hour
      $hours_remaining = ceil($calculated_pending / $images_per_hour);
      if ($hours_remaining <= 1) {
        $eta = '~' . ceil($calculated_pending / 10) . ' minutes';
      } else {
        $eta = '~' . $hours_remaining . ' hours';
      }
    }
?>
    <div class="wrap">
      <h1><?php _e('Import Properties & Images', 'kato-sync'); ?></h1>

      <!-- Processing Notice -->
      <div class="kato-sync-notice">
        <p><?php _e('We process data (properties and images) in batches to protect server resources. Please keep this page open during import. Large property imports may take several hours. After the initial image sync, future imports will be much faster.', 'kato-sync'); ?></p>
      </div>

      <!-- Status Overview -->
      <div class="kato-sync-status-overview">

        <!-- Sync Status -->
        <div class="status-section">
          <h3><?php _e('Sync', 'kato-sync'); ?></h3>
          <div class="status-grid">
            <div class="status-item">
              <span class="label"><?php _e('Last sync:', 'kato-sync'); ?></span>
              <span class="value">
                <?php
                if ($last_sync) {
                  // Get the last sync log to determine if it was auto or manual
                  $sync_logs = get_option('kato_sync_sync_logs', array());
                  $last_sync_log = null;
                  foreach (array_reverse($sync_logs) as $log) {
                    if (isset($log['timestamp']) && $log['timestamp'] == $last_sync) {
                      $last_sync_log = $log;
                      break;
                    }
                  }
                  $sync_type = $last_sync_log && isset($last_sync_log['type']) ? $last_sync_log['type'] : 'unknown';
                  echo $last_sync_relative . ', ' . esc_html($sync_type);
                } else {
                  echo __('Never', 'kato-sync');
                }
                ?>
              </span>
            </div>
            <div class="status-item">
              <span class="label"><?php _e('Next sync:', 'kato-sync'); ?></span>
              <span class="value"><?php echo $next_sync ? date('M j, g:i A', $next_sync) : __('Not scheduled', 'kato-sync'); ?></span>
            </div>
          </div>
        </div>

        <!-- Last Import Mode -->
        <div class="status-section">
          <h3><?php _e('Last Import Mode', 'kato-sync'); ?></h3>
          <div class="import-mode-display">
            <?php if ($last_sync && $last_import_mode): ?>
              <span class="mode-indicator <?php echo $last_import_mode === 'properties_and_images' ? 'active' : 'inactive'; ?>">
                <?php echo $last_import_mode === 'properties_and_images' ? __('Properties + Images', 'kato-sync') : __('Properties', 'kato-sync'); ?>
              </span>
            <?php elseif ($last_sync): ?>
              <span class="mode-indicator inactive">
                <?php _e('Unknown mode', 'kato-sync'); ?>
              </span>
            <?php else: ?>
              <span class="mode-indicator inactive">
                <?php _e('Nothing imported', 'kato-sync'); ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Images Status -->
        <div class="status-section">
          <h3><?php _e('Images', 'kato-sync'); ?></h3>
          <div class="status-grid">
            <div class="status-item">
              <span class="label"><?php _e('Imported:', 'kato-sync'); ?></span>
              <span class="value"><?php echo ImageProcessor::get_imported_images_count(); ?></span>
            </div>
            <div class="status-item">
              <span class="label"><?php _e('Pending:', 'kato-sync'); ?></span>
              <span class="value"><?php
                                  // Calculate pending as: total from feed - imported - failed
                                  $total_from_feed = ImageProcessor::get_total_images_count();
                                  $imported_count = ImageProcessor::get_imported_images_count();
                                  $failed_count = $queue_status['failed'];
                                  $calculated_pending = max(0, $total_from_feed - $imported_count - $failed_count);
                                  echo $calculated_pending;
                                  ?> <?php echo $eta ? '(' . $eta . ')' : ''; ?></span>
            </div>
            <div class="status-item">
              <span class="label"><?php _e('Next batch:', 'kato-sync'); ?></span>
              <span class="value next-batch"><?php echo $next_batch_timing; ?></span>
            </div>
            <div class="status-item">
              <span class="label"><?php _e('Failed:', 'kato-sync'); ?></span>
              <span class="value"><?php echo $queue_status['failed']; ?></span>
            </div>
          </div>
        </div>



      </div>

      <!-- Import Actions -->
      <div class="kato-sync-import-actions">
        <h3><?php _e('Manual Import', 'kato-sync'); ?></h3>

        <form method="post" action="" class="import-form">
          <?php wp_nonce_field('kato_sync_unified_action', 'kato_sync_unified_nonce'); ?>

          <div class="import-options">
            <label class="import-option">
              <input type="radio" name="import_type" value="properties_only" checked>
              <span class="option-text"><?php _e('Properties Only', 'kato-sync'); ?></span>
              <span class="option-desc"><?php _e('Import property data only. Images will be served from external URLs.', 'kato-sync'); ?></span>
            </label>

            <label class="import-option">
              <input type="radio" name="import_type" value="properties_and_images">
              <span class="option-text"><?php _e('Properties + Images', 'kato-sync'); ?></span>
              <span class="option-desc"><?php _e('Import properties and download images to media library.', 'kato-sync'); ?></span>
            </label>
          </div>

          <div class="import-controls">
            <label class="force-update-option">
              <input type="checkbox" name="force_update" value="1">
              <span><?php _e('Force update all data (bypass timestamp checks)', 'kato-sync'); ?></span>
            </label>

            <input type="hidden" name="action" value="start_import">
            <button type="submit" class="button button-primary" id="start-import-btn">
              <?php _e('Start Import', 'kato-sync'); ?>
            </button>

            <?php if (get_transient('kato_sync_running')): ?>
              <button type="submit" name="action" value="clear_sync_lock" class="button button-secondary">
                <?php _e('Clear Sync Lock', 'kato-sync'); ?>
              </button>
            <?php endif; ?>
          </div>

          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const form = document.querySelector('.import-form');
              const submitBtn = document.getElementById('start-import-btn');

              if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                  e.preventDefault(); // Prevent default form submission

                  submitBtn.disabled = true;
                  submitBtn.textContent = '<?php _e('Importing...', 'kato-sync'); ?>';

                  // Show a comprehensive progress indicator
                  const progressContainer = document.createElement('div');
                  progressContainer.className = 'kato-sync-progress';
                  progressContainer.innerHTML = `
                    <div class="notice notice-info">
                      <h4><?php _e('Import in Progress', 'kato-sync'); ?></h4>
                      <div class="progress-bar">
                        <div class="progress-fill"></div>
                      </div>
                      <div class="progress-status">
                        <p><?php _e('Fetching XML feed...', 'kato-sync'); ?></p>
                        <p class="progress-details"><?php _e('Please wait and do not close this page.', 'kato-sync'); ?></p>
                      </div>
                    </div>
                  `;
                  form.parentNode.insertBefore(progressContainer, form.nextSibling);

                  // Start progress animation
                  const progressFill = progressContainer.querySelector('.progress-fill');
                  const progressDetails = progressContainer.querySelector('.progress-details');
                  let progress = 0;
                  const progressInterval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90; // Don't go to 100% until complete
                    progressFill.style.width = progress + '%';

                    // Update status messages based on progress
                    if (progress < 20) {
                      progressDetails.textContent = '<?php _e('Fetching XML feed...', 'kato-sync'); ?>';
                    } else if (progress < 40) {
                      progressDetails.textContent = '<?php _e('Parsing property data...', 'kato-sync'); ?>';
                    } else if (progress < 60) {
                      progressDetails.textContent = '<?php _e('Processing properties...', 'kato-sync'); ?>';
                    } else if (progress < 80) {
                      progressDetails.textContent = '<?php _e('Updating database...', 'kato-sync'); ?>';
                    } else {
                      progressDetails.textContent = '<?php _e('Finalizing import...', 'kato-sync'); ?>';
                    }
                  }, 1000);

                  // Store interval for cleanup
                  window.katoSyncProgressInterval = progressInterval;

                  // Submit the form via AJAX
                  const formData = new FormData(form);
                  formData.append('action', 'kato_sync_manual_sync');
                  formData.append('nonce', '<?php echo wp_create_nonce('kato_sync_nonce'); ?>');

                  fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                      method: 'POST',
                      body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                      // Clear the progress interval
                      if (window.katoSyncProgressInterval) {
                        clearInterval(window.katoSyncProgressInterval);
                      }

                      // Complete the progress bar
                      progressFill.style.width = '100%';
                      progressDetails.textContent = '<?php _e('Import completed!', 'kato-sync'); ?>';

                      // Show success message
                      const successNotice = document.createElement('div');
                      successNotice.className = 'notice notice-success';
                      successNotice.innerHTML = '<p>' + (data.data?.message || '<?php _e('Import completed successfully!', 'kato-sync'); ?>') + '</p>';
                      progressContainer.appendChild(successNotice);

                      // Re-enable the button
                      submitBtn.disabled = false;
                      submitBtn.textContent = '<?php _e('Start Import', 'kato-sync'); ?>';

                      // Reload the page after a short delay to show updated data
                      setTimeout(() => {
                        location.reload();
                      }, 2000);
                    })
                    .catch(error => {
                      // Clear the progress interval
                      if (window.katoSyncProgressInterval) {
                        clearInterval(window.katoSyncProgressInterval);
                      }

                      // Show error message
                      const errorNotice = document.createElement('div');
                      errorNotice.className = 'notice notice-error';
                      errorNotice.innerHTML = '<p><?php _e('Import failed. Please try again.', 'kato-sync'); ?></p>';
                      progressContainer.appendChild(errorNotice);

                      // Re-enable the button
                      submitBtn.disabled = false;
                      submitBtn.textContent = '<?php _e('Start Import', 'kato-sync'); ?>';
                    });
                });
              }
            });
          </script>
        </form>
      </div>



      <!-- Import History Table -->
      <?php
      $sync_logs = get_option('kato_sync_sync_logs', array());
      ?>
      <div class="kato-sync-summary">
        <h3><?php _e('Recent Import History', 'kato-sync'); ?></h3>
        <?php if (!empty($sync_logs)): ?>
          <?php
          // Get the last 2 imports
          $recent_logs = array_slice(array_reverse($sync_logs), 0, 20);
          ?>
          <table class="widefat">
            <thead>
              <tr>
                <th><?php _e('Type', 'kato-sync'); ?></th>
                <th><?php _e('Status', 'kato-sync'); ?></th>
                <th><?php _e('Total Properties', 'kato-sync'); ?></th>
                <th><?php _e('Added', 'kato-sync'); ?></th>
                <th><?php _e('Updated', 'kato-sync'); ?></th>
                <th><?php _e('Removed', 'kato-sync'); ?></th>
                <th><?php _e('Skipped', 'kato-sync'); ?></th>
                <th><?php _e('Duration', 'kato-sync'); ?></th>
                <th><?php _e('Timestamp', 'kato-sync'); ?></th>
                <th><?php _e('Details', 'kato-sync'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_logs as $log): ?>
                <tr class="<?php echo $log['status'] === 'success' ? 'success' : 'error'; ?>">
                  <td>
                    <span class="import-type-badge">
                      <?php echo esc_html(ucfirst($log['type'] ?? 'manual')); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge status-<?php echo $log['status'] ?? 'unknown'; ?>">
                      <?php echo esc_html(ucfirst($log['status'] ?? 'unknown')); ?>
                    </span>
                  </td>
                  <td><?php echo esc_html($log['total_properties'] ?? 0); ?></td>
                  <td><?php echo esc_html($log['added'] ?? 0); ?></td>
                  <td><?php echo esc_html($log['updated'] ?? 0); ?></td>
                  <td><?php echo esc_html($log['removed'] ?? 0); ?></td>
                  <td><?php echo esc_html($log['skipped'] ?? 0); ?></td>
                  <td><?php echo esc_html($log['duration'] ?? 0); ?>s</td>
                  <td>
                    <?php
                    if (!empty($log['timestamp'])) {
                      $timestamp = is_numeric($log['timestamp']) ? $log['timestamp'] : strtotime($log['timestamp']);
                      echo date('M j, g:i A', $timestamp);
                    } else {
                      echo '—';
                    }
                    ?>
                  </td>
                  <td>
                    <?php if (!empty($log['message'])): ?>
                      <span class="import-details" title="<?php echo esc_attr($log['message']); ?>">
                        <?php echo esc_html(substr($log['message'], 0, 50)) . (strlen($log['message']) > 50 ? '...' : ''); ?>
                      </span>
                    <?php elseif (!empty($log['error'])): ?>
                      <span class="import-details error" title="<?php echo esc_attr($log['error']); ?>">
                        <?php echo esc_html(substr($log['error'], 0, 50)) . (strlen($log['error']) > 50 ? '...' : ''); ?>
                      </span>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p><?php _e('No import history available. Run your first import to see results here.', 'kato-sync'); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <style>
      .kato-sync-status-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
      }

      .status-section {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }

      .status-section h3 {
        margin: 0 0 15px 0;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .status-grid {
        display: grid;
        gap: 8px;
      }

      .status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
      }

      .status-item .label {
        font-size: 13px;
        color: #666;
        font-weight: 500;
      }

      .status-item .value {
        font-size: 13px;
        color: #333;
        font-weight: 600;
      }

      .status-item .value.next-batch {
        font-size: 11px;
        color: #666;
        font-weight: 400;
      }

      .import-mode-display {
        display: flex;
        align-items: center;
      }

      .mode-indicator {
        padding: 8px 12px;
        border: 2px solid #ddd;
        border-radius: 4px;
        background: #f9f9f9;
        font-size: 13px;
        font-weight: 500;
        color: #666;
      }

      .mode-indicator.active {
        border-color: #0073aa;
        background: #f0f8ff;
        color: #0073aa;
        font-weight: 600;
      }

      .mode-indicator.inactive {
        border-color: #ddd;
        background: #f9f9f9;
        color: #666;
      }

      .kato-sync-import-actions {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }

      .kato-sync-import-actions h3 {
        margin: 0 0 15px 0;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .import-options {
        display: grid;
        gap: 12px;
        margin-bottom: 20px;
      }

      .import-option {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 4px;
        cursor: pointer;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 6px;
        background: #f9f9f9;
        transition: all 0.2s ease;
      }

      .import-option:hover {
        border-color: #0073aa;
        background: #f8fbff;
      }

      .import-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
      }

      .import-option input[type="radio"]:checked+.option-text {
        color: #0073aa;
      }

      .import-option input[type="radio"]:checked~.option-desc {
        color: #0073aa;
      }

      .import-option input[type="radio"]:checked {
        border-color: #0073aa;
        background: #f0f8ff;
      }

      .option-text {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        transition: color 0.2s ease;
      }

      .option-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
        transition: color 0.2s ease;
      }

      .import-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #eee;
      }

      .force-update-option {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #666;
      }

      .kato-sync-quick-actions {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }

      .kato-sync-quick-actions h3 {
        margin: 0 0 15px 0;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }

      .kato-sync-notice {
        background: #f0f8ff;
        border: 1px solid #0073aa;
        border-radius: 6px;
        padding: 15px;
        margin: 20px 0;
      }

      .kato-sync-notice p {
        margin: 0;
        font-size: 12px;
        color: #666;
        line-height: 1.5;
      }

      .kato-sync-diagnostics {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }

      .kato-sync-diagnostics h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .kato-sync-diagnostics p {
        margin: 0 0 15px 0;
        font-size: 12px;
        color: #666;
      }

      .diagnostic-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }

      @media (max-width: 768px) {
        .kato-sync-status-overview {
          grid-template-columns: 1fr;
        }

        .import-controls {
          flex-direction: column;
          gap: 15px;
          align-items: stretch;
        }

        .action-buttons {
          flex-direction: column;
        }
      }

      /* Progress Bar Styles */
      .kato-sync-progress {
        margin: 20px 0;
      }

      .kato-sync-progress .notice {
        padding: 20px;
        border-left: 4px solid #0073aa;
      }

      .kato-sync-progress h4 {
        margin: 0 0 15px 0;
        color: #0073aa;
        font-size: 16px;
      }

      .progress-bar {
        width: 100%;
        height: 20px;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
        margin: 15px 0;
      }

      .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #0073aa, #005a87);
        width: 0%;
        transition: width 0.5s ease;
        border-radius: 10px;
      }

      .progress-status p {
        margin: 5px 0;
        font-size: 14px;
      }

      .progress-details {
        color: #666;
        font-style: italic;
      }

      /* Import Summary Table Styles */
      .kato-sync-summary {
        margin: 20px 0;
      }

      .kato-sync-summary h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #333;
      }

      .kato-sync-summary table {
        border-collapse: collapse;
        width: 100%;
      }

      .kato-sync-summary th {
        background: #f9f9f9;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        border: 1px solid #ddd;
      }

      .kato-sync-summary td {
        padding: 10px;
        border: 1px solid #ddd;
      }

      .kato-sync-summary tr.success {
        background-color: #f0f8f0;
      }

      .kato-sync-summary tr.error {
        background-color: #fef7f1;
      }

      .status-badge {
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
      }

      .status-badge.status-success {
        background-color: #46b450;
        color: white;
      }

      .status-badge.status-error {
        background-color: #dc3232;
        color: white;
      }

      .status-badge.status-unknown {
        background-color: #999;
        color: white;
      }

      .import-type-badge {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        background-color: #0073aa;
        color: white;
      }

      .import-details {
        font-size: 12px;
        color: #666;
        cursor: help;
      }

      .import-details.error {
        color: #dc3232;
      }

      .kato-sync-summary table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px;
      }

      .kato-sync-summary th {
        background: #f9f9f9;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        border: 1px solid #ddd;
        font-size: 12px;
      }

      .kato-sync-summary td {
        padding: 8px 10px;
        border: 1px solid #ddd;
        font-size: 12px;
      }

      .kato-sync-summary tr.success {
        background-color: #f0f8f0;
      }

      .kato-sync-summary tr.error {
        background-color: #fef7f1;
      }

      .error-details {
        margin-top: 15px;
        padding: 15px;
        background-color: #fef7f1;
        border-left: 4px solid #dc3232;
      }

      .error-details h4 {
        margin: 0 0 10px 0;
        color: #dc3232;
      }

      .error-details p {
        margin: 0;
        color: #666;
      }
    </style>


<?php
  }

  /**
   * Handle form submissions
   */
  private function handle_form_submission(): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Debug: Log all POST data to understand what's being submitted
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Debug: Check what nonce fields are actually present
    $nonce_fields_present = [];
    if (isset($_POST['kato_sync_unified_nonce'])) {
      $nonce_fields_present[] = 'kato_sync_unified_nonce: ' . $_POST['kato_sync_unified_nonce'];
    }
    if (isset($_POST['kato_sync_import_nonce'])) {
      $nonce_fields_present[] = 'kato_sync_import_nonce: ' . $_POST['kato_sync_import_nonce'];
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Try both nonce verification methods to see which one works
    $unified_nonce_valid = isset($_POST['kato_sync_unified_nonce']) &&
      wp_verify_nonce($_POST['kato_sync_unified_nonce'], 'kato_sync_unified_action');
    $import_nonce_valid = isset($_POST['kato_sync_import_nonce']) &&
      wp_verify_nonce($_POST['kato_sync_import_nonce'], 'kato_sync_import');

    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Use the correct nonce verification
    if (!$unified_nonce_valid && !$import_nonce_valid) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
      return;
    }

    if ($unified_nonce_valid) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    } else {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
    }



    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    if (!isset($_POST['import_type'])) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
      return;
    }

    $import_type = sanitize_text_field($_POST['import_type']);
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
      case 'start_import':
        $import_type = $_POST['import_type'] ?? 'properties_only';
        $force_update = isset($_POST['force_update']);

        if (defined('WP_DEBUG') && WP_DEBUG) {
        }

        // Set a timeout to prevent hanging
        set_time_limit(300); // 5 minutes max
        ini_set('max_execution_time', 300);

        // Flush output buffer to show progress
        if (ob_get_level()) {
          ob_end_flush();
        }
        flush();

        try {
          if ($import_type === 'properties' || $import_type === 'properties_only') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
            $result = SyncManager::sync_properties('manual');
            $message = sprintf(
              __('Properties sync completed. %d added, %d updated, %d skipped.', 'kato-sync'),
              $result['added'],
              $result['updated'],
              $result['skipped']
            );
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
          } elseif ($import_type === 'properties_and_images') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            $result = SyncManager::sync_properties('manual');
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // Get settings and validate them
            $settings = get_option('kato_sync_settings', array());
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // Validate that we have the required settings
            if (empty($settings)) {
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
              $settings = array(
                'image_batch_size' => 20,
                'image_timeout' => 30,
                'image_retry_attempts' => 3,
                'image_cron_interval' => 'every_2_minutes'
              );
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
            }

            $batch_size = intval($settings['image_batch_size'] ?? 20);
            $timeout = intval($settings['image_timeout'] ?? 30);
            $max_retries = intval($settings['image_retry_attempts'] ?? 3);

            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // Check if the action hook is registered
            $has_action = has_action('kato_sync_process_images');
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // Test if WordPress cron system is working at all
            $test_cron_scheduled = wp_schedule_event(time() + 300, 'hourly', 'kato_sync_test_cron');
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
            if ($test_cron_scheduled) {
              wp_clear_scheduled_hook('kato_sync_test_cron'); // Clean up test cron
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
            } else {
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
            }

            // Process images using the configured settings
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
            $image_result = ImageProcessor::process_images_with_settings($batch_size, $timeout, $max_retries);

            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // Ensure cron job is scheduled for background processing
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
            $this->ensure_image_cron_scheduled($settings);
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }

            // Trigger immediate background processing if there are remaining images
            if ($image_result['remaining'] > 0) {
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
              // Process one more batch immediately to start background processing
              $background_result = ImageProcessor::process_image_batch();
              $image_result['processed'] += $background_result['processed'];
              $image_result['remaining'] = $background_result['remaining'];

              // Also trigger a manual cron run to ensure processing starts
              $this->trigger_manual_background_processing();

              // Manually trigger the cron job to run immediately
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
              do_action('kato_sync_process_images');
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
            }

            $message = sprintf(
              __('Properties sync completed. %d added, %d updated, %d skipped. Images: %d processed immediately (batch size: %d), %d remaining in queue for background processing.', 'kato-sync'),
              $result['added'],
              $result['updated'],
              $result['skipped'],
              $image_result['processed'],
              $batch_size,
              $image_result['remaining']
            );
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
          } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
            }
            $message = __('Invalid import type.', 'kato-sync');
          }

          add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
          });
        } catch (\Exception $e) {
          if (defined('WP_DEBUG') && WP_DEBUG) {
          }
          add_action('admin_notices', function () use ($e) {
            echo '<div class="notice notice-error"><p>' .
              __('Import failed: ', 'kato-sync') . esc_html($e->getMessage()) . '</p></div>';
          });
        }
        break;

      case 'process_image_batch':
        $result = ImageProcessor::process_images_continuously(5); // Process for up to 5 minutes
        add_action('admin_notices', function () use ($result) {
          echo '<div class="notice notice-success"><p>' .
            sprintf(
              __('Processed %d images, %d failed, %d remaining in queue.', 'kato-sync'),
              $result['processed'],
              $result['failed'],
              $result['remaining']
            ) .
            '</p></div>';
        });
        break;

      case 'retry_failed_images':
        $result = ImageProcessor::retry_failed_images();
        add_action('admin_notices', function () use ($result) {
          echo '<div class="notice notice-success"><p>' .
            sprintf(__('Retried %d failed images.', 'kato-sync'), $result['retried']) .
            '</p></div>';
        });
        break;

      case 'clear_sync_lock':
        delete_transient('kato_sync_running');
        add_action('admin_notices', function () {
          echo '<div class="notice notice-success"><p>' .
            __('Sync lock cleared successfully.', 'kato-sync') .
            '</p></div>';
        });
        break;

      case 'diagnostic_action':
        $diagnostic_type = $_POST['diagnostic_type'] ?? '';

        switch ($diagnostic_type) {
          case 'process_images_now':
            // Process just one batch instead of continuous processing
            $result = ImageProcessor::process_image_batch();
            add_action('admin_notices', function () use ($result) {
              echo '<div class="notice notice-success"><p>' .
                sprintf(
                  __('Processed %d images, %d failed, %d remaining in queue.', 'kato-sync'),
                  $result['processed'],
                  $result['failed'],
                  count(get_option('kato_sync_image_queue', array()))
                ) .
                '</p></div>';
            });
            break;

          case 'reschedule_cron':
            try {
              // Clear existing cron job
              wp_clear_scheduled_hook('kato_sync_process_images');
              // Reschedule the cron job
              wp_schedule_event(time(), 'every_2_minutes', 'kato_sync_process_images');
              $next_run = wp_next_scheduled('kato_sync_process_images');

              add_action('admin_notices', function () use ($next_run) {
                echo '<div class="notice notice-success"><p>' .
                  __('Cron job rescheduled successfully. Next run: ' . ($next_run ? date('M j, g:i A', $next_run) : 'Not scheduled'), 'kato-sync') .
                  '</p></div>';
              });
            } catch (\Exception $e) {
              add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>Cron reschedule failed: ' . esc_html($e->getMessage()) . '</p></div>';
              });
            }
            break;

          case 'debug_queue':
            try {
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }

              $queue = ImageProcessor::get_image_queue();
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }

              $queue_status = ImageProcessor::get_queue_status();
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }

              $total_count = ImageProcessor::get_total_images_count();
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }

              add_action('admin_notices', function () use ($queue, $queue_status, $total_count) {
                // Get actual media library count
                $media_library_count = wp_count_posts('attachment')->inherit;

                echo '<div class="notice notice-info"><p><strong>Image Queue Debug Information:</strong></p>';
                echo '<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0;">';
                echo '<p><strong>Queue Status:</strong></p>';
                echo '<ul>';
                echo '<li>Total original images from feed: ' . $total_count . '</li>';
                echo '<li>Pending in queue: ' . $queue_status['pending'] . '</li>';
                echo '<li>Failed images: ' . $queue_status['failed'] . '</li>';
                echo '<li>Queue items: ' . count($queue) . '</li>';
                echo '<li>Media library attachments: ' . $media_library_count . '</li>';
                echo '<li>Processed images: ' . ($total_count - $queue_status['pending'] - $queue_status['failed']) . '</li>';
                echo '</ul>';

                // Check cron system status
                $next_cron = wp_next_scheduled('kato_sync_process_images');
                echo '<p><strong>Cron System Status:</strong></p>';
                echo '<ul>';
                echo '<li>Next cron run: ' . ($next_cron ? date('Y-m-d H:i:s', $next_cron) : 'Not scheduled') . '</li>';
                echo '<li>Cron system working: ' . (wp_get_schedules() ? 'Yes' : 'No') . '</li>';
                echo '<li>Available schedules: ' . implode(', ', array_keys(wp_get_schedules())) . '</li>';
                echo '</ul>';

                // Count Kato Sync attachments specifically
                global $wpdb;
                $kato_attachments = $wpdb->get_var($wpdb->prepare(
                  "SELECT COUNT(*) FROM {$wpdb->postmeta}
                   WHERE meta_key = %s",
                  '_kato_sync_attachment_id'
                ));
                echo '<p><strong>Kato Sync Attachments:</strong> ' . $kato_attachments . '</p>';

                // Show first few queue items
                if (!empty($queue)) {
                  echo '<p><strong>First 5 Queue Items:</strong></p>';
                  echo '<ul>';
                  $count = 0;
                  foreach ($queue as $item) {
                    if ($count >= 5) break;
                    echo '<li>Property ' . $item['post_id'] . ': ' . $item['image_name'] . ' (' . $item['status'] . ')</li>';
                    $count++;
                  }
                  echo '</ul>';
                }

                echo '</div></div>';
              });
            } catch (\Exception $e) {
              if (defined('WP_DEBUG') && WP_DEBUG) {
              }
              add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' .
                  __('Debug failed: ', 'kato-sync') . esc_html($e->getMessage()) . '</p></div>';
              });
            }
            break;

          case 'fix_auto_sync':
            try {
              // Get current settings
              $settings = get_option('kato_sync_settings', array());

              // Clear existing cron job
              wp_clear_scheduled_hook('kato_sync_auto_sync');

              // Get the frequency from settings
              $frequency = $settings['auto_sync_frequency'] ?? '1hour';
              $current_time = current_time('timestamp');

              // Calculate next run time based on frequency
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
                  $next_run = $current_time + 3600;
                  break;
              }

              // Schedule the cron job
              $scheduled = wp_schedule_event($next_run, $frequency, 'kato_sync_auto_sync');

              if ($scheduled) {
                update_option('kato_sync_next_sync', $next_run);
                add_action('admin_notices', function () use ($frequency, $next_run) {
                  echo '<div class="notice notice-success"><p>' .
                    sprintf(
                      __('Auto-sync rescheduled successfully. Frequency: %s, Next run: %s', 'kato-sync'),
                      esc_html($frequency),
                      date('M j, g:i A', $next_run)
                    ) . '</p></div>';
                });
              } else {
                add_action('admin_notices', function () {
                  echo '<div class="notice notice-error"><p>' .
                    __('Failed to reschedule auto-sync. Please check your cron configuration.', 'kato-sync') . '</p></div>';
                });
              }
            } catch (\Exception $e) {
              add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' .
                  __('Auto-sync fix failed: ', 'kato-sync') . esc_html($e->getMessage()) . '</p></div>';
              });
            }
            break;

          case 'debug_auto_sync':
            try {
              $settings = get_option('kato_sync_settings', array());
              $next_scheduled = wp_next_scheduled('kato_sync_auto_sync');
              $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
              $schedules = wp_get_schedules();

              add_action('admin_notices', function () use ($settings, $next_scheduled, $cron_disabled, $schedules) {
                echo '<div class="notice notice-info"><p><strong>Auto-Sync Debug Information:</strong></p>';
                echo '<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 10px 0;">';
                echo '<p><strong>Settings:</strong></p>';
                echo '<ul>';
                echo '<li>Auto-sync enabled: ' . ($settings['auto_sync_enabled'] ? 'Yes' : 'No') . '</li>';
                echo '<li>Configured frequency: ' . esc_html($settings['auto_sync_frequency'] ?? '1hour') . '</li>';
                echo '<li>Feed URL configured: ' . (!empty($settings['feed_url']) ? 'Yes' : 'No') . '</li>';
                echo '</ul>';

                echo '<p><strong>Cron Status:</strong></p>';
                echo '<ul>';
                echo '<li>WP Cron disabled: ' . ($cron_disabled ? 'Yes' : 'No') . '</li>';
                echo '<li>Next scheduled: ' . ($next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : 'Not scheduled') . '</li>';
                echo '<li>Available schedules: ' . esc_html(implode(', ', array_keys($schedules))) . '</li>';
                echo '</ul>';

                // Check if the configured frequency is available
                $frequency = $settings['auto_sync_frequency'] ?? '1hour';
                $frequency_available = isset($schedules[$frequency]);
                echo '<p><strong>Frequency Check:</strong></p>';
                echo '<ul>';
                echo '<li>Configured frequency available: ' . ($frequency_available ? 'Yes' : 'No') . '</li>';
                if (!$frequency_available) {
                  echo '<li>Missing frequency: ' . esc_html($frequency) . '</li>';
                }
                echo '</ul>';

                echo '</div></div>';
              });
            } catch (\Exception $e) {
              add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' .
                  __('Auto-sync debug failed: ', 'kato-sync') . esc_html($e->getMessage()) . '</p></div>';
              });
            }
            break;

          case 'cleanup_queue':
            try {
              $result = ImageProcessor::cleanup_image_queue();
              add_action('admin_notices', function () use ($result) {
                echo '<div class="notice notice-success"><p>' .
                  sprintf(
                    __('Queue cleanup completed. Removed %d already imported images. %d items remaining in queue.', 'kato-sync'),
                    $result['removed_count'],
                    $result['remaining_count']
                  ) .
                  '</p></div>';
              });
            } catch (\Exception $e) {
              add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-error"><p>' .
                  __('Queue cleanup failed: ', 'kato-sync') . esc_html($e->getMessage()) . '</p></div>';
              });
            }
            break;
        }
        break;
    }
  }

  /**
   * Ensures the image processing cron job is scheduled if it's not already.
   * This is important because the image processing should run in the background
   * after the initial properties import.
   */
  private function ensure_image_cron_scheduled(array $settings): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Debug: Check WordPress cron system status
    $wp_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Debug: Check available cron schedules BEFORE we try to use them
    $schedules = wp_get_schedules();
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Get the cron interval from settings
    $interval = $settings['image_cron_interval'] ?? 'every_2_minutes';
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Check if the requested interval exists
    if (!isset($schedules[$interval])) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }

      // Try to use a built-in interval as fallback
      if (isset($schedules['hourly'])) {
        $interval = 'hourly';
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
      } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        return;
      }
    } else {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    }

    // Check current cron status
    $current_cron = wp_next_scheduled('kato_sync_process_images');
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Clear any existing cron job to ensure fresh scheduling
    $cleared = wp_clear_scheduled_hook('kato_sync_process_images');
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    // Debug: Check if the action hook is registered
    global $wp_filter;
    $hook_registered = isset($wp_filter['kato_sync_process_images']) && !empty($wp_filter['kato_sync_process_images']);
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    if ($hook_registered) {
      $callbacks_count = count($wp_filter['kato_sync_process_images']->callbacks);
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    }

    // Schedule the cron job to start immediately
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    $scheduled = wp_schedule_event(time(), $interval, 'kato_sync_process_images');
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }

    if ($scheduled === false) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }

      // Try to schedule with a different built-in interval
      if ($interval !== 'hourly') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }
        $scheduled = wp_schedule_event(time(), 'hourly', 'kato_sync_process_images');
        if (defined('WP_DEBUG') && WP_DEBUG) {
        }

        if ($scheduled === false) {
          if (defined('WP_DEBUG') && WP_DEBUG) {
          }
        }
      }
    }

    // Verify the cron job was actually scheduled
    $next_run = wp_next_scheduled('kato_sync_process_images');
    if (!$next_run) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }

      // Try one more debugging approach - check all scheduled crons
      $all_crons = _get_cron_array();
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }

      // Check if ANY cron jobs are working
      $has_any_crons = !empty($all_crons);
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    } else {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    }
  }

  /**
   * Triggers a manual background processing run.
   * This is useful when you want to ensure the cron job runs immediately
   * after a manual import, even if the cron is scheduled for a later time.
   */
  private function trigger_manual_background_processing(): void {
    if (defined('WP_DEBUG') && WP_DEBUG) {
    }
    // Clear any existing cron job to ensure fresh scheduling
    wp_clear_scheduled_hook('kato_sync_process_images');
    // Schedule it to run immediately
    wp_schedule_event(time(), 'every_2_minutes', 'kato_sync_process_images');
    $next_run = wp_next_scheduled('kato_sync_process_images');
    if ($next_run) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    } else {
      if (defined('WP_DEBUG') && WP_DEBUG) {
      }
    }
  }

  /**
   * Get the next batch timing with improved accuracy
   */
  private function get_next_batch_timing(): string {
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
}
