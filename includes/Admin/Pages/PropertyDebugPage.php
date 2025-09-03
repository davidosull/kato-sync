<?php

namespace KatoSync\Admin\Pages;

/**
 * Property Debug page
 */
class PropertyDebugPage {

  /**
   * Render the property debug page
   */
  public function render(): void {
    // Handle form submission
    $external_id = '';
    $property = null;
    $error_message = '';

    if (isset($_POST['debug_external_id']) && !empty($_POST['debug_external_id'])) {
      $external_id = sanitize_text_field($_POST['debug_external_id']);

      // Find property by external ID (search in both external_id and id fields)
      global $wpdb;
      $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE (meta_key = '_kato_sync_external_id' OR meta_key = '_kato_sync_id')
         AND meta_value = %s
         LIMIT 1",
        $external_id
      ));

      if ($post_id) {
        try {
          $property = new \KatoSync\PostTypes\Kato_Property($post_id);
        } catch (\Exception $e) {
          $error_message = 'Error loading property: ' . $e->getMessage();
        }
      } else {
        $error_message = 'No property found with external ID: ' . $external_id;
      }
    }
?>
    <div class="wrap kato-debug-page">
      <h1><?php _e('Property Debug Tool', 'kato-sync'); ?></h1>

      <div class="kato-debug-intro">
        <h3>About This Tool</h3>
        <p>This tool allows you to debug property data by entering an external ID. It will show you all available property data, methods, and usage examples.</p>
        <p><strong>Note:</strong> Enter the external ID of a property (not the WordPress post ID) to view its complete data structure.</p>
      </div>

      <!-- External ID Input Form -->
      <div class="kato-debug-lookup">
        <h3>Property Lookup</h3>
        <form method="post" class="kato-debug-lookup__form">
          <?php wp_nonce_field('kato_debug_property', 'kato_debug_nonce'); ?>
          <label for="debug_external_id" class="kato-debug-lookup__label">External ID:</label>
          <input type="text"
            id="debug_external_id"
            name="debug_external_id"
            value="<?php echo esc_attr($external_id); ?>"
            placeholder="Enter property external ID..."
            class="kato-debug-lookup__input">
          <button type="submit" class="button button-primary">Debug Property</button>
          <?php if (!empty($external_id)): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=kato-sync-debug')); ?>" class="button">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="notice notice-error">
          <p><?php echo esc_html($error_message); ?></p>
        </div>
      <?php endif; ?>

      <?php if ($property): ?>
        <!-- Property Found - Show Debug Data -->
        <div class="kato-debug-success">
          <h3>✓ Property Found</h3>
          <p><strong>WordPress Post ID:</strong> <?php echo $property->ID; ?></p>
          <p><strong>External ID:</strong> <?php echo esc_html($property->external_id ?: 'No external ID'); ?></p>
          <p><strong>Property Name:</strong> <?php echo esc_html($property->name ?: $property->title ?: 'Untitled Property'); ?></p>
        </div>

        <!-- Property Data Debug Section -->
        <div class="kato-debug-content">
          <h2 class="kato-debug-title">Property Data Debug & Documentation</h2>

          <div class="kato-debug-intro">
            <h3>How to Use Property Data</h3>
            <p><strong>Property Instance:</strong> <code>$prop = new \KatoSync\PostTypes\Kato_Property($post_id);</code> - Creates property object for specific post</p>
            <p><strong>Access Data:</strong> <code>$prop->field_name</code> - Direct property access using magic methods</p>
            <p><strong>Fallback Pattern:</strong> <code>$prop->field ?? 'Default value'</code> - Use null coalescing for safe access</p>
            <p><strong>Array Fields:</strong> Many fields return arrays (types, availabilities, images, etc.)</p>
          </div>

          <?php
          // Organize data into logical sections for display
          $data_sections = [
            'Basic Information' => [
              'ID' => $property->ID,
              'external_id' => $property->external_id,
              'name' => $property->name,
              'title' => $property->title,
              'status' => $property->status,
              'featured' => $property->featured,
              'fitted' => $property->fitted,
              'url' => $property->url,
              'edit_url' => $property->edit_url,
            ],
            'Location Data' => [
              'address1' => $property->address1,
              'address2' => $property->address2,
              'city' => $property->city,
              'town' => $property->town,
              'county' => $property->county,
              'postcode' => $property->postcode,
              'latitude' => $property->latitude,
              'longitude' => $property->longitude,
              'street_view_data' => $property->street_view_data,
              'location_text' => $property->location_text,
              'location' => $property->location,
              'get_address()' => $property->get_address(),
              'get_coordinates()' => $property->get_coordinates(),
            ],
            'Property Types & Availability' => [
              'types' => $property->types,
              'get_type()' => $property->get_type(),
              'availabilities' => $property->availabilities,
              'property_type' => $property->property_type,
              'class_of_use' => $property->class_of_use,
            ],
            'Size Information' => [
              'size_sqft' => $property->size_sqft,
              'size_from' => $property->size_from,
              'size_to' => $property->size_to,
              'size_min' => $property->size_min,
              'size_max' => $property->size_max,
              'size_from_sqft' => $property->size_from_sqft,
              'size_to_sqft' => $property->size_to_sqft,
              'total_property_size' => $property->total_property_size,
              'total_property_size_sqft' => $property->total_property_size_sqft,
              'area_size_unit' => $property->area_size_unit,
              'get_size_range_formatted()' => $property->get_size_range_formatted(),
              'get_total_property_size_formatted()' => $property->get_total_property_size_formatted(),
              'get_unit_size_range_formatted()' => $property->get_unit_size_range_formatted(),
            ],
            'Pricing Information' => [
              'price' => $property->price,
              'rent' => $property->rent,
              'price_min' => $property->price_min,
              'price_max' => $property->price_max,
              'price_type' => $property->price_type,
              'price_per_sqft' => $property->price_per_sqft,
              'price_per_sqft_min' => $property->price_per_sqft_min,
              'price_per_sqft_max' => $property->price_per_sqft_max,
              'rent_components' => $property->rent_components,
              'service_charge' => $property->service_charge,
              'premium' => $property->premium,
              'get_price_range_formatted()' => $property->get_price_range_formatted(),
              'get_rent_range_formatted()' => $property->get_rent_range_formatted(),
            ],
            'Descriptions & Marketing' => [
              'description' => $property->description,
              'specification_summary' => $property->specification_summary,
              'specification_description' => $property->specification_description,
              'specification_promo' => $property->specification_promo,
              'key_selling_points' => $property->key_selling_points,
              'get_description()' => $property->get_description(),
              'get_key_selling_points()' => $property->get_key_selling_points(),
            ],
            'Agent & Contact Information' => [
              'agent_name' => $property->agent_name,
              'agent_email' => $property->agent_email,
              'agent_phone' => $property->agent_phone,
              'agent_company' => $property->agent_company,
              'contacts' => $property->contacts,
              'joint_agents' => $property->joint_agents,
              'get_agent()' => $property->get_agent(),
            ],
            'Media & Files' => [
              'images' => $property->images,
              'original_images' => $property->original_images,
              'files' => $property->files,
              'videos' => $property->videos,
              'epcs' => $property->epcs,
              'get_images()' => $property->get_images(),
              'get_files()' => $property->get_files(),
            ],
            'Floor Units & Specifications' => [
              'floor_units' => $property->floor_units,
              'get_floor_units()' => $property->get_floor_units(),
            ],
            'Property Features & Flags' => [
              'features' => $property->features,
              'tags' => $property->tags,
              'is_featured()' => $property->is_featured(),
              'is_fitted()' => $property->is_fitted(),
            ],
            'Dates & Meta' => [
              'created_at' => $property->created_at,
              'last_updated' => $property->last_updated,
              'imported_at' => $property->imported_at,
              'date' => $property->date,
              'modified' => $property->modified,
            ],
          ];
          ?>

          <?php foreach ($data_sections as $section_title => $section_data): ?>
            <div class="kato-debug-section">
              <h3 class="kato-debug-section__header"><?php echo esc_html($section_title); ?></h3>
              <div class="kato-debug-section__content">
                <?php foreach ($section_data as $field_name => $field_value): ?>
                  <div class="kato-debug-field">
                    <div class="kato-debug-field__name">
                      <?php echo esc_html($field_name); ?>
                    </div>
                    <div class="kato-debug-field__value">
                      <?php if (is_null($field_value)): ?>
                        <span class="kato-debug-value--null">NULL</span>
                      <?php elseif (is_bool($field_value)): ?>
                        <span class="kato-debug-value--<?php echo $field_value ? 'true' : 'false'; ?>">
                          <?php echo $field_value ? 'TRUE' : 'FALSE'; ?>
                        </span>
                      <?php elseif (is_array($field_value)): ?>
                        <?php if (empty($field_value)): ?>
                          <span class="kato-debug-value--empty">Empty Array []</span>
                        <?php else: ?>
                          <div class="kato-debug-value--array">
                            <pre><?php echo esc_html(print_r($field_value, true)); ?></pre>
                          </div>
                        <?php endif; ?>
                      <?php elseif (is_string($field_value) && empty(trim($field_value))): ?>
                        <span class="kato-debug-value--empty">Empty String ""</span>
                      <?php elseif ($field_value === 'No data'): ?>
                        <span class="kato-debug-value--no-data">No data</span>
                      <?php elseif (is_string($field_value) && $this->is_xml($field_value)): ?>
                        <div class="kato-debug-value--xml">
                          <div class="kato-debug-value--xml-title">XML Data:</div>
                          <pre class="kato-debug-value--xml-content"><?php echo esc_html($this->format_xml($field_value)); ?></pre>
                          <?php
                          $parsed_xml = $this->parse_xml_to_array($field_value);
                          if ($parsed_xml): ?>
                            <div class="kato-debug-value--xml-parsed">
                              <div class="kato-debug-value--xml-parsed-title">Parsed Values:</div>
                              <div class="kato-debug-value--xml-parsed-content">
                                <?php foreach ($parsed_xml as $key => $value): ?>
                                  <div>
                                    <strong><?php echo esc_html($key); ?>:</strong> <?php echo esc_html($value); ?>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php else: ?>
                        <span class="kato-debug-value--string"><?php echo esc_html($field_value); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Additional Methods Documentation -->
          <div class="kato-debug-section">
            <h3 class="kato-debug-section__header kato-debug-section__header--blue">Available Helper Methods</h3>
            <div class="kato-debug-section__content">
              <div class="kato-debug-methods">
                <div class="kato-debug-methods__section">
                  <h4>Data Access Methods</h4>
                  <ul>
                    <li><code>$prop->get_all_data()</code> - Get organized data array</li>
                    <li><code>$prop->get_all_properties()</code> - Get flat data array</li>
                    <li><code>$prop->to_array()</code> - Convert to flat array</li>
                    <li><code>$prop->get_address()</code> - Formatted address string</li>
                    <li><code>$prop->get_postcode()</code> - Full postcode</li>
                    <li><code>$prop->get_outward_postcode()</code> - Postcode first part</li>
                  </ul>
                </div>
                <div class="kato-debug-methods__section">
                  <h4>Formatting Methods</h4>
                  <ul>
                    <li><code>$prop->get_size_range_formatted()</code> - Size display array</li>
                    <li><code>$prop->get_price_range_formatted()</code> - Price display array</li>
                    <li><code>$prop->get_rent_range_formatted()</code> - Rent display array</li>
                    <li><code>$prop->get_total_property_size_formatted()</code> - Total size display</li>
                    <li><code>$prop->get_unit_size_range_formatted()</code> - Unit size display</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Usage Examples -->
          <div class="kato-debug-section">
            <h3 class="kato-debug-section__header kato-debug-section__header--green">Frontend Template Usage Examples</h3>
            <div class="kato-debug-section__content">
              <div class="kato-debug-examples__item">
                <h4>Basic Property Information</h4>
                <pre><code>&lt;?php
