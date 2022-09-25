<?php
/**
 * Intercessor Cron Class
 *
 * @package     Intercessor
 * @subpackage  Classes/Cron
 * @copyright   Copyright (c) 2021, Victor Aigbeghian
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       1.0.0
 */

namespace Intercessor;

use function wp_next_scheduled;
use function wp_unschedule_event;
use function wp_schedule_event;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Cron Class
 *
 * This class handles scheduled events
 *
 * @since 1.0.0
 */
class Cron {
	/**
	 * Get things going
	 *
	 * @throws \Exception Throws exception.
	 * @see Cron::monthly_events() or Cron::weekly_events()
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_send_time();
		add_filter( 'cron_schedules', [ $this, 'add' ] );
		add_action( 'wp', [ $this, 'events' ] );
	}

	/**
	 * Registers new cron schedules
	 *
	 * @param array $schedules Array of schedules.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function add( $schedules ) : array {
		// Adds once weekly to the existing schedules.
		$schedules['weekly'] = [
			'interval' => 604800,
			'display'  => esc_html__( 'Once Weekly', 'intercessor' ),
		];

		$schedules['monthly'] = [
			'interval' => 2635200,
			'display'  => esc_html__( 'Once Monthly', 'intercessor' ),
		];

		return $schedules;
	}

	/**
	 * Schedules our events
	 *
	 * @return void
	 * @throws \Exception Throws exception.
	 * @since 1.0.0
	 */
	public function events() {
		$notify_period = \intercessor_get_option( 'notify_period', 'weekly' );
		$timestamp     = wp_next_scheduled( 'intercessor_notify_requester' );

		if ( ! defined( 'INTERCESSOR_DISABLE_NOTIFY_REQUESTER' ) ) {
			if ( 'monthly' === $notify_period ) {
				wp_unschedule_event( $timestamp, 'intercessor_notify_requester' );
				$this->monthly_events();
			} elseif ( 'daily' === $notify_period ) {
				wp_unschedule_event( $timestamp, 'intercessor_notify_requester' );
				$this->daily_events();
			} else {
				wp_unschedule_event( $timestamp, 'intercessor_notify_requester' );
				$this->weekly_events();
			}
		}
	}

	/**
	 * Schedule monthly events
	 *
	 * @access private
	 * @return void
	 * @throws \Exception Throws exception.
	 * @since 1.0.0
	 */
	private function monthly_events() {
		if ( ! wp_next_scheduled( 'intercessor_notify_requester' ) ) {
			wp_schedule_event(
				$this->setup_send_time(),
				'monthly',
				'intercessor_notify_requester'
			);
		}
	}

	/**
	 * Schedule weekly events
	 *
	 * @access private
	 * @return void
	 * @throws \Exception Throws exception.
	 * @since 1.0.0
	 */
	private function weekly_events() {
		if ( ! wp_next_scheduled( 'intercessor_notify_requester' ) ) {
			wp_schedule_event(
				$this->setup_send_time(),
				'weekly',
				'intercessor_notify_requester'
			);
		}
	}

	/**
	 * Schedule daily events
	 *
	 * @access private
	 * @return void
	 * @throws \Exception Throws exception.
	 * @since 1.0.0
	 */
	private function daily_events() {
		if ( ! wp_next_scheduled( 'intercessor_notify_requester' ) ) {
			wp_schedule_event(
				$this->setup_send_time(),
				'daily',
				'intercessor_notify_requester'
			);
		}
	}

	/**
	 * Setup time to send email to notify requesters.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @return false|int
	 * @throws \Exception Throws exception.
	 */
	private function setup_send_time() {
		$current_timezone   = \get_option( 'timezone_string' );
		$timezone_string    = ! empty( $current_timezone ) ? $current_timezone : 'UTC';
		$requester_timezone = new \DateTimeZone( $timezone_string );
		$current_datetime   = new \DateTime( 'now', $requester_timezone );

		// Return scheduled time for prayed for email.
		return strtotime(
			intercessor_get_option( 'send_email_time', 1700 ) . 'GMT' . $current_datetime->format( 'P' ),
			time()
		);
	}

}
