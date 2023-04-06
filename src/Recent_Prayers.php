<?php
/**
 * Intercessor Recent Prayers Widget
 *
 * @package     Intercessor
 * @subpackage  Includes/Recent_Prayers
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       0.9.5
 */

namespace Intercessor;

use function intercessor_get_option;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Intercessor Recent Prayers class.
 *
 * @since 0.9.5
 */
class Recent_Prayers extends \WP_Widget {

	/**
	 * Unique identifier for this widget.
	 *
	 * Will also serve as the widget class.
	 *
	 * @var    string
	 * @since  0.9.5
	 */
	protected $widget_slug = 'intercessor-recent-requests';

	/**
	 * Widget name displayed in Widgets dashboard.
	 * Set in __construct since __() shouldn't take a variable.
	 *
	 * @var    string
	 * @since  0.9.5
	 */
	protected $widget_name = '';

	/**
	 * Default widget title displayed in Widgets dashboard.
	 * Set in __construct since __() shouldn't take a variable.
	 *
	 * @var string
	 * @since  0.9.5
	 */
	protected $default_widget_title = '';

	/**
	 * Shortcode name for this widget
	 *
	 * @var    string
	 * @since  0.9.5
	 */
	protected static $shortcode = 'intercessor-recent-requests';

	/**
	 * Construct widget class.
	 *
	 * @since  0.9.5
	 */
	public function __construct() {

		$this->widget_name          = esc_html__( 'Recent Prayer Requests', 'intercessor' );
		$this->default_widget_title = esc_html__( 'Recent Prayer Requests', 'intercessor' );
		$widget_args = [
			'classname'                   => $this->widget_slug,
			'description'                 => esc_html__( 'Display your site&#8217;s most recent prayer requests.', 'intercessor' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		];

		parent::__construct(
			$this->widget_slug,
			$this->widget_name,
			$widget_args
		);

		// Include frontend script and style.
		if ( \is_active_widget( false, false, $this->id_base ) || \is_customize_preview() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );
		}

		// Clear cache on save.
		add_action( 'save_post', [ $this, 'flush_widget_cache' ] );
		add_action( 'deleted_post', [ $this, 'flush_widget_cache' ] );
		add_action( 'switch_theme', [ $this, 'flush_widget_cache' ] );

		// Add a shortcode for our widget.
		add_shortcode( self::$shortcode, [ __CLASS__, 'get_widget' ] );
	}

	/**
	 * Delete this widget's cache.
	 *
	 * Note: Could also delete any transients
	 * delete_transient( 'some-transient-generated-by-this-widget' );
	 *
	 * @since  0.9.5
	 */
	public function flush_widget_cache() {
		wp_cache_delete( $this->widget_slug, 'widget' );
		delete_transient( $this->id );
	}

	/**
	 * Include frontend script and style.
	 *
	 * @since 0.9.5
	 */
	public function frontend_scripts() {
		// Use minified libraries if SCRIPT_DEBUG is turned off.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$styles_url = INTERCESSOR_URL . 'assets/css/recent-prayers' . $suffix . '.css';
		$script_url = INTERCESSOR_URL . 'assets/js/frontend/intercessor-ajax' . $suffix . '.js';

		// Register and enqueue widget style.
		wp_register_style( 'intercessor-recent-prayers', $styles_url, [], INTERCESSOR_VERSION, true );
		wp_enqueue_style( 'intercessor-recent-prayers' );
	
		// Enqueue widget script.
		wp_enqueue_script( 'intercessor-ajax' );
	}

