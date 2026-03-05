<?php if (! defined('ABSPATH')) exit; ?>
<?php
//---- UPLOAD IMAGE FROM URL/DATA URI OR SVG STRING --------

/**
 * Check if the host is a trusted image hosting service
 * 
 * @param string $host The hostname to check
 * @return bool True if trusted, false otherwise
 */
function fpd_is_trusted_image_host($host)
{
    // Default trusted hosts
    $default_trusted = array(
        // Social media
        'instagram.com',
        'cdninstagram.com',
        'fbcdn.net',
        'facebook.com',
        // Stock photo sites
        'pixabay.com',
        'unsplash.com',
        'pexels.com',
        'shutterstock.com',
        // CDN providers
        'cloudinary.com',
        'imgix.net',
        'amazonaws.com',
        'cloudfront.net',
        // Other image services
        'imgur.com',
        'giphy.com',
        'tenor.com',
        'flickr.com',
    );

    // SECURITY FIX: Auto-whitelist the current WordPress domain
    $current_host = parse_url(home_url(), PHP_URL_HOST);
    if ($current_host) {
        $default_trusted[] = $current_host;
    }

    // Get custom whitelist from WordPress options
    $custom_whitelist = array();

    // Merge custom and default
    $trusted_hosts = array_merge(
        $default_trusted,
        !empty($custom_whitelist) ? array_map('trim', explode(',', $custom_whitelist)) : array()
    );

    // Apply filter to allow other plugins to modify the list
    $trusted_hosts = apply_filters('fpd_trusted_image_hosts', $trusted_hosts);

    foreach ($trusted_hosts as $trusted) {
        $trusted = trim(strtolower($trusted));
        if (!empty($trusted) && strpos(strtolower($host), $trusted) !== false) {
            return true;
        }
    }

    return false;
}

$url = stripslashes($_POST['url']);

// Security: URL-decode to detect encoded content
$decoded_url = urldecode($url);

// Check if this is a data URL
$is_data_url = (stripos($url, 'data:') === 0);

if ($is_data_url) {
    // Only allow image/png and image/jpeg data URLs
    if (!preg_match('/^data:image\/(png|jpeg);base64,/i', $url)) {
        die(json_encode(array('error' => 'Only data URLs for image/png and image/jpeg are allowed.')));
    }

    // Block any suspicious content in data URLs
    if (preg_match('/<\?php|<script|javascript:/i', $url)) {
        die(json_encode(array('error' => 'Suspicious content detected in data URL.')));
    }
} else {
    // CRITICAL SECURITY: Block PHP filter chains, wrappers, and dangerous patterns for regular URLs
    $dangerous_patterns = array(
        'php://',
        'file://',
        'glob://',
        'phar://',
        'zip://',
        'compress.zlib://',
        'compress.bzip2://',
        'ogg://',
        'expect://',
    );

    foreach ($dangerous_patterns as $pattern) {
        if (stripos($url, $pattern) !== false || stripos($decoded_url, $pattern) !== false) {
            die(json_encode(array('error' => 'Only HTTP and HTTPS URLs are allowed. Dangerous protocols are not supported.')));
        }
    }

    // Block SVG strings and inline content
    if (preg_match('/<svg|<\?xml|<script|javascript:/i', $url) || preg_match('/<svg|<\?xml|<script|javascript:/i', $decoded_url)) {
        die(json_encode(array('error' => 'Only HTTP and HTTPS URLs are allowed. Inline SVG content is not supported.')));
    }

    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        die(json_encode(array('error' => 'Invalid URL format. Please provide a valid HTTP or HTTPS URL.')));
    }

    // Validate URL scheme - ONLY allow http/https
    $parsed_url = parse_url($url);
    if (!$parsed_url || !isset($parsed_url['scheme']) || !in_array(strtolower($parsed_url['scheme']), array('http', 'https'), true)) {
        die(json_encode(array('error' => 'Invalid URL scheme. Only HTTP and HTTPS URLs are allowed.')));
    }
}

