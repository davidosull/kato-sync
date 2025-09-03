<?php

/**
 * Property Card Template Example
 *
 * This template demonstrates how to create a property card using the
 * Kato Sync OOP approach with the new image helper function.
 *
 * Usage in your theme:
 * - Include this in archive templates
 * - Use in property loops
 * - Adapt styling to match your theme
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
?>

<article class="property-card" data-property-id="<?php echo esc_attr($property->ID); ?>">

  <!-- Property Image -->
  <div class="property-card__image">
    <?php
    // Get the first image using the new simplified helper function
    // The image size can be overridden by your theme using the 'kato_sync_card_image_size' filter
    $card_image = get_kato_card_image('medium', 'property-card__img', [
      'loading' => 'lazy'
    ]);

    if ($card_image): ?>
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
        $title_parts = [];
        if (!empty($property->name)) {
          $title_parts[] = $property->name;
        }
        if (!empty($property->address1)) {
          $title_parts[] = $property->address1;
        }
        if (!empty($property->get_outward_postcode())) {
          $title_parts[] = $property->get_outward_postcode();
        }
        echo esc_html(implode(' ', $title_parts));
        ?>
      </a>
    </h3>

    <!-- District: {county} -->
    <?php if (!empty($property->county)): ?>
      <p class="property-card__district"><?php echo esc_html($property->county); ?></p>
    <?php endif; ?>

    <!-- Size: same as Size Range in admin -->
    <?php $size = $property->get_size(); ?>
    <?php if ($size): ?>
      <div class="property-card__size">
        <span class="property-card__size-value"><?php echo esc_html($size); ?></span>
      </div>
    <?php endif; ?>

    <!-- Price: same as admin display -->
    <div class="property-card__price">
      <span class="property-card__price-value"><?php echo esc_html($property->get_price()); ?></span>
    </div>

    <!-- Additional Details -->
    <div class="property-card__details">
      <?php if (!empty($property->get_type())): ?>
        <span class="property-card__type"><?php echo esc_html($property->get_type()); ?></span>
      <?php endif; ?>

      <?php if ($property->is_fitted()): ?>
        <span class="property-card__fitted">Fitted</span>
      <?php endif; ?>
    </div>

  </div>

  <!-- Property Footer -->
  <div class="property-card__footer">
    <a href="<?php echo esc_url($property->url); ?>" class="property-card__link btn btn--primary">
      View Details
    </a>
  </div>

</article>

<?php
/**
 * Example CSS for styling (add to your theme's stylesheet):
 *
 * .property-card {
 *   display: flex;
 *   flex-direction: column;
 *   border: 1px solid #e1e5e9;
 *   border-radius: 8px;
 *   overflow: hidden;
 *   transition: box-shadow 0.2s ease;
 * }
 *
 * .property-card:hover {
 *   box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
 * }
 *
 * .property-card__image {
 *   position: relative;
 *   aspect-ratio: 16/9;
 *   overflow: hidden;
 * }
 *
 * .property-card__img {
 *   width: 100%;
 *   height: 100%;
 *   object-fit: cover;
 * }
 *
 * .property-card__badge {
 *   position: absolute;
 *   top: 12px;
 *   right: 12px;
 *   background: #ff6b6b;
 *   color: white;
 *   padding: 4px 8px;
 *   border-radius: 4px;
 *   font-size: 12px;
 *   font-weight: 600;
 * }
 *
 * .property-card__content {
 *   padding: 16px;
 *   flex-grow: 1;
 * }
 *
 * .property-card__title {
 *   margin: 0 0 8px 0;
 *   font-size: 18px;
 *   line-height: 1.4;
 * }
 *
 * .property-card__title a {
 *   text-decoration: none;
 *   color: #2c3e50;
 * }
 *
 * .property-card__district {
 *   margin: 0 0 12px 0;
 *   color: #6c757d;
 *   font-size: 14px;
 * }
 *
 * .property-card__size,
 * .property-card__price {
 *   margin-bottom: 8px;
 * }
 *
 * .property-card__price-value {
 *   font-weight: 600;
 *   font-size: 16px;
 *   color: #27ae60;
 * }
 *
 * .property-card__footer {
 *   padding: 16px;
 *   border-top: 1px solid #e1e5e9;
 * }
 *
 * .btn {
 *   display: inline-block;
 *   padding: 8px 16px;
 *   text-decoration: none;
 *   border-radius: 4px;
 *   transition: background-color 0.2s ease;
 * }
 *
 * .btn--primary {
 *   background-color: #3498db;
 *   color: white;
 * }
 *
 * .btn--primary:hover {
 *   background-color: #2980b9;
 * }
 */
?>
