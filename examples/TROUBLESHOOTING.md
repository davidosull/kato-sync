# Troubleshooting Guide

## Function Not Available Error

If you're getting the error `Call to undefined function get_kato_card_image()`, here are the solutions:

### Solution 1: Use the Fallback Approach (Recommended)

Update your theme template to use the fallback approach:

```php
<?php
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
```

### Solution 2: Check Plugin Loading Order

Make sure the Kato Sync plugin is loaded before your theme tries to use the function. You can:

1. **Deactivate and reactivate the plugin** to ensure proper loading
2. **Check if the plugin is active** in WordPress admin
3. **Clear any caching** if you're using caching plugins

### Solution 3: Use Direct Class Method

If the function still isn't available, use the class method directly:

```php
<?php
// Use the class method directly
if (class_exists('\KatoSync\Utils\ImageDisplay')) {
  $card_image = \KatoSync\Utils\ImageDisplay::get_simple_card_image($property->ID, 'medium', 'property-card__img', [
    'loading' => 'lazy'
  ]);
} else {
  $card_image = '';
}
?>
```

### Solution 4: Manual Image Retrieval

As a last resort, get the images directly from post meta:

```php
<?php
// Get images directly from post meta
$images = get_post_meta($property->ID, '_kato_sync_local_images', true);
if (empty($images)) {
  $images = get_post_meta($property->ID, '_kato_sync_external_images', true);
}

if (!empty($images) && is_array($images)) {
  $first_image = $images[0];

  if (isset($first_image['attachment_id'])) {
    // Local image (imported)
    $card_image = wp_get_attachment_image($first_image['attachment_id'], 'medium', false, [
      'class' => 'property-card__img',
      'loading' => 'lazy',
      'alt' => $first_image['name'] ?? ''
    ]);
  } elseif (isset($first_image['url'])) {
    // External image (not imported)
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
?>
```

## Debug Information

To help debug the issue, you can add this to your template temporarily:

```php
<?php
// Debug information
echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0;">';
echo '<strong>Debug Info:</strong><br>';
echo 'Function exists: ' . (function_exists('get_kato_card_image') ? 'Yes' : 'No') . '<br>';
echo 'Class exists: ' . (class_exists('\KatoSync\Utils\ImageDisplay') ? 'Yes' : 'No') . '<br>';
echo 'Plugin active: ' . (is_plugin_active('kato-sync/kato-sync.php') ? 'Yes' : 'No') . '<br>';
echo 'Post ID: ' . get_the_ID() . '<br>';
echo '</div>';
?>
```

## Common Issues

1. **Plugin not activated** - Make sure Kato Sync is activated in WordPress admin
2. **Caching issues** - Clear any caching plugins or server cache
3. **Theme loading before plugin** - Some themes load very early, causing timing issues
4. **Autoloader issues** - The plugin uses an autoloader that might not be working correctly

## Recommended Approach

Use the fallback approach from Solution 1, as it provides the most robust solution that will work regardless of plugin loading order or timing issues.
