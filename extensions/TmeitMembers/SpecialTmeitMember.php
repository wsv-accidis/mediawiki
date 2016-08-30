<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitMember extends TmeitSpecialMemberPage
{
	private $editMemberUrl;
	private $memberImageUrl;
	private $photos;
	private $user;

	public function __construct()
	{
		parent::__construct( 'TmeitMember', 'tmeit' );
		$this->setListed( false );
	}

	protected function prepare( $par )
	{
		if( empty( $par ) )
			throw new FatalError( 'Saknar namn.' );
		elseif( FALSE == ( $this->user = $this->db->userGetByName( $par ) ) )
			throw new FatalError( 'Användaren kunde inte hittas.' );

		$faces = new TmeitFaces();
		$username = $this->user['username'];

		$mapFun = function( $photo ) use ( $faces, $username ) { return $faces->getUrlOfPhoto( $username, $photo ); };
		$this->photos = array_map( $mapFun, $faces->findPhotos( $username ) );

		$this->editMemberUrl = SpecialPage::getTitleFor( 'TmeitMemberEdit' )->getFullURL().'/'.htmlspecialchars( $username );
		$this->memberImageUrl = SpecialPage::getTitleFor( 'TmeitMemberImage' )->getFullURL().'/'.htmlspecialchars( $username );
		return true;
	}

	protected function render()
	{
		$datePrao = TmeitDb::userGetPropString( $this->user['props'], TmeitDb::PropDatePrao );
		$dateMars = TmeitDb::userGetPropString( $this->user['props'], TmeitDb::PropDateMars );
		$dateVraq = TmeitDb::userGetPropString( $this->user['props'], TmeitDb::PropDateVraq );
		$titles = TmeitDb::userGetPropStringArray( $this->user['props'], TmeitDb::PropOldTitle );

		if( count( $this->photos ) > 0 ):
?>

<div class="tmeit-member-faces">
<?
			foreach( $this->photos as $photo ):
?>
	<img class="tmeit-member-face tmeit-self" src="<?=$photo; ?>" alt="" />
<?
			endforeach;
?>
</div>
<?
		endif;
?>
<h3><?=htmlspecialchars( $this->user['realname'] ); ?></h3>

<table class="wikitable tmeit-third-width tmeit-member-table">
	<tr>
		<td>
			Användarnamn:
		</td>
		<td class="value-column">
			<?=htmlspecialchars( $this->user['username'] ); ?>
		</td>
	</tr>
	<tr>
		<td>
			Telefon:
		</td>
		<td class="value-column">
			<?=htmlspecialchars( $this->user['phone'] ); ?>
		</td>
	</tr>
	<tr>
		<td>
			E-post:
		</td>
		<td class="value-column">
			<a href="mailto:<?=htmlspecialchars( $this->user['email'] ); ?>"><?=htmlspecialchars( $this->user['email'] ); ?></a>
		</td>
	</tr>
	<tr>
		<td class="spacer"></td>
	</tr>
<?
		if( !empty( $datePrao ) ):
?>
	<tr>
		<td>
			Prao:
		</td>
		<td class="value-column">
			<?=htmlspecialchars( $datePrao ); ?>
		</td>
	</tr>
<?
		endif;
		if( !empty( $dateMars ) ):
?>
	<tr>
		<td>
			Marskalk:
		</td>
		<td class="value-column">
			<?=htmlspecialchars( $dateMars ); ?>
		</td>
	</tr>
<?
		endif;
		if( !empty( $dateVraq ) ):
?>
	<tr>
		<td>
			Vraq:
		</td>
		<td class="value-column">
			<?=htmlspecialchars( $dateVraq ); ?>
		</td>
	</tr>
<?
	endif;
?>
</table>

<?
		if( count( $titles ) > 0 ):
?>
<div class="tmeit-member-titles">
	<ul>
<?
			foreach( $titles as $title ):
?>
			<li><?=htmlspecialchars( $title ); ?></li>
<?
			endforeach;
?>
	</ul>
</div>
<?
		endif;
?>

<div class="tmeit-member-split"></div>

<h3>Länkar</h3>
<ul>
<?
		if( $this->isAdmin ):
?>
	<li><a href="<?=$this->editMemberUrl; ?>">Redigera medlem</a></li>
<?
		endif;
?>
	<li><a href="<?=$this->memberImageUrl; ?>">Ladda upp foton</a></li>
	<li><a href="<?=$this->listMembersUrl; ?>">Lista medlemmar</a></li>
</ul>
<?
	}
}