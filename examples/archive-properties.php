<?php

/**
 * Properties Archive Template Example
 *
 * This template demonstrates how to display a grid of property cards
 * using the Kato Sync OOP approach.
 *
 * To use in your theme:
 * 1. Copy to your theme as archive-kato_property.php
 * 2. Customise layout and styling to match your design
 * 3. Add filtering/search functionality as needed
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

get_header();
?>

<div class="properties-archive">

  <div class="container">

    <!-- Archive Header -->
    <header class="archive-header">
      <h1 class="archive-title">Properties</h1>
      <p class="archive-description">Discover our latest property opportunities</p>

      <!-- Property Count -->
      <?php if (have_posts()): ?>
        <div class="archive-meta">
          <span class="properties-count">
            <?php echo esc_html(sprintf('%d properties found', $wp_query->found_posts)); ?>
          </span>
        </div>
      <?php endif; ?>
    </header>

    <!-- Properties Grid -->
    <?php if (have_posts()): ?>

      <div class="properties-grid">

        <?php while (have_posts()): the_post(); ?>

          <?php
          // Get property instance - auto-detects current post in loop
          $property = kato_property();

          if (!$property) {
            continue;
          }
          ?>

          <div class="property-grid-item">

            <!-- Use the property card template -->
            <article class="property-card" data-property-id="<?php echo esc_attr($property->ID); ?>">

              <!-- Property Image -->
              <div class="property-card__image">
                <?php
                // Get the first image using the new helper function
                $card_image = kato_get_card_image($property->ID, 'medium', [
                  'class' => 'property-card__img',
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

          </div>

        <?php endwhile; ?>

      </div>

      <!-- Pagination -->
      <div class="archive-pagination">
        <?php
        the_posts_pagination(array(
          'mid_size' => 2,
          'prev_text' => '&laquo; Previous',
          'next_text' => 'Next &raquo;',
        ));
        ?>
      </div>

    <?php else: ?>

      <!-- No Properties Found -->
      <div class="no-properties">
        <h2>No Properties Found</h2>
        <p>Sorry, we couldn't find any properties matching your criteria.</p>
      </div>

    <?php endif; ?>

  </div>

</div>

<?php get_footer(); ?>

<?php
/**
 * Example CSS for styling (add to your theme's stylesheet):
 *
 * .properties-archive {
 *   padding: 2rem 0;
 * }
 *
 * .archive-header {
 *   text-align: center;
 *   margin-bottom: 3rem;
 * }
 *
 * .archive-title {
 *   font-size: 2.5rem;
 *   margin-bottom: 1rem;
 *   color: #2c3e50;
 * }
 *
 * .archive-description {
 *   font-size: 1.2rem;
 *   color: #6c757d;
 *   margin-bottom: 1.5rem;
 * }
 *
 * .archive-meta {
 *   margin-bottom: 1rem;
 * }
 *
 * .properties-count {
 *   background: #f8f9fa;
 *   padding: 0.5rem 1rem;
 *   border-radius: 2rem;
 *   font-size: 0.9rem;
 *   color: #6c757d;
 * }
 *
 * .properties-grid {
 *   display: grid;
 *   grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
 *   gap: 2rem;
 *   margin-bottom: 3rem;
 * }
 *
 * @media (max-width: 768px) {
 *   .properties-grid {
 *     grid-template-columns: 1fr;
 *     gap: 1.5rem;
 *   }
 * }
 *
 * .property-grid-item {
 *   height: 100%;
 * }
 *
 * .property-card {
 *   display: flex;
 *   flex-direction: column;
 *   height: 100%;
 *   border: 1px solid #e1e5e9;
 *   border-radius: 8px;
 *   overflow: hidden;
 *   transition: all 0.2s ease;
 *   background: white;
 * }
 *
 * .property-card:hover {
 *   box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
 *   transform: translateY(-2px);
 * }
 *
 * .property-card__image {
 *   position: relative;
 *   aspect-ratio: 16/9;
 *   overflow: hidden;
 * }
 *
 * .property-card__image-link {
 *   display: block;
 *   width: 100%;
 *   height: 100%;
 * }
 *
 * .property-card__img {
 *   width: 100%;
 *   height: 100%;
 *   object-fit: cover;
 *   transition: transform 0.2s ease;
 * }
 *
 * .property-card:hover .property-card__img {
 *   transform: scale(1.05);
 * }
 *
 * .property-card__no-image {
 *   display: flex;
 *   align-items: center;
 *   justify-content: center;
 *   width: 100%;
 *   height: 100%;
 *   background: #f8f9fa;
 *   color: #6c757d;
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
 *   z-index: 10;
 * }
 *
 * .property-card__content {
 *   padding: 1.5rem;
 *   flex-grow: 1;
 *   display: flex;
 *   flex-direction: column;
 * }
 *
 * .property-card__title {
 *   margin: 0 0 0.75rem 0;
 *   font-size: 1.1rem;
 *   line-height: 1.4;
 *   flex-grow: 1;
 * }
 *
 * .property-card__title a {
 *   text-decoration: none;
 *   color: #2c3e50;
 *   transition: color 0.2s ease;
 * }
 *
 * .property-card__title a:hover {
 *   color: #3498db;
 * }
 *
 * .property-card__district {
 *   margin: 0 0 1rem 0;
 *   color: #6c757d;
 *   font-size: 0.9rem;
 * }
 *
 * .property-card__size,
 * .property-card__price {
 *   margin-bottom: 0.75rem;
 * }
 *
 * .property-card__price-value {
 *   font-weight: 600;
 *   font-size: 1.1rem;
 *   color: #27ae60;
 * }
 *
 * .property-card__details {
 *   display: flex;
 *   gap: 0.5rem;
 *   flex-wrap: wrap;
 *   margin-bottom: 1rem;
 * }
 *
 * .property-card__type,
 * .property-card__fitted {
 *   padding: 0.25rem 0.5rem;
 *   background: #e9ecef;
 *   border-radius: 4px;
 *   font-size: 0.8rem;
 *   color: #495057;
 * }
 *
 * .property-card__footer {
 *   padding: 1rem 1.5rem;
 *   border-top: 1px solid #f1f3f4;
 *   margin-top: auto;
 * }
 *
 * .btn {
 *   display: inline-block;
 *   width: 100%;
 *   padding: 0.75rem 1rem;
 *   text-decoration: none;
 *   border-radius: 4px;
 *   transition: all 0.2s ease;
 *   text-align: center;
 *   font-weight: 500;
 * }
 *
 * .btn--primary {
 *   background-color: #3498db;
 *   color: white;
 * }
 *
 * .btn--primary:hover {
 *   background-color: #2980b9;
 *   transform: translateY(-1px);
 * }
 *
 * .archive-pagination {
 *   text-align: center;
 *   margin-top: 3rem;
 * }
 *
 * .no-properties {
 *   text-align: center;
 *   padding: 3rem 0;
 * }
 *
 * .no-properties h2 {
 *   margin-bottom: 1rem;
 *   color: #2c3e50;
 * }
 *
 * .no-properties p {
 *   color: #6c757d;
 * }
 */
?>