// Security: Prevent SSRF attacks - disallow local/private IPs
if (isset($parsed_url['host'])) {
    $host = strtolower($parsed_url['host']);

    // Check if it's a trusted host first (bypass strict checks)
    $is_trusted = fpd_is_trusted_image_host($host);

    if (!$is_trusted) {
        // Apply strict SSRF checks only for untrusted hosts

        // Block localhost variations
        $blocked_hosts = array('localhost', '127.0.0.1', '::1', '0.0.0.0', '[::1]');
        if (in_array($host, $blocked_hosts)) {
            die(json_encode(array('error' => 'Access to localhost is not allowed.')));
        }

        // Resolve hostname to IP
        $host_ip = gethostbyname($host);

        // Block if resolution failed (returns hostname unchanged)
        if ($host_ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            die(json_encode(array('error' => 'Could not resolve hostname.')));
        }

        // Block private and reserved IP ranges (only for untrusted hosts)
        if (!filter_var($host_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            die(json_encode(array('error' => 'Access to private/reserved IPs is not allowed.')));
        }

        // Additional check for AWS metadata endpoint
        if ($host_ip === '169.254.169.254') {
            die(json_encode(array('error' => 'Access to cloud metadata endpoints is not allowed.')));
        }
    }
}

// Handle data URLs or fetch remote content
if ($is_data_url) {
    // Extract base64 data from data URL
    if (preg_match('/^data:image\/(png|jpeg);base64,(.+)$/i', $url, $matches)) {
        $mime_type = 'image/' . strtolower($matches[1]);
        $base64_data = $matches[2];

        $file_content = base64_decode($base64_data, true);

        if ($file_content === false || empty($file_content)) {
            die(json_encode(array('error' => 'Invalid base64 data in data URL.')));
        }
    } else {
        die(json_encode(array('error' => 'Invalid data URL format.')));
    }
} else {
    // SECURITY: Fetch content ONCE using WordPress HTTP API (prevents TOCTOU SSRF)
    $response = wp_safe_remote_get($url, array(
        'timeout' => 10,
        'redirection' => 0,  // CRITICAL: Disable automatic redirects to prevent TOCTOU attacks
        'sslverify' => false,
        'user-agent' => 'WordPress/' . get_bloginfo('version')
    ));

    if (is_wp_error($response)) {
        die(json_encode(array('error' => 'Could not fetch remote file: ' . $response->get_error_message())));
    }

    $http_code = wp_remote_retrieve_response_code($response);
    if ($http_code !== 200) {
        die(json_encode(array('error' => 'Remote server returned error code: ' . $http_code)));
    }

    // Check if response was a redirect attempt
    $response_headers = wp_remote_retrieve_headers($response);
    if (isset($response_headers['location']) || isset($response_headers['Location'])) {
        die(json_encode(array('error' => 'Redirects are not allowed for security reasons.')));
    }

    $file_content = wp_remote_retrieve_body($response);

    if (empty($file_content)) {
        die(json_encode(array('error' => 'Remote file is empty.')));
    }
}

// CRITICAL SECURITY FIX: Validate MIME type from content BEFORE using getimagesize()
// This prevents PHP filter chain exploitation
// First, check the magic bytes to ensure it's a real image
$magic_bytes = substr($file_content, 0, 16);

// Check for valid image magic bytes
$is_valid_image = false;

// For data URLs, we already have the MIME type, but still validate magic bytes
if ($is_data_url && isset($mime_type)) {
    // PNG: 89 50 4E 47
    if ($mime_type === 'image/png' && substr($magic_bytes, 0, 4) === "\x89\x50\x4E\x47") {
        $is_valid_image = true;
    }
    // JPEG: FF D8 FF
    elseif ($mime_type === 'image/jpeg' && substr($magic_bytes, 0, 3) === "\xFF\xD8\xFF") {
        $is_valid_image = true;
    }

    if (!$is_valid_image) {
        die(json_encode(array('error' => 'Data URL content does not match declared MIME type.')));
    }
} else {
    // For regular URLs, detect MIME type from magic bytes
    // PNG: 89 50 4E 47
    if (substr($magic_bytes, 0, 4) === "\x89\x50\x4E\x47") {
        $is_valid_image = true;
        $mime_type = 'image/png';
    }
    // JPEG: FF D8 FF
    elseif (substr($magic_bytes, 0, 3) === "\xFF\xD8\xFF") {
        $is_valid_image = true;
        $mime_type = 'image/jpeg';
    }
    // GIF: 47 49 46 38
    elseif (substr($magic_bytes, 0, 4) === "\x47\x49\x46\x38") {
        $is_valid_image = true;
        $mime_type = 'image/gif';
    }
    // WebP: 52 49 46 46 [4 bytes] 57 45 42 50
    elseif (substr($magic_bytes, 0, 4) === "\x52\x49\x46\x46" && substr($magic_bytes, 8, 4) === "\x57\x45\x42\x50") {
        $is_valid_image = true;
        $mime_type = 'image/webp';
    }
    // SVG: Check for XML or svg tag
    elseif (preg_match('/^(<\?xml|<svg)/i', trim($magic_bytes))) {
        $is_valid_image = true;
        $mime_type = 'image/svg+xml';

        // Sanitize SVG content
        $svg_handler = new FPD_Svg_Handler();
        $sanitized_svg = $svg_handler->sanitizer($file_content);
        if (false === $sanitized_svg) {
            die(json_encode(array('error' => 'SVG file contains malicious content and cannot be uploaded.')));
        }
        $file_content = $sanitized_svg;
    }
}

if (!$is_valid_image) {
    die(json_encode(array('error' => 'File is not a valid image format.')));
}

// Now safely use getimagesize() on already-validated content
$temp_file = tempnam(sys_get_temp_dir(), 'fpd_img_');
if ($temp_file === false) {
    die(json_encode(array('error' => 'Could not create temporary file for validation.')));
}

file_put_contents($temp_file, $file_content);

// Additional validation with getimagesize (for non-SVG)
if ($mime_type !== 'image/svg+xml') {
    $img_info = @getimagesize($temp_file);

    if ($img_info === false) {
        @unlink($temp_file);
        die(json_encode(array('error' => 'File is not a valid image.')));
    }

    // Verify MIME type matches magic bytes
    if ($img_info['mime'] !== $mime_type) {
        @unlink($temp_file);
        die(json_encode(array('error' => 'Image format mismatch detected.')));
    }
}

@unlink($temp_file);

$ext = str_replace('image/', '', $mime_type);

// Convert jpg to jpeg for storage
if ($ext === 'jpg') {
    $ext = 'jpeg';
}

$upload_path = FPD_Image_Utils::get_upload_path($uploads_dir, $unique_file_name);
$image_path = $upload_path['full_path'] . '.' . $ext;
$image_url = $uploads_dir_url . $upload_path['date_path'] . '.' . $ext;

// SECURITY: Use the already-fetched and validated content (no second fetch)
$result = false;

if (function_exists('file_put_contents')) {
    $result = file_put_contents($image_path, $file_content);
}
// Fallback if file_put_contents not available
else if (function_exists('fopen')) {
    try {
        $fp = fopen($image_path, 'w+');
        if ($fp !== false) {
            $result = fwrite($fp, $file_content);
            fclose($fp);
        }
    } catch (Exception $e) {
        // Log detailed error server-side for debugging
        error_log(sprintf(
            'Data-to-Image File Write Error: %s in %s (Path: %s)',
            $e->getMessage(),
            __FILE__,
            $image_path
        ));

        // Return generic error to user (no sensitive information)
        echo json_encode(array('error' => 'Failed to save image file. Please try again.'));
        die;
    }
}

if ($result) {

    // Security: Check file size after download
    if (file_exists($image_path)) {
        $file_size = filesize($image_path);
        $max_file_size_mb = intval(fpd_get_option('fpd_file_uploads_maxSize'));
        $max_size = $max_file_size_mb > 0 ? $max_file_size_mb * 1024 * 1024 : 10 * 1024 * 1024; // Default 10MB

        if ($file_size > $max_size) {
            @unlink($image_path);
            die(json_encode(array('error' => 'Downloaded file is too large. Maximum size is ' . $max_file_size_mb . 'MB.')));
        }

        // Security: Scan downloaded content for PHP code (non-SVG files)
        if ($ext !== 'svg' && function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring(file_get_contents($image_path));
            if ($image === false) {
                die(json_encode(array('error' => 'Invalid image file.', 'filename' => $filename)));
            }
        }
    }

    if (isset($_GET['filter'])) {

        $filter_result = FPD_Image_Utils::apply_imagick_filter($image_path, $_GET['filter']);

        if (isset($filter_result['image_path'])) {

            //update image url if extension has change            
            if ($new_ext = strtolower(pathinfo($filter_result['image_path'], PATHINFO_EXTENSION))) {

                if ($new_ext !== $ext) {

                    $image_url = preg_replace('/\.(jpeg|svg)$/i', '.' . $new_ext, $image_url);
                }
            }
        }
    }

    echo json_encode(array(
        'image_src' => $image_url
    ));
} else {

    echo json_encode(array(
        'error' => 'The image could not be created. Please view the error log file of your server to see what went wrong!'
    ));
}
?>