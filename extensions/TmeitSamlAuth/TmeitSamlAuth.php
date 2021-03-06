<?php
/*
 * TMEIT SAML authentication extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 * Inspired by the SimpleSamlAuth extension by Jørn Åne: https://www.mediawiki.org/wiki/Extension:SimpleSamlAuth
 * and the SAMLAuth extension by Piers Harding: https://www.mediawiki.org/wiki/Extension:SAMLAuth
 *
 * To use, register the extension and the settings provider in LocalSettings.php like so:
 *
 * require_once( "$IP/extensions/TmeitSamlAuth/TmeitSamlAuth.php" );
 * $wgSessionProviders[TmeitSamlSessionProvider::class] =
 *	[
 *		'class' => TmeitSamlSessionProvider::class,
 *		'args' => [ [ 'priority' => 100 ] ]
 *	];
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'TMEIT SAML Auth',
	'author' => 'Wilhelm Svenselius',
	'descriptionmsg' => 'tmeitsamlauth-desc'
);

$dir = dirname( __FILE__ ).'/';

$wgExtensionMessagesFiles['TmeitSamlAuth'] = $dir.'TmeitSamlAuth.i18n.php';

$wgAutoloadClasses['SpecialSamlAuth'] = $dir.'SpecialSamlAuth.php';
$wgAutoloadClasses['TmeitSamlAuth'] = $dir.'TmeitSamlAuth.class.php';
$wgAutoloadClasses['TmeitSamlSessionProvider'] = $dir.'TmeitSamlSessionProvider.php';

$wgSpecialPages['SAMLAuth'] = 'SpecialSamlAuth';

$wgHooks['PersonalUrls'][] = 'TmeitSamlAuth::hookPersonalUrls';
$wgHooks['UserLoginForm'][] = 'TmeitSamlAuth::hookUserLoginForm';

$wgSamlSimpleSAMLphpPath = $IP.'/../../simplesamlphp';
$wgSamlEntity = 'kth';
$wgSamlUsernameAttr = 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6';
$wgSamlRealNameAttr = 'urn:oid:2.16.840.1.113730.3.1.241';
$wgSamlEmailAttr = 'urn:oid:0.9.2342.19200300.100.1.3';
