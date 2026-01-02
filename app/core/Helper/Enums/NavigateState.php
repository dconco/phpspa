<?php

namespace PhpSPA\Core\Helper\Enums;

/**
 * Represents the navigation state for client-side routing.
 *
 * This enum defines the different states that can be used when navigating
 * within a single-page application to control how the browser history
 * is managed during navigation operations.
 *
 * @author dconco <me@dconco.tech>
 * @copyright 2026 Dave Conco
 * @license MIT
 */
enum NavigateState: string {
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
