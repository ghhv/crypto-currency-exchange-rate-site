<?php
namespace JTM\Crypto\Currencies;

use JTM\Crypto as Crypto;
use JTM\Crypto\Log as Log;

class Currency {
	protected static $our_readable_name;
	protected static $our_symbol;
	protected static $our_queue = array();
	protected static $our_queue_key;
	protected static $our_response_caches = array();
	protected static $our_response_caches_times = array();

	protected $my_cache_key;

	/**
	 * Determines the exchange rate for this object's currency and the $exchange_currency.
	 *
	 * @param  string $exchange_currency The symbol of the desired exchange currency. E.g. "USD"
	 * @return string The exchange rate as plain text.
	 */
	public function get_current_exchange_rate( string $exchange_currency = 'USD' ) {
		return $this->parse_api_response( $this->query_api( $this->get('symbol'), $exchange_currency ), $this->get('symbol'), $exchange_currency );
	}
	

	/**
	 * Adds $this currency to a queue for a single batch API request, as opposed to several individual ones.
	 *
	 * @return void
	 */
	public function queue() {
		self::$our_queue[] = &$this;	//	Note self::, not static::. Don't want late static binding. Want this class.		
	}

	/**
	 * Runs a single query for all queued requests queued with Currency::queue() and caches the result.
	 *
	 * @param bool $clear_queue_after
	 * @param string $exchange_currency
	 * @return void
	 */
	public static function run_queue( bool $clear_queue_after = true, string $exchange_currency = 'USD' ) {
		//	Construct symbols string
		$syms = '';
		foreach( self::$our_queue as $SC ) {
			$syms .= $SC->get('symbol') . ',';
		}
		$syms = rtrim( $syms, ',' );	//	Remove trailing comma
		
		
		Log::info( 'Running queued query for the following symbols: ' . $syms );
		

		$Curr = new Currency();
		$res = $Curr->query_api( $syms, $exchange_currency );
		
		//	Set cache key for queued items
		foreach( self::$our_queue as $SC ) {
			$SC->set( 'my_cache_key', self::$our_queue_key );
		}

		if( $clear_queue_after ) {
			self::$our_queue = array();
		}

		return $res;
	}


	/**
	 * Parses a JSON response, theoretically from an exchange rate API, and returns the exchange rate between
	 * $desired_from_curr
	 *
	 * @param string $response           A JSON object containing the desired information.
	 * @param string $desired_from_curr  A currency symbol of the currency you want to convert from. E.g. "BTC"
	 * @param string $desired_to_curr    A currency symbol of the currency you want to convert to. E.g. "USD"
	 * @return string The exchange rate as plain text.
	 */
	protected function parse_api_response( string $response, string $desired_from_curr, string $desired_to_curr ) {
		$resarr = json_decode( $response, true );

		if( empty( $resarr ) || !is_array( $resarr ) ) {
			Log::error( 'Error decoding JSON response: "' . json_last_error_msg() . '"' );
			return false;
		}

		if( !array_key_exists( $desired_from_curr, $resarr ) ) {
			Log::error( 'Requested currency ("' . $desired_curr_to_parse . '") not provided in JSON response: ' . $response );
			return false;
		}

		$currarr = $resarr[$desired_from_curr];
		
		if( !array_key_exists( $desired_to_curr, $currarr ) ) {
			Log::error( 'Desired parameter, "' . $desired_to_curr . '" does not exist in JSON response: "' . $response . '"' );
			return false;
		}

		return $currarr[$desired_to_curr];
	}
	
	
	/**
	 * Returns the contents of the page at the given $url. If $url is ommitted or empty,
	 * the default API URL is used with this class' symbol. Returns empty string on failure.
	 * Checks the built in caching mechanism to see if the contents were already retrieved recently
	 * to limit slow and costly Interwebz queries.
	 *
	 * @param string $url The web query URL.
	 * @return string Results of web query.
	 */
	protected function query_api( string $from_symbols, string $to_symbols = 'USD', string $url = '' ) {
		if( empty( $url ) ) {
			$url = static::generate_api_query_url( $from_symbols, $to_symbols );
		}

		//	Check cache
		if( !empty( $this->my_cache_key ) ) {
			$contents = self::check_cache( $this->my_cache_key, true );	//	Note that cache is part of Currency class, not derived classes (self::, not static::)
		}
		else {
			$contents = self::check_cache( $url );
		}

		//	See if we got any cache results
		if( !empty( $contents ) ) {
			return $contents;
		}

		//	No cache, query the Interwebz
		Log::perf( 'Banging the Interwebz. This is slow. If several of these are chained together, try queuing them and running a batch query to minimized Internet requests.' );
		$contents = file_get_contents( $url );

		if( empty( $contents ) ) {
			Log::error( 'Error querying API URL: "' . $url . '"' );
			return '';
		}

		Log::info( 'Successful query of API URL: "' . $url . '"' );

		//	Store in cache
		$key = self::add_to_cache( $url, $contents );
		self::$our_queue_key = $key;
		$this->my_cache_key = $key;
		return $contents;
	}

