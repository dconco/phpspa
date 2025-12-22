<?php

namespace PhpSPA\Core\Impl\RealImpl;

use BadMethodCallException;
use InvalidArgumentException;
use PhpSPA\Core\Http\HttpRequest;
use PhpSPA\Core\Utils\ArrayFlat;
use PhpSPA\Core\Utils\Formatter\ComponentTagFormatter;
use PhpSPA\Interfaces\IComponent;

/**
 * Core component implementation class
 *
 * This abstract class provides the foundational implementation for components
 * within the PhpSPA framework. It handles component properties such as routes,
 * methods, titles, target IDs, and associated scripts and stylesheets.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2025 Dave Conco
 * @method IComponent title(string $title) Set the title of the component
 * @method IComponent method(string ...$method) Set the HTTP method for the component, defaults to 'GET|VIEW'
 * @method IComponent route(string|array ...$route) Set the route(s) for the component
 * @method IComponent pattern() Shows that the given route value is a pattern in `fnmatch` format
 * @method IComponent exact() Make the component show only for that specific route
 * @method IComponent preload(string ...$componentName) This loads the component with the specific name as a layout on the exact URL on this page
 * @method IComponent name(string $value) This is a unique key for each components to use for preloading
 * @method IComponent targetID(string $targetID) Set the target ID for the component
 * @method IComponent caseSensitive() Enable case sensitivity for the component
 * @method IComponent caseInsensitive() Disable case sensitivity for the component
 * @method IComponent script(callable|string $content, ?string $name = null, ?string $type = 'text/javascript', array $attributes = []) Add scripts to the component
 * @method IComponent link(callable|string $content, ?string $name = null, ?string $type = null, ?string $rel = 'stylesheet', array $attributes = []) Add links tag to the component
 * @method IComponent reload(int $milliseconds) Set the reload interval for the component
 * @license MIT
 * @abstract
 */
abstract class ComponentImpl
{
   use ComponentTagFormatter;

   /**
    * @var callable
    */
   protected $component;

   /**
    * @var string|null
    */
   protected ?string $title = null;

   /**
    * @var array<int, array<string, string>>
    */
   protected array $metadata = [];

   /**
    * @var string
    */
   protected string $method {
		get => strtoupper($this->method);

      set(mixed $m) {
			if (\is_array($m)) {
            $m = array_map('trim', $m);
				$this->method = implode('|', $m);
         } else
				$this->method = $m;
      }
   }

   /**
    * @var array
    */
   protected array $route {
		set(array $r) => new ArrayFlat(array: $r)->flat();
	}

   /**
    * @var bool
    */
   protected bool $pattern = false;

   /**
    * @var bool
    */
   protected bool $exact;

   /**
    * @var array
    */
   protected array $preload;

   /**
    * @var string
    */
   protected string $name;

   /**
    * @var string|null
    */
   protected ?string $targetID = null;

   /**
    * Indicates whether the component should treat values as case sensitive.
    *
    * @var bool|null If true, case sensitivity is enabled; if false, it is disabled; if null, the default behavior is used.
    */
   protected ?bool $caseSensitive = null;

   /**
    * @var array<array{
    *    content: callable|string,
    *    name: string|null,
    *    type: string|null
    * }>
    */
   protected array $scripts = [];

   /**
    * @var array<array{
    *    content: callable|string,
    *    name: string|null,
    *    type: string|null,
    *    rel: string
    * }>
    */
   protected array $stylesheets = [];

   /**
    * @var int
    */
   protected int $reloadTime = 0;

   /**
    * @param mixed $method
    * @param mixed $args
    * @throws BadMethodCallException
    * @throws InvalidArgumentException
    * @return IComponent
    */
   public function __call($method, $args): static
   {
      $addAsset = function(string $property) use ($args) {
         if ($property !== 'stylesheets' && $property !== 'scripts') throw new InvalidArgumentException("Invalid property provided", 1);

         $temp = [];

         if (isset($args[0]) || isset($args['content'])) $temp['content'] = $args[0] ?? $args['content'];
         if (isset($args[1]) || isset($args['name'])) $temp['name'] = $args[1] ?? $args['name'];

         if (isset($args[2]) || isset($args['type'])) $temp['type'] = $args[2] ?? $args['type'];
         else if ($property === 'scripts') $temp['type'] = 'text/javascript';

         $attributes = $args['attributes'] ?? [];

         if ($property === 'stylesheets') {
            if (isset($args[3]) || isset($args['rel']))
               $temp['rel'] = $args[2] ?? $args['rel'];
            else
               $temp['rel'] = 'stylesheet';

            if (isset($args[4])) $attributes = $args[4];
         } else {
            if (isset($args[3])) $attributes = $args[3];
         }

         foreach ($attributes as $attribute => $value) {
            if (!\is_string($attribute) || !\is_string($value)) {
               continue;
            }
            $temp[$attribute] = $value;
         }

         $this->$property[] = $temp;
      };

      match ($method) {
         'name',
         'title',
         'targetID' => $this->$method = $args[0],
         'route',
         'method',
         'preload' => $this->$method = $args,
         'exact',
         'pattern',
         'caseSensitive' => $this->$method = true,
         'caseInsensitive' => $this->caseSensitive = false,
         'reload' => $this->reloadTime = $args[0],
         'link' => $addAsset('stylesheets'),
         'styleSheet' => $addAsset('stylesheets'),
         'script' => $addAsset('scripts'),
         default => throw new BadMethodCallException("Method {$method} does not exist in " . __CLASS__),
      };

      return $this;
   }

   public function meta(
      ?string $name = null,
      ?string $content = null,
      ?string $property = null,
      ?string $httpEquiv = null,
      ?string $charset = null,
      array $attributes = []
   ): static {
      $entry = [];

      if ($name !== null) {
         $entry['name'] = $name;
      }

      if ($property !== null) {
         $entry['property'] = $property;
      }

      if ($httpEquiv !== null) {
         $entry['http-equiv'] = $httpEquiv;
      }

      if ($charset !== null) {
         $entry['charset'] = $charset;
      }

      if ($content !== null) {
         $entry['content'] = $content;
      }

      foreach ($attributes as $attribute => $value) {
         if (!\is_string($attribute)) {
            continue;
         }
         $entry[$attribute] = $value;
      }

      if (empty($entry)) {
         return $this;
      }

      $this->metadata[] = $entry;

      return $this;
   }

   /**
    * Renders a component by executing it and formatting the output.
    *
    * @param callable $component The component to render.
    * @return string The rendered output.
    */
   public static function Render(callable $component): string
   {
      $output = \call_user_func($component, new HttpRequest());
      return static::format($output);
   }
}
