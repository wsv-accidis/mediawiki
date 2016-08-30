<?php
/*
 * Super-trivial Disable Special Pages extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) ) die( -1 );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Disable Special Pages',
	'author' => 'Wilhelm Svenselius',
	'descriptionmsg' => 'disablespecialpages-desc'
);

$dir = dirname(__FILE__).'/';
$wgExtensionMessagesFiles['DisableSpecialPages'] = $dir.'DisableSpecialPages.i18n.php';
$wgHooks['SpecialPage_initList'][] = 'DisableSpecialPagesHook';

$wgDisabledSpecialPages = array();

function DisableSpecialPagesHook( &$list )
{
	global $wgDisabledSpecialPages;
	foreach( $wgDisabledSpecialPages as $page )
		if( isset( $list[$page] ) )
			unset( $list[$page] );

	return true;
}
