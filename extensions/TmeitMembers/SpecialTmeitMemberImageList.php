<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitMemberImageList extends TmeitSpecialMemberPage
{
	const ImagesPerRow = 7;
	const ImageReallyNewTreshold = 604800; // 1 week
	const ImageNewTreshold = 2592000; // 1 month

	private $faces;
	private $photos;
	private $thisUrl;
	private $imageUrl;
	private $users;

    public function __construct()
    {
        parent::__construct( 'TmeitMemberImageList', 'tmeit' );
        $this->faces = new TmeitFaces();
    }

	protected function prepare( $par )
	{
		$this->photos = array();
		$this->users = $this->db->userGetPicklistByActive();
		$now = time();

		if( $this->hasField( 'delete' ) && $this->hasField( 'user' ) )
		{
			$toDelete = $this->getTextField( 'delete' );
			$userId = $this->getIntField( 'user' );
			if( isset( $this->users[$userId] ) )
			{
				$this->faces->deletePhoto( $this->users[$userId]['username'], $toDelete );
			}
		}

		foreach( $this->users as $userId => $user )
		{
			$username = $user['username'];
			$photos = array();
			foreach( $this->faces->findPhotos( $username ) as $photo )
			{
				$lastModified = filemtime( $this->faces->getPathOfPhoto( $username, $photo ) );

				$photos[] = array(
					'name' => $photo,
					'url' => $this->faces->getUrlOfPhoto( $user['username'], $photo ),
					'new' => ( $now - $lastModified < self::ImageNewTreshold ),
					'reallynew' => ( $now - $lastModified < self::ImageReallyNewTreshold )
				);
			}

			if( !empty( $photos ) )
				$this->photos[$userId] = $photos;
		}

		$this->imageUrl = SpecialPage::getTitleFor( 'TmeitMemberImage' )->getFullURL().'/';
		$this->thisUrl = $this->getPageTitle()->getFullURL();
		return true;
	}

	protected function render()
	{
?>
<p>
	Foton uppladdade den senaste månaden är markerade med <span style="color: yellow">gult</span>. Foton från den senaste veckan är markerade med <span style="color: red">rött</span>.
</p>
<?
		foreach( $this->users as $userId => $user ):
?>
<div class="tmeit-member-image-list">
	<h3><?=htmlspecialchars( $user['realname'] ); ?></h3>
<?
			if( !isset( $this->photos[$userId] ) ):
?>
	<p class="tmeit-important-note">
		Inga foton! <a href="<?=$this->imageUrl.$user['username']; ?>">Ladda upp några!</a>
	</p>
</div>
<?
			else:
?>
	<ul>
		<li><a href="<?=$this->imageUrl.$user['username']; ?>">Ladda upp foton</a></li>
	</ul>
	<table class="tmeit-naked-table">
<?
            $i = 0;
            foreach( $this->photos[$userId] as $photo ):

            	if( $photo['reallynew'] )
            		$class = 'tmeit-member-image-really-new';
            	elseif( $photo['new'] )
            		$class = 'tmeit-member-image-new';
            	else
            		$class = 'tmeit-member-image';

                if( 0 == $i % self::ImagesPerRow ):
?>
    	<tr>
<?
                endif;
?>
        	<td class="image-list-column">
         	   <img class="<?=$class; ?>" src="<?=$photo['url']; ?>" alt="" />
<?
                if( $this->isAdmin ):
?>
     	       <div class="tmeit-member-image-link">
        	        <a href="#" onclick="promptDelete('<?=htmlspecialchars( $photo['name'] ); ?>', '<?=$userId; ?>' );">Radera</a>
         	   </div>
<?
                endif;
?>
        	</td>
<?
                if( self::ImagesPerRow - 1 == $i % self::ImagesPerRow ):
?>
    	</tr>
<?
                endif;
                $i++;
            endforeach;
            if( 0 != $i % self::ImagesPerRow ):
?>
		</tr>
<?
            endif;
?>
	</table>
</div>
<?
			endif;
		endforeach;
		if( $this->isAdmin ):
?>
<script type="text/javascript">
    function promptDelete( id, userId )
    {
        if( confirm( 'Är du säker på att du vill ta bort fotot?' ) )
            location.href = '<?=$this->thisUrl; ?>?delete=' + id + '&user=' + userId;
    }
</script>
<?
		endif;
?>
<h3>Länkar</h3>
<ul>
    <li><a href="<?=$this->listMembersUrl; ?>">Lista medlemmar</a></li>
</ul>
<?
	}
}