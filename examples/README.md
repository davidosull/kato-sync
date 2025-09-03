# Kato Sync Usage Examples

This folder contains practical examples for integrating Kato Sync with your WordPress theme using the new OOP approach and image helper functions.

## Files Included

- **property-card.php** - Complete property card template for archive/grid layouts
- **property-card-simple.php** - Simplified property card using the new `get_kato_card_image()` function
- **single-property.php** - Full single property page template
- **archive-properties.php** - Properties archive page template
- **theme-functions-example.php** - Functions.php examples for theme integration
- **service-charge-example.php** - Example showing how to access service charge data in templates

## New Simplified Image Helper Function

### `get_kato_card_image()` - **RECOMMENDED**

A simplified function that automatically handles both imported and external images:

```php
// Basic usage (auto-detects current post)
echo get_kato_card_image();

// With custom size and class
echo get_kato_card_image('large', 'my-custom-class');

// With additional attributes
echo get_kato_card_image('medium', 'property-card__img', [
  'loading' => 'lazy',
  'data-property-id' => '123'
]);
```

**How it works:**

- **If image is imported**: Returns WordPress responsive image with proper `srcset` and `sizes`
- **If image is not imported**: Returns simple `<img>` tag with the original XML feed URL
- **Automatic alt text**: Uses image name from feed or WordPress alt text
- **No complex fallbacks needed**: Handles everything automatically

### Legacy Functions (Still Available)

#### `kato_get_card_image()`

The original complex function (still available for backward compatibility):

```php
// Basic usage (auto-detects current post)
echo kato_get_card_image();

// With specific post ID and size
echo kato_get_card_image(123, 'large');

// With custom attributes
echo kato_get_card_image(123, 'medium', [
  'class' => 'my-custom-class',
  'loading' => 'lazy',
  'data-property-id' => '123'
]);
```

#### `kato_get_card_image_url()`

Gets just the image URL without HTML wrapper:

```php
// Get image URL
$image_url = kato_get_card_image_url(123, 'medium');

if ($image_url) {
  echo '<img src="' . esc_url($image_url) . '" alt="Property image">';
}
```

## Recommended Usage

**Use the new simplified function in your templates:**

```php
// In your property card template
$card_image = get_kato_card_image('medium', 'property-card__img', [
  'loading' => 'lazy'
]);

if ($card_image): ?>
  <a href="<?php echo esc_url($property->url); ?>">
    <?php echo $card_image; ?>
  </a>
<?php else: ?>
  <div class="no-image">No Image Available</div>
<?php endif; ?>
```

**Benefits of the new function:**

- ✅ Much simpler syntax
- ✅ Automatic handling of imported vs external images
- ✅ No complex fallback logic needed
- ✅ Proper alt text handling
- ✅ Responsive images when imported
- ✅ Works with both imported and external images

## Service Charge Data Access

The plugin now supports service charge data from XML feeds. Service charge information includes:

- **Amount** - The service charge value
- **Period** - The period (e.g., "sqft", "monthly", "annually")
- **Details** - Additional text description

### Accessing Service Charge Data

Similar to how rent components are accessed, you can use these methods:

```php
// Get the property object
$prop = new \KatoSync\PostTypes\Kato_Property($post_id);

// Individual getter methods
$service_charge = $prop->get_service_charge();
$service_charge_period = $prop->get_service_charge_period();
$service_charge_text = $prop->get_service_charge_text();

// Combined rates method (similar to get_rent_rates)
$service_charge_rates = $prop->get_service_charge_rates();
```

### Example Usage

```php
if (!empty($service_charge)) {
    echo '<div class="service-charge">';
    echo '<h3>Service Charge</h3>';
    echo '<p><strong>Amount:</strong> ' . esc_html($service_charge) . '</p>';

    if (!empty($service_charge_period)) {
        echo '<p><strong>Period:</strong> ' . esc_html($service_charge_period) . '</p>';
    }

    if (!empty($service_charge_text)) {
        echo '<p><strong>Details:</strong> ' . esc_html($service_charge_text) . '</p>';
    }
    echo '</div>';
}
```

