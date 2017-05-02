<?php
/**
 * Plugin Name: POI Mapper
 * Description: This plugin allows to organize/control your POI lists.
 * Version: 1.0.0
 * Author: Vladimir Zabara
 */

include ('wp-poi-mapper.class.php');
if ( is_admin () ) {
	include ('wp-poi-mapper-admin.class.php');
    $plugin = new POIMapperAdmin();
} else {
	new POIMapper();
}
