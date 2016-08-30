<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'TMEIT Common',
	'author' => 'Wilhelm Svenselius',
	'descriptionmsg' => 'tmeitcommon-desc'
);

$dir = dirname( __FILE__ ).'/';

$wgExtensionMessagesFiles['TmeitCommon'] = $dir.'TmeitCommon.i18n.php';

$wgAutoloadClasses['SpecialTmeitServiceAuth'] = $dir.'SpecialTmeitServiceAuth.php';
$wgAutoloadClasses['TmeitDb'] = $dir.'TmeitDb.php';
$wgAutoloadClasses['TmeitGcm'] = $dir.'TmeitGcm.php';
$wgAutoloadClasses['TmeitSpecialPage'] = $dir.'TmeitSpecialPage.php';
$wgAutoloadClasses['TmeitUtil'] = $dir.'TmeitUtil.php';

$wgSpecialPages['TmeitServiceAuth'] = 'SpecialTmeitServiceAuth';

$wgSpecialPageGroups['TmeitServiceAuth'] = 'tmeit';

$wgResourceModules['ext.tmeit.styles'] = array(
    'styles' => array(
    	'styles/global.css',
    	'styles/global-new.css',
    	'styles/extevent.css',
    	'styles/event.css',
    	'styles/member.css'
    ),
    'remoteBasePath' => "$wgScriptPath/extensions/TmeitCommon",
    'localBasePath' => "$IP/extensions/TmeitCommon"
);