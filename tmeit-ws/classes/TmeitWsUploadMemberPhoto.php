<?php

/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsUploadMemberPhoto extends TmeitWsPostService
{
	const ImageBytesKey = 'image_base64';
	const ImageExtension = '.jpg';
	const ImageHeight = 120;
	const ImagePrefix = 'upload_mob_';
	const ImageWidth = 110;
	const MaxBase64Size = 300000; // 200 kB in Base64 with some margin
	const MaxFileSize = 204800; // 200 kB
	const UploadTempFolderRelative = '/images/temp/';
	const UploadToKey = 'upload_to';
	private $tmeitFaces;
	private $userName;
	private $tempPath;

	public function __construct()
	{
		parent::__construct();
		$this->tmeitFaces = new TmeitFaces();
	}

	protected function finishRequest( $obj )
	{
		if( FALSE != $this->tempPath && file_exists( $this->tempPath ) )
			@unlink( $this->tempPath );

		parent::finishRequest( $obj );
	}

	protected function processRequest( $params )
	{
		$destUser = @$params[self::UploadToKey];
		if( FALSE == $destUser || FALSE == ( $user = $this->db->userGetByName( $destUser ) ) )
			return $this->finishRequest( self::buildError( 'A required parameter is missing or invalid. Please pretend you have an API reference and use that.', self::HttpBadRequest ) );
		$this->userName = $user['username'];

		$imageBytes = @$params[self::ImageBytesKey];
		if( strlen( $imageBytes ) > self::MaxBase64Size )
			return $this->finishRequest( self::buildError( 'The image too large. It must not be larger than 200 kB.', self::HttpBadRequest ) );

		$imageBytes = base64_decode( $imageBytes );
		if( FALSE == $imageBytes )
			return $this->finishRequest( self::buildError( 'A required parameter is missing. Please pretend you have an API reference and use that.', self::HttpBadRequest ) );

		$this->tempPath = $this->writeTemporaryFile( $imageBytes );
		if( !$this->validateImageUpload() )
			return $this->finishRequest( self::buildError( 'The image did not pass validation. Ensure it is a valid JPG, at least 110x120 pixels or a multiple thereof, and not larger than 200 kB.', self::HttpBadRequest ) );

		if( !$this->moveUploadedFile() )
			return $this->finishRequest( self::buildError( 'The server encountered an internal server error and was unable to finish the upload.', self::HttpInternalServerError ) );

		http_response_code( self::HttpCreated );
		return $this->finishRequest( array( self::SuccessKey => true ) );
	}

	private function moveUploadedFile()
	{
		$finalPath = $this->tmeitFaces->getNextFilename( $this->userName, self::ImageExtension, self::ImagePrefix );
		if( !rename( $this->tempPath, $finalPath ) )
			return FALSE;
		if( !chmod( $finalPath, 0640 ) )
			return FALSE;

		return TRUE;
	}

	private function validateImageUpload()
	{
		// Image file must not be too large
		if( filesize( $this->tempPath ) > self::MaxFileSize )
			return FALSE;

		// Image must be a valid JPG
		$imageInfo = @getimagesize( $this->tempPath );
		if( FALSE == $imageInfo || IMAGETYPE_JPEG != $imageInfo[2] )
			return FALSE;

		// Image must be 110x120 or an even multiple
		$width = $imageInfo[0];
		$height = $imageInfo[1];
		if( $width < self::ImageWidth || $height < self::ImageHeight || ( $width % self::ImageWidth ) != 0 || ( $height % self::ImageHeight ) != 0 )
			return FALSE;

		return true;
	}

	private function writeTemporaryFile( $imageBytes )
	{
		global $IP;
		$fileName = $this->tmeitFaces->getNextFilename( $this->userName, self::ImageExtension );
		$fileName = $IP . self::UploadTempFolderRelative . substr( strrchr( $fileName, '/' ), 1 );

		$fPtr = fopen( $fileName, 'wb' );
		fwrite( $fPtr, $imageBytes );
		fclose( $fPtr );

		return $fileName;
	}
}