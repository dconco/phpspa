<?php

namespace PhpSPA\Core\Impl\RealImpl;

use PhpSPA\Core\Utils\ArrayFlat;
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
 * @license MIT
 * @since v1.0.0
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
 * @method IComponent script(callable $script, ?string $name = null) Add scripts to the component
 * @method IComponent styleSheet(callable $style, ?string $name = null) Add stylesheets to the component
 * @method IComponent reload(int $milliseconds) Set the reload interval for the component
 * @abstract
 */
abstract class ComponentImpl
{
   /**
    * @var callable
    */
   protected $component;

   /**
    * @var string|null
    */
   protected ?string $title = null;

   /**
    * @var string
    */
   protected string $method {
		get => strtoupper($this->method);

      set(mixed $m) {
			if (is_array($m)) {
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
    * @var array<array{0: callable, 1: string|null}>
    */
   protected array $scripts = [];

   /**
    * @var array<array{0: callable, 1: string|null}>
    */
   protected array $stylesheets = [];

   /**
    * @var int
    */
   protected int $reloadTime = 0;

   /**
    * @param mixed $method
    * @param mixed $args
    * @throws \BadMethodCallException
    * @return IComponent
    */
   public function __call($method, $args): static
   {
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
         'styleSheet' => $this->stylesheets[] = $args,
         'script' => $this->scripts[] = $args,
         default => throw new \BadMethodCallException("Method {$method} does not exist in " . __CLASS__),
      };

      return $this;
   }
}
