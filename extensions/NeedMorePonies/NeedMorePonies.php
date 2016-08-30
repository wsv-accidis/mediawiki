<?php
/*
 * The "I Need More Ponies" extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) ) die( -1 );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'I Need More Ponies',
	'author' => 'Wilhelm Svenselius',
	'descriptionmsg' => 'needmoreponies-desc'
);

$dir = dirname( __FILE__ ).'/';

$wgExtensionMessagesFiles['NeedMorePonies'] = $dir.'NeedMorePonies.i18n.php';

$wgAutoloadClasses['NeedMorePoniesHooks'] = $dir.'NeedMorePonies.hooks.php';
$wgAutoloadClasses['NeedMorePoniesHoofclap'] = $dir.'NeedMorePoniesHoofclap.php';

$wgHooks['BeforePageDisplay'][] = 'NeedMorePoniesHooks::beforePageDisplay';
$wgHooks['ParserFirstCallInit'][] = 'NeedMorePoniesHooks::setupParser';

$wgResourceModules['ext.NeedMorePonies'] = array(
    'remoteBasePath' => "$wgScriptPath/extensions/NeedMorePonies/scripts",
    'localBasePath' => "$IP/extensions/NeedMorePonies/scripts",
	'scripts' => array( 'shortcuts.js', 'ponies.js' )
);
