<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitFaces
{
	const PngExt = '.png';
	const JpegExt = '.jpg';

	private $imagesPath;
	private $imagesUrl;
	private $placeHoldersUrl;
	private static $placeholderFiles = array( "1.png", "2.png", "3.png", "4.png", "5.png", "6.png", "7.png", "8.png" );

	public function __construct()
	{
		global $wgScriptPath, $IP;
		$this->imagesPath = $IP.'/images/tmeit_members/';
		$this->imagesUrl = $wgScriptPath.'/images/tmeit_members/';
		$this->placeHoldersUrl = $this->imagesUrl.'_placeholders/';
	}

	public function deletePhoto( $username, $photo )
	{
		// Safety - only allow photos that exist to be deleted
		$userPhotos = $this->findPhotos( $username );
		if( in_array( $photo, $userPhotos ) )
		{
			$fileName = $this->getPathOfPhoto( $username, $photo );
			if( file_exists( $fileName ) )
				unlink( $fileName );
		}
	}

	public function findPhotos( $username )
	{
		$list = array();

		$userPath = $this->imagesPath.strtolower( $username );
		$dh = @opendir( $userPath );

		if( FALSE !== $dh )
		{
			while( FALSE !== ( $item = readdir( $dh ) ) )
			{
				if( '.' == $item || '..' == $item )
					continue;
				if( !self::endsWith( self::PngExt, $item ) && !self::endsWith( self::JpegExt, $item ) )
					continue;

				$list[] = $item;
			}

			@closedir( $dh );
		}

		return $list;
	}

	public function getRandomPhoto( $username, $photos )
	{
		$num = is_array( $photos ) ? count( $photos ) : 0;

		$basePath = $num > 0
			 ? $this->imagesUrl.strtolower( $username ).'/'
			 : $this->placeHoldersUrl;

		if( 0 == $num )
		{
			$photos = self::$placeholderFiles;
			$num = count( self::$placeholderFiles );
		}
		elseif( 1 == $num )
			return $basePath.$photos[0];

		$idx = mt_rand( 0, $num - 1 );
		return $basePath.$photos[$idx];
	}

	public function getNextFilename( $username, $fileExt, $prefix = 'upload_' )
	{
        $this->createFolderIfNotExists( $username );
		$basePath = $this->getPathOfPhoto( $username, $prefix );
		do {
			$rnd = mt_rand( 1, 100000 );
            $fileName = $basePath.$rnd.$fileExt;
        } while( file_exists( $fileName ) );
        return $fileName;
	}

	public function getPathOfPhoto( $username, $photo )
	{
		return $this->imagesPath.strtolower( $username ).'/'.$photo;
	}

	public function getUrlOfPhoto( $username, $photo )
	{
		return $this->imagesUrl.strtolower( $username ).'/'.$photo;
	}

    private function createFolderIfNotExists( $username )
    {
        $path = $this->imagesPath.strtolower( $username );
        if( !is_dir( $path ) )
            mkdir( $path, 0750 );
    }

	private static function endsWith( $needle, $haystack )
	{
		return $needle == substr( $haystack, -strlen( $needle ) );
	}
}