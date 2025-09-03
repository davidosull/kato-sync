<?php

namespace KatoSync\Sync;

class Normalizer {

  public static function toSnakeCaseKeys(array $data): array {
    $result = [];
    foreach ($data as $key => $value) {
      $snake = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', preg_replace('/([a-z])([A-Z])/', '$1_$2', (string)$key)));
      if (is_array($value)) {
        $result[$snake] = self::toSnakeCaseKeys($value);
      } else {
        $result[$snake] = $value;
      }
    }
    return $result;
  }

  public static function triStateBool($value): ?bool {
    if (is_bool($value)) return $value;
    if ($value === null || $value === '') return null;
    $v = strtolower((string)$value);
    if (in_array($v, ['1', 'true', 'yes', 'y', 't'])) return true;
    if (in_array($v, ['0', 'false', 'no', 'n', 'f'])) return false;
    return null;
  }

  public static function parseNumber($value): ?float {
    if ($value === null || $value === '') return null;
    if (is_numeric($value)) return (float)$value;
    $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
    if ($clean === '' || $clean === '-' || $clean === '.') return null;
    return is_numeric($clean) ? (float)$clean : null;
  }

  public static function parseInt($value): ?int {
    $num = self::parseNumber($value);
    return $num !== null ? (int)round($num) : null;
  }

