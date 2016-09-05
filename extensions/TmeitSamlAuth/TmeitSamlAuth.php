<?php
/*
 * TMEIT SAML authentication extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 * Inspired by the SimpleSamlAuth extension by Jørn Åne: https://www.mediawiki.org/wiki/Extension:SimpleSamlAuth
 * and the SAMLAuth extension by Piers Harding: https://www.mediawiki.org/wiki/Extension:SAMLAuth
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

$wgAutoloadClasses['TmeitSamlAuth'] = $dir.'TmeitSamlAuth.class.php';

$wgHooks['UserLoginForm'][] = 'TmeitSamlAuth::hookUserLoginForm';

$wgSamlSimpleSAMLphpPath = $IP.'/../../simplesamlphp';
$wgSamlEntity = 'kth';
$wgSamlUsernameAttr = 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6';
$wgSamlRealNameAttr = 'urn:oid:2.16.840.1.113730.3.1.241';
$wgSamlEmailAttr = 'urn:oid:0.9.2342.19200300.100.1.3';
