<?php

namespace PhpSPA\Core\Helper\Enums;

/**
 * Represents the navigation state for client-side routing.
 *
 * This enum defines the different states that can be used when navigating
 * within a single-page application to control how the browser history
 * is managed during navigation operations.
 *
 * @package PhpSPA\Core\Helper\Enums
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.1.0
 * @enum string
 *
 * @case PUSH    Indicates a new entry should be pushed onto the navigation stack.
 * @case REPLACE Indicates the current entry should be replaced in the navigation stack.
 */
enum NavigateState: string
{
    /**
     * Represents the 'push' navigation state, typically used to indicate
     * that a new entry should be added to the navigation history stack.
     */
    case PUSH = 'push';
    /**
     * Represents the navigation state where the current history entry is replaced.
     * Use this state when you want to navigate without adding a new entry to the browser's history stack.
     */
    case REPLACE = 'replace';
}
