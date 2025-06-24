<?php 
namespace phpSPA\Core\Helper\Enums;

/**
 * Represents the navigation state for client-side routing.
 *
 * @enum string
 * @package phpSPA\Core\Helper\Enums
 *
 * @case PUSH    Indicates a new entry should be pushed onto the navigation stack.
 * @case REPLACE Indicates the current entry should be replaced in the navigation stack.
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