<?php
/**
 * @package Habari
 *
 */

/**
 * HabariDateTime class to wrap dates in.
 *
 * @property-read HabariDateTime $clone Returns a clonned object.
 * @property-read string $sql Returns a unix timestamp for inserting into DB.
 * @property-read int $int Returns a unix timestamp as integer.
 */
class HabariDateTime extends DateTime
{
	private static $default_timezone;
	private static $default_datetime_format = 'c';
	private static $default_date_format;
	private static $default_time_format;
	
	// various time increments in seconds
	const YEAR		= 31556926;
	const MONTH		= 2629744;
	const WEEK		= 604800;
	const DAY		= 86400;
	const HOUR		= 3600;
	const MINUTE	= 60;

	/**
	 * Set default timezone to system default on init.
	 *
	 * @static
	 */
	public static function __static()
	{

		if ( Options::get( 'timezone' ) ) {
			self::set_default_timezone( Options::get( 'timezone' ) );
		}

		self::$default_timezone = date_default_timezone_get();

		self::$default_date_format = Options::get('dateformat');
		self::$default_time_format = Options::get('timeformat');

		if ( self::$default_date_format || self::$default_time_format ) {
			self::set_default_datetime_format( self::$default_date_format . ' ' . self::$default_time_format );
		}
	}

	/**
	 * Set default date/time format. The format is the same as the
	 * internal php {@link http://ca.php.net/date date() function}.
	 *
	 * @static
	 * @param string $format The date format.
	 */
	public static function set_default_datetime_format( $format )
	{
		self::$default_datetime_format = $format;
	}

	/**
	 * Get the default date/time format set.
	 *
	 * @static
	 * @see set_default_datetime_format()
	 * @return string The date format set.
	 */
	public static function get_default_datetime_format()
	{
		return self::$default_datetime_format;
	}

	/**
	 * Sets the timezone for Habari and PHP.
	 *
	 * @static
	 * @param string $timezone A timezone name, not an abbreviation, for example 'America/New York'
	 */
	public static function set_default_timezone( $timezone )
	{
		self::$default_timezone = $timezone;
		date_default_timezone_set( self::$default_timezone );
	}

	/**
	 * Get the timezone for Habari and PHP.
	 * Defaults to system timezone if not set.
	 *
	 * @static
	 * @see set_default_timezone()
	 * @param string The deafult timezone.
	 */
	public static function get_default_timezone()
	{
		return self::$default_timezone;
	}

	/**
	 * Helper function to create a HabariDateTime object for the given
	 * time and timezone. If no time is given, defaults to 'now'. If no
	 * timezone given defaults to timezone set in {@link set_default_timezone()}
	 *
	 * @static
	 * @see DateTime::__construct()
	 * @param string $time String in a format accepted by
	 * {@link http://ca.php.net/strtotime strtotime()}, defaults to "now".
	 * @param string $timezone A timezone name, not an abbreviation.
	 */
	public static function date_create( $time = null, $timezone = null )
	{
		if ( $time instanceOf HabariDateTime ) {
			return $time;
		}
		elseif ( $time instanceOf DateTime ) {
			$time = $time->format('U');
		}
		elseif ( $time == null ) {
			$time = 'now';
		}
		elseif ( is_numeric($time) ) {
			$time = '@' . $time;
		}

		if ( $timezone === null ) {
			$timezone = self::$default_timezone;
		}

		// passing the timezone to construct doesn't seem to do anything.
		$datetime = new HabariDateTime($time);
		$datetime->set_timezone($timezone);
		return $datetime;
	}

	/**
	 * Set the date of this object
	 *
	 * @see DateTime::setDate()
	 * @param int $year Year of the date
	 * @param int $month Month of the date
	 * @param int $day Day of the date
	 */
	public function set_date( $year, $month, $day )
	{
		parent::setDate($year, $month, $day);
		return $this;
	}

	/**
	 * Sets the ISO date
	 *
	 * @see DateTime::setISODate()
	 * @param int $year Year of the date
	 * @param int $month Month of the date
	 * @param int $day Day of the date
	 */
	public function set_isodate( $year, $week, $day = null )
	{
		parent::setISODate($year, $week, $day);
		return $this;
	}

	/**
	 * Set the time of this object
	 *
	 * @see DateTime::setTime()
	 * @param int $hour Hour of the time
	 * @param int $minute Minute of the time
	 * @param int $second Second of the time
	 */
	public function set_time( $hour, $minute, $second = null )
	{
		parent::setTime($hour, $minute, $second);
		return $this;
	}

	/**
	 * Set the timezone for this datetime object. Can be either string
	 * timezone identifier, or DateTimeZone object.
	 *
	 * @see DateTime::setTimezone()
	 * @param mixed The timezone to use.
	 * @return HabariDateTime $this object.
	 */
	public function set_timezone( $timezone )
	{
		if ( ! $timezone instanceof DateTimeZone ) {
			$timezone = new DateTimeZone($timezone);
		}
		parent::setTimezone($timezone);
		return $this;
	}

