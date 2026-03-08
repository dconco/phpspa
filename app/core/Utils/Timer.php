<?php

namespace PhpSPA\Core\Utils;

class Timer 
{
   private ?float $time = null;

   public function start(): void {
      $this->time = microtime(true);
   }

   public function reset(): void {
      $this->time = null;
   }

   public function getElapsedTime(): float {
      if ($this->time === null) {
         throw new \LogicException("Timer has not been started.");
      }
      return microtime(true) - $this->time;
   }

   public function getFormattedElapsedTime(): string {
      if ($this->time === null) {
         throw new \LogicException("Timer has not been started.");
      }

      $elapse = $this->getElapsedTime();
      $secs = round($elapse, 2) . 's';
      $ms = round($elapse * 1000, 2) . 'ms';

      return "$ms | $secs";
   }
}