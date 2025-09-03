<?php

/**
 * Theme Functions Example for Kato Sync Integration
 *
 * This file demonstrates how to integrate Kato Sync with your theme
 * and customise the image sizes and other settings.
 *
 * Add these snippets to your theme's functions.php file.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Example 1: Override the default card image size
 *
 * This filter allows you to change the image size used by kato_get_card_image()
 * from the default 'medium' to any registered image size.
 */
add_filter('kato_sync_card_image_size', function ($default_size, $post_id) {
  // Use your custom image size (e.g., 'landscape-md')
  return 'landscape-md';

  // Or conditionally change based on context:
  // if (is_front_page()) {
  //   return 'large';
  // }
  // return $default_size;
}, 10, 2);

/**
 * Example 2: Register custom image sizes for properties
 *
 * Add custom image sizes that work well with property images.
 */
add_action('after_setup_theme', function () {
  // Landscape image for property cards
  add_image_size('landscape-md', 400, 225, true);  // 16:9 ratio
  add_image_size('landscape-lg', 800, 450, true);  // 16:9 ratio

  // Square images for grid layouts
  add_image_size('property-square', 300, 300, true);

  // Wide banner for hero sections
  add_image_size('property-hero', 1200, 400, true);
});

/**
 * Example 3: Add custom CSS classes to property images
 *
 * Modify the default attributes for property card images.
 */
add_filter('kato_sync_card_image_attributes', function ($attributes, $post_id) {
  // Add custom CSS classes
  $attributes['class'] = ($attributes['class'] ?? '') . ' my-custom-property-image';

  // Add data attributes for JavaScript
  $attributes['data-property-id'] = $post_id;

  // Add custom alt text
  $property = kato_property($post_id);
  if ($property) {
    $attributes['alt'] = 'Property image for ' . $property->get_address();
  }

  return $attributes;
}, 10, 2);

/**
 * Example 4: Customise property card display
 *
 * Create a custom function to display property cards with your theme's styling.
 */
function my_theme_property_card($post_id = null) {
  $property = kato_property($post_id);

  if (!$property) {
    return;
  }

  // Use the new simplified function - much cleaner!
  $card_image = get_kato_card_image('landscape-md', 'my-theme-property-image', [
    'loading' => 'lazy'
  ]);
?>

  <div class="my-theme-property-card">

    <?php if ($card_image): ?>
      <div class="property-image-wrapper">
        <a href="<?php echo esc_url($property->url); ?>">
          <?php echo $card_image; ?>
        </a>

        <?php if ($property->is_featured()): ?>
          <span class="featured-badge">Featured</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="property-content">
      <h3 class="property-title">
        <a href="<?php echo esc_url($property->url); ?>">
          <?php
          $title_parts = array_filter([
            $property->name,
            $property->address1,
            $property->get_outward_postcode()
          ]);
          echo esc_html(implode(' ', $title_parts));
          ?>
        </a>
      </h3>

      <?php if ($property->county): ?>
        <p class="property-district"><?php echo esc_html($property->county); ?></p>
      <?php endif; ?>

      <div class="property-meta">
        <span class="property-price"><?php echo esc_html($property->get_price()); ?></span>

        <?php $size = $property->get_size(); ?>
        <?php if ($size): ?>
          <span class="property-size"><?php echo esc_html($size); ?></span>
        <?php endif; ?>
      </div>

      <a href="<?php echo esc_url($property->url); ?>" class="property-link">
        View Details
      </a>
    </div>

  </div>

  <?php
}

/**
 * Example 6: Simple image display function
 *
 * Show how to use the new simplified function in your templates.
 */
function my_simple_property_image($size = 'medium', $class = 'property-image') {
  // This is all you need - no complex fallbacks!
  echo get_kato_card_image($size, $class, [
    'loading' => 'lazy'
  ]);
}

/**
 * Example 7: Conditional image display
 *
 * Show how to handle cases where images might not exist.
 */
function my_conditional_property_image($size = 'medium') {
  $image = get_kato_card_image($size, 'property-image');

  if ($image): ?>
    <div class="property-image-container">
      <?php echo $image; ?>
    </div>
  <?php else: ?>
    <div class="property-no-image">
      <span>No Image Available</span>
    </div>
  <?php endif;
}

/**
 * Example 5: Add property-specific body classes
 *
 * Useful for styling property pages differently.
 */
