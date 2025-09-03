<?php

namespace KatoSync\Admin\Pages;

use KatoSync\Sync\ImageProcessor;

/**
 * Image management page
 */
class ImageManagementPage {

  /**
   * Render the image management page
   */
  public function render(): void {
    // Handle form submissions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['kato_sync_image_nonce'], 'kato_sync_image_action')) {
      $this->handle_form_submission();
    }

    $settings = get_option('kato_sync_settings', array());
    $image_mode = $settings['image_mode'] ?? 'local';
    $queue_status = ImageProcessor::get_queue_status();
    $failed_images = ImageProcessor::get_failed_images();
?>
    <div class="wrap">
      <h1><?php _e('Kato Sync - Image Management', 'kato-sync'); ?></h1>

      <!-- Current Mode -->
      <div class="notice notice-info">
        <p>
          <strong><?php _e('Current Image Mode:', 'kato-sync'); ?></strong>
          <?php echo $image_mode === 'local' ? __('Import to Media Library', 'kato-sync') : __('Serve from External URLs', 'kato-sync'); ?>
        </p>
        <p>
          <a href="<?php echo admin_url('admin.php?page=kato-sync-settings'); ?>" class="button button-secondary">
            <?php _e('Change Image Mode', 'kato-sync'); ?>
          </a>
        </p>
      </div>

      <?php if ($image_mode === 'local'): ?>
        <!-- Queue Status -->
        <div class="card">
          <h2><?php _e('Image Import Queue', 'kato-sync'); ?></h2>

          <div class="kato-sync-stats">
            <div class="stat-item">
              <span class="stat-number"><?php echo $queue_status['pending']; ?></span>
              <span class="stat-label"><?php _e('Pending', 'kato-sync'); ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-number"><?php echo $queue_status['failed']; ?></span>
              <span class="stat-label"><?php _e('Failed', 'kato-sync'); ?></span>
            </div>
            <div class="stat-item">
              <span class="stat-number"><?php echo $queue_status['total'] - $queue_status['pending'] - $queue_status['failed']; ?></span>
              <span class="stat-label"><?php _e('Imported', 'kato-sync'); ?></span>
            </div>
          </div>

          <?php
          // Simple queue info display
          $queue = get_option('kato_sync_image_queue', array());
          if (!empty($queue)) {
            $first_item = $queue[0];
            echo '<div style="background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            echo '<p><strong>Queue Info:</strong></p>';
            echo '<p>Total items: ' . count($queue) . '</p>';
            echo '<p>First item - Post ID: ' . ($first_item['post_id'] ?? 'N/A') . ', Image: ' . ($first_item['image_name'] ?? 'N/A') . '</p>';
            echo '</div>';
          }
          ?>

          <?php if ($queue_status['total'] > 0): ?>
            <form method="post" action="">
              <?php wp_nonce_field('kato_sync_image_action', 'kato_sync_image_nonce'); ?>
              <input type="hidden" name="action" id="form_action" value="">

              <button type="submit" class="button button-primary" onclick="document.getElementById('form_action').value='retry_failed';">
                <?php _e('Retry Failed Images', 'kato-sync'); ?>
              </button>

              <button type="submit" class="button button-secondary" onclick="document.getElementById('form_action').value='clear_queue'; return confirm('<?php _e('Are you sure you want to clear the entire queue?', 'kato-sync'); ?>');">
                <?php _e('Clear Queue', 'kato-sync'); ?>
              </button>

              <button type="submit" class="button button-primary" onclick="document.getElementById('form_action').value='process_batch';">
                <?php _e('Process Batch Now', 'kato-sync'); ?>
              </button>

              <button type="submit" class="button button-secondary" onclick="document.getElementById('form_action').value='test_image_urls';">
                <?php _e('Test Image URLs', 'kato-sync'); ?>
              </button>

              <button type="submit" class="button button-secondary" onclick="document.getElementById('form_action').value='debug_queue';">
                <?php _e('Debug Queue', 'kato-sync'); ?>
              </button>

              <button type="submit" class="button button-primary" onclick="document.getElementById('form_action').value='process_images_now';">
                <?php _e('Process Images Now', 'kato-sync'); ?>
              </button>

              <button type="submit" class="button button-secondary" onclick="document.getElementById('form_action').value='reschedule_cron';">
                <?php _e('Reschedule Cron Job', 'kato-sync'); ?>
              </button>
            </form>
          <?php else: ?>
            <p><?php _e('No images in queue.', 'kato-sync'); ?></p>
          <?php endif; ?>

