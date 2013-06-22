<?php

function format_response( $s, $showCheckIn )
{
	$rets = array();
	foreach( $s as $o )
	{
		$ret = array(
			'type' => 'Feature',
			'properties' => array( 'popupContent' => '', 'changed' => false ),
		);
		if ( isset( $o['possible'] ) )
		{   
			$ret['properties']['changed'] = true;
		}
		if ( isset( $o[TAGS] ) ) {
			$name = $content = ''; $image = false;
			$classes = array();
			foreach ( $o[TAGS] as $tagName => $value ) {
				list( $tagName, $value ) = explode( '=', $value );
				if ( $tagName == 'name' ) {
					$name = $value;
				} else if ( $tagName == 'title' ) {
					$name = $value; 
				} else if ( $tagName == 'thumb_url' ) { 
					$ret['properties']['thumbUrl'] = $value;
	//          } else if ( $tagName == 'thumb_url' ) {
					$image = $value;
				} else {
					$content .= "<br/>{$tagName}: {$value}\n";
				}   
				if ( in_array( $tagName, array( 'amenity', 'leisure' ) ) )
				{       
					$classes[] = preg_replace( '/[^a-z0-9]/', '', $tagName . $value );
				}           
			} 
			if ($image) {   
				$content = "<br/><div style='width: 150px'><img src='{$image}'/></div>";
			}
			else if ( $showCheckIn )
			{
				$content .= "<br/><form action='checkin.php' method='post'><input type='hidden' name='object' value='{$o['_id']}'/><input type='submit' value='check in'/></form>";
			}       
			$ret['properties']['name'] = $name;
			if ( isset( $o['distance'] ) )
			{       
				$ret['properties']['name'] .= "<br/>\n(". sprintf('%d m', $o['distance']) . ')';
			}
			$ret['properties']['classes'] = join( ' ', $classes );
			$ret['properties']['popupContent'] = "<b>{$name}</b>" . $content;
		}

		$ret['geometry'] = $o[LOC];

		$rets[] = $ret;
	}
	return $rets;
}
