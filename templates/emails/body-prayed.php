<?php
/**
 * Email Body
 *
 * @author 	Intercessor
 * @package Intercessor/Templates/Emails
 * @version 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<table style="text-align: center !important; width: 100%; table-layout: fixed;">
	<tbody>
	<tr>
		<td colspan="3" style="padding: 0 0 30px;">
			<h3 style="margin: 0;"><?php echo date( 'F j, Y' ); ?></h3>
			<p style="margin: 0;"><?php printf( __( 'Happy %1$s!', 'intercessor' ), date( 'l', current_time( 'timestamp' ) ) ); ?></p>
		</td>
	</tr>

	<tr>
		<td colspan="3" style="text-align: left !important;">
			<h3 style="margin: 0; padding-left: 40px;"><?php _e( 'Your prayer request(s):', 'intercessor' ); ?></h3>
			{intercessor_email_tag_requester_prayer_reports}
		</td>
	</tr>

	</tbody>
</table>