### XML Feed Structure

The plugin expects service charge data in this format:

```xml
<service_charge>10</service_charge>
<service_charge_period>sqft</service_charge_period>
<service_charge_text>Service charge includes maintenance and utilities</service_charge_text>
```

See `service-charge-example.php` for a complete working example.

## Theme Override for Image Size

To override the default image size in your theme, add this to your `functions.php`:

```php
// Override default 'medium' size with custom 'landscape-md'
add_filter('kato_sync_card_image_size', function($default_size, $post_id) {
  return 'landscape-md';
}, 10, 2);
```

## Image Handling

The helper functions automatically handle different image states:

- **Before Import**: Shows simple `<img>` tag with URL from feed and alt text
- **After Import**: Uses WordPress responsive image functions (`wp_get_attachment_image`) with proper `srcset` and `sizes`
- **Default Size**: Medium (can be overridden via filter)

## Property Card Structure

Each property card displays:

1. **Image** - First image in the image array
2. **Title** - `{name, if present} {address1} {outward_postcode}`
3. **Location** - County/district information
4. **Price** - Formatted price display
5. **Size** - Property size information
6. **Link** - View details link

## Custom Image Sizes

Register custom image sizes in your theme for better property display:

```php
add_action('after_setup_theme', function() {
  // 16:9 landscape for property cards
  add_image_size('landscape-md', 400, 225, true);
  add_image_size('landscape-lg', 800, 450, true);

  // Square for grid layouts
  add_image_size('property-square', 300, 300, true);

  // Hero banner
  add_image_size('property-hero', 1200, 400, true);
});
```

## Advanced Usage

### Custom Property Cards

Create your own property card function:

```php
function my_property_card($post_id = null) {
  $property = kato_property($post_id);
  if (!$property) return;

  $image = get_kato_card_image('landscape-md', 'my-theme-property-image');

  // Your custom HTML structure
  ?>
  <div class="my-card">
    <?php echo $image; ?>
    <h3><?php echo esc_html($property->get_address()); ?></h3>
    <p><?php echo esc_html($property->get_price()); ?></p>
  </div>
  <?php
}
```

### Property Queries

Query properties with custom criteria:

```php
$featured_properties = new WP_Query([
  'post_type' => 'kato_property',
  'posts_per_page' => 6,
  'meta_query' => [
    [
      'key' => '_kato_sync_is_featured',
      'value' => '1',
      'compare' => '='
    ]
  ]
]);

while ($featured_properties->have_posts()):
  $featured_properties->the_post();
  my_property_card();
endwhile;
wp_reset_postdata();
```

## Migration from Old Function

If you're currently using the complex `kato_get_card_image()` function, you can simplify your code:

**Before:**

```php
if (function_exists('kato_get_card_image')) {
  $card_image = kato_get_card_image($property->ID, 'medium', [
    'class' => 'property-card__img',
    'loading' => 'lazy'
  ]);
} else {
  // Fallback to direct class usage
  $card_image = \KatoSync\Utils\ImageDisplay::get_card_image_html($property->ID, 'medium', [
    'class' => 'property-card__img',
    'loading' => 'lazy'
  ]);
}
```

**After:**

```php
$card_image = get_kato_card_image('medium', 'property-card__img', [
  'loading' => 'lazy'
]);
```

## Best Practices

1. **Always** use `kato_property()` to get property instances
2. **Always** escape output with `esc_html()`, `esc_url()`, `esc_attr()`
3. **Check** if property exists before using: `if ($property)`
4. **Use** the new simplified `get_kato_card_image()` function
5. **Override** image sizes via filters, not hardcoded changes
6. **Test** with both external and imported images
7. **Include** fallbacks for missing images and data

## Support

For questions about implementing these examples in your theme, refer to the main plugin documentation or create an issue in the project repository.
