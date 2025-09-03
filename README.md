# Kato Sync WordPress Plugin

A WordPress plugin for importing property data from external XML feeds and storing it locally for efficient access.

## Overview

Kato Sync is designed to import property data from XML feeds and store it as custom post types in WordPress. This enables faster querying and display of property information without repeatedly fetching from remote sources.

## Features

### Core Functionality

- **Custom Post Type**: Creates `kato-property` post type for storing imported properties
- **XML Feed Import**: Fetches and parses property data from external XML feeds
- **Batch Processing**: Handles large feeds efficiently with configurable batch sizes
- **Auto-Sync**: Scheduled automatic syncing using WordPress cron
- **Manual Sync**: On-demand import with progress tracking

### Admin Interface

- **Properties Page**: View all imported properties with pagination and search
- **Import Page**: Manual sync controls and sync history
- **Settings Page**: Configure feed URL, batch size, auto-sync settings, and more
- **Tools Page**: System diagnostics, feed testing, and maintenance tools

### Advanced Features

- **Smart Updates**: Uses `lastmod` timestamps to skip unchanged properties
- **PDF Handling**: Stores PDF URLs as property meta data
- **SEO-Friendly URLs**: Generates clean URLs for property pages
- **Import/Export Settings**: Backup and restore plugin configuration
- **Comprehensive Logging**: Detailed sync history with statistics

## Installation

1. Upload the `kato-sync` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Kato Sync' in the admin menu to configure settings

## Configuration

### Basic Setup

1. **Feed URL**: Enter the URL of your XML property feed
2. **Request Timeout**: Set maximum time to wait for feed response (default: 30 seconds)
3. **Batch Size**: Number of properties to process per batch (default: 50)

### Auto-Sync Settings

- **Enable Auto-Sync**: Toggle automatic syncing on/off
- **Frequency**: Choose between hourly, daily, or weekly syncs
- **Time**: Set specific time for auto-sync (Europe/London timezone)

### Advanced Options

- **URL Pattern**: Customize property URL structure using placeholders
- **Force Update All**: Bypass timestamp checks for full re-import
- **Remove Data on Uninstall**: Option to clean up all data when plugin is removed

## Usage

### Manual Import

1. Navigate to **Kato Sync > Import**
2. Click "Start Manual Import"
3. Monitor progress and view results

### Viewing Properties

1. Go to **Kato Sync > Properties**
2. Browse imported properties with pagination
3. Click "View Frontend" to see property pages

### System Tools

1. Visit **Kato Sync > Tools**
2. Run diagnostics to check system compatibility
3. Test feed connectivity
4. Clean up old logs or reset sync status

## XML Feed Structure

The plugin expects XML feeds with property data. Example structure:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<properties>
    <property>
        <id>12345</id>
        <name>Beautiful Family Home</name>
        <address1>123 Main Street</address1>
        <postcode>SW1A 1AA</postcode>
        <price>500000</price>
        <bedrooms>3</bedrooms>
        <bathrooms>2</bathrooms>
        <description>Lovely family home in prime location...</description>
        <lastmod>2025-01-15T10:30:00Z</lastmod>
        <pdf_url>https://osull.io/brochure.pdf</pdf_url>
    </property>
</properties>
```

### Supported Fields

- `id` - Unique property identifier
- `name` - Property name/title
- `address1`, `address2` - Property address
- `city`, `county`, `postcode` - Location details
- `price` - Property price
- `bedrooms`, `bathrooms` - Room counts
- `property_type`, `tenure` - Property details
- `description`, `summary` - Property descriptions
- `lastmod` - Last modification timestamp
- `pdf_url` - Link to property brochure
- `images` - Property image URLs

## Frontend URLs

Properties are accessible at URLs like:

```
/property/beautiful-family-home-123-main-street-sw1a-1aa
```

The URL pattern can be customized in settings using placeholders:

- `{name}` - Property name
- `{address1}` - Primary address
- `{postcode}` - Postcode

## System Requirements

- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **Extensions**: SimpleXML (for XML parsing)
- **Memory**: 256MB recommended for large imports

## Troubleshooting

### Common Issues

1. **Import Fails**: Check feed URL and network connectivity
2. **Memory Errors**: Increase PHP memory limit or reduce batch size
3. **Timeout Errors**: Increase request timeout in settings
4. **XML Parsing Errors**: Verify feed format and SimpleXML extension

### Diagnostic Tools

Use the **Tools** page to:

- Check system compatibility
- Test feed connectivity
- Verify database connections
- Monitor cron job status

## Development

### File Structure

```
kato-sync/
├── kato-sync.php              # Main plugin file
├── includes/
│   ├── Plugin.php             # Main plugin class
│   ├── PostTypes/
│   │   └── Property.php       # Custom post type
│   ├── Admin/
│   │   ├── Admin.php          # Admin interface
│   │   └── Pages/             # Admin pages
│   ├── Sync/
│   │   └── SyncManager.php    # Sync functionality
│   └── Utils/
│       └── Tools.php          # Utility functions
├── assets/
│   ├── css/
│   │   └── admin.css          # Admin styles
│   └── js/
│       └── admin.js           # Admin JavaScript
└── README.md                  # This file
```

### Hooks and Filters

The plugin provides various hooks for customization:

```php
// Modify property data before import
add_filter('kato_sync_property_data', function($data) {
    // Modify $data array
    return $data;
});

// Custom sync logging
add_action('kato_sync_after_import', function($stats) {
    // Handle post-import actions
});
```

## Support

For support and feature requests, please refer to the plugin documentation or contact the development team.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0

- Initial release
- Custom post type for properties
- XML feed import functionality
- Admin interface with four main pages
- Auto-sync with WordPress cron
- Batch processing for large feeds
- Comprehensive logging and diagnostics
