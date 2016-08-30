<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

chdir( '../' );
require( __DIR__ . '/../includes/WebStart.php' );

$page = new TmeitEventAjax();
$page->run();
