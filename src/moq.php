<?php

/**
*	Part of moq - mock RESTful service.
*	@author Diego Caponera <diego.caponera@gmail.com>
*	@link https://github.com/moonwave99/moq
*	@copyright Copyright 2012 Diego Caponera
*	@license http://www.opensource.org/licenses/mit-license.php MIT License
*/

require __DIR__ . "/spyc.php";

/**
 * Recursive alternative to str_replace that supports replacing keys as well
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $settings = array(
 *    "Credits" => "@appname@ created by @author@",
 *    "Description" => "@appname@ can parse logfiles and store then in mysql",
 *    "@author@_mail" => "kevin@vanzonneveld.net"
 * );
 * $mapping = array(
 *    "@author@" => "kevin",
 *    "@appname@" => "logchopper"
 * );
 *
 * // Execute //
 * $settings = replaceTree(
 *    array_keys($mapping), array_values($mapping), $settings, true
 * );
 *
 * // Show //
 * print_r($settings);
 *
 * // expects:
 * // Array
 * // (
 * //     [Credits] => logchopper created by kevin
 * //     [Description] => logchopper can parse logfiles and store then in mysql
 * //     [kevin_mail] => kevin@vanzonneveld.net
 * // )
 * </code>
 *
 * @param string  $search
 * @param string  $replace
 * @param array   $array
 * @param boolean $keys_too
 *
 * @return array
 */
function replaceTree($search="", $replace="", $array=false, $keys_too=false)
{
  if (!is_array($array)) {
      // Regular replace
      return str_replace($search, $replace, $array);
  }

  $newArr = array();
  foreach ($array as $k=>$v) {
      // Replace keys as well?
      $add_key = $k;
      if ($keys_too) {
          $add_key = str_replace($search, $replace, $k);
      }

      // Recurse
      $newArr[$add_key] = replaceTree($search, $replace, $v, $keys_too);
  }
  return $newArr;
}

/**
*	Route Class - wraps status-based HTTP respones.
*/
class Route
{

	/**
	*	@access protected
	*	@var string
	*/
	protected $url;

	/**
	*	@access protected
	*	@var string
	*/
	protected $method;

	/**
	*	@access protected
	*	@var int
	*/
	protected $delay;

	/**
	*	@access protected
	*	@var array
	*/
	protected $responses;

	/**
	*	@access public
	*	@static
	*	@var array
	*/
	public static $statuses = array(
		'100' => 'Continue',
		'101' => 'Switching Protocols',
		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'409' => 'Conflict',
		'500' => 'Internal Server Error',
		'503' => 'Service Unavailable'
	);

	/**
	*	Default constructor.
	*	@access public
	*	@param string $url The route url
	*	@param string $method The route method [GET, POST, PUT, DELETE]
	*	@param array $respones Key-Value array of HTTP respones [status => body]
	*	@param int $delay How many seconds the response should be delayed
	*/
	public function __construct($url, $method, $responses, $delay = 0)
	{

		$this -> url = $url;
		$this -> method = $method;
		$this -> responses = $responses;
		$this -> delay = $delay;

	}

	/**
	*	Returns true if request pattern did match.
	*	@access public
	*	@param string $pattern The pattern being tested against
	*	@param string $method HTTP request method
	*	@param int $status HTTP status being looked for
	*	@return boolean
	*/
	public function match($pattern, $method, $status)
	{

		if($this -> method != $method)
			return false;

		if($this -> url == $pattern)
			return true;

		if(count($urlTokens = explode('/', $this -> url)) != count(explode('/', $pattern)))
			return false;

		$regexp = [];

		foreach($urlTokens as $token)
		{

			$regexp[] = strpos($token, ":") === 0 ? '([A-Za-z0-9^\/]+)' : $token;

		}

		$regexp = '#' . implode('/', $regexp) . '#';

		preg_match( $regexp, $pattern, $matches );

		if(count($matches) <= 1)
			return false;

		$i = 1;

		$keys = array_keys($this -> responses);

		foreach($urlTokens as $token)
		{

			strpos($token, ":") === 0 && $this -> responses[$status ?: $keys[0]] = replaceTree(
				$token,
				$matches[$i++],
				$this -> responses[$status ?: $keys[0]]
			);

		}
    
		return true;

	}

	/**
	*	Renders HTTP response.
	*	@access public
	*	@param int $status The status response being requested - if NULL, first response is rendered
	*/
	public function renderResponse($status)
	{

		sleep(min($this -> delay, 10));

		$keys = array_keys($this -> responses);

		header(sprintf('HTTP/1.0 %s %s', $status ?: $keys[0], self::$statuses[$status ?: $keys[0]]));
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		echo json_encode($this -> responses[$status ?: $keys[0]], JSON_NUMERIC_CHECK);

	}

}

/**
*	Moq Class - routes HTTP requests over own routes list.
*/
class Moq
{

	/**
	*	@access protected
	*	@var string
	*/
	protected $baseUrl;

	/**
	*	@access protected
	*	@var array
	*/
	protected $routes;

	/**
	*	@access protected
	*	@var string
	*/
	protected $method;

	/**
	*	@access protected
	*	@var int
	*/
	protected $status;
  
	/**
	*	@access protected
	*	@var boolean
	*/
	protected $isProxy;  

	/**
	*	Default constructor.
	*	@access public
	*	@param string $routesFile 'routes.yml' resource path
	*/
	public function __construct($routesFile)
	{

    $this -> isProxy = isset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this -> baseUrl = 'http://' . $_SERVER['SERVER_NAME'] . ( $this -> isProxy ? (':' . $_SERVER['SERVER_PORT']) : '' ) . str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
		$this -> routes = [];
		$this -> method = $_SERVER['REQUEST_METHOD'];
		$this -> status = isset($_REQUEST['_status']) && (int)$_REQUEST['_status'] >= 100 ? $_REQUEST['_status'] : NULL;

		!($contents = file_get_contents($routesFile)) && $this -> serverError("'" . $routesFile ."' not found.");

		foreach(spyc_load($contents) as $route)
		{

			$this -> routes[] = new Route($route['url'], $route['method'], $route['responses'], isset($route['delay'])?$route['delay']:0);

		}

	}

	/**
	*	Routes HTTP request.
	*	@access public
	*/
	public function route()
	{

		$pattern = $this -> getPatternFromURI();

		foreach($this -> routes as $route)
		{
      
			if($route -> match(
				$pattern,
				$this -> method,
				$this -> status
			)){
				$route -> renderResponse($this -> status);
				exit;
			}

		}

		$this -> notFound();

	}

	/**
	*	Renders '500 Internal Server ERror' HTTP response.
	*	@access protected
	*	@param string $body The response body
	*/
	protected function serverError($body = 'Server elves are sleeping.')
	{

		header('HTTP/1.0 500 Internal Server Error');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		echo json_encode($body, JSON_NUMERIC_CHECK);

		exit;

	}

	/**
	*	Renders '404 Not Found' HTTP response.
	*	@access protected
	*	@param string $body The response body
	*/
	protected function notFound($body = 'Resource not found baby.')
	{

		header('HTTP/1.0 404 Not Found');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		echo json_encode($body, JSON_NUMERIC_CHECK);

		exit;

	}

	/**
	*	Gets request pattern from whole URI.
	*	@access protected
	*	@return string $pattern
	*/
	protected function getPatternFromURI()
	{

		$pattern = "/" . str_replace(
			str_replace("http://" . $_SERVER['HTTP_HOST'], '', $this -> baseUrl),
			'',
			$_SERVER['REQUEST_URI']
		);

    return explode('?', $pattern)[0];

	}

}