          <!-- Cron Status -->
          <div style="margin-top: 1rem; padding: 1rem; background: #f9f9f9; border-radius: 4px;">
            <h4><?php _e('Cron Status', 'kato-sync'); ?></h4>
            <?php
            $next_scheduled = wp_next_scheduled('kato_sync_process_images');
            $cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
            ?>
            <p><strong><?php _e('Next Scheduled Run:', 'kato-sync'); ?></strong>
              <?php echo $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : __('Not scheduled', 'kato-sync'); ?>
            </p>
            <p><strong><?php _e('WP Cron Disabled:', 'kato-sync'); ?></strong>
              <?php echo $cron_disabled ? __('Yes', 'kato-sync') : __('No', 'kato-sync'); ?>
            </p>
            <?php if ($cron_disabled): ?>
              <p style="color: #dc3232;">
                <strong><?php _e('Warning:', 'kato-sync'); ?></strong>
                <?php _e('WP Cron is disabled. Image processing will not run automatically. You may need to set up a server cron job or manually trigger processing.', 'kato-sync'); ?>
              </p>
            <?php endif; ?>

            <?php if (!$next_scheduled): ?>
              <form method="post" action="">
                <?php wp_nonce_field('kato_sync_image_action', 'kato_sync_image_nonce'); ?>
                <input type="hidden" name="action" value="reschedule_cron">
                <button type="submit" class="button button-secondary">
                  <?php _e('Reschedule Cron Job', 'kato-sync'); ?>
                </button>
              </form>
            <?php endif; ?>

          </div>
        </div>

        <!-- Failed Images -->
        <?php if (!empty($failed_images)): ?>
          <div class="card">
            <h2><?php _e('Failed Images', 'kato-sync'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
              <thead>
                <tr>
                  <th><?php _e('Property ID', 'kato-sync'); ?></th>
                  <th><?php _e('Image Name', 'kato-sync'); ?></th>
                  <th><?php _e('URL', 'kato-sync'); ?></th>
                  <th><?php _e('Attempts', 'kato-sync'); ?></th>
                  <th><?php _e('Added', 'kato-sync'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($failed_images as $image): ?>
                  <tr>
                    <td>
                      <a href="<?php echo get_edit_post_link($image['post_id']); ?>" target="_blank">
                        <?php echo $image['post_id']; ?>
                      </a>
                    </td>
                    <td><?php echo esc_html($image['image_name']); ?></td>
                    <td>
                      <a href="<?php echo esc_url($image['image_url']); ?>" target="_blank">
                        <?php echo esc_url($image['image_url']); ?>
                      </a>
                    </td>
                    <td><?php echo $image['attempts']; ?>/<?php echo \KatoSync\Sync\ImageProcessor::MAX_RETRIES; ?></td>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($image['added_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <!-- Image Processing Info -->
        <div class="card">
          <h2><?php _e('Image Processing Information', 'kato-sync'); ?></h2>
          <ul>
            <?php
            $settings = get_option('kato_sync_settings', array());
            $batch_size = intval($settings['image_batch_size'] ?? \KatoSync\Sync\ImageProcessor::DEFAULT_BATCH_SIZE);
            $max_file_size = intval($settings['image_max_size'] ?? 10) * 1024 * 1024; // Convert MB to bytes
            $timeout = intval($settings['image_timeout'] ?? \KatoSync\Sync\ImageProcessor::DEFAULT_DOWNLOAD_TIMEOUT);
            $max_retries = intval($settings['image_retry_attempts'] ?? \KatoSync\Sync\ImageProcessor::DEFAULT_MAX_RETRIES);
            ?>
            <li><strong><?php _e('Batch Size:', 'kato-sync'); ?></strong> <?php echo $batch_size; ?> <?php _e('images per cron run', 'kato-sync'); ?></li>
            <li><strong><?php _e('Max File Size:', 'kato-sync'); ?></strong> <?php echo size_format($max_file_size); ?></li>
            <li><strong><?php _e('Download Timeout:', 'kato-sync'); ?></strong> <?php echo $timeout; ?> <?php _e('seconds', 'kato-sync'); ?></li>
            <li><strong><?php _e('Max Retries:', 'kato-sync'); ?></strong> <?php echo $max_retries; ?></li>
            <li><strong><?php _e('Processing Frequency:', 'kato-sync'); ?></strong> <?php _e('Every 5 minutes', 'kato-sync'); ?></li>
          </ul>
        </div>

      <?php else: ?>
        <!-- External Mode Info -->
        <div class="card">
          <h2><?php _e('External Image Mode', 'kato-sync'); ?></h2>
          <p><?php _e('Images are being served directly from external URLs. No local processing is required.', 'kato-sync'); ?></p>
          <p><strong><?php _e('Benefits:', 'kato-sync'); ?></strong></p>
          <ul>
            <li><?php _e('No storage space required on your server', 'kato-sync'); ?></li>
            <li><?php _e('No image processing overhead', 'kato-sync'); ?></li>
            <li><?php _e('Instant image availability', 'kato-sync'); ?></li>
          </ul>
          <p><strong><?php _e('Considerations:', 'kato-sync'); ?></strong></p>
          <ul>
            <li><?php _e('Relies on external server availability', 'kato-sync'); ?></li>
            <li><?php _e('No WordPress image optimization', 'kato-sync'); ?></li>
            <li><?php _e('External URLs may change or become unavailable', 'kato-sync'); ?></li>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <style>
      .kato-sync-stats {
        display: flex;
        gap: 2rem;
        margin: 1rem 0;
      }

      .stat-item {
        text-align: center;
      }

      .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: bold;
        color: #0073aa;
      }

      .stat-label {
        display: block;
        font-size: 0.9rem;
        color: #666;
      }

      .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 1rem;
        margin: 1rem 0;
      }

      .card h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.5rem;
      }
    </style>
<?php
  }

