<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitUtil
{
	public static function strF( $str )
	{
		$args = func_get_args();
		array_shift( $args );
		return self::strFA( $str, $args );
	}

	public static function strFA( $str, $args )
	{
		if( NULL != $args && is_array( $args ) )
		{
			$find = array();
			for( $i = 1; $i <= count( $args ); $i++ )
				$find[] = "{".( $i - 1 )."}";
			$str = self::stringReplaceClean( $find, $args, $str );
		}

		return $str;
	}

	public static function strFXA( $str, $args )
	{
		if( NULL != $args && is_array( $args ) )
		{
			$find = array();
			for( $i = 1; $i <= count( $args ); $i++ )
				$find[] = '{X'.( $i - 1 ).'}';
			$str = self::stringReplaceClean( $find, $args, $str );
		}

		return $str;
	}

	private static function stringReplaceClean( $search, $replace, $subject )
	{
		if( !is_array( $search ) || !is_array( $replace ) || !is_string( $subject ) )
			return $subject;

		while( count( $search ) )
		{
			$search_text = array_shift( $search );
			$replace_text = array_shift( $replace );

			$pos = strpos( $subject, $search_text );
			if( is_int( $pos ) )
			{
				$pieces = explode( $search_text, $subject );
				if( count( $search ) )
				{
					foreach( $pieces as $k => $v )
						if( strlen( $v ) )
							$pieces[$k] = self::stringReplaceClean( $search, $replace, $v );
				}

				$subject = join( $replace_text, $pieces );
				break;
			}
		}

		return $subject;
	}

	public static function validateDate( $str )
	{
		$str = trim( $str );
		if( 1 === preg_match( '/^[0-9]+$/', $str )  )
		{
			// Attempt to correct common mistakes
			if( 6 == strlen( $str ) ) // YYMMDD
				$str = '19'.$str;
			if( 8 == strlen( $str ) ) // YYYYMMDD
				$str = substr( $str, 0, 4 ).'-'.substr( $str, 4, 2 ).'-'.substr( $str, 6, 2 );
		}

		$matches = array();
		if( 1 !== preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $str, $matches ) )
			return FALSE;

		$year = $matches[1];
		$month = $matches[2];
		$day = $matches[3];
		return ( checkdate( $month, $day, $year ) ? $str : FALSE );
	}

	private function __construct()
	{
	}
}
