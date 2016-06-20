<?php
class Util {
	
	/**
	 * Checks if any strings in a given array are in another given string.
	 * Returns the first matching word in the array if so, otherwise FALSE.
	 */
	public static function contains($str, array $arr) {
    	foreach($arr as $a) {
        	if (stripos($str, $a) !== false) return $a;
    	}
    	return false;
	}

}