  /**
   * Handle form submissions
   */
  private function handle_form_submission(): void {
    $action = $_POST['action'] ?? '';

    switch ($action) {
      case 'retry_failed':
        $result = ImageProcessor::retry_failed_images();
        add_action('admin_notices', function () use ($result) {
          echo '<div class="notice notice-success"><p>' .
            sprintf(__('Retried %d failed images.', 'kato-sync'), $result['retried']) .
            '</p></div>';
        });
        break;

      case 'clear_queue':
        ImageProcessor::clear_image_queue();
        add_action('admin_notices', function () {
          echo '<div class="notice notice-success"><p>' .
            __('Image queue cleared successfully.', 'kato-sync') .
            '</p></div>';
        });
        break;

      case 'process_batch':
        $result = ImageProcessor::process_image_queue();
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

      case 'test_image_urls':
        $result = ImageProcessor::test_image_urls();
        add_action('admin_notices', function () use ($result) {
          if ($result['success']) {
            echo '<div class="notice notice-success"><p>' .
              sprintf(__('Successfully tested %d image URLs. %d accessible, %d inaccessible.', 'kato-sync'), $result['total'], $result['accessible'], $result['inaccessible']) .
              '</p></div>';
          } else {
            echo '<div class="notice notice-error"><p>' .
              sprintf(__('Failed to test image URLs. Error: %s', 'kato-sync'), $result['error']) .
              '</p></div>';
          }
        });
        break;

      case 'process_images_now':
        $result = ImageProcessor::process_images_continuously(5); // Process for 5 minutes
        add_action('admin_notices', function () use ($result) {
          echo '<div class="notice notice-success"><p>' .
            sprintf(
              __('Processed %d images, %d failed, %d remaining in queue. Time elapsed: %d seconds.', 'kato-sync'),
              $result['processed'],
              $result['failed'],
              $result['remaining'],
              $result['time_elapsed']
            ) .
            '</p></div>';
        });
        break;

      case 'reschedule_cron':
        // Clear existing cron job
        wp_clear_scheduled_hook('kato_sync_process_images');
        // Reschedule the cron job
        wp_schedule_event(time(), 'every_2_minutes', 'kato_sync_process_images');
        add_action('admin_notices', function () {
          echo '<div class="notice notice-success"><p>' .
            __('Cron job rescheduled successfully. Next run: ' . date('M j, g:i A', wp_next_scheduled('kato_sync_process_images')), 'kato-sync') .
            '</p></div>';
        });
        break;

      case 'debug_queue':
        $queue = ImageProcessor::get_image_queue();
        add_action('admin_notices', function () use ($queue) {
          if (!empty($queue)) {
            echo '<div class="notice notice-info"><p><strong>Current Image Queue:</strong></p>';
            echo '<pre style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">';
            echo 'Total items: ' . count($queue) . "\n\n";
            echo 'First 3 items (if any):' . "\n";
            $first_items = array_slice($queue, 0, 3);
            foreach ($first_items as $index => $item) {
              echo "Item " . ($index + 1) . ":\n";
              echo "  Post ID: " . ($item['post_id'] ?? 'N/A') . "\n";
              echo "  Image Name: " . ($item['image_name'] ?? 'N/A') . "\n";
              echo "  Image URL: " . ($item['image_url'] ?? 'N/A') . "\n";
              echo "  Status: " . ($item['status'] ?? 'N/A') . "\n";
              echo "  Attempts: " . ($item['attempts'] ?? 'N/A') . "\n\n";
            }
            echo '</pre>';
          } else {
            echo '<div class="notice notice-info"><p>No images in the queue.</p></div>';
          }
        });
        break;

      case 'reschedule_cron':
        wp_clear_scheduled_hook('kato_sync_process_images');
        wp_schedule_event(time(), 'every_5_minutes', 'kato_sync_process_images');
        add_action('admin_notices', function () {
          echo '<div class="notice notice-success"><p>' .
            __('Cron job rescheduled successfully.', 'kato-sync') .
            '</p></div>';
        });
        break;
    }
  }
}
