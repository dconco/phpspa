<?php

namespace phpSPA\Component;

class StateManagement
{
   private string $stateKey;
   private $value;

   public function __construct (string $stateKey, $default)
   {
      $this->value = $_SESSION["__phpspa_state_{$stateKey}"] ?? $default;
      $this->stateKey = $stateKey;
      $_SESSION["__phpspa_state_{$stateKey}"] = $_SESSION["__phpspa_state_{$stateKey}"] ?? $default;

      $reg = unserialize($_SESSION["__registered_phpspa_states"] ?? serialize([]));
      if (!in_array($stateKey, $reg))
      {
         array_push($reg, $stateKey);
         $_SESSION["__registered_phpspa_states"] = serialize($reg);
      }
   }

   public function __invoke ($value = null)
   {
      if (!$value) return $_SESSION["__phpspa_state_{$this->stateKey}"];

      $this->value = $value;
      $_SESSION["__phpspa_state_{$this->stateKey}"] = $value;
   }

   public function __tostring ()
   {
      $value = $_SESSION["__phpspa_state_{$this->stateKey}"] ?? $this->value;
      return is_array($value) ? json_encode($value) : (string) $value;
   }
}