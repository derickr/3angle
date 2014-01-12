<?php
class Functions
{
	static function split_tag( $tag )
	{
		preg_match( '/^(.*)=(.*)$/', $tag, $match );
		return array( $match[1], $match[2] );
	}

	static function split_tags( array $tags )
	{
		$returnTags = array();

		foreach ( $tags as $tag )
		{
			list( $name, $value ) = self::split_tag( $tag );
			$returnTags[$name] = $value;
		}
		return $returnTags;
	}
}
?>