	/**
	 * Front-end display of widget.
	 *
	 * @since  0.9.5
	 *
	 * @param  array $args     The widget arguments set up when a sidebar is registered.
	 * @param  array $instance The widget settings as set by user.
	 */
	public function widget( $args, $instance ) {

		// Set widget attributes.
		$title = apply_filters(
			'widget_title',
			$instance['title'],
			$instance,
			$this->id_base
		);

		$prayers_number = ( ! empty( $instance['prayers_number'] ) ) ? absint( $instance['prayers_number'] ) : 5;
		if ( ! $prayers_number ) {
			$prayers_number = 5;
		}

		$prayed_label = intercessor_get_option( 'prayed_for_label', esc_html__( 'I Prayed', 'intercessor' ) );
		$button_color = intercessor_get_option( 'button_background_color', '#00bfef' );
		$border_color = intercessor_get_option( 'button_border_color', '#0094d3' );
		$font_color   = intercessor_get_option( 'button_font_color', '#ffffff' );
		$title_color  = intercessor_get_option( 'widget_title_color' );
		$main_color   = ! empty( $instance['prayer_title'] ) ? sanitize_text_field( $instance['prayer_title'] ) : $title_color;
		$page_id      = intercessor_get_option( 'prayers_page' );
		$prayers_url  = \get_permalink( $page_id );
		$button_text  = ! empty( $instance['button_text'] ) ? sanitize_text_field( $instance['button_text'] ) : $prayed_label;
		$display_date = $instance['display_date'] ?? false;
		$show_counts  = $instance['show_counts'] ?? false;
		$show_more    = $instance['show_more'] ?? false;
		$order        = $instance['order'];
		$orderby      = $instance['orderby'];
		$words_limit  = ! empty( $instance['words_limit'] ) ? absint( $instance['words_limit'] ) : 80;

		/**
		 * Filter the arguments for the Recent Prayers widget.
		 *
		 * @since 0.9.5
		 *
		 * @see intercessor_get_prayers()
		 *
		 * @param array $args An array of arguments used to retrieve the recent prayers.
		 */
		$recent_args = apply_filters(
			'widget_prayers_args',
			[
				'number'  => $prayers_number,
				'status'  => 'active',
				'order'   => $order,
				'orderby' => $orderby,
			]
		);

		?>
		<style type="text/css" media="screen">
			/*<![CDATA[*/
			.intercessor-recent-requests .intercessor-show-more {
				background-color: <?php echo esc_attr( $button_color ); ?>;
				border: 1px solid <?php echo esc_attr( $border_color ); ?>;
				font: 12px <?php echo esc_attr( $font_color ); ?>;
			}

			.intercessor-recent-requests .prayer-title {
				color: <?php echo esc_attr( $main_color ); ?>
			}

			/*]]>*/
		</style>

		<?php
		$prayers = \intercessor_get_items( 'prayer', $recent_args );

		if ( ! empty( $prayers ) ) :

			echo $args['before_widget'];

			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title'];
			}
			?>
			<ul>
			<?php
			foreach ( $prayers as $prayer ) :
				$prayer_id    = absint( $prayer->id );
				$message      = stripslashes( $prayer->message );
				$date         = esc_attr( $prayer->date_created );
				$gmt_offset   = \get_option( 'gmt_offset' );
				$prayer_date  = \intercessor_time_ago( $date, $gmt_offset );
				$counts       = \intercessor_get_prayed_for_counts( $prayer_id );
				$display_name = \intercessor_get_prayer_name( $prayer_id );
				$prayer_title = stripslashes( $prayer->title );

				$prayed_for = sprintf(
					// Translators: Lifted number of times.
					esc_html__( 'Lifted %d times', 'intercessor' ),
					$counts
				);
				$received = sprintf(
					// Translators: Received date.
					esc_html__( 'Received: %s', 'intercessor' ),
					$prayer_date
				);
				$submitted = sprintf(
					// Translators: Submitted by.
					esc_html__( 'Submitted By: %s', 'intercessor' ),
					$display_name
				);
				?>
				<li class="prayers" id="<?php echo esc_attr( $prayer_id ); ?>">
					<h5 class="prayer-title">
						<?php echo esc_attr( $prayer_title ); ?>
					</h5>

					<div class="intercessor-requester">
						<?php echo esc_attr( $submitted ); ?>
					</div>

					<div class="prayer-list-counter">
						<form id="intercessor_update_counts" action="" method="post">
							<input type="hidden" name="prayer_id" value="<?php echo esc_attr( $prayer_id ); ?>" class="id"/>
							<input type="submit" name="intercessor_prayed_updater" class="prayed-updater intercessor-submit" value="<?php echo esc_attr( $button_text ); ?>"/>
							<?php wp_nonce_field( 'praying_nonce', 'intercessor_update_prayed_nonce' ); ?>
						</form>

						<?php if ( $show_counts ) : ?>
							<div class="prayed-for"><?php echo esc_attr( $prayed_for ); ?></div>
						<?php endif; ?>
					</div>

					<div class="recent-prayer-message">
						<?php echo esc_attr( stripslashes( intercessor_limit_text( $message, $words_limit ) ) ); ?>
					</div>

					<?php if ( $display_date ) : ?>
					<div class="recent-prayer-date">
						<?php echo esc_attr( $received ); ?>
					</div>
					<?php endif; ?>

				</li>
			<?php endforeach; ?>

			</ul>
			<?php if ( $show_more ) : ?>
				<a class="intercessor-show-more" href="<?php echo esc_url( $prayers_url ); ?>">
					<?php echo esc_html__( 'Show more prayers', 'intercessor' ); ?>
				</a>
				<?php
			endif;
			echo $args['after_widget']; // phpcs:ignore

			// Reset the global postdata.
			wp_reset_postdata();

