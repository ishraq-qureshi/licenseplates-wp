<?php

if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once 'woo-includes/woo-functions.php';
}

class Af_Logger {

	/**
	 * Log source name.
	 *
	 * @var string
	 */
	private static $source = 'woocommerce-anti-fraud';

	/**
	 * Pretty print arrays and objects.
	 *
	 * @param mixed $log Data to log.
	 * @return string
	 */
	private static function pretty_print( $log ) {
		if ( is_object( $log ) || is_array( $log ) ) {
			return wp_json_encode( $log );
		}
		return $log;
	}

	/**
	 * Trace level log.
	 *
	 * @param mixed $log Data to log.
	 */
	public static function trace( $log ) {
		$logger = wc_get_logger();
		$logger->info( self::pretty_print( $log ), array( 'source' => self::$source ) );
	}

	/**
	 * Debug level log.
	 *
	 * @param mixed $log Data to log.
	 */
	public static function debug( $log ) {
		if ( 'yes' === get_option( 'wc_af_enable_debug_logging' ) ) {
			$logger = wc_get_logger();
			$logger->debug( self::pretty_print( $log ), array( 'source' => self::$source ) );
		}
	}

	/**
	 * Error level log.
	 *
	 * @param mixed $log Data to log.
	 */
	public static function error( $log ) {
		$logger = wc_get_logger();
		$logger->error( self::pretty_print( $log ), array( 'source' => self::$source ) );
	}
}
