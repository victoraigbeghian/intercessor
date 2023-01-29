<?php
/**
 * Intercessor PDF export class
 *
 * These are functions are used for exporting pdf of requests from Intercessor.
 *
 * @package     Intercessor
 * @subpackage  Admin/PDF
 * @copyright   Copyright (c) 2019, Victor Aigbeghian
 * @license     http://opensource.org/licenses/GPL-3.0.php GNU Public License
 */

namespace Intercessor\Admin\Tools;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load autoloader.
require_once INTERCESSOR_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';

/**
 * Class Pdf
 *
 * @since 0.9.5
 */
class Pdf extends \TCPDF {

	/**
	 * Width.
	 *
	 * @var int $widths Width.
	 */
	var $widths;

	/**
	 * Alignment.
	 *
	 * @var string $aligns Alignment.
	 */
	var $aligns;

	/**
	 * Set Header.
	 */
	function Header() {
	}

	/**
	 * Set Footer.
	 */
	function Footer() {
		$this->SetY( - 15 );
		$this->SetFont( 'Helvetica', 'I', 8 );
		$this->Cell( 0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C' );
	}

	/**
	 * Set Width.
	 *
	 * @param array $w Cell Width.
	 */
	function SetWidths( $w ) {
		$this->widths = $w;
	}

	/**
	 * Set Alignment.
	 *
	 * @param string $a Cell Alignment.
	 */
	function SetAligns( $a ) {
		$this->aligns = $a;
	}

	/**
	 * Set Table Row.
	 *
	 * @param array $data Set data in a row.
	 */
	function Row( $data ) {
		$nb         = 0;
		$get_height = array();
		$data_count = count( $data );

		for ( $i = 0; $i < $data_count; $i ++ ) {
			$get_height[] = max( $nb, $this->getNumLines( $data[ $i ], $this->widths[ $i ] ) );
		}
		// Get max height from the all column.
		$max_height = max( $get_height );

		for ( $i = 0; $i < $data_count; $i ++ ) {
			$h = 7 * $max_height;
			$this->checkPageBreak( $h, '', true );

			$w = $this->widths[ $i ];
			$a = isset( $this->aligns[ $i ] ) ? $this->aligns[ $i ] : 'L';
			$x = $this->GetX();
			$y = $this->GetY();
			$this->Rect( $x, $y, $w, $h );

			$this->MultiCell( $w, $h, $data[ $i ], 0, $a, false, 1, '', '', true, 0, false, true, 0, 'M', false );
			$this->SetXY( $x + $w, $y );
		}

		$this->Ln( $max_height * 7 );
	}

}
