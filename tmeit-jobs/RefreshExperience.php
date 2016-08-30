<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

chdir( '../' );
require( __DIR__ . '/../includes/WebStart.php' );

$verbose = isset( $_GET['verbose'] );

$tmeitDb = new TmeitDb();
$users = $tmeitDb->userGetListOfNames();

$tmeitExp = new TmeitExperience();
foreach( $users as $user )
	$tmeitExp->refreshCacheByUser( $user, $verbose );

$tmeitDb->getDatabase()->commit();

echo 'OK.';
