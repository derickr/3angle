<?php
class RDP
{
	static function simplify( $points, $epsilon )
	{
		$firstPoint = $points[0];
		$lastPoint = $points[ sizeof( $points ) - 1 ];

		if ( sizeof( $points ) < 3 )
		{
			return $points;
		}

		$index = -1;
		$dist  = 0;

		for ( $i = 1; $i < sizeof( $points ) - 1; $i++ )
		{
			$cDist = self::findPerpendicularDistance( $points[ $i ], $firstPoint, $lastPoint );
			if ( $cDist > $dist )
			{
				$dist = $cDist;
				$index = $i;
			}
		}

		if ( $dist > $epsilon )
		{
			$l1 = array_slice( $points, 0, $index + 1 );
			$l2 = array_slice( $points, $index );
			$r1 = self::simplify( $l1, $epsilon );
			$r2 = self::simplify( $l2, $epsilon );

			$rs = array_slice( $r1, 0, sizeof( $r1 ) - 1 );
			$rs = array_merge( $rs, $r2 );
			return $rs;
		}
		else
		{
			return array( $firstPoint, $lastPoint );
		}
	}

	private static function findPerpendicularDistance( $p, $p1, $p2 )
	{
		if ( $p1[0] == $p2[0] )
		{
			return abs( $p[0] - $p1[0] );
		}
		else
		{
			$slope = ( $p2[1] - $p1[1] ) / ( $p2[0] - $p1[0] );
			$intercept = $p1[1] - ( $slope * $p1[0] );
			$result = abs( $slope * $p[0] - $p[1] + $intercept ) / sqrt( pow( $slope, 2 ) + 1 );
		}
		return $result;
	}
}
