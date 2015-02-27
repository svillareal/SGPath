<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full Content Template
 *
Template Name:  Test page
 *
 * @file           outcome-overview.php
 * @package        Life Group full width
 * @author         Sherilyn Villareal
 * @version        Release: 1.0
 */



get_header(); ?>

<?php 
	$x = 14;
	$y = 5;

function getOutcomeDiv() {
	global $x, $y;
	echo "X is ".$x."<br/>";
	$y = $x + $y;
}

getOutcomeDiv();

echo $y;

get_footer(); ?>
