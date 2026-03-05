<?php if (! defined('ABSPATH')) exit; ?>
<?php
if (!extension_loaded('imagick'))
    die(json_encode(array('error' => 'Imagick extension is required in order to upload PDF files. Please enable Imagick on your server!')));

$pdf_file = $_FILES['pdf'];

// First things first: input sanitation and security checks
try {
    $sanitized_name = FPD_Image_Utils::sanitize_filename($pdf_file['name']);
} catch (Exception $e) {
    // Log detailed error server-side for debugging
    error_log(sprintf(
        'FPD Filename Sanitization Error: %s in %s',
        $e->getMessage(),
        __FILE__
    ));

    // Return generic error to user (no sensitive information)
    die(json_encode(array('error' => 'Invalid filename. Please use a valid file name.')));
}

$parts = pathinfo($sanitized_name);
$filename = $parts['filename'];
$ext = strtolower($parts['extension']);

// Security: Strict extension validation - only allow PDF
if ($ext !== 'pdf') {
    die(json_encode(array(
        'error' => 'Only PDF files are allowed.',
        'filename' => $filename
    )));
}

//check for php errors
if (isset($pdf_file['error']) && $pdf_file['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(array(
        'error' => FPD_Image_Utils::file_upload_error_message($pdf_file['error']),
        'filename' => $filename
    )));
}

// Security: Use WordPress built-in file validation
$wp_filetype = wp_check_filetype_and_ext($pdf_file['tmp_name'], $pdf_file['name']);

if ($wp_filetype['ext'] !== 'pdf') {
    die(json_encode(array('error' => 'Invalid file type. Please upload a PDF file.')));
}

// Security: Additional MIME type verification
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $pdf_file['tmp_name']);

if ($mime_type !== 'application/pdf') {
    die(json_encode(array('error' => 'File is not a valid PDF.')));
}

// Security: Validate PDF magic bytes to prevent SVG/other file types masquerading as PDF
$file_handle = fopen($pdf_file['tmp_name'], 'rb');
$magic_bytes = fread($file_handle, 5);
fclose($file_handle);

if ($magic_bytes !== '%PDF-') {
    die(json_encode(array('error' => 'File is not a valid PDF format.')));
}

// Security: Check file size
$max_file_size_mb = intval(fpd_get_option('fpd_file_uploads_maxSize'));
// PDFs can be larger, use 2x the configured size or default to 20MB
$max_size = $max_file_size_mb > 0 ? ($max_file_size_mb * 2) * 1024 * 1024 : 20 * 1024 * 1024;
if ($pdf_file['size'] > $max_size) {
    die(json_encode(array(
        'error' => 'PDF file is too large. Maximum size is ' . ($max_file_size_mb * 2) . 'MB.',
        'filename' => $filename
    )));
}

// Security: Scan PDF content for embedded malicious code
$pdf_content = file_get_contents($pdf_file['tmp_name']);
if (preg_match('/<\?php|<\?=|<script[\s>]/i', $pdf_content)) {
    die(json_encode(array(
        'error' => 'Suspicious content detected in PDF file.',
        'filename' => $filename
    )));
}

$upload_path = FPD_Image_Utils::get_upload_path($uploads_dir, $unique_file_name, $ext);
$pdf_path = $upload_path['full_path'] . '.' . $ext;
$pdf_url = $uploads_dir_url . $upload_path['date_path'] . '.' . $ext;

$pdf_images = array();

if (@move_uploaded_file($pdf_file['tmp_name'], $pdf_path)) {

    try {

        $im = new Imagick();
        $im->setBackgroundColor(new ImagickPixel('transparent'));
        $im->setResolution(300, 300);
        $im->readImage($pdf_path);

        for ($i = 0; $i < $im->getNumberImages(); $i++) {

            $image_name = $unique_file_name . '_' . ($i + 1) . '.png';
            $upload_path = FPD_Image_Utils::get_upload_path($uploads_dir, $image_name, 'png');
            $temp_image_path = $upload_path['full_path'];

            $im->setIteratorIndex($i);
            $im->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
            $im->setImageFormat('png32');
            $im->writeImage($temp_image_path);

            if (isset($_GET['filter'])) {

                FPD_Image_Utils::apply_imagick_filter($temp_image_path, $_GET['filter']);
            }

            $pdf_images[] = array(
                'filename' => $image_name,
                'image_url' => $uploads_dir_url . $upload_path['date_path']
            );
        }

        echo json_encode(array(
            'pdf_images' => $pdf_images,
        ));

        $im->clear();
        unset($im);
    } catch (ImagickException $e) {
        // Log detailed error server-side for debugging
        error_log(sprintf(
            'FPD PDF Processing Error: %s in %s (Path: %s)',
            $e->getMessage(),
            __FILE__,
            $pdf_path
        ));

        // Return generic error to user (no sensitive information)
        echo json_encode(array(
            'error' => 'Failed to process PDF file. Please ensure the file is a valid PDF.'
        ));
    }
} else {

    echo json_encode(array(
        'error' => 'PHP Issue - move_upload_file failed.',
        'filename' => $filename
    ));
}
?>