	/**
	 * Get the timezone identifier that is set for this datetime object.
	 *
	 * @return DateTimeZone The timezone object.
	 */
	public function get_timezone()
	{
		return parent::getTimezone();
	}

	/**
	 * Returns date formatted according to given format.
	 *
	 * @see DateTime::format()
	 * @param string $format Format accepted by {@link http://php.net/date date()}.
	 * @return string The formatted date, false on failure.
	 */
	public function format($format = null)
	{
		if ( $format === null ) {
			$format = self::$default_datetime_format;
		}
		return parent::format($format);
	}

	/**
	 * Returns date components inserted into a string
	 * 
	 * Example:
	 * echo HabariDateTime::date_create('2010-01-01')->text_format('The year was {Y}.');
	 * // Expected output:  The year was 2010.	 	  	
	 *	
	 * @param string $format A string with single-character date format codes {@link http://php.net/date date()} surrounded by braces
	 * @return string The string with date components inserted	 
	 */	 
	public function text_format($format)
	{
		return preg_replace_callback('%\{(\w)\}%iu', array($this, 'text_format_callback'), $format);
	}

	/**
	 * Callback method for supplying replacements for HabariDatTime::text_format()
	 * 
	 * @param array $matches The matches found in the regular expression.
	 * @return string The date component value for the matched character.
	 */	 
	private function text_format_callback($matches)
	{
		return $this->format($matches[1]);
	}

	/**
	 * Alters the timestamp
	 *
	 * @param string $format A format accepted by {@link http://php.net/strtotime strtotime()}.
	 * @return HabariDateTime $this object.
	 */
	public function modify( $args )
	{
		parent::modify( $args );
		return $this;
	}

	/**
	 * @see format()
	 */
	public function get($format = null)
	{
		return $this->format($format);
	}

	/**
	 * Echos date formatted according to given format.
	 *
	 * @see format()
	 * @param string $format Format accepted by {@link http://php.net/date date()}.
	 */
	public function out($format = null)
	{
		echo $this->format($format);
	}

	/**
	 * Magic method called when this object is cast to string. Returns the
	 * unix timestamp of this object.
	 *
	 * @return string The unix timestamp
	 */
	public function __toString()
	{
		return $this->format('U');
	}

	/**
	 * Magic method to get magic ponies... properties, I mean.
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'clone':
				return clone $this;

			case 'sql':
				return $this->format('U');
				break;

			case 'int':
				return intval( $this->format('U') );
				break;

			case 'time':
				return $this->format( self::get_default_time_format() );
				break;

			case 'date':
				return $this->format( self::get_default_date_format() );
				break;
				
			case 'friendly':
				return $this->friendly();
				break;

			default:
				$info = getdate($this->format('U'));
				$info['mon0'] = substr('0' . $info['mon'], -2, 2);
				$info['mday0'] = substr('0' . $info['mday'], -2, 2);
				if ( isset($info[$property]) ) {
					return $info[$property];
				}
				return $this->$property;
		}
	}

	/**
	 * Return the default date format, as set in the Options table
	 *
	 * @return The default date format
	 **/
	public static function get_default_date_format ( ) {

		return self::$default_date_format;

	}

	/**
	 * Return the default time format, as set in the Options table
	 *
	 * @return The default time format
	 **/
	public static function get_default_time_format ( ) {

		return self::$default_time_format;

	}

	/**
	 * Returns an associative array containing the date information for
	 * this HabariDateTime object, as per {@link http://php.net/getdate getdate()}
	 *
	 * @return array Associative array containing the date information
	 */
	public function getdate()
	{
		$info = getdate($this->format('U'));
		$info['mon0'] = substr('0' . $info['mon'], -2, 2);
		$info['mday0'] = substr('0' . $info['mday'], -2, 2);
		return $info;
	}
	
