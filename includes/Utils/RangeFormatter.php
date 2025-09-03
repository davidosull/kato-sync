<?php

namespace KatoSync\Utils;

/**
 * Range Formatter Utility
 *
 * Handles display formatting for ranges where min/max values should be shown
 * as single values when they match, but preserve both values for filtering
 */
class RangeFormatter {

  /**
   * Format a range for display
   * Shows single value when min = max, but preserves both values for filtering
   *
   * @param float|int|null $min Minimum value
   * @param float|int|null $max Maximum value
   * @param string $unit Unit suffix (e.g., 'sqft', '/sqft')
   * @param string $currency Currency prefix for prices (e.g., '£')
   * @param int $decimals Number of decimal places
   * @return array Array with 'display' and 'values' keys
   */
  public static function format_range($min, $max, string $unit = '', string $currency = '', int $decimals = 0): array {
    // Handle null/empty values
    if (empty($min) && empty($max)) {
      return [
        'display' => '',
        'values' => ['min' => null, 'max' => null],
        'is_single' => true
      ];
    }

    // Ensure numeric values
    $min = $min ? (float) $min : null;
    $max = $max ? (float) $max : null;

    // Use available value if only one is set
    if ($min && !$max) {
      $max = $min;
    } elseif ($max && !$min) {
      $min = $max;
    }

    $is_single = ($min === $max);

    // Format the display string
    if ($is_single) {
      $formatted_value = $currency . number_format($min, $decimals);
      $display = $formatted_value . $unit;
    } else {
      $formatted_min = $currency . number_format($min, $decimals);
      $formatted_max = $currency . number_format($max, $decimals);
      $display = $formatted_min . ' - ' . $formatted_max . $unit;
    }

    return [
      'display' => $display,
      'values' => ['min' => $min, 'max' => $max],
      'is_single' => $is_single
    ];
  }

  /**
   * Format price range specifically
   *
   * @param float|int|null $min
   * @param float|int|null $max
   * @param string $type Price type (per_sqft, per_annum, total, poa)
   * @param string $currency
   * @return array
   */
  public static function format_price_range($min, $max, string $type = 'per_sqft', string $currency = '£'): array {
    if ($type === 'poa' || (empty($min) && empty($max))) {
      return [
        'display' => 'POA',
        'values' => ['min' => null, 'max' => null],
        'is_single' => true,
        'type' => $type
      ];
    }

    $unit = '';
    $decimals = 2;

    switch ($type) {
      case 'per_sqft':
        $unit = '/sqft';
        break;
      case 'per_annum':
        $unit = ' per annum';
        break;
      case 'total':
        $unit = '';
        break;
    }

    $result = self::format_range($min, $max, $unit, $currency, $decimals);
    $result['type'] = $type;

    return $result;
  }

  /**
   * Format size range specifically
   *
   * @param float|int|null $min
   * @param float|int|null $max
   * @param string $unit
   * @return array
   */
  public static function format_size_range($min, $max, string $unit = 'sqft'): array {
    if (empty($min) && empty($max)) {
      return [
        'display' => '',
        'values' => ['min' => null, 'max' => null],
        'is_single' => true
      ];
    }

    // For size, we don't want decimals
    $result = self::format_range($min, $max, ' ' . $unit, '', 0);

    return $result;
  }

  /**
   * Get range data for a property field
   * Handles various field name patterns and calculates correct ranges
   *
   * @param array $property_data Property data array
   * @param string $field_type Type of field ('price', 'rent', 'size', 'unit_size', 'property_size')
   * @return array
   */
  public static function get_property_range_data(array $property_data, string $field_type): array {
    switch ($field_type) {
      case 'price':
        return self::get_price_range_data($property_data);

      case 'rent':
        return self::get_rent_range_data($property_data);

      case 'size':
        return self::get_size_range_data($property_data);

      case 'unit_size':
        return self::get_unit_size_range_data($property_data);

      case 'property_size':
        return self::get_property_size_range_data($property_data);

      default:
        return [
          'display' => '',
          'values' => ['min' => null, 'max' => null],
          'is_single' => true
        ];
    }
  }

  /**
   * Get price range data from property data
   */
  private static function get_price_range_data(array $property_data): array {
    $price_min = $property_data['price_min'] ?? null;
    $price_max = $property_data['price_max'] ?? null;
    $price_type = $property_data['price_type'] ?? 'per_sqft';

    return self::format_price_range($price_min, $price_max, $price_type);
  }

