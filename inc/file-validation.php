<?php
/**
 * Text-only upload validation for enterprise file processing.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const UESP_THEME_BINARY_ERROR         = 'Binary files are not supported';
const UESP_THEME_BINARY_ERROR_FRIENDLY = 'Binary files are not supported. Please upload text-based files only.';

/**
 * Return the only extensions this project is allowed to process.
 *
 * @return array<string,string>
 */
function uesp_theme_allowed_text_mimes() {
	return array(
		'php'  => 'text/x-php',
		'js'   => 'application/javascript',
		'css'  => 'text/css',
		'html' => 'text/html',
		'json' => 'application/json',
		'txt'  => 'text/plain',
	);
}

/**
 * Binary extensions that must never be processed by this theme.
 *
 * @return string[]
 */
function uesp_theme_blocked_binary_extensions() {
	return array( 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svgz', 'mp4', 'mov', 'avi', 'mkv', 'zip', 'rar', '7z', 'tar', 'gz', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'pdf', 'exe', 'dll', 'bin' );
}

/**
 * MIME signatures that are always binary for this project.
 *
 * @return string[]
 */
function uesp_theme_blocked_binary_mime_prefixes() {
	return array( 'image/', 'video/', 'audio/', 'font/', 'application/zip', 'application/x-rar', 'application/x-7z', 'application/gzip', 'application/octet-stream', 'application/pdf' );
}

/**
 * MIME types that are acceptable after server-side content sniffing.
 *
 * @return string[]
 */
function uesp_theme_allowed_detected_text_mimes() {
	return array( 'text/plain', 'text/x-php', 'text/css', 'text/html', 'text/javascript', 'application/javascript', 'application/x-javascript', 'application/json', 'application/x-empty' );
}

/**
 * Determine whether a temporary file contains binary bytes.
 *
 * @param string $path Temporary file path.
 * @return bool
 */
function uesp_theme_file_contains_binary_bytes( $path ) {
	$handle = @fopen( $path, 'rb' );
	if ( ! $handle ) {
		return true;
	}

	$sample = fread( $handle, 8192 );
	fclose( $handle );

	if ( false === $sample ) {
		return true;
	}

	return (bool) preg_match( '/[\x00-\x08\x0E-\x1F]/', $sample );
}

/**
 * Validate an uploaded file before any processing occurs.
 *
 * @param array<string,mixed> $file Upload array from $_FILES.
 * @return true|WP_Error
 */
function uesp_theme_validate_text_upload( $file ) {
	if ( empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
		return new WP_Error( 'uesp_missing_file', esc_html__( 'Please choose a file to upload.', 'uesp-theme' ) );
	}

	$filename  = sanitize_file_name( wp_unslash( $file['name'] ) );
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( in_array( $extension, uesp_theme_blocked_binary_extensions(), true ) ) {
		return new WP_Error( 'uesp_binary_file', UESP_THEME_BINARY_ERROR_FRIENDLY );
	}

	$allowed_mimes = uesp_theme_allowed_text_mimes();
	if ( ! array_key_exists( $extension, $allowed_mimes ) ) {
		return new WP_Error( 'uesp_unsupported_extension', esc_html__( 'Unsupported file type. Please upload PHP, JS, CSS, HTML, JSON, or TXT files only.', 'uesp-theme' ) );
	}

	$filetype = wp_check_filetype( $filename, $allowed_mimes );
	if ( empty( $filetype['ext'] ) || empty( $filetype['type'] ) || $extension !== $filetype['ext'] ) {
		return new WP_Error( 'uesp_invalid_mime', UESP_THEME_BINARY_ERROR_FRIENDLY );
	}

	$detected_mime = '';
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( $finfo ) {
			$detected_mime = (string) finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );
		}
	} elseif ( function_exists( 'mime_content_type' ) ) {
		$detected_mime = (string) mime_content_type( $file['tmp_name'] );
	}

	foreach ( uesp_theme_blocked_binary_mime_prefixes() as $blocked_mime ) {
		if ( $detected_mime && 0 === strpos( $detected_mime, $blocked_mime ) ) {
			return new WP_Error( 'uesp_binary_mime', UESP_THEME_BINARY_ERROR_FRIENDLY );
		}
	}

	if ( $detected_mime && ! in_array( $detected_mime, uesp_theme_allowed_detected_text_mimes(), true ) && 0 !== strpos( $detected_mime, 'text/' ) ) {
		return new WP_Error( 'uesp_invalid_mime', UESP_THEME_BINARY_ERROR_FRIENDLY );
	}

	if ( uesp_theme_file_contains_binary_bytes( $file['tmp_name'] ) ) {
		return new WP_Error( 'uesp_binary_content', UESP_THEME_BINARY_ERROR_FRIENDLY );
	}

	return true;
}