// Get property title with fallback
$title = $prop->specification_summary ?: $prop->title ?: 'Untitled Property';

// Get address with null coalescing
$address = $prop->get_address() ?: 'Address not available';

// Check if property is featured
if ($prop->is_featured()) {
    echo '&lt;span class="featured-badge"&gt;Featured&lt;/span&gt;';
}
?&gt;</code></pre>
              </div>

              <div class="kato-debug-examples__item">
                <h4>Working with Arrays (Types, Images, etc.)</h4>
                <pre><code>&lt;?php
// Property types
if (is_array($prop->types) && !empty($prop->types)) {
    foreach ($prop->types as $type) {
        echo '&lt;span class="property-type"&gt;' . esc_html($type) . '&lt;/span&gt;';
    }
}

// Property images
$images = $prop->get_images();
if (!empty($images)) {
    foreach ($images as $image) {
        echo '&lt;img src="' . esc_url($image['url']) . '" alt="Property Image"&gt;';
    }
}
?&gt;</code></pre>
              </div>

              <div class="kato-debug-examples__item">
                <h4>Using Formatted Range Data</h4>
                <pre><code>&lt;?php
// Get formatted price range
$price_data = $prop->get_price_range_formatted();
if (!empty($price_data['display'])) {
    echo '&lt;span class="price"&gt;' . esc_html($price_data['display']) . '&lt;/span&gt;';
} else {
    echo '&lt;span class="price-poa"&gt;POA&lt;/span&gt;';
}

