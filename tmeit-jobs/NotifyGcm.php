<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

chdir( '../' );
require( __DIR__ . '/../includes/WebStart.php' );

$verbose = isset( $_GET['verbose'] );

$tmeitGcm = new TmeitGcm( $verbose );
$tmeitGcm->sendPendingNotifications();
(new TmeitDb())->getDatabase()->commit();

echo 'OK.';
