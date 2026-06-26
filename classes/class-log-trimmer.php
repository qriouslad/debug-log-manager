<?php

namespace DLM\Classes;

/**
 * Trims the debug log file and returns bounded tail chunks based on PHP memory limit.
 *
 * @since 2.5.0
 */
class Log_Trimmer {

	/**
	 * Percentage of memory limit used as the log size cap.
	 *
	 * @var int
	 */
	const CAP_PERCENT = 75;

	/**
	 * Fallback base size in bytes when memory_limit is unlimited (-1).
	 *
	 * @var int
	 */
	const FALLBACK_BASE_BYTES = 67108864; // 64 MB.

	/**
	 * Maximum bytes to read when parsing log entries in the admin UI.
	 *
	 * @var int
	 */
	const MAX_PARSE_WINDOW_BYTES = 33554432; // 32 MB.

	/**
	 * Minimum bytes to read when parsing log entries in the admin UI.
	 *
	 * @var int
	 */
	const MIN_PARSE_WINDOW_BYTES = 4194304; // 4 MB.

	/**
	 * Returns the configured PHP memory limit in bytes.
	 *
	 * @since 2.5.2
	 *
	 * @return int
	 */
	private function get_memory_limit_bytes() {

		$memory_limit = ini_get( 'memory_limit' );

		if ( '-1' === $memory_limit ) {
			$limit_bytes = self::FALLBACK_BASE_BYTES;
		} else {
			$limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
		}

		if ( $limit_bytes <= 0 ) {
			$limit_bytes = self::FALLBACK_BASE_BYTES;
		}

		return $limit_bytes;
	}

	/**
	 * Returns the maximum allowed debug log file size in bytes.
	 *
	 * @since 2.5.0
	 *
	 * @return int
	 */
	public function get_max_log_size_bytes() {

		$limit_bytes = $this->get_memory_limit_bytes();

		return (int) floor( $limit_bytes * ( self::CAP_PERCENT / 100 ) );
	}

	/**
	 * Returns the maximum bytes the parser should read into memory at once.
	 *
	 * @since 2.5.2
	 *
	 * @return int
	 */
	public function get_parse_window_bytes() {

		$limit_bytes        = $this->get_memory_limit_bytes();
		$parse_window_bytes = (int) floor( $limit_bytes / 8 );

		if ( $parse_window_bytes < self::MIN_PARSE_WINDOW_BYTES ) {
			$parse_window_bytes = self::MIN_PARSE_WINDOW_BYTES;
		}

		return (int) min( self::MAX_PARSE_WINDOW_BYTES, $parse_window_bytes );
	}

	/**
	 * Reads a bounded chunk from the end of the log file for parsing.
	 *
	 * @since 2.5.2
	 *
	 * @param string   $path      Full path to the log file.
	 * @param int|null $max_bytes Optional. Maximum bytes to read. Defaults to parse window size.
	 * @return string|false Tail chunk on success, false on failure.
	 */
	public function read_tail_chunk( $path, $max_bytes = null ) {

		if ( empty( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
			return false;
		}

		if ( null === $max_bytes ) {
			$max_bytes = $this->get_parse_window_bytes();
		}

		$max_bytes = (int) $max_bytes;

		if ( $max_bytes <= 0 ) {
			return false;
		}

		return $this->get_tail_chunk( $path, $max_bytes );
	}

	/**
	 * Trims the log file if it exceeds the size cap.
	 *
	 * @since 2.5.0
	 *
	 * @param string|null $path Optional. Full path to the log file.
	 * @return bool True if trimmed, false if no trim was needed or trim failed.
	 */
	public function maybe_trim_log( $path = null ) {

		if ( null === $path ) {
			$path = get_option( 'debug_log_manager_file_path' );
		}

		if ( empty( $path ) || ! is_file( $path ) ) {
			return false;
		}

		$max_bytes = $this->get_max_log_size_bytes();
		$file_size = (int) filesize( $path );

		if ( $file_size <= $max_bytes ) {
			return false;
		}

		return $this->trim_log_file( $path, $max_bytes );
	}

	/**
	 * Trims the log file by keeping only the newest entries up to max_bytes.
	 *
	 * @since 2.5.0
	 *
	 * @param string $path      Full path to the log file.
	 * @param int    $max_bytes Maximum file size in bytes.
	 * @return bool True on success, false on failure.
	 */
	public function trim_log_file( $path, $max_bytes ) {

		$file_size = (int) filesize( $path );

		if ( $file_size <= $max_bytes ) {
			return false;
		}

		$chunk = $this->get_tail_chunk( $path, $max_bytes );

		if ( false === $chunk || '' === $chunk ) {
			return false;
		}

		$notice = $this->get_trim_notice_line();
		$content = $notice . $chunk;

		$dir       = dirname( $path );
		$temp_path = $dir . '/' . wp_unique_filename( $dir, 'dlm-trim-temp.log' );

		if ( false === file_put_contents( $temp_path, $content ) ) {
			return false;
		}

		if ( ! rename( $temp_path, $path ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- cleanup temp file on failure.
			@unlink( $temp_path );
			return false;
		}

		return true;
	}

	/**
	 * Reads the newest portion of the log file up to the requested size.
	 *
	 * @since 2.5.2
	 *
	 * @param string $path      Full path to the log file.
	 * @param int    $max_bytes Maximum bytes to read.
	 * @return string|false
	 */
	private function get_tail_chunk( $path, $max_bytes ) {

		$file_size = (int) filesize( $path );

		if ( $file_size <= 0 ) {
			return '';
		}

		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return false;
		}

		$read_size = min( $max_bytes, $file_size );
		$offset    = $file_size - $read_size;

		if ( fseek( $handle, $offset, SEEK_SET ) !== 0 ) {
			fclose( $handle );
			return false;
		}

		$chunk = fread( $handle, $read_size );
		fclose( $handle );

		if ( false === $chunk || '' === $chunk ) {
			return $chunk;
		}

		if ( $offset > 0 ) {
			$boundary_offset = $this->find_entry_boundary_offset( $chunk );

			if ( $boundary_offset > 0 ) {
				$chunk = substr( $chunk, $boundary_offset );
			}
		}

		return $chunk;
	}

	/**
	 * Finds the byte offset of the first complete log entry in a tail chunk.
	 *
	 * @since 2.5.0
	 *
	 * @param string $chunk Tail portion of the log file.
	 * @return int Byte offset to start keeping content, or 0 if none found.
	 */
	private function find_entry_boundary_offset( $chunk ) {

		if ( preg_match( '/\[\d{2}-[A-Za-z]{3}-\d{4}/', $chunk, $matches, PREG_OFFSET_CAPTURE ) ) {
			$match_pos = $matches[0][1];

			// Walk back to the opening bracket of this entry.
			$bracket_pos = strrpos( substr( $chunk, 0, $match_pos + 1 ), '[' );

			if ( false !== $bracket_pos ) {
				return $bracket_pos;
			}
		}

		return 0;
	}

	/**
	 * Returns a single-line notice to prepend when the log is trimmed.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	private function get_trim_notice_line() {

		if ( function_exists( 'wp_date' ) ) {
			$timestamp = wp_date( 'd-M-Y H:i:s' ) . ' UTC';
		} else {
			$timestamp = gmdate( 'd-M-Y H:i:s' ) . ' UTC';
		}

		$message = __( 'Debug Log Manager: Older log entries were removed to stay within the size limit.', 'debug-log-manager' );

		return '[' . $timestamp . '] ' . $message . PHP_EOL;
	}

}
