<?php

/**
 * Register component scope variables
 *
 * This function provides a convenient way to register multiple component
 * variables that can be used in component templates with @ or $ syntax.
 *
 * @param array $variables Array of variable name => callable pairs
 * @return void
 */
function scope(array $variables): void
{
    \PhpSPA\Core\Helper\ComponentScope::register($variables);
}