<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

require( 'ServiceStart.php' );
$handler = new TmeitWsGetExternalEventDetails();
$handler->handleRequest();
