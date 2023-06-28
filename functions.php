<?php
include 'config.php';

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

class Triangle
{
	private $m;
	private $d;
	private $o;
	private $tags;
	private $fields = array();
	public  $title = "Check in";

	function __construct()
	{
		$this->m = new MongoClient( 'mongodb://localhost' );
		$this->d = $this->m->selectDb( DATABASE );
		$this->c = $this->d->selectCollection( COLLECTION );
		$this->sc = $this->d->selectCollection( 'suggestions' );

		/* Find object */
		$object = isset( $_POST['object'] ) ? $_POST['object'] : $_GET['object'];

		/* #2: Write the query that find that object with the object ID in $object, and assign it's result to $this->o */
		$this->o = $this->c->findOne( array( '_id' => preg_replace( '/[^nw0-9]/', '', $object ) ) );

		/* Split the tags on their = */
		$this->tags = Functions::split_tags( $this->o[TAGS] );
	}

	private function add_field( $name, $widget)
	{
		if ( !array_key_exists( $name, $this->fields ) )
		{
			$this->fields[$name] = array( $name, $widget, array_key_exists( $name, $this->tags ) ? $this->tags[$name] : NULL );
		}
	}

	private function add_addres_fields()
	{
		$this->add_field( 'addr:housename', 'freeform' );
		$this->add_field( 'addr:housenumber', 'freeform' );
		$this->add_field( 'addr:street', 'freeform' );
		$this->add_field( 'addr:city', 'freeform' );
		$this->add_field( 'addr:postcode', 'freeform' );
	}

	function getLocation()
	{
		if ( !isset( $this->o[TYPE] ) )
		{
			return '';
		}
		if ( $this->o[LOC]['type'] == 'Polygon' )
		{
			$loc = $this->o[LOC]['coordinates'][0][0];
		}
		else if ( $this->o[LOC]['type'] == 'LineString' )
		{
			$loc = $this->o[LOC]['coordinates'][0];
		}
		else
		{
			$loc = $this->o[LOC]['coordinates'];
		}

		return "lat={$loc[1]}&lon={$loc[0]}";
	}

	function alias( $tags )
	{
		if ( isset( $tags['postal_code'] ) )
		{
			$tags['addr:postcode'] = $tags['postal_code'];
		}
		return $tags;
	}

	function run()
	{
		$tags = $this->tags;

		/* Do alias */
		$tags = $this->alias( $tags );

		/* Do tests */
		if ( array_key_exists( 'amenity', $tags ) )
		{
			$amenity = $tags['amenity'];
			$this->title = "Check in into $amenity";

			$this->add_field( 'amenity', 'static' );
			$this->add_field( 'name', 'freeform' );

			if ( in_array( $amenity, array( 'restaurant' ) ) )
			{
				$this->add_field( 'cuisine', 'listUsage' );
			}
			if ( in_array( $amenity, array( 'pub', 'bar' ) ) )
			{
				$this->add_field( 'real_ale', 'boolean' );
				$this->add_field( 'real_cider', 'boolean' );
			}
			$this->add_field( 'wheelchair', 'listUsage' );
		}

		/* Add the rest of the fields */
		foreach ( $tags as $name => $value )
		{
			if ( !array_key_exists( $name, $this->fields ) )
			{
				$this->fields[$name] = array( $name, 'freeform', $value );
			}
		}

		$this->add_field( 'name', 'freeform' );
		$this->add_addres_fields();
	}

	private function get_usage( $name )
	{
		$pipeline = array(
			array( '$match' => array( TAGS => new MongoRegex( "/^{$name}=/" ) ) ),
			array( '$project' => array( TAGS => 1 ) ),
			array( '$unwind' => '$' . TAGS ),
			array( '$match' => array( TAGS => new MongoRegex( "/^{$name}=/" ) ) ),
			array( '$group' => array(
				'_id' => '$' . TAGS,
				'count' => array( '$sum' => 1 ),
			) ),
			array( '$sort' => array( '_id' => 1 ) )
		);

		$result = $this->c->aggregate( $pipeline );

		$cuisines = array();
		foreach ( $result['result'] as $item )
		{
			list( $name, $value ) = Functions::split_tag( $item['_id'] );
			$cuisines[] = $value;
		}
		return $cuisines;
	}

	private static function approve( $name )
	{
		$ret = '';
		$ret .= "<td><b>Approve:</b><br/> \n";
		$ret .= "<input type='radio' name='approve_{$name}' value='0'>don't know<br/>\n";
		$ret .= "<input type='radio' name='approve_{$name}' value='-1'>no<br/>\n";
		$ret .= "<input type='radio' name='approve_{$name}' value='1'>yes<br/>\n";
		$ret .= "</td>\n";
		return $ret;
	}

	private static function static_widget( $name, $value )
	{
		return "<tr><td>$name</td><td>{$value}</td></tr>\n";
	}

