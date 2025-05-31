<?php declare(strict_types=1);

namespace phpSPA\Interfaces;

/**
 * undocumented class
 *
 * @package default
 * @author `g:snips_author`
 */
interface MapInterface
{
   /**
    * Validating $route methods
    *
    * @param string $method
    * @param string|array $route
    */
   public function match(string $method, string|array $route): bool|array;
}