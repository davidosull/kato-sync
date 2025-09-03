<?php

/**
 * Simple Property Card Template (Using New Simplified Function)
 *
 * This version uses the new simplified get_kato_card_image() function
 * which automatically handles both imported and external images.
 *
 * Copy this to your theme and use it reliably.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get property instance - auto-detects current post in loops
$property = kato_property();

if (!$property) {
  return;
}

// Get the first image using the new simplified function
// If the function isn't available, fall back to direct class usage
if (function_exists('get_kato_card_image')) {
  $card_image = get_kato_card_image('medium', 'property-card__img', [
    'loading' => 'lazy'
  ]);
} else {
  // Fallback to direct class usage if function isn't available
  if (class_exists('\KatoSync\Utils\ImageDisplay')) {
    $card_image = \KatoSync\Utils\ImageDisplay::get_simple_card_image($property->ID, 'medium', 'property-card__img', [
      'loading' => 'lazy'
    ]);
  } else {
    // Ultimate fallback - get images directly from post meta
    $images = get_post_meta($property->ID, '_kato_sync_local_images', true);
    if (empty($images)) {
      $images = get_post_meta($property->ID, '_kato_sync_external_images', true);
    }

    if (!empty($images) && is_array($images)) {
      $first_image = $images[0];
      if (isset($first_image['attachment_id'])) {
        $card_image = wp_get_attachment_image($first_image['attachment_id'], 'medium', false, [
          'class' => 'property-card__img',
          'loading' => 'lazy',
          'alt' => $first_image['name'] ?? ''
        ]);
      } elseif (isset($first_image['url'])) {
        $card_image = sprintf(
          '<img src="%s" alt="%s" class="property-card__img" loading="lazy">',
          esc_url($first_image['url']),
          esc_attr($first_image['name'] ?? basename($first_image['url']))
        );
      } else {
        $card_image = '';
      }
    } else {
      $card_image = '';
    }
  }
}
?>

<article class="property-card" data-property-id="<?php echo esc_attr($property->ID); ?>">

  <!-- Property Image -->
  <div class="property-card__image">
    <?php if ($card_image): ?>
      <a href="<?php echo esc_url($property->url); ?>" class="property-card__image-link">
        <?php echo $card_image; ?>
      </a>
    <?php else: ?>
      <div class="property-card__no-image">
        <span>No Image Available</span>
      </div>
    <?php endif; ?>

    <?php if ($property->is_featured()): ?>
      <span class="property-card__badge property-card__badge--featured">Featured</span>
    <?php endif; ?>
  </div>

  <!-- Property Content -->
  <div class="property-card__content">

    <!-- Title: {name, if present} {address1} {outward_postcode} -->
    <h3 class="property-card__title">
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

    <!-- Location -->
    <?php if ($property->county): ?>
      <p class="property-card__location"><?php echo esc_html($property->county); ?></p>
    <?php endif; ?>

    <!-- Property Details -->
    <div class="property-card__details">
      <span class="property-card__price"><?php echo esc_html($property->get_price()); ?></span>

      <?php $size = $property->get_size(); ?>
      <?php if ($size): ?>
        <span class="property-card__size"><?php echo esc_html($size); ?></span>
      <?php endif; ?>
    </div>

    <!-- View Details Link -->
    <a href="<?php echo esc_url($property->url); ?>" class="property-card__link">
      View Details
    </a>
  </div>

</article>

<style>
  /* Basic styling - add to your theme's CSS */
  .property-card {
    display: flex;
    flex-direction: column;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
  }

  .property-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  .property-card__image {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
  }

  .property-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .property-card__no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    color: #6c757d;
  }

  .property-card__badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #ff6b6b;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
  }

  .property-card__content {
    padding: 16px;
    flex-grow: 1;
  }

  .property-card__title {
    margin: 0 0 8px 0;
    font-size: 18px;
    line-height: 1.4;
  }

  .property-card__title a {
    text-decoration: none;
    color: #2c3e50;
  }

  .property-card__location {
    margin: 0 0 12px 0;
    color: #6c757d;
    font-size: 14px;
  }

  .property-card__price {
    font-weight: 600;
    font-size: 16px;
    color: #27ae60;
  }

  .property-card__size {
    font-size: 14px;
    color: #555;
  }

  .property-card__footer {
    padding: 16px;
    border-top: 1px solid #e1e5e9;
  }

  .btn {
    display: inline-block;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.2s ease;
  }

  .btn--primary {
    background-color: #3498db;
    color: white;
  }

  .btn--primary:hover {
    background-color: #2980b9;
  }
</style>
