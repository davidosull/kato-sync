<?php

namespace KatoSync\PostTypes;

/**
 * Kato Property class for retrieving property data
 *
 * This class provides a unified interface for accessing property data
 * from the Kato Sync system.
 */
class Kato_Property {

  /**
   * Property post ID
   */
  private int $post_id;

  /**
   * Property post object
   */
  private \WP_Post $post;

  /**
   * Cached property data
   */
  private ?array $cached_data = null;

  /**
   * Constructor
   */
  public function __construct(?int $post_id = null) {
    // Auto-detect current post if no ID provided
    if ($post_id === null) {
      $post_id = \get_the_ID();
      if (!$post_id) {
        throw new \InvalidArgumentException('No post ID provided and no current post context available');
      }
    }

    $this->post_id = $post_id;
    $this->post = \get_post($post_id);

    if (!$this->post) {
      throw new \InvalidArgumentException('Invalid property post ID: Post does not exist');
    }

    // Accept both post type formats
    if (!in_array($this->post->post_type, ['kato_property', 'kato-property'])) {
      throw new \InvalidArgumentException('Invalid property post ID: Post type is ' . $this->post->post_type . ', expected kato_property or kato-property');
    }
  }

  /**
   * Magic method to provide direct property access
   */
  public function __get(string $name) {
    // Handle WordPress post properties
    if (property_exists($this->post, $name)) {
      return $this->post->$name;
    }

    // Handle special cases
    switch ($name) {
      case 'ID':
        return $this->post_id;
      case 'post_id':
        return $this->post_id;
      case 'title':
        return $this->get_title();
      case 'content':
        return $this->post->post_content;
      case 'excerpt':
        return $this->post->post_excerpt;
      case 'status':
        return $this->get_status();
      case 'date':
        return $this->post->post_date;
      case 'modified':
        return $this->post->post_modified;
      case 'slug':
        return $this->post->post_name;
      case 'url':
        return \get_permalink($this->post_id);
      case 'edit_url':
        return \get_edit_post_link($this->post_id);
      case 'images':
        return $this->get_images();
    }

    // Handle meta fields with _kato_sync_ prefix
    $meta_field = '_kato_sync_' . $name;
    $value = \get_post_meta($this->post_id, $meta_field, true);


    if (empty($value)) {
      return null;
    }

    // Try to decode JSON if it looks like JSON
    if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }

    return $value;
  }

  /**
   * Check if a property exists
   */
  public function __isset(string $name): bool {
    // Handle WordPress post properties
    if (property_exists($this->post, $name)) {
      return true;
    }

    // Handle special cases
    $special_cases = ['ID', 'post_id', 'title', 'content', 'excerpt', 'status', 'date', 'modified', 'slug', 'url', 'edit_url', 'images'];
    if (in_array($name, $special_cases)) {
      return true;
    }

    // Handle meta fields
    $meta_field = '_kato_sync_' . $name;
    $value = \get_post_meta($this->post_id, $meta_field, true);
    return !empty($value);
  }

  /**
   * Get property title
   */
  public function get_title(): string {
    // Try specification_summary first, then fall back to post title
    $title = \get_post_meta($this->post_id, '_kato_sync_specification_summary', true);
    return $title ?: $this->post->post_title;
  }

  /**
   * Get property address
   */
  public function get_address(): string {
    $address_parts = [];

    if (!empty($this->address1)) $address_parts[] = $this->address1;
    if (!empty($this->address2)) $address_parts[] = $this->address2;
    if (!empty($this->city)) $address_parts[] = $this->city;
    if (!empty($this->town)) $address_parts[] = $this->town;
    if (!empty($this->county)) $address_parts[] = $this->county;

    return implode(', ', $address_parts);
  }

  /**
   * Get property price
   * @deprecated 1.1.0 Use raw properties ($this->price, $this->rent, etc.)
   */
  public function get_price(): string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Use raw properties ($this->price, $this->rent)');
    }
    return $this->price ?? $this->rent ?? 'Price on application';
  }

  /**
   * Get property size
   * @deprecated 1.1.0 Use raw properties ($this->size, $this->size_from, $this->size_to)
   */
  public function get_size(): string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Use raw properties ($this->size, $this->size_from, $this->size_to)');
    }
    $size_from = $this->size_from ?? '';
    $size_to = $this->size_to ?? '';
    $unit = $this->area_size_unit ?? 'sq ft';

    if ($size_from && $size_to) {
      if ($size_from === $size_to) {
        return $size_from . ' ' . $unit;
      } else {
        return $size_from . ' - ' . $size_to . ' ' . $unit;
      }
    } elseif ($size_from) {
      return $size_from . ' ' . $unit;
    }

    return $this->size_sqft ?? $this->size ?? '';
  }

  /**
   * Get property size range (formatted for display)
   * @deprecated 1.1.0 Use get_size_range_formatted() or compute inline from raw properties
   */
  public function get_size_range(): string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Use get_size_range_formatted() or compute inline from raw properties');
    }
    $size_min = $this->size_min ?? null;
    $size_max = $this->size_max ?? null;

    if ($size_min && $size_max) {
      if ($size_min === $size_max) {
        return \number_format($size_min) . ' sqft';
      } else {
        return \number_format($size_min) . ' - ' . \number_format($size_max) . ' sqft';
      }
    } elseif ($size_min) {
      return \number_format($size_min) . ' sqft';
    }

    return '';
  }

  /**
   * Get size range formatted with new range logic
   */
  public function get_size_range_formatted(): array {
    $size_min = $this->size_min ?? null;
    $size_max = $this->size_max ?? null;

    return \KatoSync\Utils\RangeFormatter::format_size_range($size_min, $size_max, 'sqft');
  }

  /**
   * Get property price range (formatted for display)
   * @deprecated 1.1.0 Use get_price_range_formatted() or compute inline from raw properties
   */
  public function get_price_range(): string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Use get_price_range_formatted() or compute inline from raw properties');
    }
    $price_min = $this->price_min ?? null;
    $price_max = $this->price_max ?? null;
    $price_type = $this->price_type ?? 'poa';

    if ($price_type === 'poa' || (!$price_min && !$price_max)) {
      return 'POA';
    }

    if ($price_min && $price_max) {
      if ($price_min === $price_max) {
        return $this->format_price($price_min, $price_type);
      } else {
        // For ranges, format differently to avoid repeating "per sq ft"
        if ($price_type === 'per_sqft') {
          return '£' . \number_format((float)$price_min, 2) . ' - ' . '£' . \number_format((float)$price_max, 2) . '/sqft';
        }
        return '£' . \number_format((float)$price_min, 2) . ' - ' . '£' . \number_format((float)$price_max, 2);
      }
    } elseif ($price_min) {
      $suffix = $price_type === 'per_sqft' ? '/sqft' : '';
      return '£' . \number_format((float)$price_min, 2) . $suffix;
    }

    return '';
  }

  /**
   * Get price range formatted with new range logic
   */
  public function get_price_range_formatted(): array {
    $price_min = $this->price_min ?? null;
    $price_max = $this->price_max ?? null;
    $price_type = $this->price_type ?? 'per_sqft';

    return \KatoSync\Utils\RangeFormatter::format_price_range($price_min, $price_max, $price_type);
  }

  /**
   * Get rent range formatted with new range logic
   */
  public function get_rent_range_formatted(): array {
    // Use the RangeFormatter to get rent data from property data
    $property_data = [
      'floor_units' => $this->floor_units ?? [],
      'rent_min' => $this->rent_min ?? null,
      'rent_max' => $this->rent_max ?? null,
      'price_min' => $this->price_min ?? null,
      'price_max' => $this->price_max ?? null,
    ];

    return \KatoSync\Utils\RangeFormatter::get_property_range_data($property_data, 'rent');
  }

  /**
   * Get total property size (not unit ranges)
   */
  public function get_total_property_size_formatted(): array {
    $total_size_min = $this->total_property_size_min ?? null;
    $total_size_max = $this->total_property_size_max ?? null;

    // Fallback to single total property size value
    if (!$total_size_min && !$total_size_max) {
      $total_size = $this->total_property_size_sqft ?? $this->total_property_size ?? null;
      if ($total_size) {
        $total_size_min = $total_size_max = (int) $total_size;
      }
    }

    return \KatoSync\Utils\RangeFormatter::format_size_range($total_size_min, $total_size_max, 'sqft');
  }

  /**
   * Get unit size range (based on individual units, not total property)
   */
  public function get_unit_size_range_formatted(): array {
    $property_data = [
      'floor_units' => $this->floor_units ?? [],
      'size_min' => $this->size_min ?? null,
      'size_max' => $this->size_max ?? null,
    ];

    return \KatoSync\Utils\RangeFormatter::get_property_range_data($property_data, 'unit_size');
  }

  /**
   * Get size range as numeric values for filtering
   */
  public function get_size_numeric(): array {
    return array(
      'min' => $this->size_min ?? null,
      'max' => $this->size_max ?? null
    );
  }

  /**
   * Get price range as numeric values for filtering
   */
  public function get_price_numeric(): array {
    return array(
      'min' => $this->price_min ?? null,
      'max' => $this->price_max ?? null,
      'type' => $this->price_type ?? 'poa'
    );
  }

  /**
   * Format price based on type
   */
  private function format_price(int $price, string $type): string {
    switch ($type) {
      case 'per_sqft':
        return '£' . \number_format($price) . ' per sq ft';
      case 'per_annum':
        return '£' . \number_format($price) . ' per annum';
      case 'total':
        return '£' . \number_format($price);
      default:
        return '£' . \number_format($price);
    }
  }

  /**
   * Get property description
   */
  public function get_description(): string {
    return $this->specification_description ??
      $this->location ??
      $this->description ??
      $this->post->post_content;
  }

  /**
   * Get property type
   */
  public function get_type(): string {
    $types = $this->types ?? [];
    if (is_array($types) && !empty($types)) {
      return implode(', ', $types);
    }

    return $this->property_type ?? '';
  }

  /**
   * Get property status
   */
  public function get_status(): string {
    return $this->status ?? 'Available';
  }

  /**
   * Get property URL
   */
  public function get_url(): string {
    return \get_permalink($this->post_id);
  }

  /**
   * Get property postcode
   */
  public function get_postcode(): string {
    return $this->postcode ?? '';
  }

  /**
   * Get property outward postcode (first part of postcode)
   */
  public function get_outward_postcode(): string {
    $postcode = $this->get_postcode();
    if (empty($postcode)) {
      return '';
    }

    // Extract the outward part (before the space)
    $parts = explode(' ', trim($postcode));
    return $parts[0] ?? '';
  }

  /**
   * Get property images
   */
  public function get_images(): array {
    // Use the ImageProcessor for the new system
    return \KatoSync\Sync\ImageProcessor::get_property_images($this->post_id);
  }

  /**
   * Get primary agent
   */
  public function get_agent(): array {
    // For new system, try to get agent data
    $agent_data = $this->get_agent_data();
    return $agent_data['primary_agent'] ?? [];
  }

  /**
   * Get key selling points
   */
  public function get_key_selling_points(): array {
    $points = $this->key_selling_points ?? [];
    return is_array($points) ? $points : [];
  }

  /**
   * Get floor units
   */
  public function get_floor_units(): array {
    $units = $this->floor_units ?? [];
    return is_array($units) ? $units : [];
  }

  /**
   * Get files/documents
   */
  public function get_files(): array {
    $files = $this->files ?? [];
    return is_array($files) ? $files : [];
  }

  /**
   * Get coordinates
   */
  public function get_coordinates(): array {
    return [
      'lat' => $this->latitude ?? $this->lat ?? '',
      'lon' => $this->longitude ?? $this->lon ?? ''
    ];
  }

  /**
   * Check if property is featured
   */
  public function is_featured(): bool {
    $featured = $this->featured ?? '';
    return $featured === 't' || $featured === true || $featured === '1';
  }

  /**
   * Check if property is fitted
   */
  public function is_fitted(): bool {
    $fitted = $this->fitted ?? '';
    return $fitted === 't' || $fitted === true || $fitted === '1';
  }

  /**
   * Get all property data
   */
  public function get_all_data(): array {
    if ($this->cached_data !== null) {
      return $this->cached_data;
    }

    $this->cached_data = [
      'basic_info' => $this->get_basic_info(),
      'location' => $this->get_location_data(),
      'pricing' => $this->get_pricing_data(),
      'specifications' => $this->get_specifications(),
      'media' => $this->get_media_data(),
      'agent' => $this->get_agent_data(),
      'floor_units' => $this->get_floor_units_data(),
      'marketing' => $this->get_marketing_data(),
      'meta' => $this->get_meta_data()
    ];

    return $this->cached_data;
  }

  /**
   * Get property data as a flat array for easy access
   */
  public function to_array(): array {
    $data = $this->get_all_data();
    $flat = [];

    // Flatten the nested structure
    foreach ($data as $section => $section_data) {
      if (is_array($section_data)) {
        foreach ($section_data as $key => $value) {
          $flat[$key] = $value;
        }
      }
    }

    // Add WordPress post properties
    $flat['ID'] = $this->post_id;
    $flat['title'] = $this->post->post_title;
    $flat['content'] = $this->post->post_content;
    $flat['excerpt'] = $this->post->post_excerpt;
    $flat['status'] = $this->post->post_status;
    $flat['date'] = $this->post->post_date;
    $flat['modified'] = $this->post->post_modified;
    $flat['slug'] = $this->post->post_name;
    $flat['url'] = \get_permalink($this->post_id);

    return $flat;
  }

  /**
   * Get all available properties as a comprehensive array
   */
  public function get_all_properties(): array {
    $properties = [];

    // Add WordPress post properties
    $properties['ID'] = $this->post_id;
    $properties['title'] = $this->post->post_title;
    $properties['content'] = $this->post->post_content;
    $properties['excerpt'] = $this->post->post_excerpt;
    $properties['status'] = $this->post->post_status;
    $properties['date'] = $this->post->post_date;
    $properties['modified'] = $this->post->post_modified;
    $properties['slug'] = $this->post->post_name;
    $properties['url'] = \get_permalink($this->post_id);
    $properties['edit_url'] = \get_edit_post_link($this->post_id);

    // Add all meta fields
    $all_meta = \get_post_meta($this->post_id);
    foreach ($all_meta as $key => $values) {
      if (strpos($key, '_kato_sync_') === 0) {
        $field_name = str_replace('_kato_sync_', '', $key);
        $value = $values[0] ?? null;

        // Special handling for images field
        if ($field_name === 'images') {
          $properties[$field_name] = \KatoSync\Sync\ImageProcessor::get_property_images($this->post_id);
          continue;
        }

        // Try to decode JSON if it looks like JSON
        if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
          $decoded = json_decode($value, true);
          if (json_last_error() === JSON_ERROR_NONE) {
            $properties[$field_name] = $decoded;
          } else {
            $properties[$field_name] = $value;
          }
        } else {
          $properties[$field_name] = $value;
        }
      }
    }

    return $properties;
  }

  /**
   * Get basic property information
   */
  private function get_basic_info(): array {
    return array(
      'id' => $this->get_meta_value('id'),
      'external_id' => $this->get_meta_value('external_id'),
      'object_id' => $this->get_meta_value('object_id'),
      'name' => $this->get_meta_value('name'),
      'title' => $this->post->post_title,
      'property_type' => $this->get_meta_value('property_type'),
      'status' => $this->get_meta_value('status'),
      'description' => $this->get_meta_value('description'),
      'features' => $this->get_meta_value('features'),
      'created_at' => $this->get_meta_value('created_at'),
      'last_updated' => $this->get_meta_value('last_updated'),
      'imported_at' => $this->get_meta_value('imported_at'),
      'featured' => $this->get_meta_value('featured'),
      'fitted' => $this->get_meta_value('fitted'),
      'fitted_comment' => $this->get_meta_value('fitted_comment'),
      'specification_summary' => $this->get_meta_value('specification_summary'),
      'specification_promo' => $this->get_meta_value('specification_promo'),
      'specification_description' => $this->get_meta_value('specification_description'),
      'url' => $this->get_meta_value('url'),
      'particulars_url' => $this->get_meta_value('particulars_url'),
      'togc' => $this->get_meta_value('togc'),
      'sale_type' => $this->get_meta_value('sale_type'),
      'tenancy_passing_giy' => $this->get_meta_value('tenancy_passing_giy'),
      'tenancy_passing_niy' => $this->get_meta_value('tenancy_passing_niy'),
      'turnover_pa' => $this->get_meta_value('turnover_pa'),
      'tenancy_status' => $this->get_meta_value('tenancy_status'),
      'class_of_use' => $this->get_meta_value('class_of_use'),
      'legal_fees_applicable' => $this->get_meta_value('legal_fees_applicable'),
      'lease_length' => $this->get_meta_value('lease_length'),
      'protected_act' => $this->get_meta_value('protected_act'),
      'insurance_type' => $this->get_meta_value('insurance_type'),
      'availability_reasons' => $this->get_meta_value('availability_reasons'),
      'shop_frontage_ft' => $this->get_meta_value('shop_frontage_ft'),
      'shop_frontage_m' => $this->get_meta_value('shop_frontage_m'),
      'shop_frontage_inches' => $this->get_meta_value('shop_frontage_inches'),
      'travel_times' => $this->get_meta_value('travel_times'),
      'tags' => $this->get_meta_value('tags'),
    );
  }

  /**
   * Get location data
   */
  private function get_location_data(): array {
    $postcode = $this->get_meta_value('postcode');

    return array(
      'address1' => $this->get_meta_value('address1'),
      'address2' => $this->get_meta_value('address2'),
      'city' => $this->get_meta_value('city'),
      'town' => $this->get_meta_value('town'),
      'county' => $this->get_meta_value('county'),
      'postcode' => $postcode,
      'postcode_full' => $postcode,
      'postcode_first_part' => $this->get_postcode_first_part($postcode),
      'latitude' => $this->get_meta_value('latitude'),
      'longitude' => $this->get_meta_value('longitude'),
      'lat' => $this->get_meta_value('lat'),
      'lon' => $this->get_meta_value('lon'),
      'location' => $this->get_meta_value('location'),
      'street_view_data' => $this->get_meta_value('street_view_data'),
      'submarkets' => $this->get_meta_value('submarkets'),
    );
  }

  /**
   * Get pricing data
   */
  private function get_pricing_data(): array {
    return array(
      'price' => $this->get_meta_value('price'),
      'rent' => $this->get_meta_value('rent'),
      'price_per_sqft' => $this->get_meta_value('price_per_sqft'),
      'price_per_sqft_min' => $this->get_meta_value('price_per_sqft_min'),
      'price_per_sqft_max' => $this->get_meta_value('price_per_sqft_max'),
      'total_price' => $this->get_meta_value('total_price'),
      'total_monthly_min' => $this->get_meta_value('total_monthly_min'),
      'total_monthly_max' => $this->get_meta_value('total_monthly_max'),
      'total_yearly_min' => $this->get_meta_value('total_yearly_min'),
      'total_yearly_max' => $this->get_meta_value('total_yearly_max'),
      'turnover' => $this->get_meta_value('turnover'),
      'profit_gross' => $this->get_meta_value('profit_gross'),
      'profit_net' => $this->get_meta_value('profit_net'),
      'initial_yield' => $this->get_meta_value('initial_yield'),
      'premium' => $this->get_meta_value('premium'),
      'premium_nil' => $this->get_meta_value('premium_nil'),
      'parking_ratio' => $this->get_meta_value('parking_ratio'),
      'rent_components' => $this->get_meta_value('rent_components'),
      'service_charge' => $this->get_meta_value('service_charge'),
    );
  }

  /**
   * Get specifications data
   */
  private function get_specifications(): array {
    return array(
      'size_sqft' => $this->get_meta_value('size_sqft'),
      'size_from' => $this->get_meta_value('size_from'),
      'size_to' => $this->get_meta_value('size_to'),
      'total_property_size' => $this->get_meta_value('total_property_size'),
      'total_property_size_metric' => $this->get_meta_value('total_property_size_metric'),
      'area_size_unit' => $this->get_meta_value('area_size_unit'),
      'area_size_type' => $this->get_meta_value('area_size_type'),
      'size_from_sqft' => $this->get_meta_value('size_from_sqft'),
      'size_to_sqft' => $this->get_meta_value('size_to_sqft'),
      'size_measure' => $this->get_meta_value('size_measure'),
      'land_size_from' => $this->get_meta_value('land_size_from'),
      'land_size_to' => $this->get_meta_value('land_size_to'),
      'land_size_metric' => $this->get_meta_value('land_size_metric'),
      'travel_times' => $this->get_meta_value('travel_times'),
    );
  }

  /**
   * Get media data
   */
  private function get_media_data(): array {
    // Use the ImageProcessor to get the correct images (local or external)
    $images = \KatoSync\Sync\ImageProcessor::get_property_images($this->post_id);

    return array(
      'images' => $images,
      'original_images' => $this->get_meta_value('original_images'),
      'files' => $this->get_meta_value('files'),
      'epcs' => $this->get_meta_value('epcs'),
      'videos' => $this->get_meta_value('videos'),
      'videos_detail' => $this->get_meta_value('videos_detail'),
    );
  }

  /**
   * Get agent data
   */
  private function get_agent_data(): array {
    $contacts = $this->get_meta_value('contacts');

    return array(
      'agent_name' => $this->get_meta_value('agent_name'),
      'agent_email' => $this->get_meta_value('agent_email'),
      'agent_phone' => $this->get_meta_value('agent_phone'),
      'agent_company' => $this->get_meta_value('agent_company'),
      'contacts' => $contacts,
      'joint_agents' => $this->get_meta_value('joint_agents'),
    );
  }

  /**
   * Get floor units data
   */
  private function get_floor_units_data(): array {
    $floor_units = $this->get_meta_value('floor_units');

    return array(
      'floor_units' => $floor_units,
    );
  }

  /**
   * Get marketing data
   */
  private function get_marketing_data(): array {
    return array(
      'marketing_title_1' => $this->get_meta_value('marketing_title_1'),
      'marketing_title_2' => $this->get_meta_value('marketing_title_2'),
      'marketing_title_3' => $this->get_meta_value('marketing_title_3'),
      'marketing_title_4' => $this->get_meta_value('marketing_title_4'),
      'marketing_title_5' => $this->get_meta_value('marketing_title_5'),
      'marketing_text_1' => $this->get_meta_value('marketing_text_1'),
      'marketing_text_2' => $this->get_meta_value('marketing_text_2'),
      'marketing_text_3' => $this->get_meta_value('marketing_text_3'),
      'marketing_text_4' => $this->get_meta_value('marketing_text_4'),
      'marketing_text_5' => $this->get_meta_value('marketing_text_5'),
      'marketing_title_transport' => $this->get_meta_value('marketing_title_transport'),
      'marketing_text_transport' => $this->get_meta_value('marketing_text_transport'),
      'key_selling_points' => $this->get_meta_value('key_selling_points'),
    );
  }

  /**
   * Get meta data
   */
  private function get_meta_data(): array {
    return array(
      'id' => $this->post_id,
      'post_status' => $this->post->post_status,
      'post_date' => $this->post->post_date,
      'post_modified' => $this->post->post_modified,
      'post_name' => $this->post->post_name,
    );
  }

  /**
   * Get meta value with fallback to "No data"
   */
  private function get_meta_value(string $meta_key, $default = 'No data') {
    $meta_field = '_kato_sync_' . $meta_key;
    $value = \get_post_meta($this->post_id, $meta_field, true);


    if (empty($value)) {
      return $default;
    }

    // Try to decode JSON if it looks like JSON
    if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }

    return $value;
  }

  /**
   * Get postcode first part
   */
  private function get_postcode_first_part(string $postcode): string {
    if (empty($postcode)) {
      return 'No data';
    }

    $parts = explode(' ', $postcode);
    return $parts[0] ?? 'No data';
  }

  /**
   * Get rent components data
   * @deprecated 1.1.0 Access $this->rent_components directly
   */
  public function get_rent_components(): ?array {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Access $this->rent_components directly');
    }
    $rent_components = $this->rent_components;

    if (empty($rent_components)) {
      return null;
    }

    // Ensure it's an array
    if (!is_array($rent_components)) {
      return null;
    }

    return $rent_components;
  }

  /**
   * Get rent from value
   * @deprecated 1.1.0 Access $this->rent_components['from']
   */
  public function get_rent_from(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use rent_components['from']");
    }
    $components = $this->get_rent_components();
    return $components['from'] ?? null;
  }

  /**
   * Get rent to value
   * @deprecated 1.1.0 Access $this->rent_components['to']
   */
  public function get_rent_to(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use rent_components['to']");
    }
    $components = $this->get_rent_components();
    return $components['to'] ?? null;
  }

  /**
   * Get rent metric
   * @deprecated 1.1.0 Access $this->rent_components['metric']
   */
  public function get_rent_metric(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use rent_components['metric']");
    }
    $components = $this->get_rent_components();
    return $components['metric'] ?? null;
  }

  /**
   * Get rent rates
   * @deprecated 1.1.0 Access $this->rent_components['rates']
   */
  public function get_rent_rates(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use rent_components['rates']");
    }
    $components = $this->get_rent_components();
    return $components['rates'] ?? null;
  }

  /**
   * Get rent comment
   * @deprecated 1.1.0 Access $this->rent_components['comment']
   */
  public function get_rent_comment(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use rent_components['comment']");
    }
    $components = $this->get_rent_components();
    return $components['comment'] ?? null;
  }

  /**
   * Check if rent is on application
   * @deprecated 1.1.0 Access $this->rent_components['on_application']
   */
  public function is_rent_on_application(): bool {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use rent_components['on_application']");
    }
    $components = $this->get_rent_components();
    return ($components['on_application'] ?? '0') === '1';
  }

  /**
   * Get service charge data
   * @deprecated 1.1.0 Access $this->service_charge directly
   */
  public function get_service_charge(): ?array {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Access $this->service_charge directly');
    }
    $service_charge = $this->service_charge;

    if (empty($service_charge)) {
      return null;
    }

    // Ensure it's an array
    if (!is_array($service_charge)) {
      return null;
    }

    return $service_charge;
  }

  /**
   * Get service charge amount
   * @deprecated 1.1.0 Access $this->service_charge['service_charge']
   */
  public function get_service_charge_amount(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use service_charge['service_charge']");
    }
    $service_charge = $this->get_service_charge();
    return $service_charge['service_charge'] ?? null;
  }

  /**
   * Get service charge period
   * @deprecated 1.1.0 Access $this->service_charge['service_charge_period']
   */
  public function get_service_charge_period(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use service_charge['service_charge_period']");
    }
    $service_charge = $this->get_service_charge();
    return $service_charge['service_charge_period'] ?? null;
  }

  /**
   * Get service charge text
   * @deprecated 1.1.0 Access $this->service_charge['service_charge_text']
   */
  public function get_service_charge_text(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', "Use service_charge['service_charge_text']");
    }
    $service_charge = $this->get_service_charge();
    return $service_charge['service_charge_text'] ?? null;
  }

  /**
   * Get service charge rates (similar to get_rent_rates for consistency)
   * @deprecated 1.1.0 Compute inline from raw properties
   */
  public function get_service_charge_rates(): ?string {
    if (function_exists('_deprecated_function')) {
      \_deprecated_function(__METHOD__, '1.1.0', 'Compute inline from raw properties');
    }
    $service_charge = $this->get_service_charge();
    $amount = $service_charge['service_charge'] ?? null;
    $period = $service_charge['service_charge_period'] ?? null;

    if (empty($amount)) {
      return null;
    }

    if (!empty($period)) {
      return $amount . ' / ' . $period;
    }

    return (string)$amount;
  }

  /**
   * Debug method to see the raw service charge data structure
   */
  public function debug_service_charge_data(): array {
    $raw_service_charge = $this->get_meta_value('service_charge');
    $raw_period = $this->get_meta_value('service_charge_period');
    $raw_text = $this->get_meta_value('service_charge_text');

    return [
      'raw_service_charge' => $raw_service_charge,
      'raw_period' => $raw_period,
      'raw_text' => $raw_text,
      'processed_service_charge' => $this->get_service_charge(),
      'processed_period' => $this->get_service_charge_period(),
      'processed_text' => $this->get_service_charge_text(),
      'rates' => $this->get_service_charge_rates()
    ];
  }

  /**
   * Get property by ID
   */
  public static function get_by_id(int $post_id): ?self {
    try {
      return new self($post_id);
    } catch (\InvalidArgumentException $e) {
      // Log the error for debugging
      if (defined('WP_DEBUG') && WP_DEBUG) {
        \error_log('Kato_Property::get_by_id() failed for post ID ' . $post_id . ': ' . $e->getMessage());
      }
      return null;
    }
  }
}