	/**
	 * Generates a API URL using cryptocompare.com.
	 *
	 * @param string $from_symbol The crypto currency's symbol (e.g. BTC, LTC, ETH) or a comma separated string of them.
	 * @param string $to_symbol The symbol of the currency to convert to (e.g. USD, EUR)
	 * @return string An API URL
	 */
	protected static function generate_api_query_url( string $from_symbols, string $to_symbols = "USD" ) {
		$no_query_args = 'https://min-api.cryptocompare.com/data/pricemulti';
		$query_arr = array( 'fsyms'=>$from_symbols, 'tsyms'=>$to_symbols );

		$url = Crypto\untrailingslashit( $no_query_args ) . '?' . http_build_query( $query_arr );

		return $url;
	}

	/**
	 * Creates a short hash of some identifer for use as array indexes and IDs for cached query results.
	 *
	 * @param string $identifier An identifying value that uniquely labels specific API queries.
	 * @return string The hashed identifier.
	 */
	protected static function cache_hash_name( string $identifier ) {
		return crc32( $identifier );
	}

	/**
	 * Checks the query cache for an entry with the given identifying value, $id.
	 *
	 * @param string $id The cache ID. If ID has already been hashed, set $already_hashed flag to TRUE. Otherwise will be hashed automatically.
	 * @param bool $already_hashed A flag specifying whether the ID value needs to be run through the ID hashing process. Defaults to FALSE.
	 * @param integer $max_cache_age The maximum lifespan of cache results in seconds. Defaults to 60 seconds.
	 * @return mixed The cached response, if one exists and isn't too old. Boolean FALSE if no suitable cached value is available.
	 */
	protected static function check_cache( string $id, bool $already_hashed = false, $max_cache_age = 60 ) {
		if( !$already_hashed ) {
			$key = static::cache_hash_name( $id );
		}
		else {
			$key = $id;
		}

		//	Make sure we have key!
		if( empty( $key ) ) {
			throw new LogicException( 'Unable to determine cache key. Manually specify a cache URL, or store a cache key in object\'s $my_cache_key property' );
		}

		//	Okay, look at the cache
		if( array_key_exists( $key, self::$our_response_caches ) && array_key_exists( $key, self::$our_response_caches_times ) ) {
			//	We have a cache entry... is it too old?
			$age = time() - self::$our_response_caches_times[$key];
			if( $age <= $max_cache_age ) {
				//	It's not too old... return it
				Log::info( 'Retrieved cached API query result, with key of ' . $key . '. Cache is ' . $age . ' seconds old. Will expire at ' . $max_cache_age . ' seconds old.' );
				return self::$our_response_caches[$key];
			}
			else {
				Log::info( 'Cache expired for "' . $key . '".' );
			}
		}

		//	Something went wrong
		return false;
	}

	/**
	 * Add a query result to the cache.
	 *
	 * @param string $url The query URL. Will be used as the unique identifying value for the cache.
	 * @param string $content The query result to cache.
	 * @return string The hashed ID value for the cached query.
	 */
	protected static function add_to_cache( string $url, string $content ) {
		$key = static::cache_hash_name( $url );

		self::$our_response_caches[$key] = $content;
		self::$our_response_caches_times[$key] = time();

		Log::info( 'Cached API query (cache key: ' . $key . ') to "' . $url . '": ' . $content );

		return $key;
	}



	/**
	 * Checks to ensure required information for derived currency classes exists. Throws a LogicException if not.
	 *
	 * @return bool FALSE if check failed. TRUE if succeeded.
	 */
	protected function currency_data_check() {
		if( empty( $this->get( 'readable_name' ) ) ) {
			throw new LogicException( get_class( $this ) . ' class must have a readable name property ($our_readable_name) specified.' );
			return false;
		}

		if( empty( $this->get( 'symbol' ) ) ) {
			throw new LogicException( get_class( $this ) . ' class must have a symbol property ($our_symbol) specifed.' );
			return false;
		}

		return true;
	}


	/**
	 * Gets class properties. Throws LogicException if property does not exist.
	 *
	 * @param string $property Property name.
	 * @return mixed The property value. Returns NULL if no such property exists.
	 */
	public function get( string $property ) {
		switch( $property ) {
			//	Map static symbol variable.
			case 'symbol':
			case 'my_symbol':
			case 'our_symbol':
				return static::$our_symbol;
				break;
		
			//	Map static readable name variable.
			case 'readable_name':
			case 'my_readable_name':
			case 'our_readable_name':
				return static::$our_readable_name;
				break;

			//	Default search of properties.
			default:
				if( property_exists( $this, $property ) ) {
					return $this->$property;
					break;
				}				
		}

		//	No such property exists or is accessible.
		throw new LogicException( 'No such property "' . $property . '" in ' . get_class( $this ) . ' class, or property is inaccessible.' );
		return null;
	}

	/**
	 * Sets an object's property. Creates property if it does not already exist.
	 *
	 * @param string $property The property name.
	 * @param mixed $value The value to set it to.
	 * @return void
	 */
	public function set( string $property, $value ) {
		$this->$property = $value;
	}
}