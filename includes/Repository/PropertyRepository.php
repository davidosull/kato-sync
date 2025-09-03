<?php

namespace KatoSync\Repository;

use KatoSync\Sync\Normalizer;

class PropertyRepository {

  public static function upsert(int $post_id, array $normalized, array $original): void {
    global $wpdb;

    $table_property = $wpdb->prefix . 'kato_property';
    $table_units = $wpdb->prefix . 'kato_property_units';
    $table_media = $wpdb->prefix . 'kato_property_media';
    $table_contacts = $wpdb->prefix . 'kato_property_contacts';

    $now = current_time('mysql');

    // Compute payload hash
    $payload_hash = hash('sha256', wp_json_encode($normalized));

    // Update normalized timestamps
    $normalized['kato_meta']['imported_at'] = $now;
    $normalized['kato_meta']['payload_hash'] = $payload_hash;

    // Extract top-level fields
    $km = $normalized['kato_meta'];
    $prop = $normalized['property'];
    $loc = $normalized['location'];
    $price = $normalized['pricing'];
    $size = $normalized['size'];

    $rent_json = wp_json_encode($price['rent']);
    $sc_json = wp_json_encode($price['service_charge']);
    $types_json = wp_json_encode($prop['types']);

    // Upsert main row
    $data = [
      'post_id' => $post_id,
      'external_id' => (string)($km['external_id'] ?? ''),
      'name' => $prop['name'],
      'address1' => $loc['address1'],
      'address2' => $loc['address2'],
      'city' => $loc['city'],
      'town' => $loc['town'],
      'county' => $loc['county'],
      'postcode' => $loc['postcode'],
      'outward_postcode' => $loc['outward_postcode'],
      'lat' => $loc['lat'],
      'lng' => $loc['lng'],
      'status' => $prop['status'],
      'types_json' => $types_json,
      'is_featured' => !empty($km['is_featured']) ? 1 : 0,
      'is_archived' => !empty($km['is_archived']) ? 1 : 0,
      'size_min' => $size['size_min'],
      'size_max' => $size['size_max'],
      'area_size_unit' => $size['area_size_unit'],
      'total_size_sqft' => $size['total_size_sqft'],
      'price_min' => $price['price_min'],
      'price_max' => $price['price_max'],
      'price_type' => $price['price_type'],
      'rent_json' => $rent_json,
      'service_charge_json' => $sc_json,
      'created_at' => $km['created_at'],
      'last_updated_at' => $km['last_updated'],
      'imported_at' => $km['imported_at'],
      'payload_hash' => $payload_hash,
      'schema_version' => (int)($km['schema_version'] ?? 1),
    ];

    // REPLACE INTO for upsert
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '%s');
    // type casting
    $formats = [
      'post_id' => '%d',
      'external_id' => '%s',
      'name' => '%s',
      'address1' => '%s',
      'address2' => '%s',
      'city' => '%s',
      'town' => '%s',
      'county' => '%s',
      'postcode' => '%s',
      'outward_postcode' => '%s',
      'lat' => '%f',
      'lng' => '%f',
      'status' => '%s',
      'types_json' => '%s',
      'is_featured' => '%d',
      'is_archived' => '%d',
      'size_min' => '%d',
      'size_max' => '%d',
      'area_size_unit' => '%s',
      'total_size_sqft' => '%d',
      'price_min' => '%f',
      'price_max' => '%f',
      'price_type' => '%s',
      'rent_json' => '%s',
      'service_charge_json' => '%s',
      'created_at' => '%s',
      'last_updated_at' => '%s',
      'imported_at' => '%s',
      'payload_hash' => '%s',
      'schema_version' => '%d'
    ];

    $values = [];
    $valueFormats = [];
    foreach ($columns as $col) {
      $values[] = $data[$col];
      $valueFormats[] = $formats[$col] ?? '%s';
    }

