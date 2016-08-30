<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitSpecialPage extends SpecialPage
{
	/** @var TmeitDb $db */
	protected $db;
	protected $isAdmin;
	protected $isMember;
	private $restriction;

	public function __construct( $name, $restriction = '' )
	{
		parent::__construct( $name, $restriction );
		$this->restriction = $restriction;
	}

	public function execute( $par )
	{
		$this->validateAccess();
		$this->setHeaders();
		$this->initSpecialUrls();
		$this->db = new TmeitDb();

		if( !$this->prepare( $par ) )
			return;

		ob_start();
		$this->render();
		$output = ob_get_contents();
		ob_end_clean();

		global $wgOut;
        $wgOut->addModuleStyles( 'ext.tmeit.styles' );
		$wgOut->addHTML( $output );
	}

	protected abstract function prepare( $par );
	protected abstract function render();

	protected function action( $url, $icon, $title )
	{
		echo TmeitUtil::strF( '<a href="{0}"><img src="{1}" title="{2}" alt="{2}" /></a>',
			$url, $icon, htmlspecialchars( $title ) );
	}

	protected function actionIf( $condition, $url, $icon, $title )
	{
		if( $condition )
			$this->action( $url, $icon, $title );
	}

	protected function getPageUrl( $page )
	{
		return Title::newFromText( $page )->getFullURL();
	}

	protected function getSpecialPageUrl( $page )
	{
		return SpecialPage::getTitleFor( $page )->getFullURL();
	}

	protected function iconIf( $condition, $icon, $title )
	{
		if( $condition )
			echo TmeitUtil::strF( '<img src="{0}" title="{1}" alt="{1}" />',
				$icon, htmlspecialchars( $title ) );
	}

	protected function linkIf( $condition, $url, $text )
	{
		if( $condition )
			echo TmeitUtil::strF( '<a href="{0}">{1}</a>', $url, htmlspecialchars( $text ) );
		else
			echo htmlspecialchars( $text );
	}

	protected function initSpecialUrls()
	{
	}

	protected function hasField( $name )
	{
		global $wgRequest;
		$value = $wgRequest->getVal( $name );
		return !empty( $value );
	}

	protected function getBoolField( $name, $default = false )
	{
		global $wgRequest;
		return $wgRequest->getBool( $name, $default );
	}

	protected function getTextField( $name, $default = '' )
	{
		global $wgRequest;
		$value = trim( $wgRequest->getText( $name ) );
		return empty( $value ) ? $default : $value;
	}

	protected function getIntField( $name, $default = 0 )
	{
		global $wgRequest;
		return $wgRequest->getInt( $name, $default );
	}

	protected function isValidDate( $date )
	{
		return preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date );
	}

	protected function isValidDateTime( $dateTime )
	{
		return preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-2]*[0-9]:[0-5][0-9]$/', $dateTime );
	}

	protected function isValidTime( $time )
	{
		return preg_match( '/^[0-2]*[0-9]:[0-5][0-9]$/', $time );
	}

		protected function redirectToSpecial( $specialPageName, $urlSuffix = '' )
	{
		global $wgOut;
		$url = SpecialPage::getTitleFor( $specialPageName )->getFullURL();
		$wgOut->redirect( $url.$urlSuffix );
	}

	protected function validateAccess()
	{
		global $wgUser;
		if( NULL != $this->restriction && !$wgUser->isAllowed( $this->restriction ) )
			throw new PermissionsError( $this->restriction );

		$this->isMember = $wgUser->isAllowed( 'tmeit' );
		$this->isAdmin = $wgUser->isAllowed( 'tmeitadmin' );
	}

	protected function wasPosted()
	{
		global $wgRequest;
		return $wgRequest->wasPosted();
	}
}