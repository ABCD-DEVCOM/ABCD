<?php
/**
 * Name: inc_wait.php
 * Author: Roger C. Guilherme
 * Created: 2026-04-19
 * Description: Common include file for displaying a loading spinner.
 * This file contains the HTML and CSS for a loading spinner that can be included in various parts of the ABCD application to indicate that a process is ongoing. The spinner is designed to be simple and visually consistent across the application.
 * How to use: Include this file in any PHP script where you want to display the loading spinner. The spinner will be hidden by default and can be shown or hidden using JavaScript by toggling the display property of the #preloader element.
 * Example usage:
 * <?php include("../common/inc_wait.php"); ?>
 */
?>
	
<div id="preloader">
	<i class="fas fa-circle-notch fa-spin"></i>
</div>