<?php

/**
 * Service Charge Data Usage Example
 *
 * This example shows how to access service charge data in frontend templates,
 * similar to how rent components are accessed.
 */

// Get the property object
$prop = new \KatoSync\PostTypes\Kato_Property($post_id);

// Access service charge data using individual getter methods
$service_charge = $prop->get_service_charge();
$service_charge_period = $prop->get_service_charge_period();
$service_charge_text = $prop->get_service_charge_text();

// Or use the combined rates method (similar to get_rent_rates)
$service_charge_rates = $prop->get_service_charge_rates();

// Example usage in templates
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

  // Or use the combined rates
  if (!empty($service_charge_rates)) {
    echo '<p><strong>Rates:</strong> ' . esc_html($service_charge_rates) . '</p>';
  }

  echo '</div>';
}

// Alternative: Access all pricing data at once
$all_data = $prop->get_all_data();
if (isset($all_data['pricing']['service_charge']) && !empty($all_data['pricing']['service_charge'])) {
  $service_charge_data = $all_data['pricing'];

  echo '<div class="service-charge-alt">';
  echo '<h3>Service Charge (Alternative Method)</h3>';
  echo '<p><strong>Amount:</strong> ' . esc_html($service_charge_data['service_charge']) . '</p>';

  if (!empty($service_charge_data['service_charge_period'])) {
    echo '<p><strong>Period:</strong> ' . esc_html($service_charge_data['service_charge_period']) . '</p>';
  }

  if (!empty($service_charge_data['service_charge_text'])) {
    echo '<p><strong>Details:</strong> ' . esc_html($service_charge_data['service_charge_text']) . '</p>';
  }
  echo '</div>';
}