add_filter('body_class', function ($classes) {
  if (is_singular('kato_property')) {
    $property = kato_property();

    if ($property) {
      $classes[] = 'single-property';

      if ($property->is_featured()) {
        $classes[] = 'featured-property';
      }

      if ($property->is_fitted()) {
        $classes[] = 'fitted-property';
      }

      // Add property type as class
      $type = $property->get_type();
      if ($type) {
        $classes[] = 'property-type-' . sanitize_html_class(strtolower($type));
      }
    }
  }

  if (is_post_type_archive('kato_property')) {
    $classes[] = 'properties-archive';
  }

  return $classes;
});

/**
 * Example 6: Custom property query for homepage
 *
 * Display featured properties on your homepage.
 */
function my_theme_featured_properties($limit = 6) {
  $properties = new WP_Query([
    'post_type' => 'kato_property',
    'posts_per_page' => $limit,
    'meta_query' => [
      [
        'key' => '_kato_sync_is_featured',
        'value' => '1',
        'compare' => '='
      ]
    ]
  ]);

  if ($properties->have_posts()): ?>
    <section class="featured-properties">
      <h2>Featured Properties</h2>
      <div class="properties-grid">
        <?php while ($properties->have_posts()): $properties->the_post(); ?>
          <?php my_theme_property_card(); ?>
        <?php endwhile; ?>
      </div>
    </section>
    <?php wp_reset_postdata(); ?>
  <?php endif;
}

/**
 * Example 7: Add property schema markup
 *
 * Improve SEO with structured data.
 */
add_action('wp_head', function () {
  if (is_singular('kato_property')) {
    $property = kato_property();

    if (!$property) {
      return;
    }

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'RealEstateAgent',
      'name' => $property->get_address(),
      'description' => $property->get_description(),
      'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => $property->city ?: $property->town,
        'addressRegion' => $property->county,
        'postalCode' => $property->get_postcode()
      ]
    ];

    $coordinates = $property->get_coordinates();
    if (!empty($coordinates['lat']) && !empty($coordinates['lon'])) {
      $schema['geo'] = [
        '@type' => 'GeoCoordinates',
        'latitude' => $coordinates['lat'],
        'longitude' => $coordinates['lon']
      ];
    }

    $images = $property->get_images();
    if (!empty($images)) {
      $schema['image'] = $images[0];
    }

    echo '<script type="application/ld+json">' . json_encode($schema) . '</script>';
  }
});

/**
 * Example 8: Enqueue property-specific styles and scripts
 */
add_action('wp_enqueue_scripts', function () {
  if (is_singular('kato_property') || is_post_type_archive('kato_property')) {
    // Enqueue property-specific CSS
    wp_enqueue_style(
      'my-theme-properties',
      get_template_directory_uri() . '/assets/css/properties.css',
      [],
      wp_get_theme()->get('Version')
    );

    // Enqueue property-specific JavaScript
    wp_enqueue_script(
      'my-theme-properties',
      get_template_directory_uri() . '/assets/js/properties.js',
      ['jquery'],
      wp_get_theme()->get('Version'),
      true
    );

    // Pass property data to JavaScript
    if (is_singular('kato_property')) {
      $property = kato_property();
      if ($property) {
        wp_localize_script('my-theme-properties', 'propertyData', [
          'id' => $property->ID,
          'coordinates' => $property->get_coordinates(),
          'images' => $property->get_images()
        ]);
      }
    }
  }
});

/**
 * Example 9: Create shortcode for property listings
 */
add_shortcode('property_listings', function ($atts) {
  $atts = shortcode_atts([
    'limit' => 6,
    'featured_only' => false,
    'columns' => 3
  ], $atts);

  $query_args = [
    'post_type' => 'kato_property',
    'posts_per_page' => intval($atts['limit'])
  ];

  if ($atts['featured_only']) {
    $query_args['meta_query'] = [
      [
        'key' => '_kato_sync_is_featured',
        'value' => '1',
        'compare' => '='
      ]
    ];
  }

  $properties = new WP_Query($query_args);

  if (!$properties->have_posts()) {
    return '<p>No properties found.</p>';
  }

  ob_start();
  ?>
  <div class="property-shortcode-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
    <?php while ($properties->have_posts()): $properties->the_post(); ?>
      <?php my_theme_property_card(); ?>
    <?php endwhile; ?>
  </div>
<?php
  wp_reset_postdata();

  return ob_get_clean();
});
