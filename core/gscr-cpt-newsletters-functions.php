<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	GSCR_CPT_Newsletters
 * @subpackage GSCR_CPT_Newsletters/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		GSCR_CPT_Newsletters
 */
function GSCRCPTNEWSLETTERS() {
	return GSCR_CPT_Newsletters::instance();
}