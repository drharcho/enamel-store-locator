=== Enamel Store Locator ===
Contributors: entel
Tags: store locator, maps, dental clinic, locations, google maps
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intelligent store locator with Google Maps integration, customizable branding, and comprehensive location management for dental practices.

== Description ==

Enamel Store Locator is a comprehensive WordPress plugin designed specifically for dental practices and healthcare providers who need a professional, customizable store locator solution.

**Key Features:**

* **Interactive Google Maps Integration** - Fully integrated with Google Maps API for accurate location display and directions
* **Customizable Branding** - Complete color scheme and text customization to match your practice's brand
* **Mobile Responsive** - Works seamlessly on desktop, tablet, and mobile devices
* **Location Search** - Users can search by address, zip code, or use their current location
* **Comprehensive Location Management** - Easy-to-use admin interface for managing clinic locations
* **Advanced Map Customization** - Control map types, marker styles, clustering, and more
* **REST API Ready** - Built-in API for integration with other systems
* **SEO Friendly** - Optimized for search engines with proper meta tags

**Perfect For:**
* Dental practices with multiple locations
* Healthcare providers
* Service businesses with physical locations
* Any business needing a professional location finder

**Admin Features:**
* Drag-and-drop location management
* Bulk location import/export
* Custom business hours for each location
* Service offerings per location
* Advanced map settings
* Color scheme presets
* Complete text customization

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/enamel-store-locator` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Store Locator > Settings to configure your Google Maps API key
4. Add your clinic locations through Store Locator > Locations
5. Use the `[enamel_store_locator]` shortcode on any page or post

== Frequently Asked Questions ==

= Do I need a Google Maps API key? =

Yes, you'll need a Google Maps API key to display the interactive map. You can get one for free from the Google Cloud Console.

= Can I customize the colors and text? =

Absolutely! The plugin includes comprehensive customization options for colors, text, and branding to match your website.

= Is it mobile responsive? =

Yes, the store locator is fully responsive and works great on all device sizes.

= Can I add custom fields to locations? =

Yes, each location supports custom services, business hours, and metadata fields.

= Does it work with any WordPress theme? =

Yes, the plugin is designed to work with any properly coded WordPress theme.

= Can I import existing locations? =

The plugin includes tools for managing locations through the WordPress admin. Bulk import features are planned for future releases.

== Screenshots ==

1. Store locator frontend with map and location list
2. Admin dashboard with setup checklist
3. Location management interface
4. Comprehensive settings page with customization options
5. Map settings and marker customization
6. Color scheme customization
7. Mobile responsive design

== Changelog ==

= 1.2.2 =
* NEW: Performance Settings section added to Settings page with user-configurable options
* NEW: Lazy loading toggle - Enable/disable lazy loading of Google Maps via admin
* NEW: Defer scripts toggle - Enable/disable script deferring via admin
* NEW: Preconnect hints toggle - Enable/disable preconnect hints via admin
* CLEANUP: Removed ~1,400 lines of unused code (admin/, api/ folders, admin.js)
* IMPROVED: Plugin now consists of only 3 core files for easier maintenance

= 1.2.1 =
* NEW: Google Map ID setting for Advanced Markers support
* NEW: AdvancedMarkerElement migration - replaces deprecated google.maps.Marker
* NEW: Added loading=async parameter to Maps API for optimal performance
* NEW: Marker library now loaded for future-proof marker support
* IMPROVED: Graceful fallback to legacy Marker when Map ID not provided
* IMPROVED: DOM-based marker content for better styling and transitions

= 1.2.0 =
* NEW: Lazy loading option - Delays loading Google Maps until the user scrolls to the store locator
* NEW: Defer scripts option - Loads JavaScript after page content to reduce render-blocking
* NEW: Preconnect hints - Establishes early connections to Google domains for faster map loading (enabled by default)
* NEW: Performance settings section in admin panel
* IMPROVED: IntersectionObserver used for efficient lazy loading with 200px pre-load margin

= 1.1.0 =
* NEW: Custom marker upload - Use WordPress media library to upload custom default and active marker images
* NEW: WP Rocket compatibility - Moved CSS to external file with safelist filters for "Remove Unused CSS" feature
* NEW: Map style optimization - Only selected style preset is output (reduced payload ~80%)
* IMPROVED: Card highlighting now uses coordinates instead of array index for reliability
* IMPROVED: Reduced plugin code from 2,262 to 1,909 lines while maintaining all functionality
* FIX: Better fallback to vector pins when custom marker images aren't provided

= 1.0.0 =
* Initial release
* Google Maps integration
* Complete admin interface
* Mobile responsive design
* REST API endpoints
* Custom post type for locations
* Comprehensive customization options

== Upgrade Notice ==

= 1.2.2 =
Adds user-configurable Performance Settings (lazy loading, defer scripts, preconnect hints) and major code cleanup.

= 1.2.1 =
Fixes Google Maps deprecation warnings by adding AdvancedMarkerElement support. Add a Map ID in Settings to enable. Strongly recommended update.

= 1.2.0 =
Performance improvements with lazy loading, deferred scripts, and preconnect hints. Update recommended.

= 1.1.0 =
Custom marker upload, WP Rocket compatibility, and performance optimizations. Update recommended.

= 1.0.0 =
Initial release of the Enamel Store Locator plugin.

== Advanced Usage ==

**Shortcode Options:**

Basic usage:
`[enamel_store_locator]`

With custom center and zoom:
`[enamel_store_locator center_lat="30.3072" center_lng="-97.7560" zoom="12"]`

With custom dimensions:
`[enamel_store_locator height="600px" width="100%"]`

**REST API Endpoints:**

* `GET /wp-json/enamel-store-locator/v1/locations` - Get all locations
* `POST /wp-json/enamel-store-locator/v1/locations` - Create location
* `GET /wp-json/enamel-store-locator/v1/settings` - Get plugin settings
* `POST /wp-json/enamel-store-locator/v1/settings` - Update settings

**Developer Hooks:**

The plugin includes various action and filter hooks for developers to extend functionality.

== Support ==

For support, feature requests, or bug reports, please contact the plugin developers or visit the support forums.

== Privacy Policy ==

This plugin uses the Google Maps API to display location information. Please refer to Google's Privacy Policy for information about data handling by Google services.