  /**
   * Get rent range data from property data
   */
  private static function get_rent_range_data(array $property_data): array {
    // Look for rent data in floor units first, then fallback to main property
    $floor_units = $property_data['floor_units'] ?? [];

    if (!empty($floor_units)) {
      $rent_values = [];
      foreach ($floor_units as $unit) {
        if (isset($unit['rent_sqft_numeric']) && $unit['rent_sqft_numeric'] > 0) {
          $rent_values[] = (float) $unit['rent_sqft_numeric'];
        }
      }

      if (!empty($rent_values)) {
        $min = min($rent_values);
        $max = max($rent_values);
        return self::format_price_range($min, $max, 'per_sqft');
      }
    }

    // Fallback to main property rent data
    $rent_min = $property_data['rent_min'] ?? $property_data['price_min'] ?? null;
    $rent_max = $property_data['rent_max'] ?? $property_data['price_max'] ?? null;

    return self::format_price_range($rent_min, $rent_max, 'per_sqft');
  }

  /**
   * Get overall size range data (based on floor units)
   */
  private static function get_size_range_data(array $property_data): array {
    // This should be based on floor units, not total property size
    $floor_units = $property_data['floor_units'] ?? [];

    if (!empty($floor_units)) {
      $size_values = [];
      foreach ($floor_units as $unit) {
        if (isset($unit['total_sqft_numeric']) && $unit['total_sqft_numeric'] > 0) {
          $size_values[] = (int) $unit['total_sqft_numeric'];
        }
      }

      if (!empty($size_values)) {
        $min = min($size_values);
        $max = max($size_values);
        return self::format_size_range($min, $max, 'sqft');
      }
    }

    // Fallback to stored size range
    $size_min = $property_data['size_min'] ?? null;
    $size_max = $property_data['size_max'] ?? null;

    return self::format_size_range($size_min, $size_max, 'sqft');
  }

  /**
   * Get unit size range data (same as size range - based on individual units)
   */
  private static function get_unit_size_range_data(array $property_data): array {
    // This is the same as get_size_range_data - both represent unit ranges
    return self::get_size_range_data($property_data);
  }

  /**
   * Get property size range data (based on total property size, not units)
   */
  private static function get_property_size_range_data(array $property_data): array {
    // This should represent the total property size, not unit ranges
    $total_size = $property_data['total_property_size_sqft'] ??
      $property_data['total_property_size'] ?? null;

    if ($total_size) {
      $total_size = (int) $total_size;
      return self::format_size_range($total_size, $total_size, 'sqft');
    }

    return self::format_size_range(null, null, 'sqft');
  }

  /**
   * Identify fields that could benefit from range logic
   *
   * @param array $property_data
   * @return array Array of field recommendations
   */
  public static function identify_range_candidates(array $property_data): array {
    $candidates = [];

    // Check for service charge ranges
    if (isset($property_data['service_charge'])) {
      $sc = $property_data['service_charge'];
      if (is_array($sc) && isset($sc['min'], $sc['max'])) {
        $candidates[] = [
          'field' => 'service_charge',
          'current' => $sc,
          'recommendation' => 'Apply range formatting to service charge'
        ];
      }
    }

    // Check for business rates ranges
    if (isset($property_data['business_rates'])) {
      $br = $property_data['business_rates'];
      if (is_array($br) && isset($br['min'], $br['max'])) {
        $candidates[] = [
          'field' => 'business_rates',
          'current' => $br,
          'recommendation' => 'Apply range formatting to business rates'
        ];
      }
    }

    // Check for parking costs
    if (isset($property_data['parking_cost'])) {
      $pc = $property_data['parking_cost'];
      if (is_array($pc) && isset($pc['min'], $pc['max'])) {
        $candidates[] = [
          'field' => 'parking_cost',
          'current' => $pc,
          'recommendation' => 'Apply range formatting to parking costs'
        ];
      }
    }

    // Check floor count ranges
    if (isset($property_data['floors'])) {
      $floors = $property_data['floors'];
      if (is_array($floors) && isset($floors['min'], $floors['max'])) {
        $candidates[] = [
          'field' => 'floors',
          'current' => $floors,
          'recommendation' => 'Apply range formatting to floor counts'
        ];
      }
    }

    // Check availability date ranges
    if (isset($property_data['availabilities'])) {
      $availabilities = $property_data['availabilities'];
      if (is_array($availabilities)) {
        $dates = [];
        foreach ($availabilities as $avail) {
          if (isset($avail['date'])) {
            $dates[] = $avail['date'];
          }
        }
        if (count($dates) > 1) {
          $candidates[] = [
            'field' => 'availability_dates',
            'current' => $dates,
            'recommendation' => 'Consider date range formatting for availability periods'
          ];
        }
      }
    }

    return $candidates;
  }
}
