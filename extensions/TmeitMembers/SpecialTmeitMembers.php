<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitMembers extends TmeitSpecialMemberPage
{
	const Source = 'TmeitMembers';
	private $data;
	/** @var TmeitFaces $faces */
	private $faces;
	private $viewMemberUrl;
	private $experience;

	public function __construct()
	{
		parent::__construct( 'TmeitMembers' );
		$this->faces = new TmeitFaces();
		$this->experience = new TmeitExperience();
	}

	protected function prepare( $par )
	{
		$this->data = $this->buildData();
		$this->viewMemberUrl = SpecialPage::getTitleFor( 'TmeitMember' )->getFullURL().'/';
		return true;
	}

	protected function render()
	{
		if( $this->hasDataFor( 'masters' ) )
		{
			$this->renderSectionStart( '', 10 );
			foreach( $this->data['masters'] as $userId => $user )
				$this->renderTop( 'tmeit-master', $user, $userId );
			$this->renderToC();
		}

		if( $this->hasDataFor( 'marskalk' ) )
		{
			$this->renderSectionStart( 'Marskalk' );
			foreach( $this->data['marskalk'] as $userId => $user )
				$this->renderNormal( 'tmeit-marskalk', $user, $userId );
			$this->renderToC();
		}

		if( $this->hasDataFor( 'prao' ) )
		{
			$this->renderSectionStart( 'Prao' );
			foreach( $this->data['prao'] as $userId => $user )
				$this->renderNormal( 'tmeit-prao', $user, $userId );
			$this->renderToC();
		}

		if( $this->hasDataFor( 'vraq' ) )
		{
			$this->renderSectionStart( 'Vraq' );
			foreach( $this->data['vraq'] as $userId => $user )
				$this->renderBottom( 'tmeit-vraq', $user, $userId );
		}
	}

	private function hasDataFor( $title )
	{
		return isset( $this->data[$title] ) && count( $this->data[$title] ) > 0;
	}

	private function renderToC()
	{
?>
<div class="tmeit-member-split"></div>
<ul id="tmeit-toc">
	<li><a href="#">Mästare</a></li>
	<li><a href="#Marskalk">Marskalk</a></li>
	<li><a href="#Prao">Prao</a></li>
	<li><a href="#Vraq">Vraq</a></li>
</ul>
<?
	}

	private function renderTop( $faceStyle, $user, $userId )
	{
?>
<div class="tmeit-member-top">
	<img class="tmeit-member-face <?=$faceStyle; ?>" src="<?=$this->getRandomPhoto( $user ); ?>" alt="<?=htmlspecialchars( $user['username'] ); ?>" />
	<div class="tmeit-member-name">
		<a href="<?=$this->getMemberLink( $user ); ?>"><?=htmlspecialchars( $user['realname'] ); ?></a>
	</div>
	<div class="tmeit-member-title">
		<?=htmlspecialchars( $user['title'] ); ?>
	</div>
	<div class="tmeit-member-details">
		E-post: <a href="mailto:<?=htmlspecialchars( $user['email'] ); ?>"><?=htmlspecialchars( $user['email'] ); ?></a><br />
		Telefon: <?=htmlspecialchars( $user['phone'] ); ?>
	</div>
<?
		$this->renderExperience( $userId, 'tmeit-member-exp tmeit-member-exp-left' );
?>
</div>
<?
	}

	private function renderNormal( $faceStyle, $user, $userId )
	{
?>
<div class="tmeit-member-normal">
	<img class="tmeit-member-face <?=$faceStyle; ?>" src="<?=$this->getRandomPhoto( $user ); ?>" alt="<?=htmlspecialchars( $user['username'] ); ?>" />
	<div class="tmeit-member-name">
		<a href="<?=$this->getMemberLink( $user ); ?>"><?=htmlspecialchars( $user['realname'] ); ?></a>
	</div>
	<div class="tmeit-member-title">
		<?=htmlspecialchars( $user['title'] ); ?>
	</div>
<?
		$this->renderExperience( $userId, 'tmeit-member-exp tmeit-member-exp-center' );
?>
</div>
<?
	}

	private function renderBottom( $faceStyle, $user, $userId )
	{
		$titles = TmeitDb::userGetPropStringArray( $user['props'], TmeitDb::PropOldTitle );
		$datePrao = TmeitDb::userGetPropString( $user['props'], TmeitDb::PropDatePrao );
		$dateMarskalk = TmeitDb::userGetPropString( $user['props'], TmeitDb::PropDateMars );
		$dateVraq = TmeitDb::userGetPropString( $user['props'], TmeitDb::PropDateVraq );
?>
<div class="tmeit-member-bottom">
	<img class="tmeit-member-face <?=$faceStyle; ?>" src="<?=$this->getRandomPhoto( $user ); ?>" alt="<?=htmlspecialchars( $user['username'] ); ?>" />
	<div class="tmeit-member-name">
		<a href="<?=$this->getMemberLink( $user ); ?>"><?=htmlspecialchars( $user['realname'] ); ?></a>
	</div>
	<div class="tmeit-member-title">
		<?=htmlspecialchars( $user['title'] ); ?>
	</div>
<?
		$this->renderExperience( $userId, 'tmeit-member-exp tmeit-member-exp-left' );
?>
	<div class="tmeit-member-details">
		Prao: <?=htmlspecialchars( empty( $datePrao ) ? '-' : $datePrao ); ?><br />
		Marskalk: <?=htmlspecialchars( empty( $dateMarskalk ) ? '-' : $dateMarskalk ); ?><br />
		Vraq: <?=htmlspecialchars( empty( $dateVraq ) ? '-' : $dateVraq ); ?>
<?
	if( count( $titles ) > 0 ):
?>
		<div class="tmeit-member-old-titles">
<?
		foreach( $titles as $title ):
?>
			<?=htmlspecialchars( $title ); ?><br />
<?
		endforeach;
?>
        </div>
<?
	endif;
?>
	</div>
</div>
<?
	}

	private function renderSectionStart( $title = '', $extraSpacing = 0 )
	{
?>
<div class="tmeit-member-split"<? if( $extraSpacing > 0 ): ?> style="margin-top: <?=$extraSpacing; ?>px"<? endif; ?>></div>
<?
		if( !empty( $title ) ):
?>
<h2 class="tmeit-member-header" id="<?=$title; ?>"><?=htmlspecialchars( $title ); ?></h2>
<?
		endif;
	}

	private function buildData()
	{
		return array(
			'masters'   => $this->buildDataFor( TmeitDb::GroupMaster, true ),
			'marskalk'  => $this->buildDataFor( TmeitDb::GroupMarskalk ),
			'prao'	  => $this->buildDataFor( TmeitDb::GroupPrao ),
			'vraq'	  => $this->buildDataFor( TmeitDb::GroupVraq, false, true )
		);
	}

	private function buildDataFor( $title, $titleOrder = false, $getProps = false )
	{
		$list = $this->db->userGetListPublicByGroup( $title, $titleOrder, $getProps );
		foreach( $list as $id => $user )
			$list[$id]['photo'] = $photos = $this->faces->findPhotos( $user['username'] );

		return $list;
	}

	private function getMemberLink( $user )
	{
		if( $this->isMember )
			return $this->viewMemberUrl.htmlspecialchars( $user['username'] );
		else
			return 'mailto:'.htmlspecialchars( $user['email'] );
	}

	private function getRandomPhoto( $user )
	{
		$photos = isset( $user['photo'] ) ? $user['photo'] : NULL;
		return $this->faces->getRandomPhoto( $user['username'], $photos );
	}

	private function renderExperience( $userId, $cssClass )
	{
		if( !$this->isMember )
			return;

		$badges = $this->experience->getHtmlByUser( $userId );
		if( '' == $badges )
			return;

?>
	<div class="<?=$cssClass; ?>">
		<?=$badges; ?>
	</div>
<?
	}
}
?>