		else :
			?>
			<div class="no-prayers">
				<?php echo esc_html__( 'There are no prayer requests to display', 'intercessor' ); ?>
			</div>
			<?php
		endif;

	}

	/**
	 * Return the widget/shortcode output
	 *
	 * @since  0.9.5
	 *
	 * @param  array $atts Array of widget/shortcode attributes/args.
	 * @return string      Widget output
	 */
	public static function get_widget( $atts ) {

		$defaults = [
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
			'title'         => '',
		];

		// Parse defaults and create a shortcode.
		$atts = shortcode_atts( $defaults, (array) $atts, self::$shortcode );

		// Start an output buffer.
		ob_start();

		// Start widget markup.
		echo $atts['before_widget']; // phpcs:ignore

		// Maybe display widget title.
		echo ( $atts['title'] ) ? $atts['before_title'] . esc_html( $atts['title'] ) . $atts['after_title'] : '' ; // phpcs:ignore

		// End the widget markup.
		echo $atts['after_widget']; // phpcs:ignore

		// Return the output buffer.
		return ob_get_clean();
	}

	/**
	 * Update form values as they are saved.
	 *
	 * @since  0.9.5
	 *
	 * @param  array $new_instance New settings for this instance as input by the user.
	 * @param  array $old_instance Old settings for this instance.
	 * @return array               Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {

		// Previously saved values.
		$instance = $old_instance;

		// Sanity check new data existing.
		$instance['title']          = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['prayer_title']   = ! empty( $new_instance['prayer_title'] ) ? sanitize_text_field( $new_instance['prayer_title'] ) : '';
		$instance['button_text']    = ! empty( $new_instance['button_text'] ) ? sanitize_text_field( $new_instance['button_text'] ) : '';
		$instance['display_date']   = isset( $new_instance['display_date'] ) ? (bool) $new_instance['display_date'] : false;
		$instance['show_counts']    = isset( $new_instance['show_counts'] ) ? (bool) $new_instance['show_counts'] : false;
		$instance['show_more']      = isset( $new_instance['show_more'] ) ? (bool) $new_instance['show_more'] : false;
		$instance['prayers_number'] = (int) $new_instance['prayers_number'];
		$instance['order']          = $new_instance['order'];
		$instance['orderby']        = $new_instance['orderby'];
		$instance['words_limit']    = $new_instance['words_limit'];

		// Flush cache.
		$this->flush_widget_cache();

		return $instance;
	}

	/**
	 * Back-end widget form with defaults.
	 *
	 * @since  0.9.5
	 *
	 * @param  array $instance Current settings.
	 */
	public function form( $instance ) {

		// Set defaults.
		$defaults = [
			'title'          => $this->default_widget_title,
			'prayer_title'   => '',
			'button_text'    => esc_html__( 'I Prayed', 'intercessor' ),
			'prayers_number' => 5,
			'display_date'   => false,
			'show_counts'    => false,
			'show_more'      => false,
			'order'          => 'DESC',
			'orderby'        => 'id',
			'words_limit'    => 80,
		];

		// Parse args.
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'intercessor' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>


		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'prayer_title' ) ); ?>"><?php esc_html_e( 'Prayer Request Title Color:', 'intercessor' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'prayer_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'prayer_title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['prayer_title'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"><?php esc_html_e( 'Button Text:', 'intercessor' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['button_text'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'prayers_number' ) ); ?>"><?php esc_html_e( 'Number of prayers to show:', 'intercessor' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'prayers_number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'prayers_number' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $instance['prayers_number'] ); ?>" size="3" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'words_limit' ) ); ?>"><?php esc_html_e( 'Number of words to display on the message section. Default is 80 words:', 'intercessor' ); ?></label>
			<input class="text" id="<?php echo esc_attr( $this->get_field_id( 'words_limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'words_limit' ) ); ?>" type="number" step="5" min="50" max="500" value="<?php echo esc_attr( $instance['words_limit'] ); ?>" size="100" />
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php checked( $instance['display_date'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'display_date' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'display_date' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'display_date' ) ); ?>"><?php esc_html_e( 'Display prayers date?', 'intercessor' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php checked( $instance['show_counts'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_counts' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_counts' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_counts' ) ); ?>"><?php esc_html_e( 'Display prayed counts?', 'intercessor' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox"<?php checked( $instance['show_more'] ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_more' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_more' ) ); ?>" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_more' ) ); ?>"><?php esc_html_e( 'Display a button for more prayers?', 'intercessor' ); ?></label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Orderby:', 'intercessor' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" style="width:100%;">
				<option value="id" <?php selected( $instance['orderby'], 'ID' ); ?>><?php esc_html_e( 'ID', 'intercessor' ); ?></option>
				<option value="start_date" <?php selected( $instance['orderby'], 'start_date' ); ?>><?php esc_html_e( 'Date', 'intercessor' ); ?></option>
				<option value="random" <?php selected( $instance['orderby'], 'random' ); ?>><?php esc_html_e( 'Random', 'intercessor' ); ?></option>
				<option value="requester" <?php selected( $instance['orderby'], 'requester' ); ?>><?php esc_html_e( 'Requester', 'intercessor' ); ?></option>
				<option value="title" <?php selected( $instance['orderby'], 'title' ); ?>><?php esc_html_e( 'Title', 'intercessor' ); ?></option>
			</select>
		</p>


		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Prayers Order:', 'intercessor' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" style="width:100%;">
				<option value="DESC" <?php selected( $instance['order'], 'DESC' ); ?>><?php esc_html_e( 'DESC', 'intercessor' ); ?></option>
				<option value="ASC" <?php selected( $instance['order'], 'ASC' ); ?>><?php esc_html_e( 'ASC', 'intercessor' ); ?></option>
			</select>
		</p>
		<?php
	}
}
