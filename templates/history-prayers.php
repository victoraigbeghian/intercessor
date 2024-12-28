<?php
/**
 * Intercessor Prayer History Template.
 *
 * @since       0.9.5
 * @subpackage  Templates/Prayer_History
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0php GNU Public License
 * @package     Intercessor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $ipr_prayer_history_redirect;

/**
 * Print all errors.
 */
do_action( 'intercessor_print_errors' );

$history = new Intercessor\Prayer_History();
$history->print_errors();

$tweet   = intercessor_get_option( 'enable_prayer_tweet' );
$prayers = [];
$user    = '0';

if ( is_user_logged_in() ) {
	$user    = get_current_user_id();
	$number  = 15;
	$prayers = intercessor_user_prayers( $user, $number );
}

if ( ! empty( $prayers ) ) : ?>

	<div id="intercessor_user_history_main">
		<div class="intercessor_user_history_notice"></div>
		<h2><?php echo esc_html__( 'Your prayer requests are listed below', 'intercessor' ); ?></h2>
		<table id="intercessor_user_history" class="intercessor-table">
			<thead>
			<tr class="intercessor-prayer-row">
				<?php
				/**
				 * Fires in current user prayer history table, before the header row start.
				 *
				 * Allows you to add new <th> elements to the header, before other headers in the row.
				 *
				 * @param array $prayers The available prayer requests.
				 *
				 * @since 0.9.5
				 */
				do_action( 'intercessor_prayer_history_header_before', $prayers );

				?>
				<th class="intercessor-prayer-id"><?php esc_html_e( 'ID', 'intercessor' ); ?></th>
				<th class="intercessor-prayer-date"><?php esc_html_e( 'Date', 'intercessor' ); ?></th>
				<th class="intercessor-prayer-count"><?php esc_html_e( 'Counts', 'intercessor' ); ?></th>
				<th class="intercessor-prayer-details"><?php esc_html_e( 'Prayer Details', 'intercessor' ); ?></th>
				<th class="intercessor-prayer-status"><?php esc_html_e( 'Status', 'intercessor' ); ?></th>
				<?php

				/**
				 * Fires in current user prayer history table, after the header row ends.
				 *
				 * Allows you to add new <th> elements to the header, after other headers in the row.
				 *
				 * @param array $prayers The available prayer requests.
				 *
				 * @since 0.9.5
				 */
				do_action( 'intercessor_prayer_history_header_after', $prayers );
				?>
			</tr>
			</thead>
			<?php
			foreach ( $prayers as $prayer ) :
				$prayer_id    = $prayer->id;
				$requester_id = intercessor_get_prayer_requester_id( $prayer_id );
				$requester    = new Intercessor\Requester( $requester_id );
				$first_name   = $requester->get_first_name();
				$last_name    = $requester->get_last_name();
				$required     = $history->get_required_fields();
				$prayed_args  = [
					'date_created_query' => false,
					'prayer_id'          => $prayer->id,
				];
				$prayed_text  = intercessor_count_prayed( $prayed_args );
			?>
				<tr class="intercessor-prayer-row">
					<?php
					/**
					 * Fires in current user prayer history table, before the row statrs.
					 *
					 * Allows you to add new <td> elements to the row, before other elements in the row.
					 *
					 * @param int $prayer_id The ID of the prayer.
					 *
					 * @since 0.9.5
					 */
					do_action( 'intercessor_prayer_history_row_start', $prayer_id );

					$prayer_date    = date_i18n( get_option( 'date_format' ), strtotime( $prayer->date ) );
					$prayer_title   = stripslashes( $prayer->title );
					$prayer_message = stripslashes( $prayer->message );
					$prayer_share   = esc_attr( $prayer->share );
					$prayer_status  = esc_attr( $prayer->status );
					$status_label   = intercessor_get_prayer_status_label( $prayer_id );
					$prayer_notify  = esc_attr( $prayer->notify );
					?>
					<td class="intercessor-prayer-id">#<?php echo esc_attr( $prayer_id ); ?></td>
					<td class="intercessor-prayer-date"><?php echo esc_attr( $prayer_date ); ?></td>
					<td class="intercessor-prayer-count">
						<span class="intercessor-prayer-count">
							<?php echo esc_attr( $prayed_text ); ?>
						</span>
					</td>

					<td class="intercessor-prayer-details">
						<p class="prayer-title">
							<?php echo esc_html( stripslashes( $prayer_title ) ); ?>
						</p>
						<p class="prayer-message">
							<?php echo esc_html( stripslashes( $prayer_message ) ); ?>
						</p>
						<div class="prayer-history-actions">
							<div id="ipr_history_prayer_actions">
							<?php
								/**
								 *  Fires before actions on the prayer request.
								 *
								 * @since 0.9.5
								 */
								do_action( 'intercessor_before_history_actions' );

								// Setup submit button styling (from options).
								$color      = intercessor_get_option( 'button_font_color', '#fff' );
								$color      = ( 'inherit' === $color ) ? '' : $color;
								$background = intercessor_get_option( 'button_background_color', '#00bfef' );
								$background = ( 'inherit' === $background ) ? '' : $background;
								$border     = intercessor_get_option( 'button_border_color', '#0094d3' );
								$border     = ( 'inherit' === $border ) ? '' : $border;
								
								?>
								<div class="ipr-prayers-history-edit" style="display:none;">
									<form id="ipr_prayers_history" action="<?php intercessor_get_history_page_uri(); ?>" method="post" class="intercessor-row">
										<div class="col form-column">
											<h4 class="intercessor-history-answered">
												<?php esc_html_e( 'You could check the box below if this prayer request is answered and click the update button.', 'intercessor' ); ?>
											</h4>
											<input type="checkbox" name="ipr_history_answered_prayer" value="1">
											<label for="intercessor-answered-prayer" class="intercessor-label">
												<?php esc_html_e( 'Answered Prayer?', 'intercessor' ); ?>
											</label>
										</div>

										<div class="col form-column">
											<h4 class="intercessor-history-answered">
												<?php esc_html_e( 'You could also edit the contents of your prayer request and click the update button.', 'intercessor' ); ?>
											</h4>
											<label class="intercessor-label" for="intercessor-history-title">
												<?php esc_html_e( 'Prayer Title', 'intercessor' ); ?>
												<?php if ( $required[ 'ipr_history_title' ] ) { ?>
													<span class="intercessor-required-indicator">*</span>
												<?php } ?>
											</label>
											<input class="intercessor-input" required="required" type="text"
													name="ipr_history_title"
													placeholder="<?php esc_html_e( 'Prayer Title', 'intercessor' ); ?>"
													id="intercessor-history-title" value="<?php echo esc_attr( $prayer_title ); ?>"
													aria-describedby="ipr-title-description" />
										</div>

										<div class="col form-column">
											<label class="intercessor-label" for="intercessor-history-message">
												<?php esc_html_e( 'Prayer Message', 'intercessor' ); ?>
												<?php if ( $required[ 'ipr_history_message' ] ) { ?>
													<span class="intercessor-required-indicator">*</span>
												<?php } ?>
											</label>
											<textarea cols="30" rows="4" class="intercessor-input" required="required" name="ipr_history_message" id="intercessor-history-message" aria-describedby="intercessor-message-description">
												<?php echo esc_attr( $prayer_message ); ?>
											</textarea>
										</div>


										<div class="col form-column">
											<label class="intercessor-label" for="intercessor-history-share">
												<?php esc_html_e( 'Prayer Share', 'intercessor' ); ?>
												<?php if ( $required[ 'ipr_history_share' ] ) { ?>
													<span class="intercessor-required-indicator">*</span>
												<?php } ?>
											</label>
											<?php 
											$selected = $prayer->share;
											?>
											<select name="ipr_history_share" id="ipr_history_share" data-nonce="<?php echo wp_create_nonce( 'ipr-history-share-nonce' ); ?>" class="intercessor-select">
												<option value="0" selected="selected" disabled="disabled">
													<?php esc_html_e( 'Please select an option', 'intercessor' ); ?>
												</option>
												<option data-type="freely" value="freely">
													<?php esc_html_e( 'Share freely', 'intercessor' ); ?>
												</option>
												<option data-type="anon" value="anon">
													<?php esc_html_e( 'Share anonymously', 'intercessor' ); ?>
												</option>
												<option data-type="personal" value="personal">
													<?php esc_html_e( 'Do not share - private prayer', 'intercessor' ); ?>
												</option>
												<?php if ( 1 === $tweet ) : ?>
												<option data-type="tweet" value="tweet">
													<?php esc_html_e( 'Share  and tweet', 'intercessor' ); ?>
												</option>
												<?php endif; ?>
											</select>
											<span class="intercessor-description" id="intercessor-share-description">
												<?php esc_html_e( 'Choose how we share your prayer request.', 'intercessor' ); ?>
											</span>
										</div>


										<div class="col form-column">
											<input name="ipr_history_notify" type="checkbox" id="ipr_history_notify" value="<?php echo $prayer_notify; ?>"/>
											<label for="ipr_history_notify">
												<?php echo esc_html__( 'Check this box if you want to be notified when this prayer request has been prayed for.', 'intercessor' ); ?>
											</label>
										</div>

										<style type="text/css" media="screen">
											/*<![CDATA[*/
											#intercessor_prayer_button {
												background-color: <?php echo $background; ?>;
												border-color: <?php echo $border; ?>;
												color: <?php echo $color; ?>;
												margin-right: '50px';
											}

											/*]]>*/
										</style>
										<input type="submit" name="ipr_history_delete" id="intercessor_prayer_button"class="intercessor-submit left"  value="<?php esc_html_e( 'Delete', 'intercessor' ); ?>" />
										<input type="submit" name="ipr_history_submit" id="intercessor_prayer_button" class="intercessor-submit right"  value="<?php esc_html_e( 'Update', 'intercessor' ); ?>" />
										<input type="hidden" name="ipr_history_id" value="<?php echo esc_attr( $prayer_id ); ?>"/>
										<input type="hidden" name="ipr_prayer_history_nonce" value="<?php echo wp_create_nonce( 'ipr-prayer-history-nonce' ); ?>"/>

									</form>
								</div>

								<div id="intercessor_prayers_dropdown" class="intercessor-prayers-dropdown">
									<?php
										// Allow prayer edit if status is active or personal.
										if ( 'active' === $prayer_status || 'personal' === $prayer_status ) :
									?>
		                            <a href="#" class="intercessor_prayers_links">
			                            <?php esc_html_e( 'Edit or Delete', 'intercessor' ); ?>
		                            </a>
		                            <a href="#" class="intercessor_prayers_links" style="display:none;">
			                            <?php esc_html_e( 'Cancel', 'intercessor' ); ?>
		                            </a>
									<?php
										endif;
									?>
	                            </div>
                            </div>

						</div>
					</td>
					<td class="intercessor-prayer-status">
						<?php
							echo esc_attr( $status_label ) . '</br>';
							$share = intercessor_get_prayer_share( $prayer_id );
							if ( 'anon' === $share ) {
								echo '<em>' . esc_html__( 'Anonymous', 'intercessor' ) . '</em>';
							}
						?>
					</td>
					<?php
					/**
					 * Fires in current user prayer history table, after the row ends.
					 *
					 * Allows you to add new <td> elements to the row, after other elements in the row.
					 *
					 * @since 0.9.5
					 *
					 * @param int   $prayer_id       The ID of the prayer.
					 */
					do_action( 'intercessor_prayer_history_row_end', $prayer->id );
					?>
				</tr>
			<?php endforeach; ?>

			<?php
			/**
			 * Fires in footer of user prayer history table.
			 *
			 * Allows you to add new <tfoot> elements to the row, after other elements in the row.
			 *
			 * @param int   $prayer_id       The ID of the prayer.
			 *
			 * @since 0.9.5
			 */
			do_action( 'intercessor_prayer_history_table_end', $prayer_id );
			?>
		</table>
		<div class="intercessor-pagination">
			<?php
			$page  = intercessor_get_current_page_number();
			$pages = absint( ceil( intercessor_count_prayers_of_requester() / 10 ) );
			$big   = 999999;

			echo paginate_links( array(
				'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'  => '?paged=%#%',
				'current' => $page,
				'total'   => $pages, // 20 items per page.
			) );
			?>
		</div>
	</div>
	<?php wp_reset_postdata(); ?>
<?php else : ?>
	<?php
		echo '<h4>' . esc_html__( 'It looks like you haven\'t submitted any prayer request.', 'intercessor' ) . '</h4>';
	?>
<?php endif;
