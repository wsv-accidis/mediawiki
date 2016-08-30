<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'TMEIT Members',
	'author' => 'Wilhelm Svenselius',
	'descriptionmsg' => 'tmeitmembers-desc'
);

$dir = dirname( __FILE__ ).'/';

$wgExtensionMessagesFiles['TmeitMembers'] = $dir.'TmeitMembers.i18n.php';

$wgAutoloadClasses['SpecialTmeitExperience'] = $dir.'SpecialTmeitExperience.php';
$wgAutoloadClasses['SpecialTmeitExplainExperience'] = $dir.'SpecialTmeitExplainExperience.php';
$wgAutoloadClasses['SpecialTmeitGroupList'] = $dir.'SpecialTmeitGroupList.php';
$wgAutoloadClasses['SpecialTmeitMember'] = $dir.'SpecialTmeitMember.php';
$wgAutoloadClasses['SpecialTmeitMembers'] = $dir.'SpecialTmeitMembers.php';
$wgAutoloadClasses['SpecialTmeitMemberEdit'] = $dir.'SpecialTmeitMemberEdit.php';
$wgAutoloadClasses['SpecialTmeitMemberImage'] = $dir.'SpecialTmeitMemberImage.php';
$wgAutoloadClasses['SpecialTmeitMemberImageList'] = $dir.'SpecialTmeitMemberImageList.php';
$wgAutoloadClasses['SpecialTmeitMemberList'] = $dir.'SpecialTmeitMemberList.php';
$wgAutoloadClasses['SpecialTmeitTeamList'] = $dir.'SpecialTmeitTeamList.php';
$wgAutoloadClasses['SpecialTmeitTitleList'] = $dir.'SpecialTmeitTitleList.php';
$wgAutoloadClasses['TmeitBadges'] = $dir.'TmeitBadges.php';
$wgAutoloadClasses['TmeitExperience'] = $dir.'TmeitExperience.php';
$wgAutoloadClasses['TmeitFaces'] = $dir.'TmeitFaces.php';
$wgAutoloadClasses['TmeitHooks'] = $dir.'TmeitHooks.php';
$wgAutoloadClasses['TmeitMemberSync'] = $dir.'TmeitMemberSync.php';
$wgAutoloadClasses['TmeitSpecialMemberPage'] = $dir.'TmeitSpecialMemberPage.php';

$wgSpecialPages['TmeitExperience'] = 'SpecialTmeitExperience';
$wgSpecialPages['TmeitExplainExperience'] = 'SpecialTmeitExplainExperience';
$wgSpecialPages['TmeitGroupList'] = 'SpecialTmeitGroupList';
$wgSpecialPages['TmeitMember'] = 'SpecialTmeitMember';
$wgSpecialPages['TmeitMembers'] = 'SpecialTmeitMembers';
$wgSpecialPages['TmeitMemberEdit'] = 'SpecialTmeitMemberEdit';
$wgSpecialPages['TmeitMemberImage'] = 'SpecialTmeitMemberImage';
$wgSpecialPages['TmeitMemberImageList'] = 'SpecialTmeitMemberImageList';
$wgSpecialPages['TmeitMemberList'] = 'SpecialTmeitMemberList';
$wgSpecialPages['TmeitTeamList'] = 'SpecialTmeitTeamList';
$wgSpecialPages['TmeitTitleList'] = 'SpecialTmeitTitleList';

$wgSpecialPageGroups['TmeitExperience'] = 'tmeit';
$wgSpecialPageGroups['TmeitExplainExperience'] = 'tmeit';
$wgSpecialPageGroups['TmeitGroupList'] = 'tmeit';
$wgSpecialPageGroups['TmeitMembers'] = 'tmeit';
$wgSpecialPageGroups['TmeitMemberEdit'] = 'tmeit';
$wgSpecialPageGroups['TmeitMemberImage'] = 'tmeit';
$wgSpecialPageGroups['TmeitMemberImageList'] = 'tmeit';
$wgSpecialPageGroups['TmeitMemberList'] = 'tmeit';
$wgSpecialPageGroups['TmeitTeamList'] = 'tmeit';
$wgSpecialPageGroups['TmeitTitleList'] = 'tmeit';

$wgHooks['UserLoginComplete'][] = 'TmeitHooks::initUser';

$wgResourceModules['ext.tmeit.members.imageareaselect'] = array(
    'position' => 'top',
    'styles' => 'styles/imgareaselect-default.css',
    'scripts' => 'scripts/jquery.imgareaselect.min.js',
    'remoteBasePath' => "$wgScriptPath/extensions/TmeitMembers",
    'localBasePath' => "$IP/extensions/TmeitMembers"
);
