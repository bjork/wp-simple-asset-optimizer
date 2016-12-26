<?php
/*
------------------------------------------------------------------------

Copyright 2015–2016 Aki Björklund.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

-----------------------------------------------------------------------

Version: 0.2.0

*/

require_once( 'class-wp-simple-asset-optimizer.php' );

add_action( 'init', function () {
	new WP_Simple_Asset_Optimizer( wp_scripts(), wp_styles() );
} );