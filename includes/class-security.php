<?php
/**
 * Security utilities for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Security
 *
 * Provides security-related helper methods for nonce verification,
 * capability checks, and safe output rendering.
 */
class Security {

	/**
	 * Nonce action prefix.
	 *
	 * @var string
	 */
	const NONCE_PREFIX = 'nitroman_';

	/**
	 * Required capability for managing plugin settings.
	 *
	 * @var string
	 */
	const REQUIRED_CAPABILITY = 'manage_options';

	/**
	 * Verify an admin request (nonce + capability).
	 *
	 * @param string $action The nonce action suffix.
	 * @param string $nonce_key The key in $_POST or $_GET containing the nonce.
	 * @param string $method Request method to check ('POST' or 'GET').
	 * @return bool True if valid.
	 */
	public static function verify_request( $action, $nonce_key = '_wpnonce', $method = 'POST' ) {
		// Check user capability.
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			return false;
		}

		// Get the nonce value.
		$nonce = '';
		if ( 'POST' === $method ) {
			$nonce = isset( $_POST[ $nonce_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$nonce = isset( $_GET[ $nonce_key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $nonce_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, self::NONCE_PREFIX . $action );
	}

	/**
	 * Generate a nonce field for a form.
	 *
	 * @param string $action The action suffix.
	 * @return void
	 */
	public static function nonce_field( $action ) {
		wp_nonce_field( self::NONCE_PREFIX . $action, self::NONCE_PREFIX . $action . '_nonce' );
	}

	/**
	 * Create a nonce URL.
	 *
	 * @param string $url     The base URL.
	 * @param string $action  The action suffix.
	 * @return string The URL with nonce parameter.
	 */
	public static function nonce_url( $url, $action ) {
		return wp_nonce_url( $url, self::NONCE_PREFIX . $action, self::NONCE_PREFIX . $action . '_nonce' );
	}

	/**
	 * Check if the current user has the required capability.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::REQUIRED_CAPABILITY );
	}

	/**
	 * Die with an unauthorized message if the user lacks permissions.
	 *
	 * @return void
	 */
	public static function check_permissions_or_die() {
		if ( ! self::current_user_can_manage() ) {
			wp_die(
				esc_html__( 'You do not have permission to access this page.', 'nitroman' ),
				esc_html__( 'Unauthorized', 'nitroman' ),
				array( 'response' => 403 )
			);
		}
	}

	/**
	 * Sanitize a boolean option value.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return bool
	 */
	public static function sanitize_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize an integer option value within bounds.
	 *
	 * @param mixed $value The value to sanitize.
	 * @param int   $min   Minimum allowed value.
	 * @param int   $max   Maximum allowed value.
	 * @return int
	 */
	public static function sanitize_int( $value, $min = 0, $max = PHP_INT_MAX ) {
		$value = (int) $value;
		return max( $min, min( $max, $value ) );
	}

	/**
	 * Sanitize a select/radio option against allowed values.
	 *
	 * @param string $value   The value to check.
	 * @param array  $allowed Allowed values.
	 * @param string $default Default if invalid.
	 * @return string
	 */
	public static function sanitize_select( $value, $allowed, $default = '' ) {
		return in_array( $value, $allowed, true ) ? $value : $default;
	}
}
