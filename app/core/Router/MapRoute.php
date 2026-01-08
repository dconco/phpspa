<?php

declare(strict_types=1);

namespace PhpSPA\Core\Router;

use PhpSPA\App;
use PhpSPA\Http\Request;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Interfaces\MapInterface;

/**
 * Class MapRoute
 *
 * This class is responsible for mapping and matching routes against the current request URI and HTTP method.
 * It extends the Controller class and implements the MapInterface interface.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 * @var string|array $route
 * @var string $request_uri
 * @var array $method
 * @var bool $caseSensitive
 */
class MapRoute implements MapInterface
{
   use \PhpSPA\Core\Utils\Routes\StrictTypes;


   /**
    * @var string $request_uri The URI of the current request.
    */
   private string $request_uri;

	private Request $request;

   /**
    * @var array $method An array to store HTTP methods for routing.
    */
   private array $method;

   /**
    * {@inheritdoc}
    */
   public function __construct(
		string $method,
		readonly private array $routes,
		readonly private bool $caseSensitive,
		readonly private bool $pattern = false
	) {
		$this->request = new HttpRequest();
      $this->method = explode('|', strtoupper($method));

      // --- Replacing first and last forward slashes, $request_uri will be empty if req uri is / ---
      $this->request_uri = trim(App::$request_uri, '/');
      $this->request_uri = empty($this->request_uri) ? '/' : $this->request_uri;

      // --- If caseSensitive is false, convert request URI to lowercase ---
      $this->request_uri = $caseSensitive ? $this->request_uri : strtolower($this->request_uri);
   }

	public function match(): array|bool {
		foreach ($this->routes as $route) {

         $route = trim($this->caseSensitive ? $route : strtolower($route), '/');

			$match = $this->pattern
            ? $this->pattern($route)
            : $this->realMatch($route);

			if ($match) return $match;
		}

		return false;
	}

   private function realMatch (string $route): array|bool {
      // will store all the parameters value in this array
      $req = [];
      $unvalidate_req = [];
      $req_value = [];

      // will store all the parameters names in this array
      $paramKey = [];

      // finding if there is any {?} parameter in $route
		preg_match_all('/(?<={).+?(?=})/', $route, $paramMatches);

      // if the route does not contain any param call routing();
      if (empty($paramMatches) || empty($paramMatches[0] ?? [])) {
         return $this->matchRouting($route);
      }
      
      
      // setting parameters names
      foreach ($paramMatches[0] as $key) {
         $paramKey[] = $key;
      }
      
      // exploding route address
      $uri = explode('/', $route);
      
      // will store index number where {?} parameter is required in the $route
      $indexNum = [];
      
      // storing index number, where {?} parameter is required with the help of regex
      foreach ($uri as $index => $param) {
         if (preg_match('/{.*}/', $param)) {
            $indexNum[] = $index;
         }
      }
      
      /**
       *   ----------------------------------------------------------------------------------
       *   |   Exploding request uri string to array to get the exact index number value of parameter from $_REQUEST['uri']
       *   ----------------------------------------------------------------------------------
      */
      $reqUri = explode('/', $this->request_uri);
      
      // Check if the number of segments matches
      if (\count($reqUri) !== \count($uri)) {
         return false;
      }
      
      /**
       *   ----------------------------------------------------------------------------------
       *   |   Running for each loop to set the exact index number with reg expression this will help in matching route
       *   ----------------------------------------------------------------------------------
      */
      foreach ($indexNum as $key => $index) {
         /**
          *   --------------------------------------------------------------------------------
         *   |   In case if req uri with param index is empty then return because URL is not valid for this route
          *   --------------------------------------------------------------------------------
         */
        
        if (empty($reqUri[$index])) {
           return false;
         }
         
         if (str_contains($paramKey[$key], ':')) {
            $unvalidate_req[] = [ $paramKey[$key], $reqUri[$index] ];
         }
         
         // setting params with params names
         $key = trim((string) explode(':', $paramKey[$key], 2)[0]);
         $req[$key] = $reqUri[$index];
         $req_value[] = $reqUri[$index];
         
         // this is to create a regex for comparing route address
         // Use [^/]+ to match only one path segment (not including slashes)
         $reqUri[$index] = '([^/]+)';
      }
      // converting array to string
      $reqUri = implode('/', $reqUri);
      
      /**
       *   -----------------------------------
       *   |   replace all / with \/ for reg expression
       *   |   regex to match route is ready!
       *   -----------------------------------
      */
      $reqUri = str_replace('/', '\\/', $reqUri);
      
      // now matching route with regex
      if (preg_match("/$reqUri/", $route . '$')) {
         if (!empty($unvalidate_req)) {
            foreach ($unvalidate_req as $value) {
               $param_name = trim((string) explode(':', $value[0], 2)[0]);
               $param_types = trim((string) explode(':', $value[0], 2)[1]);
               $param_types = preg_split('/\|(?![^<]*>)/', $param_types);
               $param_value = $value[1];
               
               $parsed_value = static::matchStrictType($param_value, $param_types);

               if (!$parsed_value) return false;

               // checks if the requested method is of the given route
               if (!\in_array($this->request->method(), $this->method))
                  return false;

               $req[$param_name] = $parsed_value;
            }
         }

         return [
				'route' => $route,
				'params_value' => $req_value,
				'params' => $req,
         ];
      }

      return false;
   }

   /**
    * Matches the current request URI and method against the defined routes.
    *
    * This method checks if the current request URI matches any of the defined routes
    * and if the request method is allowed for the matched route. If a match is found,
    * it returns an array containing the request method and the matched route. If no
    * match is found, it returns false.
    *
    * @return bool|array Returns an array with 'method' and 'route' keys if a match is found, otherwise false.
    */
   private function matchRouting(array|string $route): bool|array
   {
      $uri = [];
      $str_route = '';

      if (\is_array($route)) {
         for ($i = 0; $i < \count($route); $i++) {
            $each_route = preg_replace("/(^\/)|(\/$)/", '', $route[$i]);

            empty($each_route)
					? array_push($uri, '/')
					: array_push($uri, strtolower($this->caseSensitive ? $each_route : strtolower($each_route)));
         }
      } else
         $str_route = empty($route) ? '/' : $route;

      if (\in_array($this->request_uri, $uri) || $this->request_uri === $str_route) {
         if (!\in_array($this->request->method(), $this->method))
            return false;

         return [
          'route' => $route,
         ];
      } else {
         return false;
      }
   }

   /**
    * Validates and matches a route pattern.
    *
    * This method checks if the route is an array and iterates through each value to find a pattern match.
    * If a pattern match is found, it validates the pattern and returns the matched result.
    * If no match is found in the array, it returns false.
    * If the route is not an array, it directly validates the pattern and returns the result.
    *
    * @return array|bool The matched pattern as an array if found, or false if no match is found.
    */
   private function pattern($route): array|bool
   {
      if (fnmatch($route, $this->request_uri)) {
         if (!\in_array($this->request->method(), $this->method)) {
            return false;
         }

         return [
          	'route' => $route,
         ];
      }
      return false;
   }
}
