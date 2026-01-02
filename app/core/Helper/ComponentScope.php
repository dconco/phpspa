<?php

namespace PhpSPA\Core\Helper;

/**
 * Component scope management utility
 *
 * This class manages component scope variables for the ComponentTagFormatter.
 * It provides a scoped registry for variables that can be accessed
 * during component rendering. Each component gets its own scope to avoid
 * variable name collisions between different components.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
class ComponentScope
{
   /**
    * Static registry of scoped variables organized by component instance
    *
    * @var array
    */
   private static array $scopes = [];

   /**
    * Current active scope identifier
    *
    * @var string|null
    */
   private static ?string $currentScope = null;

   /**
    * Generate a unique scope identifier for a component
    *
    * @return string
    */
   public static function createScope(): string
   {
      $scopeId = 'scope_' . uniqid() . '_' . bin2hex(random_bytes(8));
      self::$currentScope = $scopeId;
      self::$scopes[$scopeId] = [];
      return $scopeId;
   }

   /**
    * Set the current active scope
    *
    * @param string $scopeId Scope identifier
    * @return void
    */
   public static function setCurrentScope(string $scopeId): void
   {
      if (!isset(self::$scopes[$scopeId])) {
         self::$scopes[$scopeId] = [];
      }
      self::$currentScope = $scopeId;
   }

   /**
    * Register variables in the current scope
    *
    * @param array $variables Array of variable name => callable pairs
    * @return void
    */
   public static function register(array $variables): void
   {
      if (self::$currentScope === null) {
         self::createScope();
      }

      foreach ($variables as $name => $callable) {
         if (!is_callable($callable)) {
            throw new \InvalidArgumentException("Variable '{$name}' is not callable");
         }
         self::$scopes[self::$currentScope][$name] = $callable;
      }
   }

   /**
    * Get a variable from the current scope or any parent scopes
    *
    * @param string $name Variable name
    * @return callable|null The callable if found, null otherwise
    */
   public static function get(string $name): ?callable
   {
      // First check current scope
      if (self::$currentScope !== null && isset(self::$scopes[self::$currentScope][$name])) {
         return self::$scopes[self::$currentScope][$name];
      }
      
      // Then check all other scopes (for nested component calls)
      foreach (self::$scopes as $scope) {
         if (isset($scope[$name])) {
            return $scope[$name];
         }
      }
      
      return null;
   }

   /**
    * Check if a variable exists in any scope
    *
    * @param string $name Variable name
    * @return bool
    */
   public static function has(string $name): bool
   {
      // Check current scope first
      if (self::$currentScope !== null && isset(self::$scopes[self::$currentScope][$name])) {
         return true;
      }
      
      // Then check all other scopes
      foreach (self::$scopes as $scope) {
         if (isset($scope[$name])) {
            return true;
         }
      }
      
      return false;
   }

   /**
    * Clear all variables from the current scope
    *
    * @return void
    */
   public static function clear(): void
   {
      if (self::$currentScope !== null) {
         self::$scopes[self::$currentScope] = [];
      }
   }

   /**
    * Clear all scopes (used for cleanup)
    *
    * @return void
    */
   public static function clearAll(): void
   {
      self::$scopes = [];
      self::$currentScope = null;
   }

   /**
    * Remove a specific scope
    *
    * @param string $scopeId Scope identifier
    * @return void
    */
   public static function removeScope(string $scopeId): void
   {
      unset(self::$scopes[$scopeId]);
      if (self::$currentScope === $scopeId) {
         self::$currentScope = null;
      }
   }

   /**
    * Clear a specific variable from the current scope
    *
    * @param string $name Variable name
    * @return void
    */
   public static function unregister(string $name): void
   {
      if (self::$currentScope !== null) {
         unset(self::$scopes[self::$currentScope][$name]);
      }
   }
}