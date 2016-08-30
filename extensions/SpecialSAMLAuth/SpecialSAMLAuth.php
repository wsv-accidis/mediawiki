<?php
/*
 * SAMLAuth Extension for MediaWiki
 *
 * Originally by Piers Harding, Catalyst IT Ltd
 * Improved by Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 *
 * Licensed under the GNU General Public License v2.
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

$wgExtensionCredits['other'][] = array(
	'name' => 'SAMLAuth',
	'author' => 'Wilhelm Svenselius',
	'description' => 'SAMLAuth uses the SimpleSAMLphp libraries and services to provide SSO based authentication.',
	'descriptionmsg' => 'samlauth-desc'
);

$dir = dirname( __FILE__ ).'/';

$wgAutoloadClasses['SAMLAuthHooks'] = $dir.'SpecialSAMLAuth.hooks.php';
$wgAutoloadClasses['SAMLAuth'] = $dir.'SpecialSAMLAuth_body.php';
$wgExtensionMessagesFiles['SAMLAuth'] = $dir.'SpecialSAMLAuth.i18n.php';
$wgSpecialPages['SAMLAuth'] = 'SAMLAuth';

$wgHooks['UserLoginForm'][] = 'SAMLAuthHooks::customizeLoginForm';
$wgHooks['UserLogoutComplete'][] = 'SAMLAuthHooks::logout';
$wgHooks['MediaWikiPerformAction'][] = 'SAMLAuthHooks::checkLoginAtPerformAction';
$wgHooks['PersonalUrls'][] = 'SAMLAuthHooks::insertLoginLink';

global $wgSAMLAuthSimpleSAMLphpLibPath,
	   $wgSAMLAuthSimpleSAMLphpEntity,
	   $wgSAMLAuthUserNameAttr,
	   $wgSAMLAuthRealNameAttr,
	   $wgSAMLAuthEmailAttr,
	   $wgSAMLCreateUser,
	   $wgSAMLAuthAutoLogout,
	   $wgSAMLMainPage;

// Path to libraries
$wgSAMLAuthSimpleSAMLphpLibPath = '../simplesaml';

// Name of authentication source
$wgSAMLAuthSimpleSAMLphpEntity = "kth";

// Attribute which maps to username
$wgSAMLAuthUserNameAttr = 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6';

// Attribute which maps to real name
$wgSAMLAuthRealNameAttr = 'urn:oid:2.16.840.1.113730.3.1.241';

// Attribute which maps to e-mail address
$wgSAMLAuthEmailAttr    = 'urn:oid:0.9.2342.19200300.100.1.3';

// Create user accounts for users that do not exist?
$wgSAMLCreateUser = true;

// Log out from IdP?
$wgSAMLAuthAutoLogout = true;

// The name of the main page
$wgSAMLMainPage = "TraditionsMEsterIT";

// Private configuration settings that don't go into source control
require( $dir.'SpecialSAMLAuth.private.php' );
