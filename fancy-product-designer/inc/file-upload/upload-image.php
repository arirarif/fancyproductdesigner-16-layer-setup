<?php if (! defined('ABSPATH')) exit; ?>
<?php

$warning = null;

foreach ($_FILES as $fieldName => $file) {

    // First things first: input sanitation and security checks
    try {
        $sanitized_name = FPD_Image_Utils::sanitize_filename($file['name'][0]);
    } catch (Exception $e) {
        die(json_encode(array('error' => $e->getMessage())));
    }

    // Determining file name parts using pathinfo() instead of explode()
    // prevents double extensions (file.jpg.php) and directory traversal (../../file.jpg)
    $parts = pathinfo($sanitized_name);
    $filename = $parts['filename'];
    $ext = strtolower($parts['extension']);

    // Convert jpg to jpeg for storage
    if ($ext === 'jpg') {
        $ext = 'jpeg';
    }

    //check for php errors
    if (isset($file['error']) && $file['error'][0] !== UPLOAD_ERR_OK) {
        die(json_encode(array(
            'error' => FPD_Image_Utils::file_upload_error_message($file['error'][0]),
            'filename' => $filename
        )));
    }

    // Security: Check file size (configurable max size)
    $max_file_size_mb = intval(fpd_get_option('fpd_file_uploads_maxSize'));
    $max_file_size = $max_file_size_mb > 0 ? $max_file_size_mb * 1024 * 1024 : 10 * 1024 * 1024; // Default 10MB
    if ($file['size'][0] > $max_file_size) {
        die(json_encode(array(
            'error' => 'File too large. Maximum size is ' . $max_file_size_mb . 'MB.',
            'filename' => $filename
        )));
    }

    //check if its an image
    if ((!getimagesize($file['tmp_name'][0]) && $ext !== 'svg') || !in_array($file['type'][0], $valid_mime_types)) {

        die(json_encode(array(
            'error' => 'This file is not an image!',
            'filename' => $filename
        )));
    }

    // Security: Additional MIME type verification using finfo
    if ($ext !== 'svg' && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_mime = finfo_file($finfo, $file['tmp_name'][0]);

        $expected_mimes = array(
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        );

        if (isset($expected_mimes[$ext]) && $detected_mime !== $expected_mimes[$ext]) {
            die(json_encode(array(
                'error' => 'File extension does not match file content.',
                'filename' => $filename
            )));
        }
    }

    // Security: Re-encode image to strip any embedded payloads
    if ($ext !== 'svg' && function_exists('imagecreatefromstring')) {
        $image = @imagecreatefromstring(file_get_contents($file['tmp_name'][0]));
        if ($image === false) {
            die(json_encode(array('error' => 'Invalid image file.', 'filename' => $filename)));
        }
    }

    $upload_path = FPD_Image_Utils::get_upload_path($uploads_dir, $unique_file_name, $ext);
    $image_path = $upload_path['full_path'] . '.' . $ext;
    $image_url = $uploads_dir_url . $upload_path['date_path'] . '.' . $ext;

    if (@move_uploaded_file($file['tmp_name'][0], $image_path)) {

        if ($ext === 'jpeg') {

            if (function_exists('exif_read_data')) {

                $exif = @exif_read_data($image_path);

                if ($exif && isset($exif['Orientation']) && !empty($exif['Orientation'])) {

                    $image = imagecreatefromjpeg($image_path);
                    $resolution = imageresolution($image);
                    unlink($image_path);

                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;

                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;

                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }

                    if (is_array($resolution)) {
                        imageresolution($image, $resolution[0], $resolution[1]);
                    }

                    imagejpeg($image, $image_path, 100);
                }
            } else
                $warning = 'exif_read_data function is not enabled.';
        } else if ($ext === 'svg') {

            //sanitize svg content and resave image
            if (function_exists('file_get_contents')) {
                $fpd_svg_handler = new FPD_Svg_Handler();
                $fpd_svg_handler->sanitize_svg($image_path);
            }
        }

        if (isset($_GET['filter'])) {

            $result = FPD_Image_Utils::apply_imagick_filter($image_path, $_GET['filter']);

            if (isset($result['error'])) {

                $warning = $result['error'];
            } else if (isset($result['image_path'])) {

                $image_path = $result['image_path'];

                //update image url if extension has changed
                if ($new_ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION))) {

                    if ($new_ext !== $ext) {

                        $image_url = preg_replace('/\.(jpeg|svg)$/i', '.' . $new_ext, $image_url);
                    }
                }
            }
        }

        // Get image dimensions
        $image_width = 0;
        $image_height = 0;

        // For regular images (jpg, png, etc.)
        $image_size = getimagesize($image_path);
        if ($image_size !== false) {
            $image_width = $image_size[0];
            $image_height = $image_size[1];
        }

        echo json_encode(array(
            'image_src' => $image_url,
            'filename' => $filename,
            'warning' => $warning,
            'width' => $image_width,
            'height' => $image_height
        ));
    } else {

        echo json_encode(array(
            'error' => 'PHP Issue - move_upload_file failed.',
            'filename' => $filename
        ));
    }
}
?>