  public static function normalize(array $raw): array {
    $o = self::toSnakeCaseKeys($raw);

    $external_id = $o['id'] ?? ($o['external_id'] ?? null);
    $created_at = $o['created'] ?? ($o['created_at'] ?? null);
    $last_updated = $o['last_updated'] ?? ($o['updated'] ?? null);

    $name = $o['name'] ?? null;
    $status = $o['status'] ?? null;
    $types = [];
    if (!empty($o['types'])) {
      if (is_array($o['types'])) {
        if (isset($o['types']['type']) && is_array($o['types']['type'])) {
          foreach ($o['types']['type'] as $t) {
            if (is_array($t) && isset($t['name'])) $types[] = (string)$t['name'];
            elseif (is_string($t)) $types[] = $t;
          }
        } else {
          foreach ($o['types'] as $t) {
            if (is_array($t) && isset($t['name'])) $types[] = (string)$t['name'];
            elseif (is_string($t)) $types[] = $t;
          }
        }
      } elseif (is_string($o['types'])) {
        $types[] = $o['types'];
      }
    }

    $county = $o['county'] ?? ($o['location_county'] ?? null);
    $postcode = $o['postcode'] ?? null;
    $outward = null;
    if ($postcode) {
      $parts = explode(' ', $postcode);
      $outward = $parts[0] ?? null;
    }

    $lat = $o['lat'] ?? ($o['latitude'] ?? null);
    $lng = $o['lng'] ?? ($o['longitude'] ?? ($o['lon'] ?? null));

    $size_min = self::parseInt($o['size_min'] ?? ($o['total_size_min'] ?? ($o['size_from'] ?? null)));
    $size_max = self::parseInt($o['size_max'] ?? ($o['total_size_max'] ?? ($o['size_to'] ?? null)));
    $area_unit = $o['area_size_unit'] ?? ($o['unit'] ?? 'sqft');
    $total_sqft = self::parseInt($o['total_size_sqft'] ?? ($o['size_sqft'] ?? null));

    $price_min = self::parseNumber($o['price_min'] ?? null);
    $price_max = self::parseNumber($o['price_max'] ?? null);
    $price_type = $o['price_type'] ?? ($o['price_metric'] ?? null);

    // Rent components (prefer nested structure)
    $rent_components = $o['rent_components'] ?? [];
    if (is_array($rent_components)) {
      $rent_components = self::toSnakeCaseKeys($rent_components);
    }
    $rent = [
      'from' => self::parseNumber($rent_components['from'] ?? ($o['rent_from'] ?? null)),
      'to' => self::parseNumber($rent_components['to'] ?? ($o['rent_to'] ?? null)),
      'metric' => $rent_components['metric'] ?? ($o['rent_metric'] ?? null),
      'rates' => $rent_components['rates'] ?? ($o['rent_rates'] ?? null),
      'comment' => $rent_components['comment'] ?? ($o['rent_comment'] ?? null),
      'on_application' => self::triStateBool($rent_components['on_application'] ?? ($o['rent_on_application'] ?? null))
    ];

    // Service charge (prefer nested structure)
    $sc_block = $o['service_charge'] ?? [];
    if (is_array($sc_block)) {
      $sc_block = self::toSnakeCaseKeys($sc_block);
    }
    $service = [
      'amount' => self::parseNumber($sc_block['service_charge'] ?? ($o['service_charge'] ?? null)),
      'period' => $sc_block['service_charge_period'] ?? ($o['service_charge_period'] ?? null),
      'text' => $sc_block['service_charge_text'] ?? ($o['service_charge_text'] ?? null),
      'rates' => $sc_block['service_charge_rates'] ?? ($o['service_charge_rates'] ?? null)
    ];

    // Units
    $units = [];
    $rawUnits = $o['units'] ?? ($o['floor_units'] ?? []);
    if (is_array($rawUnits)) {
      foreach ($rawUnits as $u) {
        if (!is_array($u)) continue;
        $u = self::toSnakeCaseKeys($u);
        $unit_id = $u['unit_id'] ?? ($u['id'] ?? ($u['meta_id'] ?? null));
        if (!$unit_id) {
          $fingerprint = ($u['floor'] ?? '') . '|' . ($u['level'] ?? '') . '|' . ($u['size_sqft'] ?? ($u['size'] ?? '')) . '|' . ($u['rent_metric'] ?? '') . '|' . ($u['status'] ?? '') . '|' . ($u['sort_order'] ?? '');
          $unit_id = substr(sha1($fingerprint), 0, 16);
        }
        $units[] = [
          'unit_external_id' => (string)$unit_id,
          'floor_label' => $u['floor'] ?? ($u['level'] ?? ($u['floorunit'] ?? null)),
          'size_sqft' => $u['size_sqft'] ?? ($u['size'] ?? null), // Keep as string
          'rent_min' => $u['rent_min'] ?? ($u['rent_price'] ?? null), // Keep as string
          'rent_max' => $u['rent_max'] ?? ($u['rent_price'] ?? null), // Keep as string
          'rent_metric' => $u['rent_metric'] ?? null,
          'rates_text' => $u['rates_sqft'] ?? ($u['rates_text'] ?? null),
          'status' => $u['status'] ?? null,
          'availability_date' => $u['availability_date'] ?? null,
          'sort_order' => isset($u['sort_order']) ? (int)$u['sort_order'] : null,
          'raw' => $u,
        ];
      }
    }

    // Media
    $images = [];
    $brochures = [];
    $floorplans = [];
    $videos = [];
    $rawImages = $o['images'] ?? [];
    if (is_array($rawImages)) {
      foreach ($rawImages as $i) {
        if (is_string($i)) {
          // Accept simple URL strings as images
          $images[] = [
            'url' => $i,
            'alt' => null,
            'sort_order' => null,
            'raw' => ['url' => $i]
          ];
          continue;
        }
        if (!is_array($i)) continue;
        $i = self::toSnakeCaseKeys($i);
        $images[] = [
          'url' => $i['url'] ?? ($i['src'] ?? null),
          'alt' => $i['alt'] ?? ($i['name'] ?? null),
          'sort_order' => isset($i['sort_order']) ? (int)$i['sort_order'] : null,
          'raw' => $i
        ];
      }
    }
    // Files routing by type (11 brochure, 15 floorplan)
    if (!empty($o['files'])) {
      $files = $o['files'];
      if (isset($files['file'])) {
        $files = $files['file'];
      }
      foreach ((array)$files as $f) {
        if (!is_array($f)) continue;
        $f = self::toSnakeCaseKeys($f);
        $entry = ['url' => $f['url'] ?? null, 'title' => $f['name'] ?? null, 'raw' => $f];
        if (($f['type'] ?? '') == '11') {
          $brochures[] = $entry;
        } elseif (($f['type'] ?? '') == '15') {
          $floorplans[] = $entry;
        }
      }
    }
    $rawBrochures = $o['brochures'] ?? [];
    foreach ((array)$rawBrochures as $b) {
      if (!is_array($b)) $b = ['url' => $b];
      $b = self::toSnakeCaseKeys($b);
      $brochures[] = ['url' => $b['url'] ?? null, 'title' => $b['title'] ?? null, 'raw' => $b];
    }
    $rawFloor = $o['floor_plans'] ?? [];
    foreach ((array)$rawFloor as $f) {
      if (!is_array($f)) $f = ['url' => $f];
      $f = self::toSnakeCaseKeys($f);
      $floorplans[] = ['url' => $f['url'] ?? null, 'title' => $f['title'] ?? null, 'raw' => $f];
    }
    $rawVideos = $o['videos'] ?? [];
    foreach ((array)$rawVideos as $v) {
      if (!is_array($v)) $v = ['url' => $v];
      $v = self::toSnakeCaseKeys($v);
      // Only add videos that have a URL
      if (!empty($v['url']) && trim($v['url']) !== '') {
        $videos[] = ['url' => $v['url'] ?? null, 'title' => $v['title'] ?? null, 'raw' => $v];
      }
    }

    // Contacts
    $contacts = [];
    $rawContacts = $o['contacts'] ?? ($o['agent'] ?? ($o['agents'] ?? []));
    foreach ((array)$rawContacts as $c) {
      if (!is_array($c)) continue;
      $c = self::toSnakeCaseKeys($c);
      $email = isset($c['email']) ? strtolower($c['email']) : null;
      $contacts[] = [
        'name' => $c['name'] ?? null,
        'email' => $email,
        'phone' => $c['phone'] ?? ($c['telephone'] ?? ($c['tel'] ?? null)),
        'company' => $c['company'] ?? ($c['office'] ?? null),
        'role' => $c['role'] ?? 'agent',
        'is_primary' => self::triStateBool($c['is_primary'] ?? null) ?? false,
        'sort_order' => isset($c['sort_order']) ? (int)$c['sort_order'] : null,
        'raw' => $c,
      ];
    }

    // Key selling points
    $key_selling_points = [];
    $rawKsp = $o['key_selling_points'] ?? [];
    if (is_array($rawKsp)) {
      foreach ($rawKsp as $ksp) {
        if (is_string($ksp) && !empty(trim($ksp))) {
          $key_selling_points[] = trim($ksp);
        }
      }
    }

    // Amenities and specifications
    $amenities_specs = [];
    $rawAmenities = $o['amenities_specifications'] ?? [];
    if (is_array($rawAmenities)) {
      foreach ($rawAmenities as $spec) {
        if (is_array($spec) && isset($spec['label']) && isset($spec['value'])) {
          $amenities_specs[] = [
            'label' => trim($spec['label']),
            'value' => trim($spec['value'])
          ];
        }
      }
    }

    // Availabilities
    $availabilities = [];
    $rawAvail = $o['availabilities'] ?? [];
    if (is_array($rawAvail)) {
      foreach ($rawAvail as $avail) {
        if (is_string($avail) && !empty(trim($avail))) {
          $availabilities[] = trim($avail);
        }
      }
    }

    // EPCs and Videos
    $epcs = [];
    $rawEpcs = $o['epcs'] ?? [];
    if (is_array($rawEpcs)) {
      foreach ($rawEpcs as $epc) {
        if (is_string($epc) && !empty(trim($epc))) {
          $epcs[] = trim($epc);
        }
      }
    }

    $videos_detail = [];
    $rawVidDetail = $o['videos_detail'] ?? [];
    if (is_array($rawVidDetail)) {
      foreach ($rawVidDetail as $vid) {
        if (is_string($vid) && !empty(trim($vid))) {
          $videos_detail[] = trim($vid);
        }
      }
    }

    $normalized = [
      'kato_meta' => [
        'external_id' => $external_id,
        'created_at' => $created_at,
        'imported_at' => null,
        'last_updated' => $last_updated,
        'payload_hash' => null,
        'schema_version' => 1,
        'is_archived' => false,
        'is_featured' => (bool) (self::triStateBool($o['featured'] ?? null) ?? false),
      ],
      'property' => [
        'name' => $name,
        'title' => $o['title'] ?? $name,
        'status' => $status,
        'types' => $types,
        'availabilities' => $availabilities,
        'specification_summary' => $o['specification_summary'] ?? null,
        'specification_promo' => $o['specification_promo'] ?? null,
        'description' => $o['specification_description'] ?? ($o['description'] ?? null),
        'features' => $o['features'] ?? null,
        'fitted' => self::triStateBool($o['fitted'] ?? null),
        'fitted_comment' => $o['fitted_comment'] ?? null,
        'property_type' => $o['property_type'] ?? null,
        'url' => $o['url'] ?? null,
        'particulars_url' => $o['particulars_url'] ?? null,
      ],
      'location' => [
        'name' => $name,
        'address1' => $o['address1'] ?? null,
        'address2' => $o['address2'] ?? null,
        'city' => $o['city'] ?? null,
        'town' => $o['town'] ?? null,
        'county' => $county,
        'postcode' => $postcode,
        'outward_postcode' => $outward,
        'lat' => $lat,
        'lng' => $lng,
        'location_text' => $o['location'] ?? null,
        'street_view' => isset($o['street_view_data']) && is_array($o['street_view_data']) ? self::toSnakeCaseKeys($o['street_view_data']) : null,
        'submarkets' => (function ($sm) {
          $out = [];
          if (empty($sm)) return $out;
          if (isset($sm['submarket'])) {
            $sm = $sm['submarket'];
          }
          foreach ((array)$sm as $s) {
            if (is_array($s) && isset($s['name'])) $out[] = $s['name'];
          }
          return $out;
        })($o['submarkets'] ?? null),
        'travel_times' => $o['travel_times'] ?? null,
      ],
      'pricing' => [
        'price_min' => $price_min,
        'price_max' => $price_max,
        'price_type' => $price_type,
        'price' => self::parseNumber($o['price'] ?? null),
        'total_price' => self::parseNumber($o['total_price'] ?? null),
        'price_per_sqft' => self::parseNumber($o['price_per_sqft'] ?? null),
        'price_per_sqft_min' => self::parseNumber($o['price_per_sqft_min'] ?? null),
        'price_per_sqft_max' => self::parseNumber($o['price_per_sqft_max'] ?? null),
        'total_monthly_min' => self::parseNumber($o['total_monthly_min'] ?? null),
        'total_monthly_max' => self::parseNumber($o['total_monthly_max'] ?? null),
        'total_yearly_min' => self::parseNumber($o['total_yearly_min'] ?? null),
        'total_yearly_max' => self::parseNumber($o['total_yearly_max'] ?? null),
        'rent' => $rent,
        'service_charge' => $service,
        'initial_yield' => self::parseNumber($o['initial_yield'] ?? null),
        'premium' => self::parseNumber($o['premium'] ?? null),
        'premium_nil' => self::triStateBool($o['premium_nil'] ?? null),
        'parking_ratio' => $o['parking_ratio'] ?? null,
      ],
      'business_financial' => [
        'turnover' => self::parseNumber($o['turnover'] ?? null),
        'turnover_pa' => self::parseNumber($o['turnover_pa'] ?? null),
        'profit_gross' => self::parseNumber($o['profit_gross'] ?? null),
        'profit_net' => self::parseNumber($o['profit_net'] ?? null),
        'tenancy_passing_giy' => self::parseNumber($o['tenancy_passing_giy'] ?? null),
        'tenancy_passing_niy' => self::parseNumber($o['tenancy_passing_niy'] ?? null),
        'tenancy_status' => $o['tenancy_status'] ?? null,
      ],
      'legal_regulatory' => [
        'sale_type' => $o['sale_type'] ?? null,
        'class_of_use' => $o['class_of_use'] ?? null,
        'legal_fees_applicable' => self::triStateBool($o['legal_fees_applicable'] ?? null),
        'lease_length' => $o['lease_length'] ?? null,
        'protected_act' => self::triStateBool($o['protected_act'] ?? null),
        'insurance_type' => $o['insurance_type'] ?? null,
        'availability_reasons' => $o['availability_reasons'] ?? null,
        'togc' => self::triStateBool($o['togc'] ?? null),
      ],
      'physical_features' => [
        'shop_frontage_ft' => self::parseNumber($o['shop_frontage_ft'] ?? null),
        'shop_frontage_m' => self::parseNumber($o['shop_frontage_m'] ?? null),
        'shop_frontage_inches' => self::parseNumber($o['shop_frontage_inches'] ?? null),
        'land_size_from' => self::parseNumber($o['land_size_from'] ?? null),
        'land_size_to' => self::parseNumber($o['land_size_to'] ?? null),
        'land_size_metric' => $o['land_size_metric'] ?? null,
        'total_property_size' => self::parseNumber($o['total_property_size'] ?? null),
        'total_property_size_metric' => $o['total_property_size_metric'] ?? null,
        'area_size_type' => $o['area_size_type'] ?? null,
        'size_measure' => $o['size_measure'] ?? null,
      ],
      'size' => [
        'size_min' => $size_min,
        'size_max' => $size_max,
        'area_size_unit' => $area_unit,
        'total_size_sqft' => $total_sqft,
        'size_from_sqft' => self::parseInt($o['size_from_sqft'] ?? null),
        'size_to_sqft' => self::parseInt($o['size_to_sqft'] ?? null),
      ],
      'marketing' => [
        'marketing_title_1' => $o['marketing_title_1'] ?? null,
        'marketing_title_2' => $o['marketing_title_2'] ?? null,
        'marketing_title_3' => $o['marketing_title_3'] ?? null,
        'marketing_title_4' => $o['marketing_title_4'] ?? null,
        'marketing_title_5' => $o['marketing_title_5'] ?? null,
        'marketing_text_1' => $o['marketing_text_1'] ?? null,
        'marketing_text_2' => $o['marketing_text_2'] ?? null,
        'marketing_text_3' => $o['marketing_text_3'] ?? null,
        'marketing_text_4' => $o['marketing_text_4'] ?? null,
        'marketing_text_5' => $o['marketing_text_5'] ?? null,
        'marketing_title_transport' => $o['marketing_title_transport'] ?? null,
        'marketing_text_transport' => $o['marketing_text_transport'] ?? null,
      ],
      'selling_points' => [
        'key_selling_points' => $key_selling_points,
        'amenities_specifications' => $amenities_specs,
      ],
      'certifications' => [
        'epcs' => $epcs,
        'tags' => $o['tags'] ?? null,
      ],
      'units' => $units,
      'media' => [
        'images' => $images,
        'brochures' => $brochures,
        'floorplans' => $floorplans,
        'videos' => $videos,
        'videos_detail' => $videos_detail,
      ],
      'contacts' => $contacts,
      'admin' => [
        'sync_run_id' => null,
        'source_name' => 'agents-society',
        'raw_links' => [],
      ],
    ];

    return $normalized;
  }
}