/**
 * Create deny rules in a text-upload directory to prevent execution.
 *
 * @param string $directory Absolute directory path.
 * @return void
 */
function uesp_theme_harden_text_upload_directory( $directory ) {
	if ( ! wp_mkdir_p( $directory ) ) {
		return;
	}

	$htaccess = trailingslashit( $directory ) . '.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		$rules = "Options -Indexes\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps .js .html .css .json .txt\nRemoveType .php .phtml .php3 .php4 .php5 .php7 .phps\n<FilesMatch \".*\">\n\tRequire all denied\n</FilesMatch>\n";
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $htaccess, $rules, FS_CHMOD_FILE );
		}
	}

	$index = trailingslashit( $directory ) . 'index.php';
	if ( ! file_exists( $index ) ) {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;
		if ( $wp_filesystem ) {
			$wp_filesystem->put_contents( $index, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}
	}
}

/**
 * Sanitize and store a validated text upload without executing it.
 *
 * @param string $field File input field name.
 * @return array<string,mixed>|WP_Error
 */
function uesp_theme_handle_text_upload( $field = 'file' ) {
	if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) {
		return new WP_Error( 'uesp_missing_file', esc_html__( 'Please choose a file to upload.', 'uesp-theme' ) );
	}

	$file       = $_FILES[ $field ];
	$validation = uesp_theme_validate_text_upload( $file );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}

	$upload = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => uesp_theme_allowed_text_mimes(),
		)
	);

	if ( isset( $upload['error'] ) ) {
		return new WP_Error( 'uesp_upload_error', esc_html( $upload['error'] ) );
	}

	$uploads       = wp_get_upload_dir();
	$text_dir      = trailingslashit( $uploads['basedir'] ) . 'uesp-text-uploads';
	$safe_basename = sanitize_file_name( basename( $upload['file'] ) );
	$stored_name   = $safe_basename . '.txt';
	$target        = trailingslashit( $text_dir ) . $stored_name;

	uesp_theme_harden_text_upload_directory( $text_dir );

	if ( ! @rename( $upload['file'], $target ) ) {
		@unlink( $upload['file'] );
		return new WP_Error( 'uesp_storage_error', esc_html__( 'Unable to store the text file safely.', 'uesp-theme' ) );
	}

	@chmod( $target, 0644 );

	return array(
		'file'     => esc_html( $target ),
		'filename' => esc_html( $stored_name ),
		'original' => esc_html( $safe_basename ),
		'type'     => esc_html( $upload['type'] ),
	);
}

/**
 * AJAX endpoint for validating/storing text-only files.
 *
 * @return void
 */
function uesp_theme_ajax_text_upload() {
	check_ajax_referer( 'uesp_theme_nonce', 'nonce' );

	$result = uesp_theme_handle_text_upload( 'file' );
	if ( is_wp_error( $result ) ) {
		$error_code = $result->get_error_code();
		$message    = in_array( $error_code, array( 'uesp_binary_file', 'uesp_binary_content', 'uesp_invalid_mime', 'uesp_binary_mime' ), true ) ? UESP_THEME_BINARY_ERROR : $result->get_error_message();

		wp_send_json(
			array(
				'success' => false,
				'message' => esc_html( $message ),
			),
			400
		);
	}

	wp_send_json_success(
		array(
			'message' => esc_html__( 'Text file uploaded successfully.', 'uesp-theme' ),
			'file'    => $result,
		)
	);
}
add_action( 'wp_ajax_uesp_text_upload', 'uesp_theme_ajax_text_upload' );