// Get formatted size range
$size_data = $prop->get_size_range_formatted();
if (!empty($size_data['display'])) {
    echo '&lt;span class="size"&gt;' . esc_html($size_data['display']) . '&lt;/span&gt;';
}
?&gt;</code></pre>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- No Property Selected -->
        <div class="kato-debug-empty">
          <h3>Enter an External ID above to debug property data</h3>
          <p>This tool will show you all available property fields, methods, and usage examples.</p>
        </div>
      <?php endif; ?>
    </div>
<?php
  }

  /**
   * Check if a string contains XML data
   */
  private function is_xml(string $string): bool {
    return strpos(trim($string), '<') === 0 && strpos($string, '>') !== false;
  }

  /**
   * Format XML string for better display
   */
  private function format_xml(string $xml): string {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    // Suppress errors for malformed XML
    libxml_use_internal_errors(true);
    $dom->loadXML($xml);
    libxml_clear_errors();

    return $dom->saveXML();
  }

  /**
   * Parse XML to associative array
   */
  private function parse_xml_to_array(string $xml): ?array {
    try {
      // Suppress errors for malformed XML
      libxml_use_internal_errors(true);
      $parsed = simplexml_load_string($xml);
      libxml_clear_errors();

      if ($parsed === false) {
        return null;
      }

      // Convert SimpleXMLElement to array
      $array = json_decode(json_encode($parsed), true);

      return $array;
    } catch (\Exception $e) {
      return null;
    }
  }
}
