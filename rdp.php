<?php
class RDP
{
	static function simplify( $points, $epsilon )
	{
		self::simplifyInternal( $points, $epsilon, 0, sizeof( $points ) - 1 );
		return array_merge( $points );
	}

	private static function findPerpendicularDistance( $p, $p1, $p2 )
	{
		if ( $p1[0] == $p2[0] ) {
			return abs( $p[0] - $p1[0] );
		} else {
			$slope = ( $p2[1] - $p1[1] ) / ( $p2[0] - $p1[0] );
			$intercept = $p1[1] - ( $slope * $p1[0] );
			$result = abs( $slope * $p[0] - $p[1] + $intercept ) / sqrt( pow( $slope, 2 ) + 1 );
			return $result;
		}
	}

	static private function simplifyInternal( &$points, $epsilon, $start, $end )
	{
		$firstPoint = $points[$start];
		$lastPoint = $points[$end];
		$index = -1;
		$dist  = 0;

		if ( $end - $start < 2 ) {
			return;
		}

		for ( $i = $start + 1; $i < $end; $i++ ) {
			if ( !isset( $points[$i] ) ) {
				continue;
			}

			$cDist = self::findPerpendicularDistance( $points[ $i ], $firstPoint, $lastPoint );

			if ( $cDist > $dist ) {
				$dist = $cDist;
				$index = $i;
			}
		}

		if ( $dist > $epsilon ) {
			self::simplifyInternal( $points, $epsilon, $start, $index );
			self::simplifyInternal( $points, $epsilon, $index, $end );

			return;
		} else {
			for ( $i = $start + 1; $i < $end; $i++ ) {
				unset( $points[$i] );
			}
			return;
		}
	}
}
