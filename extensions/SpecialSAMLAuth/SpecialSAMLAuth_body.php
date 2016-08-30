<?php
/*
 * SAMLAuth Extension for MediaWiki
 *
 * Originally by Piers Harding, Catalyst IT Ltd
 * Improved by Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 *
 * Licensed under the GNU General Public License v2.
 */

class SAMLAuth extends SpecialPage
{
	public function __construct()
	{
		parent::__construct( 'SAMLAuth' );
	}

	public function isListed()
	{
		return false;
	}

	private function authenticate()
	{
		global $wgSAMLAuthSimpleSAMLphpEntity;
		$as = new SimpleSAML_Auth_Simple( $wgSAMLAuthSimpleSAMLphpEntity );
		$as->requireAuth();
		return $as->getAttributes();
	}

	private function areAttributesValid( &$attributes )
	{
		global $wgSAMLAuthUserNameAttr;
		return isset( $attributes[$wgSAMLAuthUserNameAttr] );
	}

	private function createNewUser( $username, &$attributes )
	{
		global $wgSAMLAuthEmailAttr, $wgSAMLAuthRealNameAttr, $wgEmailAuthentication, $wgUser, $wgAuth, $wgSAMLDummyPassword;

		// Submit a fake login form to authenticate the user.
		$user = User::newFromName( $username );
		$user->setName( $username );
		$user->load();

		$user->setToken();
		$token = $user->getToken();
		$params = new FauxRequest( array(
			'wpName' => $username,
			'wpPassword' => '',
			'wpDomain' => '',
			'wpRemember' => '',
			'wpLoginToken' => $token,
		) );

		// Construct the dummy authentication plugin - this will authenticate, and update user info
		$wgAuth = new SAMLAuthLogin();
		$loginForm = new LoginForm( $params );
		$loginForm->authenticateUserData();

		// Pretend we're the login form
		$loginForm->initUser( $user, true );

		// Initialize value
		$email = $this->tryGetValue( $attributes, $wgSAMLAuthEmailAttr );
		$realName = $this->tryGetValue( $attributes, $wgSAMLAuthRealNameAttr );
		$user->setEmail( $email );
		$user->setRealName( $realName );
		$user->setPassword( $wgSAMLDummyPassword );

		if( $wgEmailAuthentication && Sanitizer::validateEmail( $user->getEmail() ) )
			$user->sendConfirmationMail();

		$user->setToken();
		$user->saveSettings();
		wfSetupSession();
		$user->setCookies( null, null, true );
		$wgUser = $user;
		$user->addNewUserLogEntry();
		wfRunHooks( 'AddNewAccount', array( $user ) );
		return TRUE;
	}

	private function finishAuthenticate()
	{
		global $wgRequest, $wgUser, $wgOut, $wgSAMLMainPage;

		// Mark this session as SAML controlled
		$_SESSION['SAMLSessionControlled'] = true;

		// Run hooks
		$currentUser = $wgUser;
		$injected_html = '';
		wfRunHooks( 'UserLoginComplete', array( &$currentUser, &$injected_html ) );

		// Get redirect, if any
		$returnto = $wgRequest->getVal( "returnto" );
		if( $returnto )
			$target = Title::newFromText( $returnto );
		if( empty( $target ) )
			$target = Title::newFromText( $wgSAMLMainPage );

		$wgOut->redirect( $target->getFullUrl() );
	}

	private function getUsernameFromAttributes( &$attributes )
	{
		global $wgSAMLAuthUserNameAttr;
		$username = $attributes[$wgSAMLAuthUserNameAttr][0];
		$at_pos = strpos( $username, '@' );
		if( FALSE !== $at_pos )
			$username = substr( $username, 0, $at_pos );

		// Use MediaWiki's mangling of titles to ensure result is a valid MediaWiki username
		$nt = Title::makeTitleSafe( NS_USER, $username );
		return $nt->getText();
	}

	private function raiseError( $message )
	{
		global $wgOut, $wgSAMLMainPage;
		error_log( $message );
		$target = Title::newFromText( $wgSAMLMainPage );
		$wgOut->redirect( $target->getFullUrl() );
	}

	private function tryGetValue( &$attributes, $key, $default = "" )
	{
		if( isset( $attributes[$key] ) )
			return $attributes[$key][0];

		return $default;
	}

	private function tryLoginExistingUser( $username, &$notFound )
	{
		global $IP, $wgAuth, $wgUser;
		require_once( $IP.'/includes/specials/SpecialUserLogin.php' );

		if( NULL == User::idFromName( $username ) )
		{
			$notFound = TRUE;
			return FALSE;
		}

		// Submit a fake login form to authenticate the user
		$user = User::newFromName( $username );
		$user->load();

		LoginForm::setLoginToken();
		$token = LoginForm::getLoginToken();
		$params = new FauxRequest( array(
			'wpName' => $username,
			'wpPassword' => '',
			'wpDomain' => '',
			'wpRemember' => '',
			'wpLoginToken' => $token,
		) );

		// Construct the dummy authentication plugin - this will authenticate, and update user info
		$wgAuth = new SAMLAuthLogin();
		$loginForm = new LoginForm( $params );
		$result = $loginForm->authenticateUserData();

		if( $result != LoginForm::SUCCESS )
		{
			$this->raiseError( "Unexpected authentication failure: ".$result );
			return FALSE;
		}

		$wgUser->saveSettings();
		wfSetupSession();
		$wgUser->setCookies();
		return TRUE;
	}

	public function execute()
	{
		global $wgSAMLCreateUser, $wgSAMLAuthSimpleSAMLphpLibPath;

		// Include SimpleSAMLphp
		require_once( $wgSAMLAuthSimpleSAMLphpLibPath."/_include.php" );

		// Authenticate using SAML
		$attributes = $this->authenticate();

		if( !$this->areAttributesValid( $attributes ) )
		{
			$this->raiseError( "Missing required SAML attributes." );
			return;
		}

		$username = $this->getUsernameFromAttributes( $attributes );

		$notExists = FALSE;
		if( $this->tryLoginExistingUser( $username, $notExists ) )
		{
			// User already exists
			$this->finishAuthenticate();
		}
		elseif( $notExists && $wgSAMLCreateUser && $this->createNewUser( $username, $attributes ) )
		{
			// Created the user
			$this->finishAuthenticate();
		}
		elseif( $notExists )
			$this->raiseError( "User doesn't exist and automatic user creation is disabled." );
	}
}

class SAMLAuthLogin extends AuthPlugin
{
	public function addUser( $user, $password, $email = '', $realname = '' )
	{
		return false;
	}

	public function authenticate( $username, $password )
	{
		return true;
	}

	public function allowPropChange( $prop = '' )
	{
		// Must be true to allow setting the properties when we auto-create users
		return true;
	}

	public function allowPasswordChange()
	{
		// Must be true to allow setting the password when we auto-create users
		return true;
	}
}
?>