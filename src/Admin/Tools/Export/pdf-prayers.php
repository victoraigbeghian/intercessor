<?php
/**
 * Exports PDF Functions
 *
 * These are functions are used for exporting pdf of requests from Intercessor.
 *
 * @package     Intercessor
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0.php GNU Public License
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Generate pdf data.
 *
 * @since 1.0.0
 */
function intercessor_generate_pdf() {

	if ( ! current_user_can( 'view_prayer_reports' ) ) {
		wp_die(
			esc_html__( 'You do not have permission to generate prayer requests PDF.', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			[ 'response' => 403 ]
		);
	}

	if ( ! wp_verify_nonce( $_POST['intercessor_export_settings_nonce'], 'intercessor_export_pdf_prayers_nonce' ) ) {
		wp_die(
			esc_html__( 'Nonce verification failed.', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			[ 'response' => 403 ]
		);
	}

	if ( ! file_exists( INTERCESSOR_DIR . '/src/Admin/Tools/Pdf.php' ) ) {
		wp_die(
			esc_html__( 'Main Dependency is Missing.', 'intercessor' ),
			esc_html__( 'Error', 'intercessor' ),
			[ 'response' => 403 ]
		);
	}

	$daterange = utf8_decode(
		sprintf(
		/* translators: 1: start date 2: end date */
			esc_html__( '%1$s to %2$s', 'intercessor' ),
			date_i18n( intercessor_date_format(), mktime( 0, 0, 0, 1, 1, date( 'Y' ) ) ),
			date_i18n( intercessor_date_format() )
		)
	);

	$pdf          = new \Intercessor\Admin\Tools\Pdf( 'L', 'mm', 'A', true, 'UTF-8', false );
	$default_font = apply_filters( 'intercessor_pdf_default_font', 'Helvetica' );
	$custom_font  = 'dejavusans';
	$font_style   = '';

	if ( file_exists( INTERCESSOR_DIR . '/vendor/tecnickcom/tcpdf/fonts/CODE2000.TTF' ) ) {
		TCPDF_FONTS::addTTFfont( INTERCESSOR_DIR . '/vendor/tecnickcom/tcpdf/fonts/CODE2000.TTF', '' );
		$custom_font = 'CODE2000';
		$font_style  = 'B';
	}

	$pdf->AddPage( 'L', 'A4' );
	$pdf->setImageScale( 1.5 );
	$pdf->SetTitle( utf8_decode( esc_html__( 'All prayer requests for the current year.', 'intercessor' ) ) );
	$pdf->SetAuthor( utf8_decode( esc_html__( 'Intercessor - Intercessory Prayers', 'intercessor' ) ) );
	$pdf->SetCreator( utf8_decode( esc_html__( 'Intercessor - Intercessory Prayers', 'intercessor' ) ) );

	// Image URL should have absolute path. @see https://tcpdf.org/examples/example_009/.
	$pdf->Image( apply_filters( 'intercessor_pdf_export_logo', INTERCESSOR_DIR . 'assets/images/hands.png' ), 247, 8 );

	$pdf->SetMargins( 8, 8, 8 );
	$pdf->SetX( 8 );

	$pdf->SetFont( $default_font, '', 16 );
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->Cell( 0, 3, utf8_decode( esc_html__( 'Prayer requests for the current year', 'intercessor' ) ), 0, 2, 'L', false );

	$pdf->SetFont( $default_font, '', 13 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Ln( 1 );
	$pdf->Cell( 0, 6, utf8_decode( esc_html__( 'Date Range: ', 'intercessor' ) ) . $daterange, 0, 2, 'L', false );
	$pdf->Ln();
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->SetFont( $default_font, '', 14 );
	$pdf->Cell( 0, 10, utf8_decode( esc_html__( 'Table View', 'intercessor' ) ), 0, 2, 'L', false );
	$pdf->SetFont( $default_font, '', 12 );

	$pdf->SetFillColor( 238, 238, 238 );
	$pdf->SetTextColor( 0, 0, 0, 100 );
	$pdf->Cell( 20, 6, utf8_decode( esc_html__( 'ID', 'intercessor' ) ), 1, 0, 'L', true );
	$pdf->Cell( 50, 6, utf8_decode( esc_html__( 'Title', 'intercessor' ) ), 1, 0, 'L', true );
	$pdf->Cell( 90, 6, utf8_decode( esc_html__( 'Prayer Request', 'intercessor' ) ), 1, 0, 'L', true );
	$pdf->Cell( 45, 6, utf8_decode( esc_html__( 'Requester', 'intercessor' ) ), 1, 0, 'L', true );
	$pdf->Cell( 30, 6, utf8_decode( esc_html__( 'Prayed Counts', 'intercessor' ) ), 1, 1, 'L', true );

	// Set Custom Font.
	$pdf->SetFont( apply_filters( 'intercessor_pdf_custom_font', $custom_font ), $font_style, 12 );

	// Prayer request stats.
	$prayer_stats = new Intercessor\Stats();

	$args = [
		'number' => 9999999999,
	];

	// Get available prayer requests.
	$prayers = intercessor_get_prayers( $args );

	if ( $prayers ) {
		$pdf->SetWidths( array( 20, 50, 90, 45, 30 ) );

		foreach ( $prayers as $prayer ):
			$pdf->SetFillColor( 255, 255, 255 );

			$email      = esc_attr( $prayer->email );
			$r_class = intercessor_get_requester_by( "email", $email);
			$first_name = $r_class->get_first_name();
			$last_name  = $r_class->get_last_name();
			$name       = esc_attr( $first_name. ' ' . $last_name );
			$number     = absint( $prayer->id );
			$title      = wp_unslash( $prayer->title );
			$message    = wp_unslash( $prayer->message );
			$requester  = $name . "\n\n" . $email;
			$prayed     = intercessor_get_prayed_for_counts( $prayer->id );

			// Get prayer requests.
			$prayer_requests = $prayer_stats->get_prayer_count( $prayer->id, 'this_year' );

			// This will help filter data before appending it to PDF Report.
			$prepare_pdf_data   = [];
			$prepare_pdf_data[] = $number;
			$prepare_pdf_data[] = $title;
			$prepare_pdf_data[] = $message;
			$prepare_pdf_data[] = $requester;
			$prepare_pdf_data[] = $prayed;

			$pdf->Row( $prepare_pdf_data );

		endforeach;
	} else {
		$no_found_width = 190;
		$title = utf8_decode(
			esc_html__( 'No prayer request found.', 'intercessor' )
		);
		$pdf->MultiCell( $no_found_width, 5, $title, 1, 'C', false, 1, '', '', true, 0, false, true, 0, 'T', false );
	} // End if().
	$pdf->Ln();
	$pdf->SetTextColor( 50, 50, 50 );
	$pdf->SetFont( $default_font, '', 14 );

	// Output prayer requests graph on a new page.
	$pdf->AddPage( 'L', 'A4' );
	$pdf->Cell( 0, 10, utf8_decode( esc_html__( 'Prayer Request Graph View', 'intercessor' ) ), 0, 2, 'L', false );
	$pdf->SetFont( $default_font, '', 12 );

	$image = html_entity_decode( urldecode( intercessor_output_prayers_chart() ) );
	$image = str_replace( ' ', '%20', $image );

	$pdf->SetX( 25 );
	$pdf->Image( $image . '&file=.png' );
	$pdf->Ln( 7 );

	// Output the pdf and graph.
	$pdf->Output( apply_filters( 'intercessor_requests_pdf_export_filename', 'intercessor-report-' . date_i18n( 'Y-m-d' ) ) . '.pdf', 'D' );
	exit();
}
add_action( 'intercessor_generate_pdf_prayers', 'intercessor_generate_pdf' );

/**
 * Outputs Chart for PDF Report.
 *
 * @since  0.9.5
 * @uses   GoogleChart
 * @uses   GoogleChartData
 * @uses   GoogleChartShapeMarker
 * @uses   GoogleChartTextMarker
 * @uses   GoogleChartAxis
 * @return string $chart->getUrl() URL for the Google Chart
 */
function intercessor_output_prayers_chart(): string {
	require_once INTERCESSOR_DIR . '/src/libraries/googlechartlib/GoogleChart.php';
	require_once INTERCESSOR_DIR . '/src/libraries/googlechartlib/markers/GoogleChartShapeMarker.php';
	require_once INTERCESSOR_DIR . '/src/libraries/googlechartlib/markers/GoogleChartTextMarker.php';

	$chart = new GoogleChart( 'lc', 900, 330 );
	$stats = new \Intercessor\Reports();

	$i        = 1;
	$personal = "";
	$prayers  = "";

	while ( $i <= 12 ) :
		$personal .= $stats->get_prayer_requests( null, $i, date( 'Y' ), 'personal' ) . ",";
		$prayers  .= $stats->get_prayer_requests( null, $i, date( 'Y' ), 'active' ) . ",";
		$i ++;
	endwhile;

	$personal_array = explode( ",", $personal );
	$prayers_array  = explode( ",", $prayers );

	$i = 0;
	while ( $i <= 11 ) {
		if ( empty( $prayers_array[ $i ] ) ) {
			$prayers_array[ $i ] = 0;
		}
		$i ++;
	}

	$min_personal   = 0;
	$max_personal   = max( $personal_array );
	$personal_scale = round( $max_personal, - 1 );

	$data = new GoogleChartData( array(
		$personal_array[0],
		$personal_array[1],
		$personal_array[2],
		$personal_array[3],
		$personal_array[4],
		$personal_array[5],
		$personal_array[6],
		$personal_array[7],
		$personal_array[8],
		$personal_array[9],
		$personal_array[10],
		$personal_array[11],
	) );

	$data->setLegend( esc_html__( 'Private', 'intercessor' ) );
	$data->setColor( '1b58a3' );
	$chart->addData( $data );

	$shape_marker = new GoogleChartShapeMarker( GoogleChartShapeMarker::CIRCLE );
	$shape_marker->setColor( '000000' );
	$shape_marker->setSize( 7 );
	$shape_marker->setBorder( 2 );
	$shape_marker->setData( $data );
	$chart->addMarker( $shape_marker );

	$value_marker = new GoogleChartTextMarker( GoogleChartTextMarker::VALUE );
	$value_marker->setColor( '000000' );
	$value_marker->setData( $data );
	$chart->addMarker( $value_marker );

	$data = new GoogleChartData( array(
		$prayers_array[0],
		$prayers_array[1],
		$prayers_array[2],
		$prayers_array[3],
		$prayers_array[4],
		$prayers_array[5],
		$prayers_array[6],
		$prayers_array[7],
		$prayers_array[8],
		$prayers_array[9],
		$prayers_array[10],
		$prayers_array[11],
	) );
	$data->setLegend( esc_html__( 'Active', 'intercessor' ) );
	$data->setColor( 'ff6c1c' );
	$chart->addData( $data );

	$chart->setTitle( esc_html__( 'All Prayer Requests received by months', 'intercessor' ), '336699', 18 );

	$chart->setScale( 0, $max_personal );

	$y_axis = new GoogleChartAxis( 'y' );
	$y_axis->setDrawTickMarks( true )->setLabels( array( 0, $max_personal ) );
	$chart->addAxis( $y_axis );

	$x_axis = new GoogleChartAxis( 'x' );
	$x_axis->setTickMarks( 5 );
	$x_axis->setLabels( array(
		esc_html__( 'Jan', 'intercessor' ),
		esc_html__( 'Feb', 'intercessor' ),
		esc_html__( 'Mar', 'intercessor' ),
		esc_html__( 'Apr', 'intercessor' ),
		esc_html__( 'May', 'intercessor' ),
		esc_html__( 'June', 'intercessor' ),
		esc_html__( 'July', 'intercessor' ),
		esc_html__( 'Aug', 'intercessor' ),
		esc_html__( 'Sept', 'intercessor' ),
		esc_html__( 'Oct', 'intercessor' ),
		esc_html__( 'Nov', 'intercessor' ),
		esc_html__( 'Dec', 'intercessor' ),
	) );
	$chart->addAxis( $x_axis );

	$shape_marker = new GoogleChartShapeMarker( GoogleChartShapeMarker::CIRCLE );
	$shape_marker->setSize( 6 );
	$shape_marker->setBorder( 2 );
	$shape_marker->setData( $data );
	$chart->addMarker( $shape_marker );

	$value_marker = new GoogleChartTextMarker( GoogleChartTextMarker::VALUE );
	$value_marker->setData( $data );
	$chart->addMarker( $value_marker );

	return $chart->getUrl();
}
