<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitMemberImage extends TmeitSpecialMemberPage
{
	// Note - these must match values set on the script in scripts/SpecialTmeitMemberImage.js.
    const ImageHeight = 120;
    const ImageWidth = 110;

    const ImageQuality = 99;
    const ImagesPerRow = 6;
    const MaxFileSize = 2097152; // 2 MB
    const MaxFileSizeText = '2 MB';
    const UploadFileMaxAge = 86400; // 24 hours
    const UploadTempFolderRelative = '/images/temp/';
    const UploadTempPrefix = 'tmeit_temp_';

    private $editMemberUrl;
    private $errorMessage;
    private $faces;
    private $failed = false;
    private $hasUpload = false;
	private $listImagesUrl;
    private $thisUrl;
    private $uploadedImagePath;
    private $user;
    private $userPhotos;

    public function __construct()
    {
        parent::__construct( 'TmeitMemberImage', 'tmeit' );
        $this->faces = new TmeitFaces();
    }

    protected function prepare( $par )
    {
        if( empty( $par ) || FALSE == ( $this->user = $this->db->userGetByName( $par ) ) )
            throw new FatalError( 'Användaren kunde inte hittas.' );
        $username = $this->user['username'];

		if( $this->hasField( 'delete' ) )
		{
			$toDelete = $this->getTextField( 'delete' );
			$this->faces->deletePhoto( $username, $toDelete );
		}

        if( $this->wasPosted() )
        	$this->handlePostback();

        $this->userPhotos = $this->faces->findPhotos( $username );
        $this->listImagesUrl = SpecialPage::getTitleFor( 'TmeitMemberImageList' )->getFullURL();
        $this->editMemberUrl = SpecialPage::getTitleFor( 'TmeitMemberEdit' )->getFullURL().'/'.htmlspecialchars( $username );
        $this->thisUrl = $this->getPageTitle()->getFullURL().'/'.htmlspecialchars( $username );
        $this->getOutput()->setPageTitle( "TMEIT - Foton på ".htmlspecialchars( $this->user['realname'] ) );
        return true;
    }

    protected function render()
    {
        if( $this->failed )
            $this->renderErrorMessage();

        if( $this->hasUpload )
            $this->renderImageEditor();
        else
        {
            $this->renderCurrentImages();
            $this->renderUploadForm();
        }

        $this->renderFooter();
    }

    private function renderCurrentImages()
    {
        if( count( $this->userPhotos ) > 0 ):
?>
<table class="tmeit-naked-table">
<?
            $i = 0;
            foreach( $this->userPhotos as $photo ):
                $url = $this->faces->getUrlOfPhoto( $this->user['username'], $photo );

                if( 0 == $i % self::ImagesPerRow ):
?>
    <tr>
<?
                endif;
?>
        <td class="image-list-column">
            <img class="tmeit-member-face" src="<?=$url; ?>" alt="" />
<?
                if( $this->isAdmin ):
?>
            <div class="tmeit-member-image-link">
                <a href="#" onclick="promptDelete('<?=htmlspecialchars( $photo ); ?>');">Radera</a>
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
<?
            if( $this->isAdmin ):
?>
<script type="text/javascript">
    function promptDelete( id )
    {
        if( confirm( 'Är du säker på att du vill ta bort fotot?' ) )
            location.href = '<?=$this->thisUrl; ?>?delete=' + id;
    }
</script>
<?
            endif;
        endif;
    }

    private function renderErrorMessage()
    {
?>
<p class="tmeit-important-note">
    <?=$this->errorMessage; ?>
</p>
<?
    }

    private function renderFooter()
    {
?>
<h3>Länkar</h3>
<ul>
<?
        if( $this->isAdmin ):
?>
    <li><a href="<?=$this->editMemberUrl; ?>">Redigera medlem</a></li>
<?
        endif;
?>
    <li><a href="<?=$this->listMembersUrl; ?>">Lista medlemmar</a></li>
    <li><a href="<?=$this->listImagesUrl; ?>">Lista medlemsfoton</a></li>
</ul>
<?
    }

    private function renderImageEditor()
    {
        global $wgScriptPath;
        $urlToImage = $wgScriptPath.$this->uploadedImagePath;
        $filenameOfImage = substr( $this->uploadedImagePath, strlen( self::UploadTempFolderRelative ) );
?>
<p>
	Flytta den streckade rektangeln för att markera den del av bilden du vill använda. Klicka och dra i hörnen för att ändra storlek. Klicka på Spara när du är nöjd.
	Om du laddade upp fel bild, klicka bakåt i din webbläsare och försök på nytt.
</p>

<form id="tmeit-uploaded-image-form" action="" method="post">
	<input type="hidden" name="image_filename" value="<?=htmlspecialchars( $filenameOfImage ); ?>" />
    <input type="hidden" name="selection_x" id="selection_x" value="0" />
    <input type="hidden" name="selection_y" id="selection_y" value="0" />
    <input type="hidden" name="selection_w" id="selection_w" value="<?=self::ImageWidth; ?>" />
    <input type="hidden" name="selection_h" id="selection_h" value="<?=self::ImageHeight; ?>" />
	<div>
    	<input type="submit" value="Spara" class="tmeit-button" />
	</div>
	<img id="tmeit-uploaded-image" src="<?=$urlToImage; ?>" />
	<div>
    	<input type="submit" value="Spara" class="tmeit-button" />
    </div>
</form>
<?
    }

    private function renderUploadForm()
    {
?>
<h3 style="margin-top: 20px">Ladda upp</h3>

<p class="tmeit-important-note">
    Absolut inga bilder som är stötande eller som personen på bilden skulle ha invändningar mot att publicera på Internet
    får laddas upp. Bilden ska vara ett foto på personens ansikte och personen ska gå att känna igen. Bilder som bryter mot detta tas bort.
    Om detta inte respekteras kommer funktionen att stängas av.
</p>

<form id="tmeit-form" action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?=self::MaxFileSize; ?>" />

    <table class="tmeit-new-table tmeit-half-width" style="margin-top: 10px;">
        <tr>
            <td class="caption-column">
                Ladda upp foto
            </td>
            <td class="field-column" style="padding-bottom: 10px">
                <input name="image_upload" type="file" class="tmeit-button" /><br />
                <ul style="padding-top: 10px">
                    <li>Maximal storlek <?=self::MaxFileSizeText; ?></li>
                    <li>Minsta upplösning <?=self::ImageWidth; ?> x <?=self::ImageHeight; ?> pixlar</li>
                    <li>Format JPEG eller PNG</li>
                </ul>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="submit-column">
                <input type="submit" value="Ladda upp" />
            </td>
        </tr>
    </table>
</form>
<?
    }

    private static function cleanUpOldUploads()
    {
        global $IP;
        $tempFolder = $IP.self::UploadTempFolderRelative;
        $now = time();

        $uploadFiles = scandir( $tempFolder );
        foreach( $uploadFiles as $fileName )
        {
            $filePath = $tempFolder.$fileName;
            if( is_dir( $filePath ) )
                continue;

            // Delete files that have been rotting in the temp folder
            if( $now - filemtime( $filePath ) > self::UploadFileMaxAge )
                unlink( $filePath );
        }
    }

    private static function cropAndResizeImage( $filePath, $outPath, $srcX, $srcY, $srcW, $srcH, &$errorMessage )
    {
        if( !file_exists( $filePath ) || false === ( $imageInfo = @getimagesize( $filePath ) ) )
        {
            $errorMessage = 'Kan inte hitta den uppladdade filen. Det kanske är för lång tid sedan den laddades upp. Prova att ladda upp den igen eller kontakta webbansvarig om felet kvarstår.';
            return false;
        }

        switch( $imageInfo[2] )
        {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg( $filePath );
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng( $filePath );
                break;
            default:
                // Shouldn't happen since we filtered for this already - but oh well
                $errorMessage = 'Filen är inte i ett tillåtet format. Bara JPEG- och PNG-bilder kan laddas upp.';
                return false;
        }

        $outImage = imagecreatetruecolor( self::ImageWidth, self::ImageHeight );
        imagecopyresampled( $outImage, $image, 0, 0, $srcX, $srcY, self::ImageWidth, self::ImageHeight, $srcW, $srcH );
        imagejpeg( $outImage, $outPath, self::ImageQuality );
        chmod( $outPath, 0640 );
        imagedestroy( $image );
        imagedestroy( $outImage );
        return true;
    }

    private static function findFreeTempFilename( $fileExt )
    {
        global $IP;
        $base = $IP.self::UploadTempFolderRelative.self::UploadTempPrefix;

        do {
        	$rnd = mt_rand( 1, 100000 );
            $fileName = $base.$rnd.$fileExt;
        } while( file_exists( $fileName ) );

        return $fileName;
    }

    private static function getFileExtensionByImageType( $imageType )
    {
        switch( $imageType )
        {
            case IMAGETYPE_JPEG:
                return '.jpg';
            case IMAGETYPE_PNG:
                return '.png';
            default:
                return false;
        }
    }

	private function handlePostback()
	{
		global $IP;
		if( isset( $_FILES['image_upload'] ) )
		{
			// Step 1 - someone is trying to upload a file
			$file = $_FILES['image_upload'];
			$errorMessage = '';
			if( false === ( $imageType = self::validateFileUpload( $file, $errorMessage ) ) )
			{
				$this->failed = true;
				$this->errorMessage = $errorMessage;
				return;
			}

			$fileExt = self::getFileExtensionByImageType( $imageType );
			self::cleanUpOldUploads();
			$tempFileName = self::findFreeTempFilename( $fileExt );

			if( !move_uploaded_file( $file['tmp_name'], $tempFileName ) )
			{
				$this->failed = true;
				$this->errorMessage = 'Uppladdningen misslyckades, filen kunde inte flyttas till tempkatalogen. Kontakta webbansvarig.';
				return;
			}

			$this->hasUpload = true;
			$this->uploadedImagePath = substr( $tempFileName, strlen( $IP ) );
	        $this->getOutput()->addModules( 'ext.tmeit.members.specialtmeitmemberimage' );
		}
		elseif( $this->hasField( 'image_filename' ) )
		{
			// Step 2 - cropping and resizing the image
			$srcX = $this->getIntField( 'selection_x' );
			$srcY = $this->getIntField( 'selection_y' );
			$srcW = $this->getIntField( 'selection_w' );
			$srcH = $this->getIntField( 'selection_h' );
			$fileName = $this->getTextField( 'image_filename' );

			// Protection against manipulation of the postback to point somewhere else
			$filePath = $IP.self::UploadTempFolderRelative.$fileName;
			if( substr( $fileName, 0, strlen( self::UploadTempPrefix ) ) != self::UploadTempPrefix || !file_exists( $filePath ) )
			{
				$this->failed = true;
				$this->errorMessage = 'Kan inte hitta den uppladdade filen. Prova att ladda upp en ny eller kontakta webbansvarig om felet kvarstår.';
				return;
			}

			$outPath = $this->faces->getNextFilename( $this->user['username'], '.jpg' );
			$errorMessage = '';
			if( !$this->cropAndResizeImage( $filePath, $outPath, $srcX, $srcY, $srcW, $srcH, $errorMessage ) )
			{
				@unlink( $filePath );
				@unlink( $outPath );
				$this->failed = true;
				$this->errorMessage = $errorMessage;
				return;
			}

			unlink( $filePath );
		}
	}

    private static function validateFileUpload( $file, &$errorMessage )
    {
        if( UPLOAD_ERR_OK == $file['error'] )
        {
            if( !is_uploaded_file( $file['tmp_name'] ) )
            {
                $errorMessage = 'Uppladdningen misslyckades på grund av ett internt fel. Försök igen.';
                return false;
            }

            if( $file['size'] > self::MaxFileSize )
            {
                $errorMessage = 'Uppladdningen misslyckades, filen är för stor. Förminska den, t ex genom att minska upplösningen, och försök därefter igen.';
                return false;
            }

            if( false === ( $imageInfo = @getimagesize( $file['tmp_name'] ) ) || ( IMAGETYPE_JPEG != $imageInfo[2] && IMAGETYPE_PNG != $imageInfo[2] ) )
            {
                $errorMessage = 'Filen är inte i ett tillåtet format. Bara JPEG- och PNG-bilder kan laddas upp. GIF, med eller utan animering, stöds inte.';
                return false;
            }

            list( $width, $height ) = $imageInfo;
            if( $width < self::ImageWidth || $height < self::ImageHeight )
            {
                $errorMessage = 'Bilden är för liten. För att kunna användas måste bilder ha minst måtten '.self::ImageWidth.' x '.self::ImageHeight.' pixlar.';
                return false;
            }

            return $imageInfo[2];
        }
        else
        {
            $errorMessage = 'Uppladdningen misslyckades. Kontrollera att filen existerar och inte är för stor. Försök därefter igen.';
            return false;
        }
    }
}