	private static function freeform_widget( $name, $value, $suggestion )
	{
		if ( $suggestion )
		{
			$ret = "<tr><th>$name</th><td>";
			$ret .= "<b>Current:</b><br/>{$value}<br/>";
			$ret .= "<b>Suggestion:</b><br/>{$suggestion}<br/>";
			$ret .= "</td>";
			$ret .= self::approve( $name );
			$ret .= "</tr>\n";
			return $ret;
		}
		else
		{
			return "<tr><td>$name</td><td><input type='text' name='{$name}' value='{$value}'></td></tr>\n";
		}
	}

	private static function boolean_widget( $name, $value, $suggestion )
	{
		$yes = $no = '';
		if ( $value == 'yes' )
		{
			$yes = "selected='selected' ";
		}
		if ( $value == 'no' )
		{
			$no = "selected='selected' ";
		}
		$ret = "<tr><td>$name</td><td><select name='{$name}'>";
		$ret .= "<option value=''>«unknown»</option>\n";
		$ret .= "<option {$yes}value='1'>yes</option>\n";
		$ret .= "<option {$no}value='0'>no</option>\n";
		$ret .= "</select></td></tr>\n";
		return $ret;
	}

	private function usage_widget( $name, $value, $suggestion )
	{
		$ret = "<tr><td>$name</td><td><select name='{$name}'>";
		$ret .= "<option value='0'>«none set»</option>\n";
		foreach ( $this->get_usage( $name ) as $item )
		{
			$selected = $value == $item ? ' selected="selected"' : '';
			$ret .= "<option value='{$item}'{$selected}>{$item}</option>\n";
		}
		$ret .= "</select></td></tr>\n";
		return $ret;
	}

	private function create_widget( $type, $name, $initValue, $suggestion )
	{
		$value = isset( $initValue[2] ) ? $initValue[2] : '';
		switch ( $type )
		{
			case 'static':
				return self::static_widget( $name, $value );

			case 'boolean':
				return self::boolean_widget( $name, $value, $suggestion );

			case 'freeform':
				return self::freeform_widget( $name, $value, $suggestion );

			case 'listUsage':
				return $this->usage_widget( $name, $value, $suggestion );

		}
	}

	function show_data()
	{
		/* Query for suggestions */
		$r = $this->sc->aggregate( array(
			array( '$match' => array( 'id' => $this->o['_id'] ) ),
			array( '$sort' => array( 'key' => 1, 'value' => 1 ) ),
			array( '$group' => array( '_id' => '$key', 'items' => array( '$push' => '$value' ) ) ),
		) );
		$r = $this->sc->find( array( 'id' => $this->o['_id'] ) );

		$suggestion = array();
		foreach ( $r as $result )
		{
			$suggestion[$result['key']] = $result['value'];
		}

		$ret = '';
		$ret .= "<input type='hidden' name='action' value='checkin'>\n";
		$ret .= "<input type='hidden' name='object' value='{$this->o['_id']}'>\n";
		$ret .= '<table>';
		foreach ( $this->fields as $name => $value )
		{
			$ret .= $this->create_widget( $value[1], $name, $value, array_key_exists( $name, $suggestion ) ? $suggestion[$name] : false );
		}
		$ret .= "<tr><td colspan='2'><input type='submit' name='checkin' value='check in'></td></tr>\n";
		$ret .= '</table>';
		return $ret;
	}

	function commit()
	{
		$values = $_GET;
		$updates = array();
		unset( $values['action'] );
		unset( $values['object'] );
		unset( $values['checkin'] );
			
		/* Check for _n values first */
		foreach ( $values as $key => $value )
		{
			if ( preg_match( "/(.*)_n$/", $key, $m ) )
			{
				$values[$m[1]] = $values[$key];
				unset( $values[$key] );
			}
		}

		foreach ( $values as $key => $value )
		{
			if ( $value === '' )
			{
				continue;
			}
			if ( preg_match( '/_n$/', $key ) )
			{
				continue;
			}
			if ( isset( $this->tags[$key] ) )
			{
				if ( $this->tags[$key] == $value )
				{
//					echo "Value for $key is the same: $value<br/>\n";
				}
				else
				{
//					echo "Value for $key is changed from {$this->tags[$key]} to {$value}<br/>\n";
					$this->doSuggestion( $this->o['_id'], $key, $value );
				}
			}
			else
			{
//				echo "Key $key is new with value {$value}<br/>\n";
				$this->doSuggestion( $this->o['_id'], $key, $value );
			}
		}
	}

	function doSuggestion( $id, $key, $value )
	{
		// first we check if there is an entry already
		if ( $this->sc->findOne( array( 'id' => $id, 'key' => $key, 'value' => $value ) ) === null )
		{
			// we need to add something
			$this->sc->insert(
				array( 'id' => $id, 'key' => $key, 'value' => $value, 'suggester' => time(), 'last' => time() )
			);
		}
		else
		{
			// if so, we just push the new USER id (timestamp in our case)
			$this->sc->update(
				array( 'id' => $id, 'key' => $key, 'value' => $value ),
				array(
					'$set' => array( 'last' => time() ),
					'$addToSet' => array( 'approver' => time() )
				)
			);
		}
	}
}
?>
