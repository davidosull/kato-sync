<?php

/**
 * Single Property Template Example
 *
 * This template demonstrates how to display a complete property page
 * using the Kato Sync OOP approach.
 *
 * To use in your theme:
 * 1. Copy to your theme as single-kato_property.php
 * 2. Customise styling and layout to match your design
 * 3. Add/remove sections as needed
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

get_header();

// Get property instance
$property = kato_property();

if (!$property): ?>
  <div class="container">
    <p>Property not found.</p>
  </div>
<?php get_footer();
  return;
endif;
?>

<article class="single-property" data-property-id="<?php echo esc_attr($property->ID); ?>">

  <div class="container">

    <!-- Property Header -->
    <header class="property-header">
      <div class="property-header__content">

        <!-- Title with name, address and postcode -->
        <h1 class="property-header__title">
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
        </h1>

        <!-- Location Info -->
        <div class="property-header__location">
          <p class="property-address"><?php echo esc_html($property->get_address()); ?></p>
          <?php if (!empty($property->county)): ?>
            <p class="property-district"><?php echo esc_html($property->county); ?></p>
          <?php endif; ?>
        </div>

        <!-- Key Details -->
        <div class="property-header__details">
          <div class="property-detail">
            <span class="property-detail__label">Price:</span>
            <span class="property-detail__value property-detail__value--price">
              <?php echo esc_html($property->get_price()); ?>
            </span>
          </div>

          <?php $size = $property->get_size(); ?>
          <?php if ($size): ?>
            <div class="property-detail">
              <span class="property-detail__label">Size:</span>
              <span class="property-detail__value"><?php echo esc_html($size); ?></span>
            </div>
          <?php endif; ?>

          <?php if (!empty($property->get_type())): ?>
            <div class="property-detail">
              <span class="property-detail__label">Type:</span>
              <span class="property-detail__value"><?php echo esc_html($property->get_type()); ?></span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Badges -->
        <div class="property-badges">
          <?php if ($property->is_featured()): ?>
            <span class="property-badge property-badge--featured">Featured</span>
          <?php endif; ?>
          <?php if ($property->is_fitted()): ?>
            <span class="property-badge property-badge--fitted">Fitted</span>
          <?php endif; ?>
        </div>

      </div>
    </header>

    <!-- Property Images -->
    <section class="property-images">
      <h2 class="property-section__title">Images</h2>
      <?php
      // Display all property images using the existing utility
      \KatoSync\Utils\ImageDisplay::display_frontend_images($property->ID, 'large');
      ?>
    </section>

    <!-- Property Description -->
    <?php $description = $property->get_description(); ?>
    <?php if ($description): ?>
      <section class="property-description">
        <h2 class="property-section__title">Description</h2>
        <div class="property-description__content">
          <?php echo wp_kses_post(wpautop($description)); ?>
        </div>
      </section>
    <?php endif; ?>

    <!-- Key Selling Points -->
    <?php $selling_points = $property->get_key_selling_points(); ?>
    <?php if (!empty($selling_points)): ?>
      <section class="property-selling-points">
        <h2 class="property-section__title">Key Features</h2>
        <ul class="property-selling-points__list">
          <?php foreach ($selling_points as $point): ?>
            <li class="property-selling-points__item"><?php echo esc_html($point); ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <!-- Property Specifications -->
    <section class="property-specifications">
      <h2 class="property-section__title">Specifications</h2>
      <div class="property-specifications__grid">

        <?php if (!empty($property->get_postcode())): ?>
          <div class="property-spec">
            <span class="property-spec__label">Postcode:</span>
            <span class="property-spec__value"><?php echo esc_html($property->get_postcode()); ?></span>
          </div>
        <?php endif; ?>

        <?php $price_range = $property->get_price_range(); ?>
        <?php if ($price_range): ?>
          <div class="property-spec">
            <span class="property-spec__label">Price Range:</span>
            <span class="property-spec__value"><?php echo esc_html($price_range); ?></span>
          </div>
        <?php endif; ?>

        <?php $size_range = $property->get_size_range(); ?>
        <?php if ($size_range): ?>
          <div class="property-spec">
            <span class="property-spec__label">Size Range:</span>
            <span class="property-spec__value"><?php echo esc_html($size_range); ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($property->get_status())): ?>
          <div class="property-spec">
            <span class="property-spec__label">Status:</span>
            <span class="property-spec__value"><?php echo esc_html($property->get_status()); ?></span>
          </div>
        <?php endif; ?>

      </div>
    </section>

    <!-- Agent Information -->
    <?php $agent = $property->get_agent(); ?>
    <?php if (!empty($agent)): ?>
      <section class="property-agent">
        <h2 class="property-section__title">Contact Agent</h2>
        <div class="property-agent__card">

          <?php if (!empty($agent['name'])): ?>
            <h3 class="property-agent__name"><?php echo esc_html($agent['name']); ?></h3>
          <?php endif; ?>

          <div class="property-agent__contact">
            <?php if (!empty($agent['email'])): ?>
              <p class="property-agent__email">
                <a href="mailto:<?php echo esc_attr($agent['email']); ?>">
                  <?php echo esc_html($agent['email']); ?>
                </a>
              </p>
            <?php endif; ?>

            <?php if (!empty($agent['phone'])): ?>
              <p class="property-agent__phone">
                <a href="tel:<?php echo esc_attr($agent['phone']); ?>">
                  <?php echo esc_html($agent['phone']); ?>
                </a>
              </p>
            <?php endif; ?>
          </div>

        </div>
      </section>
    <?php endif; ?>

    <!-- Location Map (if coordinates available) -->
    <?php $coordinates = $property->get_coordinates(); ?>
    <?php if (!empty($coordinates['lat']) && !empty($coordinates['lon'])): ?>
      <section class="property-location">
        <h2 class="property-section__title">Location</h2>
        <div class="property-location__map"
          data-lat="<?php echo esc_attr($coordinates['lat']); ?>"
          data-lon="<?php echo esc_attr($coordinates['lon']); ?>">
          <!-- Add your map integration here (Google Maps, OpenStreetMap, etc.) -->
          <p>Map showing location: <?php echo esc_html($coordinates['lat']); ?>, <?php echo esc_html($coordinates['lon']); ?></p>
        </div>
      </section>
    <?php endif; ?>

  </div>

</article>

<?php get_footer(); ?>

<?php
/**
 * Example CSS for styling (add to your theme's stylesheet):
 *
 * .single-property {
 *   padding: 2rem 0;
 * }
 *
 * .property-header {
 *   margin-bottom: 3rem;
 *   padding-bottom: 2rem;
 *   border-bottom: 1px solid #e1e5e9;
 * }
 *
 * .property-header__title {
 *   font-size: 2.5rem;
 *   margin-bottom: 1rem;
 *   color: #2c3e50;
 * }
 *
 * .property-header__location {
 *   margin-bottom: 1.5rem;
 * }
 *
 * .property-address {
 *   font-size: 1.2rem;
 *   color: #6c757d;
 *   margin-bottom: 0.5rem;
 * }
 *
 * .property-district {
 *   font-size: 1rem;
 *   color: #6c757d;
 * }
 *
 * .property-header__details {
 *   display: flex;
 *   gap: 2rem;
 *   margin-bottom: 1rem;
 *   flex-wrap: wrap;
 * }
 *
 * .property-detail {
 *   display: flex;
 *   flex-direction: column;
 *   gap: 0.25rem;
 * }
 *
 * .property-detail__label {
 *   font-size: 0.9rem;
 *   color: #6c757d;
 *   font-weight: 500;
 * }
 *
 * .property-detail__value {
 *   font-size: 1.1rem;
 *   font-weight: 600;
 * }
 *
 * .property-detail__value--price {
 *   color: #27ae60;
 *   font-size: 1.3rem;
 * }
 *
 * .property-badges {
 *   display: flex;
 *   gap: 0.5rem;
 * }
 *
 * .property-badge {
 *   padding: 0.25rem 0.75rem;
 *   border-radius: 1rem;
 *   font-size: 0.8rem;
 *   font-weight: 600;
 * }
 *
 * .property-badge--featured {
 *   background: #ff6b6b;
 *   color: white;
 * }
 *
 * .property-badge--fitted {
 *   background: #4ecdc4;
 *   color: white;
 * }
 *
 * .property-section__title {
 *   font-size: 1.5rem;
 *   margin-bottom: 1rem;
 *   color: #2c3e50;
 * }
 *
 * .property-images,
 * .property-description,
 * .property-selling-points,
 * .property-specifications,
 * .property-agent,
 * .property-location {
 *   margin-bottom: 2.5rem;
 * }
 *
 * .property-selling-points__list {
 *   list-style: none;
 *   padding: 0;
 * }
 *
 * .property-selling-points__item {
 *   padding: 0.5rem 0;
 *   border-bottom: 1px solid #f1f3f4;
 *   position: relative;
 *   padding-left: 1.5rem;
 * }
 *
 * .property-selling-points__item::before {
 *   content: "✓";
 *   position: absolute;
 *   left: 0;
 *   color: #27ae60;
 *   font-weight: bold;
 * }
 *
 * .property-specifications__grid {
 *   display: grid;
 *   grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
 *   gap: 1rem;
 * }
 *
 * .property-spec {
 *   display: flex;
 *   justify-content: space-between;
 *   padding: 0.75rem;
 *   background: #f8f9fa;
 *   border-radius: 4px;
 * }
 *
 * .property-spec__label {
 *   font-weight: 500;
 *   color: #6c757d;
 * }
 *
 * .property-spec__value {
 *   font-weight: 600;
 * }
 *
 * .property-agent__card {
 *   background: #f8f9fa;
 *   padding: 1.5rem;
 *   border-radius: 8px;
 * }
 *
 * .property-agent__name {
 *   margin-bottom: 1rem;
 *   color: #2c3e50;
 * }
 *
 * .property-agent__contact a {
 *   color: #3498db;
 *   text-decoration: none;
 * }
 *
 * .property-agent__contact a:hover {
 *   text-decoration: underline;
 * }
 */
?>
