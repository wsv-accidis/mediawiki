<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'TMEIT Events',
	'author' => 'Wilhelm Svenselius',
	'descriptionmsg' => 'tmeitevents-desc'
);

$dir = dirname( __FILE__ ).'/';

$wgExtensionMessagesFiles['TmeitEvents'] = $dir.'TmeitEvents.i18n.php';

$wgAutoloadClasses['TmeitEventAjax'] = $dir.'TmeitEventAjax.php';
$wgAutoloadClasses['SpecialTmeitEventEdit'] = $dir.'SpecialTmeitEventEdit.php';
$wgAutoloadClasses['SpecialTmeitEventList'] = $dir.'SpecialTmeitEventList.php';
$wgAutoloadClasses['SpecialTmeitEvents'] = $dir.'SpecialTmeitEvents.php';
$wgAutoloadClasses['SpecialTmeitEventWork'] = $dir.'SpecialTmeitEventWork.php';
$wgAutoloadClasses['SpecialTmeitExtEventEdit'] = $dir.'SpecialTmeitExtEventEdit.php';
$wgAutoloadClasses['SpecialTmeitExtEventList'] = $dir.'SpecialTmeitExtEventList.php';
$wgAutoloadClasses['SpecialTmeitReportEdit'] = $dir.'SpecialTmeitReportEdit.php';
$wgAutoloadClasses['SpecialTmeitReportSummary'] = $dir.'SpecialTmeitReportSummary.php';
$wgAutoloadClasses['TmeitSpecialEventPage'] = $dir.'TmeitSpecialEventPage.php';

$wgSpecialPages['TmeitEventEdit'] = 'SpecialTmeitEventEdit';
$wgSpecialPages['TmeitEventList'] = 'SpecialTmeitEventList';
$wgSpecialPages['TmeitEvents'] = 'SpecialTmeitEvents';
$wgSpecialPages['TmeitEventWork'] = 'SpecialTmeitEventWork';
$wgSpecialPages['TmeitExtEventEdit'] = 'SpecialTmeitExtEventEdit';
$wgSpecialPages['TmeitExtEventList'] = 'SpecialTmeitExtEventList';
$wgSpecialPages['TmeitReportEdit'] = 'SpecialTmeitReportEdit';
$wgSpecialPages['TmeitReportSummary'] = 'SpecialTmeitReportSummary';

$wgSpecialPageGroups['TmeitEventEdit'] = 'tmeit';
$wgSpecialPageGroups['TmeitEventList'] = 'tmeit';
$wgSpecialPageGroups['TmeitEvents'] = 'tmeit';
$wgSpecialPageGroups['TmeitEventWork'] = 'tmeit';
$wgSpecialPageGroups['TmeitExtEventEdit'] = 'tmeit';
$wgSpecialPageGroups['TmeitExtEventList'] = 'tmeit';
$wgSpecialPageGroups['TmeitReportEdit'] = 'tmeit';
$wgSpecialPageGroups['TmeitReportSummary'] = 'tmeit';

$wgResourceModules['ext.tmeit.events.specialtmeitevents'] = array(
	'scripts' => ['scripts/SpecialTmeitEvents.js'],
	'remoteBasePath' => "$wgScriptPath/extensions/TmeitEvents",
	'localBasePath' => "$IP/extensions/TmeitEvents"
);