	/**
	 * Returns a friendlier string version of the time, ie: 3 days, 1 hour, and 5 minutes ago
	 * 
	 * @todo Change $round to $format and support the same format as DateInterval::format() in PHP 5.3
	 * @todo How can we account for skipped intervals in the format? ie: omit 'week' and get that included as more days?
	 * @todo should we add a global option to define this format, too?
	 * @todo Add $omit_nulls param to omit 0-value intervals (ie: 1 month, 0 days vs. 1 month)
	 * 
	 * @param boolean $round Round the time to something less absolute but shorter: 'about 3 months'.
	 * @return string Time passed in the specified units.
	 */
	public function friendly ( $round = false )
	{
		$difference = self::date_create()->int - $this->int;
		
		$difference = self::difference( self::date_create(), $this );
				
		
		$result = array();
		
		if ( $difference['y'] ) {
			$result[] = sprintf( '%d %s', $difference['y'], _n( 'year', 'years', $difference['y'] ) );
		}
		
		if ( $difference['m'] ) {
			$result[] = sprintf( '%d %s', $difference['m'], _n( 'month', 'months', $difference['m'] ) );
		}
		
		if ( $difference['w'] ) {
			$result[] = sprintf( '%d %s', $difference['w'], _n( 'week', 'weeks', $difference['w'] ) );
		}
		
		if ( $difference['d'] ) {
			$result[] = sprintf( '%d %s', $difference['d'], _n( 'day', 'days', $difference['d'] ) );
		}
		
		if ( $difference['h'] ) {
			$result[] = sprintf( '%d %s', $difference['h'], _n( 'hour', 'hours', $difference['h'] ) );
		}
		
		if ( $difference['i'] ) {
			$result[] = sprintf( '%d %s', $difference['i'], _n( 'minute', 'minutes', $difference['i'] ) );
		}
		
		if ( $difference['s'] ) {
			$result[] = sprintf( '%d %s', $difference['s'], _n( 'second', 'seconds', $difference['s'] ) );
		}
		
		// only stick 'and' into the mix if there's more than a single element
		if ( count( $result ) > 1 ) {
		
			// pop the last element off the end of the array
			$last = array_pop( $result );
			
			// stick 'and' in before the last element
			$result[] = _t( 'and ' ) . $last;
		
		}
		
		// if there are only 2 elements, don't use a comma
		if ( count( $result ) > 2 ) {
			$result = implode( ', ', $result );
		}
		else {
			$result = implode( ' ', $result );
		}
		
		
		if ( $difference['invert'] == true ) {
			$suffix = _t('from now');
		}
		else {
			$suffix = _t('ago');
		}
		
		$result = $result . ' ' . $suffix;
		
		return $result;
		
	}
	
	/**
	 * Returns an array representing the difference between two times by interval.
	 * 
	 * <code>
	 * 	print_r( HabariDateTime::difference( 'now', 'January 1, 2010' ) );
	 * 	// output (past): Array ( [invert] => [y] => 0 [m] => 9 [w] => 3 [d] => 5 [h] => 22 [i] => 33 [s] => 5 )
	 * 	print_r( HabariDateTime::difference( 'now', 'January 1, 2011' ) );
	 * 	// output (future): Array ( [invert] => 1 [y] => 0 [m] => 2 [w] => 0 [d] => 3 [h] => 5 [i] => 33 [s] => 11 ) 
	 * </code>
	 * 
	 *  If 'invert' is true, the time is in the future (ie: x from now). If it is false, the time is in the past (ie: x ago).
	 *  
	 *  For more information, see PHP's DateInterval class, which this and friendly() attempt to emulate for < PHP 5.3
	 *  
	 *  @todo Add total_days, total_years, etc. values?
	 * 
	 * @param mixed $start_date The start date, as a HDT object or any format accepted by HabariDateTime::date_create().
	 * @param mixed $end_date The end date, as a HDT object or any format accepted by HabariDateTime::date_create().
	 * @return array Array of each interval and whether the interval is inverted or not.
	 */
	public static function difference ( $start_date, $end_date ) {
		
		// if the dates aren't HDT objects, try to convert them to one. this lets you pass in just about any format
		if ( !$start_date instanceof HabariDateTime ) {
			$start_date = HabariDateTime::date_create($start_date);
		}
		
		if ( !$end_date instanceof HabariDateTime ) {
			$end_date = HabariDateTime::date_create($end_date);
		}
		
		$result = array();
		
		// calculate the difference, in seconds
		$difference = $end_date->int - $start_date->int;
		
		if ( $difference < 0 ) {
			// if it's negative, time AGO
			$result['invert'] = false;
		}
		else {
			// if it's positive, time UNTIL
			$result['invert'] = true;
		}
		
		$difference = abs( $difference );
		
		// we'll progressively subtract from the seconds left, so initialize it
		$seconds_left = $difference;
		
		$result['y'] = floor( $seconds_left / self::YEAR );
		$seconds_left = $seconds_left - ( $result['y'] * self::YEAR );
		
		$result['m'] = floor( $seconds_left / self::MONTH );
		$seconds_left = $seconds_left - ( $result['m'] * self::MONTH );
		
		$result['w'] = floor( $seconds_left / self::WEEK );
		$seconds_left = $seconds_left - ( $result['w'] * self::WEEK );
		
		$result['d'] = floor( $seconds_left / self::DAY );
		$seconds_left = $seconds_left - ( $result['d'] * self::DAY );
		
		$result['h'] = floor( $seconds_left / self::HOUR );
		$seconds_left = $seconds_left - ( $result['h'] * self::HOUR );
		
		$result['i'] = floor( $seconds_left / self::MINUTE );
		$seconds_left = $seconds_left - ( $result['i'] * self::MINUTE );
		
		$result['s'] = $seconds_left;
		
		return $result;
		
	}
}

?>
