<?php

declare(strict_types=1);

namespace PhpSPA\Interfaces;

/**
 * Interface for class-based UI components in PhpSPA.
 *
 * Any class invoked as an HTML component tag (<MyComponent /> or <MyComponent::method />)
 * must implement this interface. This prevents arbitrary userland or third-party classes
 * from being instantiated or executed via component tag formatting.
 *
 * @author dconco <me@dconco.net>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
interface UIComponent
{
}
