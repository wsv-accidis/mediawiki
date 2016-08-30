<?php
/*
 * SAMLAuth Extension for MediaWiki
 *
 * Originally by Piers Harding, Catalyst IT Ltd
 * Improved by Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 *
 * Licensed under the GNU General Public License v2.
 */

class SAMLAuthHooks
{
	public static function checkLoginAtPerformAction( $output, $article, $title, $user, $request, $wiki )
	{
		global $wgSAMLAuthSimpleSAMLphpLibPath, $wgSAMLAuthSimpleSAMLphpEntity, $wgSAMLAuthAutoLogout;

		if( $user->isLoggedIn() )
		{
			if( isset( $_SESSION['SAMLSessionControlled'] ) )
			{
				// Include SimpleSAMLphp
				require_once( $wgSAMLAuthSimpleSAMLphpLibPath."/_include.php" );

				$session = SimpleSAML_Session::getInstance();
				$hasValidSession = $session->isValid( $wgSAMLAuthSimpleSAMLphpEntity );

				if( !$hasValidSession )
				{
					$wgSAMLAuthAutoLogout = true;
					SAMLAuthHooks::logout();
				}
			}
		}

		return true;
	}

	public static function customizeLoginForm( BaseTemplate &$template )
	{
		global $wgScriptPath, $wgRequest;
		$samlAuthUrl = SpecialPage::getTitleFor( 'SAMLAuth' )->getLocalURL();
		$returnto = $wgRequest->getVal( "returnto" );
        if( "" != $returnto ) {
		    $samlAuthUrl .= '?returnto='.wfUrlencode( $returnto );
        }
		$template->set( 'header', '<a href="'.$samlAuthUrl.'"><img id="kth-login-link" src="'.$wgScriptPath.'/skins/tmeit/images/loggain.png" /></a>' );
	}

    public static function logout()
	{
		global $wgSAMLAuthSimpleSAMLphpLibPath, $wgSAMLAuthSimpleSAMLphpEntity, $wgSAMLAuthAutoLogout, $wgSAMLMainPage, $wgUser, $wgRequest;

		unset( $_SESSION['SAMLSessionControlled'] );

		// Logout from MediaWiki
		$wgUser->doLogout();

		// Get redirect, if any
		$returnto = $wgRequest->getVal( "returnto" );
		if( $returnto )
			$target = Title::newFromText( $returnto );
		if( empty( $target ) )
			$target = Title::newFromText( $wgSAMLMainPage );

		$redirectUrl = $target->getFullURL();

		// Include SimpleSAMLphp
		require_once( $wgSAMLAuthSimpleSAMLphpLibPath."/_include.php" );

        // Log out from IdP
        if( $wgSAMLAuthAutoLogout )
		{
			$session = SimpleSAML_Session::getInstance();
			$hasValidSession = $session->isValid( $wgSAMLAuthSimpleSAMLphpEntity );

			if( $hasValidSession )
			{
				$as = new SimpleSAML_Auth_Simple( $wgSAMLAuthSimpleSAMLphpEntity );
				$as->logout( $redirectUrl );
			}
			else
                SimpleSAML_Utilities::redirect( $redirectUrl );
		}
		else
			SimpleSAML_Utilities::redirect( $redirectUrl );

		return true;
    }

	public static function insertLoginLink( &$personal_urls, $title )
	{
		if( isset( $personal_urls['login'] ) )
		{
            global $wgServer;

			$loginTitle = SpecialPage::getTitleFor( 'Userlogin' )->getPartialURL();
			$loginUrl = $personal_urls['login'];
            $loginHref = $wgServer.$loginUrl['href'];
            $personal_urls['login']['href'] = $loginHref;

			$personal_urls['login_saml'] = array(
				'text' => $loginUrl['text'].' (kth-id)',
				'href' => str_replace( $loginTitle, 'SAMLAuth', $loginHref )
			);
		}

		return true;
	}
}
?>