    $sql = "REPLACE INTO {$table_property} (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $valueFormats) . ")";
    $wpdb->query($wpdb->prepare($sql, $values));

    // Replace child rows
    $wpdb->delete($table_units, ['post_id' => $post_id], ['%d']);
    foreach ($normalized['units'] as $u) {
      $wpdb->insert($table_units, [
        'post_id' => $post_id,
        'unit_external_id' => (string)($u['unit_external_id'] ?? ''),
        'floor_label' => $u['floor_label'] ?? null,
        'size_sqft' => $u['size_sqft'] ?? null,
        'rent_min' => $u['rent_min'] ?? null,
        'rent_max' => $u['rent_max'] ?? null,
        'rent_metric' => $u['rent_metric'] ?? null,
        'rates_text' => $u['rates_text'] ?? null,
        'status' => $u['status'] ?? null,
        'availability_date' => $u['availability_date'] ?? null,
        'sort_order' => $u['sort_order'] ?? null,
        'raw_json' => wp_json_encode($u['raw'] ?? [])
      ], ['%d', '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%d', '%s']);
    }

    $wpdb->delete($table_media, ['post_id' => $post_id], ['%d']);
    foreach ($normalized['media']['images'] as $i) {
      $wpdb->insert($table_media, [
        'post_id' => $post_id,
        'type' => 'image',
        'url' => $i['url'] ?? '',
        'attachment_id' => null,
        'alt' => $i['alt'] ?? null,
        'is_primary' => 0,
        'sort_order' => $i['sort_order'] ?? null,
        'raw_json' => wp_json_encode($i['raw'] ?? [])
      ], ['%d', '%s', '%s', '%d', '%s', '%d', '%d', '%s']);
    }
    foreach ($normalized['media']['brochures'] as $b) {
      $wpdb->insert($table_media, [
        'post_id' => $post_id,
        'type' => 'brochure',
        'url' => $b['url'] ?? '',
        'attachment_id' => null,
        'alt' => $b['title'] ?? null,
        'is_primary' => 0,
        'sort_order' => null,
        'raw_json' => wp_json_encode($b['raw'] ?? [])
      ], ['%d', '%s', '%s', '%d', '%s', '%d', '%s']);
    }
    foreach ($normalized['media']['floorplans'] as $f) {
      $wpdb->insert($table_media, [
        'post_id' => $post_id,
        'type' => 'floorplan',
        'url' => $f['url'] ?? '',
        'attachment_id' => null,
        'alt' => $f['title'] ?? null,
        'is_primary' => 0,
        'sort_order' => null,
        'raw_json' => wp_json_encode($f['raw'] ?? [])
      ], ['%d', '%s', '%s', '%d', '%s', '%d', '%s']);
    }
    foreach ($normalized['media']['videos'] as $v) {
      $wpdb->insert($table_media, [
        'post_id' => $post_id,
        'type' => 'video',
        'url' => $v['url'] ?? '',
        'attachment_id' => null,
        'alt' => $v['title'] ?? null,
        'is_primary' => 0,
        'sort_order' => null,
        'raw_json' => wp_json_encode($v['raw'] ?? [])
      ], ['%d', '%s', '%s', '%d', '%s', '%d', '%s']);
    }

    $wpdb->delete($table_contacts, ['post_id' => $post_id], ['%d']);
    foreach ($normalized['contacts'] as $c) {
      $wpdb->insert($table_contacts, [
        'post_id' => $post_id,
        'name' => $c['name'] ?? null,
        'email' => $c['email'] ?? null,
        'phone' => $c['phone'] ?? null,
        'company' => $c['company'] ?? null,
        'role' => $c['role'] ?? null,
        'is_primary' => !empty($c['is_primary']) ? 1 : 0,
        'sort_order' => $c['sort_order'] ?? null,
        'raw_json' => wp_json_encode($c['raw'] ?? [])
      ], ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']);
    }

    // View model
    $view = self::buildViewModel($normalized);

    // Post meta writes
    update_post_meta($post_id, '_kato_raw_original', wp_json_encode($original));
    update_post_meta($post_id, '_kato_raw', wp_json_encode($normalized));
    update_post_meta($post_id, '_kato_view', wp_json_encode($view));
  }

  private static function buildViewModel(array $n): array {
    // Derive primary image from first image
    $primary = ['url' => null, 'attachment_id' => null, 'alt' => null];
    if (!empty($n['media']['images'])) {
      $first = $n['media']['images'][0];
      $primary = [
        'url' => $first['url'] ?? null,
        'attachment_id' => null,
        'alt' => $first['alt'] ?? null,
      ];
    }

    return [
      'kato_meta' => $n['kato_meta'],
      'property' => $n['property'],
      'location' => $n['location'],
      'pricing' => $n['pricing'],
      'business_financial' => $n['business_financial'] ?? [],
      'legal_regulatory' => $n['legal_regulatory'] ?? [],
      'physical_features' => $n['physical_features'] ?? [],
      'size' => $n['size'],
      'marketing' => $n['marketing'] ?? [],
      'selling_points' => $n['selling_points'] ?? [],
      'certifications' => $n['certifications'] ?? [],
      'units' => $n['units'],
      'media' => [
        'primary_image' => $primary,
        'images' => $n['media']['images'],
        'brochures' => $n['media']['brochures'],
        'floorplans' => $n['media']['floorplans'],
        'videos' => $n['media']['videos'],
        'videos_detail' => $n['media']['videos_detail'] ?? [],
      ],
      'contacts' => $n['contacts'],
      'admin' => $n['admin'],
    ];
  }
}
