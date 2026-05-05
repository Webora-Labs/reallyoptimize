=== Webora Image Optimizer ===
Contributors: weboralabs, evhenbulba
Tags: image optimization, webp, avif, compress, lazy load
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight image optimization plugin for WordPress. Compress, convert to AVIF and WebP, lazy load, and bulk optimize.

== Description ==

Webora Image Optimizer is a lightweight image optimization plugin for WordPress.

**Features:**

* Compress JPEG and PNG images on upload using CLI tools (mozjpeg, jpegoptim, pngquant, oxipng, optipng) with GD and Imagick fallback.
* Convert images to AVIF format on upload. Requires PHP 8.1 or newer with GD AVIF support.
* Convert images to WebP format on upload using cwebp or GD fallback.
* Add loading="lazy" attribute to images for deferred loading.
* Automatically add width and height attributes to images to prevent layout shift.
* Resize oversized images on upload to configurable maximum dimensions.
* Bulk optimize all existing images in your media library.

== Installation ==

1. Upload the "webora-image-optimizer" folder to /wp-content/plugins/.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Settings, then Webora Image Optimizer to configure.

== Frequently Asked Questions ==

= AVIF conversion is not available. What should I do? =
AVIF requires PHP 8.1 or newer compiled with GD and AVIF support. The Images tab shows whether your server supports it.

= Can I use CLI tools for better compression? =
Yes. If mozjpeg, jpegoptim, pngquant, oxipng, optipng, or cwebp are installed on your server, the plugin will detect and use them automatically. You can also specify custom binary paths in the settings.

= What happens if no CLI tools are installed? =
The plugin falls back to the built-in WordPress image editor (GD or Imagick) for compression and WebP conversion.

== Changelog ==

= 1.0.0 =
* Initial release.
* Image compression on upload with CLI tools and GD/Imagick fallback.
* AVIF and WebP conversion.
* Lazy load images.
* Auto width and height attributes for layout shift prevention.
* Resize oversized images on upload.
* Bulk optimize existing media library.
