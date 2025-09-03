<?php

namespace KatoSync\Admin\Pages;



/**
 * Properties page
 */
class PropertiesPage {

  /**
   * Render the properties page
   */
  public function render(): void {
    $properties_data = $this->get_properties();
    $properties = $properties_data['posts'];
    $total_properties = $properties_data['total'];
    $total_pages = $properties_data['total_pages'];
    $current_page = $properties_data['current_page'];

    // Get filter options
    $filter_options = $this->get_filter_options();
?>
    <div class="wrap">
      <h1><?php _e('Properties', 'kato-sync'); ?></h1>

      <!-- Filter Bar - Always show filters -->
      <div class="kato-sync-filter-bar">
        <form method="get" class="kato-sync-filters-form">
          <input type="hidden" name="page" value="kato-sync">

          <div class="filter-row">
            <!-- Search -->
            <div class="filter-group">
              <label for="search-properties"><?php _e('Search:', 'kato-sync'); ?></label>
              <input type="text"
                id="search-properties"
                name="s"
                value="<?php echo esc_attr(isset($_GET['s']) ? $_GET['s'] : ''); ?>"
                placeholder="<?php _e('Search by title, postcode, name, or external ID...', 'kato-sync'); ?>">
            </div>

            <!-- Type Filter -->
            <div class="filter-group">
              <label for="filter-type"><?php _e('Type:', 'kato-sync'); ?></label>
              <select name="type" id="filter-type">
                <option value=""><?php _e('All Types', 'kato-sync'); ?></option>
                <?php foreach ($filter_options['types'] as $slug => $name): ?>
                  <option value="<?php echo esc_attr($slug); ?>"
                    <?php selected(isset($_GET['type']) ? $_GET['type'] : '', $slug); ?>>
                    <?php echo esc_html($name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Location Filter -->
            <div class="filter-group">
              <label for="filter-location"><?php _e('Location:', 'kato-sync'); ?></label>
              <select name="location" id="filter-location">
                <option value=""><?php _e('All Locations', 'kato-sync'); ?></option>
                <?php foreach ($filter_options['locations'] as $slug => $name): ?>
                  <option value="<?php echo esc_attr($slug); ?>"
                    <?php selected(isset($_GET['location']) ? $_GET['location'] : '', $slug); ?>>
                    <?php echo esc_html($name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Availability Filter -->
            <div class="filter-group">
              <label for="filter-availability"><?php _e('Availability:', 'kato-sync'); ?></label>
              <select name="availability" id="filter-availability">
                <option value=""><?php _e('All Availabilities', 'kato-sync'); ?></option>
                <?php foreach ($filter_options['availabilities'] as $slug => $name): ?>
                  <option value="<?php echo esc_attr($slug); ?>"
                    <?php selected(isset($_GET['availability']) ? $_GET['availability'] : '', $slug); ?>>
                    <?php echo esc_html($name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="filter-row">
            <!-- Size Range -->
            <div class="filter-group">
              <label for="size-min"><?php _e('Size Min (sqft):', 'kato-sync'); ?></label>
              <input type="number"
                id="size-min"
                name="size_min"
                value="<?php echo esc_attr(isset($_GET['size_min']) ? $_GET['size_min'] : ''); ?>"
                placeholder="0">
            </div>

            <div class="filter-group">
              <label for="size-max"><?php _e('Size Max (sqft):', 'kato-sync'); ?></label>
              <input type="number"
                id="size-max"
                name="size_max"
                value="<?php echo esc_attr(isset($_GET['size_max']) ? $_GET['size_max'] : ''); ?>"
                placeholder="10000">
            </div>

            <!-- Price Range -->
            <div class="filter-group">
              <label for="price-min"><?php _e('Price Min (£/sqft):', 'kato-sync'); ?></label>
              <input type="number"
                id="price-min"
                name="price_min"
                value="<?php echo esc_attr(isset($_GET['price_min']) ? $_GET['price_min'] : ''); ?>"
                placeholder="0"
                step="0.01">
            </div>

            <div class="filter-group">
              <label for="price-max"><?php _e('Price Max (£/sqft):', 'kato-sync'); ?></label>
              <input type="number"
                id="price-max"
                name="price_max"
                value="<?php echo esc_attr(isset($_GET['price_max']) ? $_GET['price_max'] : ''); ?>"
                placeholder="100"
                step="0.01">
            </div>

            <!-- Featured Filter -->
            <div class="filter-group">
              <label for="filter-featured"><?php _e('Featured Only:', 'kato-sync'); ?></label>
              <input type="checkbox"
                id="filter-featured"
                name="featured"
                value="1"
                <?php checked(isset($_GET['featured']) && $_GET['featured'] === '1'); ?>>
            </div>
          </div>

          <div class="filter-actions">
            <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'kato-sync'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kato-sync')); ?>" class="button"><?php _e('Clear All', 'kato-sync'); ?></a>
          </div>
        </form>
      </div>
























      <div class="kato-sync-properties-table">
        <?php if (!empty($properties)): ?>
          <div class="tablenav top">
            <div class="tablenav-pages">
              <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_properties, 'kato-sync'), number_format_i18n($total_properties)); ?></span>
              <?php if ($total_pages > 1): ?>
                <?php echo $this->pagination($current_page, $total_pages); ?>
              <?php endif; ?>
            </div>
          </div>

          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th class="sortable <?php echo $this->get_sort_class('title'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('title')); ?>">
                    <span><?php _e('Property Title', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th class="sortable <?php echo $this->get_sort_class('type'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('type')); ?>">
                    <span><?php _e('Type', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th class="sortable <?php echo $this->get_sort_class('location'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('location')); ?>">
                    <span><?php _e('Location', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th class="sortable <?php echo $this->get_sort_class('availability'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('availability')); ?>">
                    <span><?php _e('Availability', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th class="sortable <?php echo $this->get_sort_class('size'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('size')); ?>">
                    <span><?php _e('Size Range (sqft)', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th class="sortable <?php echo $this->get_sort_class('price'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('price')); ?>">
                    <span><?php _e('Price Range', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th class="sortable <?php echo $this->get_sort_class('date'); ?>">
                  <a href="<?php echo esc_url($this->get_sort_url('date')); ?>">
                    <span><?php _e('Last Updated', 'kato-sync'); ?></span>
                    <span class="sorting-indicator"></span>
                  </a>
                </th>
                <th><?php _e('Actions', 'kato-sync'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($properties as $property): ?>
                <?php
                // Use new OOP approach for property data
                try {
                  $kato_property = new \KatoSync\PostTypes\Kato_Property($property->ID);

                  // Get types and availabilities using the OOP method
                  $types = $kato_property->types;
                  $property_type_display = is_array($types) ? implode(', ', $types) : ($types ?: '');

                  $availabilities = $kato_property->availabilities;
                  $availability_display = is_array($availabilities) ? implode(', ', $availabilities) : ($availabilities ?: '');
                } catch (\Exception $e) {
                  // Fallback if OOP approach fails
                  $property_type_display = '';
                  $availability_display = '';
                }



                // Get location from county field
                $location = get_post_meta($property->ID, '_kato_sync_county', true);

                // Get size information from new meta fields
                $size_sqft = get_post_meta($property->ID, '_kato_sync_size_sqft', true);
                $size_from = get_post_meta($property->ID, '_kato_sync_size_from', true);
                $size_to = get_post_meta($property->ID, '_kato_sync_size_to', true);
                $size_from_sqft = get_post_meta($property->ID, '_kato_sync_size_from_sqft', true);
                $size_to_sqft = get_post_meta($property->ID, '_kato_sync_size_to_sqft', true);

                // Get price information from new meta fields
                $price = get_post_meta($property->ID, '_kato_sync_price', true);
                $rent = get_post_meta($property->ID, '_kato_sync_rent', true);
                $price_per_sqft = get_post_meta($property->ID, '_kato_sync_price_per_sqft', true);
                $price_per_sqft_min = get_post_meta($property->ID, '_kato_sync_price_per_sqft_min', true);
                $price_per_sqft_max = get_post_meta($property->ID, '_kato_sync_price_per_sqft_max', true);
                ?>
                <tr>
                  <td>
                    <strong><?php echo esc_html($property->post_title); ?></strong>
                  </td>
                  <td>
                    <?php
                    if (!empty($property_type_display)) {
                      echo esc_html($property_type_display);
                    } else {
                      echo '<span class="text-muted">—</span>';
                    }
                    ?>
                  </td>
                  <td>
                    <?php echo !empty($location) ? esc_html($location) : '<span class="text-muted">—</span>'; ?>
                  </td>
                  <td>
                    <?php echo !empty($availability_display) ? esc_html($availability_display) : '<span class="text-muted">—</span>'; ?>
                  </td>
                  <td>
                    <?php
                    // Use inline computation for size range
                    try {
                      $kato_property = new \KatoSync\PostTypes\Kato_Property($property->ID);
                      $size_min = $kato_property->size_min ?? null;
                      $size_max = $kato_property->size_max ?? null;

                      if ($size_min && $size_max) {
                        if ($size_min === $size_max) {
                          echo '<span class="size-value">' . esc_html(number_format((float)$size_min) . ' sqft') . '</span>';
                        } else {
                          echo '<span class="size-value">' . esc_html(number_format((float)$size_min) . ' - ' . number_format((float)$size_max) . ' sqft') . '</span>';
                        }
                      } elseif ($size_min) {
                        echo '<span class="size-value">' . esc_html(number_format((float)$size_min) . ' sqft') . '</span>';
                      } else {
                        echo '<span class="text-muted">—</span>';
                      }
                    } catch (\Exception $e) {
                      echo '<span class="text-muted">—</span>';
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    // Use new range formatting logic
                    try {
                      $kato_property = new \KatoSync\PostTypes\Kato_Property($property->ID);
                      $price_range = $kato_property->get_price_range_formatted();

                      if (!empty($price_range['display'])) {
                        echo '<span class="price-range">' . esc_html($price_range['display']) . '</span>';
                      } else {
                        echo '<span class="text-muted">—</span>';
                      }
                    } catch (\Exception $e) {
                      echo '<span class="text-muted">—</span>';
                    }
                    ?>
                  </td>
                  <td>
                    <?php
                    $imported_at = get_post_meta($property->ID, '_kato_sync_imported_at', true);
                    if ($imported_at) {
                      echo esc_html(human_time_diff(strtotime($imported_at), current_time('timestamp'))) . ' ago';
                    } else {
                      echo esc_html__('Unknown', 'kato-sync');
                    }
                    ?>
                  </td>
                  <td style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="<?php echo esc_url(get_permalink($property->ID)); ?>"
                      class="button button-small"
                      target="_blank"
                      style="width: 100%; text-align: center;">
                      <?php _e('View Property', 'kato-sync'); ?>
                    </a>
                    <button type="button"
                      class="button button-small kato-sync-view-data"
                      data-property-id="<?php echo esc_attr($property->ID); ?>"
                      style="width: 100%;">
                      <?php _e('View Data', 'kato-sync'); ?>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
              <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_properties, 'kato-sync'), number_format_i18n($total_properties)); ?></span>
                <?php echo $this->pagination($current_page, $total_pages); ?>
              </div>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="kato-sync-message kato-sync-message-info">
            <p><?php _e('No properties found matching your current filters. Try adjusting your search criteria or clear the filters to see all properties.', 'kato-sync'); ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Property Data Modal -->
    <div id="kato-sync-property-modal" class="kato-sync-modal" style="display: none;">
      <div class="kato-sync-modal-content">
        <div class="kato-sync-modal-header">
          <h2 id="kato-sync-modal-title"><?php _e('Property Data', 'kato-sync'); ?></h2>
          <span class="kato-sync-modal-close">&times;</span>
        </div>
        <div class="kato-sync-modal-body">
          <div id="kato-sync-modal-loading" style="display: none;">
            <p><?php _e('Loading property data...', 'kato-sync'); ?></p>
          </div>
          <div id="kato-sync-modal-content"></div>
        </div>
      </div>
    </div>
<?php
  }

  /**
   * Get filter options
   */
  private function get_filter_options(): array {
    $options = array();

    // Get all unique property types from stored data
    global $wpdb;

    $types = array();

    // Get types from _kato_sync_types field
    $types_meta = $wpdb->get_col($wpdb->prepare(
      "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
      '_kato_sync_types'
    ));

    foreach ($types_meta as $meta_value) {
      $decoded = json_decode($meta_value, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        foreach ($decoded as $type) {
          if (is_string($type)) {
            $types[] = $type;
          } elseif (is_array($type) && isset($type['name'])) {
            $types[] = $type['name'];
          }
        }
      }
    }

    // Get types from _kato_sync_property_type field (fallback)
    $property_types_meta = $wpdb->get_col($wpdb->prepare(
      "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
      '_kato_sync_property_type'
    ));

    foreach ($property_types_meta as $meta_value) {
      $decoded = json_decode($meta_value, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        foreach ($decoded as $type) {
          if (is_string($type)) {
            $types[] = $type;
          } elseif (is_array($type) && isset($type['name'])) {
            $types[] = $type['name'];
          }
        }
      } elseif (is_string($meta_value) && !empty($meta_value)) {
        $types[] = $meta_value;
      }
    }

    // Get unique types and create options
    $unique_types = array_unique(array_filter($types));
    sort($unique_types);

    $options['types'] = array();
    foreach ($unique_types as $type) {
      $options['types'][sanitize_title($type)] = $type;
    }

    // Get locations
    $locations = get_terms(array(
      'taxonomy' => 'kato_location',
      'hide_empty' => false,
    ));

    $options['locations'] = array();
    if (!is_wp_error($locations) && is_array($locations)) {
      foreach ($locations as $location) {
        if (is_object($location) && isset($location->slug, $location->name)) {
          $options['locations'][$location->slug] = $location->name;
        }
      }
    }

    // Get availabilities
    $availabilities = get_terms(array(
      'taxonomy' => 'kato_availability',
      'hide_empty' => false,
    ));

    $options['availabilities'] = array();
    if (!is_wp_error($availabilities) && is_array($availabilities)) {
      foreach ($availabilities as $availability) {
        if (is_object($availability) && isset($availability->slug, $availability->name)) {
          $options['availabilities'][$availability->slug] = $availability->name;
        }
      }
    }

    // If no availability terms exist, check if we can get them from property meta
    if (empty($options['availabilities'])) {
      global $wpdb;

      // Get availabilities from meta data (similar to how we handle types)
      $availability_meta = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
         WHERE meta_key = %s AND meta_value != '' AND meta_value != 'No data'",
        '_kato_sync_availabilities'
      ));

      $availabilities_from_meta = array();
      foreach ($availability_meta as $meta_value) {
        $decoded = json_decode($meta_value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
          foreach ($decoded as $availability) {
            if (is_array($availability) && isset($availability['name'])) {
              $availabilities_from_meta[] = $availability['name'];
            } elseif (is_string($availability) && !empty($availability)) {
              $availabilities_from_meta[] = $availability;
            }
          }
        } elseif (is_string($meta_value) && !empty($meta_value)) {
          $availabilities_from_meta[] = $meta_value;
        }
      }

      // Get unique availabilities and create options
      $unique_availabilities = array_unique(array_filter($availabilities_from_meta));
      foreach ($unique_availabilities as $availability) {
        $options['availabilities'][sanitize_title($availability)] = $availability;
      }
    }

    return $options;
  }

  /**
   * Get properties with pagination
   */
  private function get_properties(): array {
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

    $args = array(
      'post_type' => 'kato-property',
      'post_status' => 'publish',
      'posts_per_page' => 50,
      'paged' => $paged,
      'orderby' => $this->get_orderby_value($orderby),
      'order' => $order,
      'meta_query' => array(),
    );

    // Set meta_key for location sorting
    if ($orderby === 'location') {
      $args['meta_key'] = '_kato_sync_county';
    }

    // Enhanced search for title, postcode, name, or external id
    if (!empty($search)) {
      // Remove default search
      unset($args['s']);

      // Add custom meta query for multiple field search
      $search_meta_query = array(
        'relation' => 'OR',
        array(
          'key' => '_kato_sync_external_id',
          'value' => $search,
          'compare' => 'LIKE',
        ),
        array(
          'key' => '_kato_sync_id',
          'value' => $search,
          'compare' => 'LIKE',
        ),
        array(
          'key' => '_kato_sync_name',
          'value' => $search,
          'compare' => 'LIKE',
        ),
        array(
          'key' => '_kato_sync_postcode',
          'value' => $search,
          'compare' => 'LIKE',
        ),
      );

      // Ensure the search meta query is properly structured
      $args['meta_query'] = $search_meta_query;

      // Also search in post title using a custom WP_Query approach
      add_filter('posts_where', array($this, 'search_title_filter'), 10, 2);
      $args['search_term'] = $search; // Store for the filter

      // For external ID searches, include all post statuses to ensure we find the property
      if (isset($_GET['s']) && !empty($_GET['s'])) {
        $args['post_status'] = 'any';
      }

      // Add filter to exclude results that are clearly just image filenames
      add_filter('posts_where', array($this, 'exclude_image_filenames_filter'), 10, 2);
    }

    // Type filter
    if (!empty($_GET['type'])) {
      $type_filter = sanitize_text_field($_GET['type']);

      // Get the filter options to find the original type name
      $filter_options = $this->get_filter_options();
      $original_type_name = null;

      // Find the original type name from the sanitized slug
      foreach ($filter_options['types'] as $slug => $name) {
        if ($slug === $type_filter) {
          $original_type_name = $name;
          break;
        }
      }

      if ($original_type_name) {
        $args['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => '_kato_sync_types',
            'value' => $original_type_name,
            'compare' => 'LIKE',
          ),
          array(
            'key' => '_kato_sync_property_type',
            'value' => $original_type_name,
            'compare' => 'LIKE',
          ),
        );
      }
    }

    // Location filter
    if (!empty($_GET['location'])) {
      $args['tax_query'][] = array(
        'taxonomy' => 'kato_location',
        'field' => 'slug',
        'terms' => sanitize_text_field($_GET['location']),
      );
    }

    // Availability filter - search in meta data instead of taxonomy
    if (!empty($_GET['availability'])) {
      $availability_filter = sanitize_text_field($_GET['availability']);

      // Get the filter options to find the original availability name
      $filter_options = $this->get_filter_options();
      $original_availability_name = null;

      // Find the original availability name from the sanitized slug
      foreach ($filter_options['availabilities'] as $slug => $name) {
        if ($slug === $availability_filter) {
          $original_availability_name = $name;
          break;
        }
      }

      if ($original_availability_name) {
        $args['meta_query'][] = array(
          'key' => '_kato_sync_availabilities',
          'value' => $original_availability_name,
          'compare' => 'LIKE',
        );
      }
    }

    // Size filters
    if (!empty($_GET['size_min']) || !empty($_GET['size_max'])) {
      $size_query = array('relation' => 'AND');

      if (!empty($_GET['size_min'])) {
        $size_min = (int) $_GET['size_min'];
        $size_query[] = array(
          'key' => '_kato_sync_size_max',
          'value' => $size_min,
          'type' => 'NUMERIC',
          'compare' => '>=',
        );
      }

      if (!empty($_GET['size_max'])) {
        $size_max = (int) $_GET['size_max'];
        $size_query[] = array(
          'key' => '_kato_sync_size_min',
          'value' => $size_max,
          'type' => 'NUMERIC',
          'compare' => '<=',
        );
      }

      if (count($size_query) > 1) {
        $args['meta_query'][] = $size_query;
      }
    }

    // Price filters
    if (!empty($_GET['price_min']) || !empty($_GET['price_max'])) {
      $price_query = array('relation' => 'OR');

      if (!empty($_GET['price_min']) && !empty($_GET['price_max'])) {
        $price_min = (float) $_GET['price_min'];
        $price_max = (float) $_GET['price_max'];

        // Handle properties with price ranges
        $price_query[] = array(
          'relation' => 'AND',
          array(
            'key' => '_kato_sync_price_max',
            'value' => $price_min,
            'type' => 'DECIMAL',
            'compare' => '>=',
          ),
          array(
            'key' => '_kato_sync_price_min',
            'value' => $price_max,
            'type' => 'DECIMAL',
            'compare' => '<=',
          ),
        );

        // Handle properties with single price values
        $price_query[] = array(
          'relation' => 'AND',
          array(
            'key' => '_kato_sync_price_per_sqft',
            'value' => $price_min,
            'type' => 'DECIMAL',
            'compare' => '>=',
          ),
          array(
            'key' => '_kato_sync_price_per_sqft',
            'value' => $price_max,
            'type' => 'DECIMAL',
            'compare' => '<=',
          ),
        );
      } elseif (!empty($_GET['price_min'])) {
        $price_min = (float) $_GET['price_min'];

        // Properties with max price >= min filter OR single price >= min filter
        $price_query[] = array(
          'key' => '_kato_sync_price_max',
          'value' => $price_min,
          'type' => 'DECIMAL',
          'compare' => '>=',
        );
        $price_query[] = array(
          'key' => '_kato_sync_price_per_sqft',
          'value' => $price_min,
          'type' => 'DECIMAL',
          'compare' => '>=',
        );
      } elseif (!empty($_GET['price_max'])) {
        $price_max = (float) $_GET['price_max'];

        // Properties with min price <= max filter OR single price <= max filter
        $price_query[] = array(
          'key' => '_kato_sync_price_min',
          'value' => $price_max,
          'type' => 'DECIMAL',
          'compare' => '<=',
        );
        $price_query[] = array(
          'key' => '_kato_sync_price_per_sqft',
          'value' => $price_max,
          'type' => 'DECIMAL',
          'compare' => '<=',
        );
      }

      if (count($price_query) > 1) {
        $args['meta_query'][] = $price_query;
      }
    }

    // Featured filter
    if (isset($_GET['featured']) && $_GET['featured'] === '1') {
      $args['meta_query'][] = array(
        'key' => '_kato_sync_featured',
        'value' => array('1', 't'),
        'compare' => 'IN',
      );
    }

    // Handle multiple meta queries
    if (!empty($args['meta_query']) && count($args['meta_query']) > 1) {
      // For search queries, we want OR relation to find properties matching ANY condition
      // For other filters (type, location, etc.), we want AND relation
      if (!empty($search)) {
        $args['meta_query']['relation'] = 'OR';
      } else {
        $args['meta_query']['relation'] = 'AND';
      }
    }

    // Handle multiple tax queries
    if (!empty($args['tax_query']) && count($args['tax_query']) > 1) {
      $args['tax_query']['relation'] = 'AND';
    }



    $query = new \WP_Query($args);



    // Remove the search filters after the query
    if (!empty($search)) {
      remove_filter('posts_where', array($this, 'search_title_filter'), 10);
      remove_filter('posts_where', array($this, 'exclude_image_filenames_filter'), 10);
    }

    return array(
      'posts' => $query->posts,
      'total' => $query->found_posts,
      'total_pages' => $query->max_num_pages,
      'current_page' => $paged,
    );
  }

  /**
   * Get orderby value for WP_Query
   */
  private function get_orderby_value(string $orderby): string {
    switch ($orderby) {
      case 'title':
        return 'title';
      case 'type':
        return 'meta_value';
      case 'location':
        return 'meta_value';
      case 'availability':
        return 'meta_value';
      case 'size':
        return 'meta_value_num';
      case 'price':
        return 'meta_value_num';
      case 'date':
      default:
        return 'date';
    }
  }

  /**
   * Get sort class for table headers
   */
  private function get_sort_class(string $column): string {
    $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'date';
    $current_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

    if ($current_orderby === $column) {
      return 'sorted ' . strtolower($current_order);
    }
    return 'sortable';
  }

  /**
   * Get sort URL for table headers
   */
  private function get_sort_url(string $column): string {
    $current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'date';
    $current_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

    // If clicking the same column, toggle order
    if ($current_orderby === $column) {
      $new_order = $current_order === 'ASC' ? 'DESC' : 'ASC';
    } else {
      $new_order = 'ASC';
    }

    $params = $_GET;
    $params['orderby'] = $column;
    $params['order'] = $new_order;
    $params['page'] = 'kato-sync';

    return add_query_arg($params, admin_url('admin.php'));
  }

  /**
   * Generate pagination
   */
  private function pagination($current_page, $total_pages): string {
    $output = '<span class="pagination-links">';

    // Previous page
    if ($current_page > 1) {
      $output .= '<a class="prev-page" href="' . esc_url(add_query_arg('paged', $current_page - 1)) . '">‹</a>';
    }

    // Page numbers with ellipsis for large numbers
    $max_visible_pages = 7;
    $start_page = max(1, $current_page - floor($max_visible_pages / 2));
    $end_page = min($total_pages, $start_page + $max_visible_pages - 1);

    // Adjust start if we're near the end
    if ($end_page - $start_page < $max_visible_pages - 1) {
      $start_page = max(1, $end_page - $max_visible_pages + 1);
    }

    // First page and ellipsis
    if ($start_page > 1) {
      $output .= '<a class="paging-input" href="' . esc_url(add_query_arg('paged', 1)) . '">1</a>';
      if ($start_page > 2) {
        $output .= '<span class="paging-input">…</span>';
      }
    }

    // Page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
      if ($i == $current_page) {
        $output .= '<span class="paging-input">' . $i . '</span>';
      } else {
        $output .= '<a class="paging-input" href="' . esc_url(add_query_arg('paged', $i)) . '">' . $i . '</a>';
      }
    }

    // Last page and ellipsis
    if ($end_page < $total_pages) {
      if ($end_page < $total_pages - 1) {
        $output .= '<span class="paging-input">…</span>';
      }
      $output .= '<a class="paging-input" href="' . esc_url(add_query_arg('paged', $total_pages)) . '">' . $total_pages . '</a>';
    }

    // Next page
    if ($current_page < $total_pages) {
      $output .= '<a class="next-page" href="' . esc_url(add_query_arg('paged', $current_page + 1)) . '">›</a>';
    }

    $output .= '</span>';

    return $output;
  }

  /**
   * Filter to add title search to WHERE clause
   */
  public function search_title_filter($where, $wp_query) {
    global $wpdb;

    $search_term = $wp_query->get('search_term');
    if (!empty($search_term)) {
      $search_term = $wpdb->esc_like($search_term);
      $where .= " OR {$wpdb->posts}.post_title LIKE '%{$search_term}%'";
    }

    return $where;
  }

  /**
   * Filter to exclude results that are clearly just image filenames
   */
  public function exclude_image_filenames_filter($where, $wp_query) {
    global $wpdb;

    // Exclude posts where the title looks like an image filename
    // This will exclude titles that end with common image extensions
    $where .= " AND NOT ({$wpdb->posts}.post_title REGEXP '\\.(jpg|jpeg|png|gif|bmp|webp|svg|ico)$' AND {$wpdb->posts}.post_title NOT LIKE '% %')";

    // Also exclude titles that are just filenames without spaces (likely image files)
    $where .= " AND NOT ({$wpdb->posts}.post_title REGEXP '^[^\\s]+\\.(jpg|jpeg|png|gif|bmp|webp|svg|ico)$')";

    return $where;
  }
}
