<?php

namespace KatoSync\Admin\Pages;

use KatoSync\Utils\RangeFormatter;
use KatoSync\PostTypes\Kato_Property;

/**
 * Range Test Page
 *
 * Demonstrates the new range formatting logic and identifies other fields
 * that could benefit from similar range handling
 */
class RangeTestPage {

  /**
   * Add admin menu
   */
  public static function init(): void {
    add_action('admin_menu', [self::class, 'add_menu_page']);
  }

  /**
   * Add menu page
   */
  public static function add_menu_page(): void {
    add_submenu_page(
      'kato-sync',
      __('Range Testing', 'kato-sync'),
      __('Range Testing', 'kato-sync'),
      'manage_options',
      'kato-sync-ranges',
      [self::class, 'display_page']
    );
  }

  /**
   * Display the range test page
   */
  public static function display_page(): void {
?>
    <div class="wrap">
      <h1><?php echo esc_html(__('Range Formatting Test & Analysis', 'kato-sync')); ?></h1>

      <?php self::display_demo_section(); ?>
      <?php self::display_current_data_analysis(); ?>
      <?php self::display_recommendations(); ?>
    </div>
  <?php
  }

  /**
   * Display demonstration of range formatting
   */
  private static function display_demo_section(): void {
  ?>
    <div class="card">
      <h2>Range Formatting Demonstration</h2>

      <h3>Price Range Examples</h3>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th>Min</th>
            <th>Max</th>
            <th>Type</th>
            <th>Old Display</th>
            <th>New Display</th>
            <th>Is Single?</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $price_examples = [
            [39.00, 39.00, 'per_sqft'],
            [39.50, 39.50, 'per_sqft'],
            [25.00, 45.00, 'per_sqft'],
            [null, null, 'poa'],
            [150000, 150000, 'total'],
            [100000, 200000, 'total'],
          ];

          foreach ($price_examples as [$min, $max, $type]) {
            $formatted = RangeFormatter::format_price_range($min, $max, $type);
            $old_display = self::old_price_format($min, $max, $type);

            echo '<tr>';
            echo '<td>' . ($min ? '£' . number_format($min, 2) : 'null') . '</td>';
            echo '<td>' . ($max ? '£' . number_format($max, 2) : 'null') . '</td>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '<td>' . esc_html($old_display) . '</td>';
            echo '<td><strong>' . esc_html($formatted['display']) . '</strong></td>';
            echo '<td>' . ($formatted['is_single'] ? 'Yes' : 'No') . '</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>

      <h3>Size Range Examples</h3>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th>Min</th>
            <th>Max</th>
            <th>Old Display</th>
            <th>New Display</th>
            <th>Is Single?</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $size_examples = [
            [349, 349],
            [349, 577],
            [1483, 1483], // Total property size
            [null, null],
            [500, 500],
          ];

          foreach ($size_examples as [$min, $max]) {
            $formatted = RangeFormatter::format_size_range($min, $max);
            $old_display = self::old_size_format($min, $max);

            echo '<tr>';
            echo '<td>' . ($min ? number_format($min) : 'null') . '</td>';
            echo '<td>' . ($max ? number_format($max) : 'null') . '</td>';
            echo '<td>' . esc_html($old_display) . '</td>';
            echo '<td><strong>' . esc_html($formatted['display']) . '</strong></td>';
            echo '<td>' . ($formatted['is_single'] ? 'Yes' : 'No') . '</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  <?php
  }

  /**
   * Display current property data analysis
   */
  private static function display_current_data_analysis(): void {
    // Get some sample properties
    $properties = get_posts([
      'post_type' => 'kato-property',
      'post_status' => 'publish',
      'numberposts' => 5,
    ]);

  ?>
    <div class="card">
      <h2>Current Property Data Analysis</h2>

      <?php if (empty($properties)): ?>
        <p>No properties found. Import some properties first.</p>
      <?php else: ?>
        <?php foreach ($properties as $post): ?>
          <?php
          try {
            $property = new Kato_Property($post->ID);

            // Get raw data for analysis
            $raw_data = get_post_meta($post->ID, '_kato_sync_raw_data', true);
            if ($raw_data) {
              $raw_data = json_decode($raw_data, true);
            }
          ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0;">
              <h3><?php echo esc_html($property->title); ?></h3>

              <h4>Range Comparisons</h4>
              <table class="wp-list-table widefat">
                <tr>
                  <th>Field</th>
                  <th>Current Display</th>
                  <th>New Formatted Display</th>
                  <th>Improvement</th>
                </tr>

                <!-- Price Range -->
                <tr>
                  <td><strong>Price Range</strong></td>
                  <td><?php echo esc_html($property->get_price_range()); ?></td>
                  <td>
                    <?php
                    $price_formatted = $property->get_price_range_formatted();
                    echo '<strong>' . esc_html($price_formatted['display']) . '</strong>';
                    ?>
                  </td>
                  <td><?php echo $price_formatted['is_single'] ? '✓ Shows single value' : 'Shows range'; ?></td>
                </tr>

                <!-- Size Range -->
                <tr>
                  <td><strong>Unit Size Range</strong></td>
                  <td><?php echo esc_html($property->get_size_range()); ?></td>
                  <td>
                    <?php
                    $unit_size_formatted = $property->get_unit_size_range_formatted();
                    echo '<strong>' . esc_html($unit_size_formatted['display']) . '</strong>';
                    ?>
                  </td>
                  <td><?php echo $unit_size_formatted['is_single'] ? '✓ Shows single value' : 'Shows range'; ?></td>
                </tr>

                <!-- Total Property Size -->
                <tr>
                  <td><strong>Total Property Size</strong></td>
                  <td><?php echo esc_html($property->total_property_size_sqft ?? 'Not set'); ?></td>
                  <td>
                    <?php
                    $total_size_formatted = $property->get_total_property_size_formatted();
                    echo '<strong>' . esc_html($total_size_formatted['display']) . '</strong>';
                    ?>
                  </td>
                  <td>✓ Separated from unit ranges</td>
                </tr>

                <!-- Rent Range -->
                <tr>
                  <td><strong>Rent Range</strong></td>
                  <td><?php echo esc_html($property->rent ?? 'Not set'); ?></td>
                  <td>
                    <?php
                    $rent_formatted = $property->get_rent_range_formatted();
                    echo '<strong>' . esc_html($rent_formatted['display']) . '</strong>';
                    ?>
                  </td>
                  <td><?php echo $rent_formatted['is_single'] ? '✓ Shows single value' : 'Shows range'; ?></td>
                </tr>
              </table>

              <?php if ($raw_data): ?>
                <h4>Other Range Candidates</h4>
                <?php
                $candidates = RangeFormatter::identify_range_candidates($raw_data);
                if (!empty($candidates)): ?>
                  <ul>
                    <?php foreach ($candidates as $candidate): ?>
                      <li>
                        <strong><?php echo esc_html($candidate['field']); ?>:</strong>
                        <?php echo esc_html($candidate['recommendation']); ?>
                        <br><small>Current data: <?php echo esc_html(print_r($candidate['current'], true)); ?></small>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p>No additional range candidates found for this property.</p>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php } catch (Exception $e) { ?>
            <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0;">
              <p>Error loading property <?php echo esc_html($post->ID); ?>: <?php echo esc_html($e->getMessage()); ?></p>
            </div>
          <?php } ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  <?php
  }

  /**
   * Display recommendations for implementing range logic
   */
  private static function display_recommendations(): void {
  ?>
    <div class="card">
      <h2>Implementation Recommendations</h2>

      <h3>Fields That Could Benefit from Range Logic</h3>
      <ol>
        <li><strong>Service Charge</strong> - Often has min/max values that could be displayed as single value when identical</li>
        <li><strong>Business Rates</strong> - Similar to service charge, may have range values</li>
        <li><strong>Parking Costs</strong> - May vary by space type or location</li>
        <li><strong>Floor Counts</strong> - Buildings may span multiple floors</li>
        <li><strong>Availability Dates</strong> - Date ranges for when units become available</li>
        <li><strong>Lease Terms</strong> - Min/max lease lengths</li>
        <li><strong>Ceiling Heights</strong> - May vary across units</li>
        <li><strong>Energy Ratings</strong> - May have range of ratings across units</li>
      </ol>

      <h3>Benefits of Range Logic</h3>
      <ul>
        <li>✓ <strong>Cleaner Display:</strong> Shows "£39.00/sqft" instead of "£39.00 - £39.00/sqft"</li>
        <li>✓ <strong>Preserved Data:</strong> Both min and max values still stored for filtering</li>
        <li>✓ <strong>Correct Size Ranges:</strong> Unit ranges (349-577) separate from total property size (1483)</li>
        <li>✓ <strong>Consistent Formatting:</strong> Standardised approach across all range fields</li>
        <li>✓ <strong>User Experience:</strong> Less visual clutter, easier to read</li>
      </ul>

      <h3>Implementation Steps</h3>
      <ol>
        <li>Update frontend templates to use new range formatting methods</li>
        <li>Test with existing property data</li>
        <li>Apply range logic to other identified fields</li>
        <li>Update filtering system to use preserved min/max values</li>
      </ol>
    </div>
<?php
  }

  /**
   * Simulate old price formatting for comparison
   */
  private static function old_price_format($min, $max, $type): string {
    if ($type === 'poa' || (!$min && !$max)) {
      return 'POA';
    }

    $suffix = $type === 'per_sqft' ? '/sqft' : '';

    if ($min && $max) {
      return '£' . number_format($min, 2) . ' - £' . number_format($max, 2) . $suffix;
    } elseif ($min) {
      return '£' . number_format($min, 2) . $suffix;
    }

    return '';
  }

  /**
   * Simulate old size formatting for comparison
   */
  private static function old_size_format($min, $max): string {
    if (!$min && !$max) {
      return '';
    }

    if ($min && $max) {
      return number_format($min) . ' - ' . number_format($max) . ' sqft';
    } elseif ($min) {
      return number_format($min) . ' sqft';
    }

    return '';
  }
}
