<?php

namespace KatoSync\DB;

class Migrations {

  const DB_VERSION = '1.0.1';
  const OPTION_KEY = 'kato_sync_db_version';

  public static function activate(): void {
    self::migrate();
  }

  public static function maybe_migrate(): void {
    $installed = get_option(self::OPTION_KEY);
    if ($installed !== self::DB_VERSION) {
      self::migrate();
    }
  }

  public static function migrate(): void {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $table_property = $wpdb->prefix . 'kato_property';
    $table_units = $wpdb->prefix . 'kato_property_units';
    $table_media = $wpdb->prefix . 'kato_property_media';
    $table_contacts = $wpdb->prefix . 'kato_property_contacts';

    $sql_property = "CREATE TABLE {$table_property} (
      post_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
      external_id VARCHAR(64) NOT NULL,
      name VARCHAR(255) NULL,
      address1 VARCHAR(255) NULL,
      address2 VARCHAR(255) NULL,
      city VARCHAR(128) NULL,
      town VARCHAR(128) NULL,
      county VARCHAR(128) NULL,
      postcode VARCHAR(32) NULL,
      outward_postcode VARCHAR(16) NULL,
      lat DECIMAL(10,6) NULL,
      lng DECIMAL(10,6) NULL,
      status VARCHAR(32) NULL,
      types_json LONGTEXT NULL,
      is_featured TINYINT(1) NOT NULL DEFAULT 0,
      is_archived TINYINT(1) NOT NULL DEFAULT 0,
      size_min INT NULL,
      size_max INT NULL,
      area_size_unit VARCHAR(16) NULL,
      total_size_sqft INT NULL,
      price_min DECIMAL(12,2) NULL,
      price_max DECIMAL(12,2) NULL,
      price_type VARCHAR(32) NULL,
      rent_json LONGTEXT NULL,
      service_charge_json LONGTEXT NULL,
      created_at DATETIME NULL,
      last_updated_at DATETIME NULL,
      imported_at DATETIME NULL,
      payload_hash CHAR(64) NOT NULL,
      schema_version INT NOT NULL DEFAULT 1,
      KEY idx_external_id (external_id),
      UNIQUE KEY uniq_external_id (external_id),
      KEY idx_county (county),
      KEY idx_status (status),
      KEY idx_featured (is_featured),
      KEY idx_size (size_min, size_max),
      KEY idx_price (price_min, price_max),
      KEY idx_updated (last_updated_at)
    ) {$charset_collate};";

    $sql_units = "CREATE TABLE {$table_units} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      post_id BIGINT UNSIGNED NOT NULL,
      unit_external_id VARCHAR(64) NULL,
      floor_label VARCHAR(64) NULL,
      size_sqft INT NULL,
      rent_min DECIMAL(12,2) NULL,
      rent_max DECIMAL(12,2) NULL,
      rent_metric VARCHAR(32) NULL,
      rates_text VARCHAR(255) NULL,
      status VARCHAR(32) NULL,
      availability_date DATE NULL,
      sort_order INT NULL,
      raw_json LONGTEXT NULL,
      KEY idx_post (post_id),
      KEY idx_status (status),
      KEY idx_size (size_sqft),
      KEY idx_rent (rent_min, rent_max),
      UNIQUE KEY uniq_unit (post_id, unit_external_id)
    ) {$charset_collate};";

    $sql_media = "CREATE TABLE {$table_media} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      post_id BIGINT UNSIGNED NOT NULL,
      type VARCHAR(16) NOT NULL,
      url VARCHAR(1024) NOT NULL,
      attachment_id BIGINT UNSIGNED NULL,
      alt VARCHAR(255) NULL,
      is_primary TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NULL,
      raw_json LONGTEXT NULL,
      KEY idx_post (post_id),
      KEY idx_type (type),
      KEY idx_primary (is_primary),
      UNIQUE KEY uniq_media (post_id, url(255))
    ) {$charset_collate};";

    $sql_contacts = "CREATE TABLE {$table_contacts} (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      post_id BIGINT UNSIGNED NOT NULL,
      name VARCHAR(255) NULL,
      email VARCHAR(255) NULL,
      phone VARCHAR(64) NULL,
      company VARCHAR(255) NULL,
      role VARCHAR(64) NULL,
      is_primary TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NULL,
      raw_json LONGTEXT NULL,
      KEY idx_post (post_id),
      KEY idx_role (role),
      KEY idx_email (email)
    ) {$charset_collate};";

    dbDelta($sql_property);
    dbDelta($sql_units);
    dbDelta($sql_media);
    dbDelta($sql_contacts);

    update_option(self::OPTION_KEY, self::DB_VERSION);
  }
}
