<?php
class GeoJSONPoint
{
	public $p;

	function __construct( $lon, $lat )
	{
		$this->p = array( (float) $lon, (float) $lat );
	}

	function getGeoJSON()
	{
		return array( 'type' => 'Point', 'coordinates' => $this->p );
	}

	static function fromGeoJson( $json )
	{
		$geo = new GeoJSONPoint( $json['coordinates'][0], $json['coordinates'][1] );
		return $geo;
	}
}

class GeoJSONLineString
{
	public $ls;

	function __construct( $ls )
	{
		$this->ls = $ls;
	}

	function getGeoJSON()
	{
		return array( 'type' => 'LineString', 'coordinates' => $this->ls );
	}
}

class GeoJSONPolygon
{
	public $pg;

	function __construct( $pg )
	{
		$this->pg = $pg;
	}

	function getGeoJSON()
	{
		return array( 'type' => 'Polygon', 'coordinates' => $this->pg );
	}

	static function fromGeoJson( $json )
	{
		$geo = new GeoJSONPolygon( $json['coordinates'] );
		return $geo;
	}

	static function createFromBounds( $n, $e, $s, $w, $segments = 1 )
	{
		$coordinates = [];

		/* West to East, North side */
		for ($j = 0; $j <= $segments; $j++ )
		{
			$coordinates[] = [ $w + (($e-$w)/$segments*$j), $n ];
		}
		/* East to West, South side */
		for ($j = $segments; $j >= 0; $j-- )
		{
			$coordinates[] = [ $w + (($e-$w)/$segments*$j), $s ];
		}
		/* North West corner to tie it up */
		$coordinates[] = [ $w, $n ];

		return new GeoJSONPolygon( [ $coordinates ] );
	}
}
?>
