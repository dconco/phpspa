<?php

namespace PhpSPA\Interfaces;

use PhpSPA\Component;

/**
 * Core PhpSPA application contract
 *
 * This interface defines the essential contract for PhpSPA applications,
 * including methods for initialization, configuration, and core functionality
 * such as routing, CORS handling, and component management.
 *
 * @author dconco <concodave@gmail.com>
 * @copyright 2025 Dave Conco
 * @license MIT
 * @since v1.0.0
 * @see https://phpspa.vercel.app/core-concepts
 */
interface ApplicationContract {
    /**
     * Sets the target ID for the application.
     *
     * @param string $targetID The target ID to be set.
     * @return self
     * @see https://phpspa.vercel.app/layout/#setting-the-default-target-id
     */
    public function defaultTargetID (string $targetID): self;


    /**
     * Sets the default behavior to case sensitive.
     *
     * Implementing this method should configure the system or component
     * to treat relevant operations (such as string comparisons or lookups)
     * as case sensitive by default.
     *
     * @return self
     * @see https://phpspa.vercel.app/routing/component-configuration/#global-case-sensitivity
     */
    public function defaultToCaseSensitive (): self;


    /**
     * Configure HTML compression manually
     *
     * @param int $level Compression level (0=none, 1=auto, 2=basic, 3=aggressive, 4=extreme)
     * @param bool $gzip Enable gzip compression
     * @return self
     * @see https://phpspa.vercel.app/performance/html-compression
     */
    public function compression (int $level, bool $gzip = true): self;


    /**
     * Set cache duration for CSS/JS assets
     *
     * @param int $hours Number of hours to cache assets (0 for session-only) Default is 24 hours
     * @return self
     * @see https://phpspa.vercel.app/performance/assets-caching
     */
    public function assetCacheHours (int $hours): self;


    /**
     * Set compression based on environment
     *
     * @param string $environment Environment: 'development', 'staging', 'production'
     * @return self
     * @see https://phpspa.vercel.app/performance/html-compression/#environment-based-configuration-recommended
     */
    public function compressionEnvironment (string $environment): self;


    /**
     * Add a global script to the application
     *
     * This script will be executed on every component render throughout the application.
     * Scripts are added to the global scripts array and will be rendered alongside
     * component-specific scripts.
     *
     * @param callable $script The callable that returns the JavaScript code
     * @param string|null $name Optional name for the script asset
     * @return self
     * @see https://phpspa.vercel.app/performance/managing-styles-and-scripts
     */
    public function script (callable $script, ?string $name = null): self;


    /**
     * Add a global stylesheet to the application
     *
     * This stylesheet will be included on every component render throughout the application.
     * Stylesheets are added to the global stylesheets array and will be rendered alongside
     * component-specific styles.
     *
     * @param callable $style The callable that returns the CSS code
     * @param string|null $name Optional name for the stylesheet asset
     * @return self
     * @see https://phpspa.vercel.app/performance/managing-styles-and-scripts
     */
    public function styleSheet (callable $style, ?string $name = null): self;


    /**
     * Configure CORS (Cross-Origin Resource Sharing) settings for the application.
     *
     * Loads default CORS configuration from the config file and optionally merges
     * custom settings provided via the data parameter. Automatically removes
     * duplicate values from array-type configuration options.
     *
     * @param array $data Optional custom CORS configuration to merge with defaults.
     *                    Can include keys like:
     *                    - 'allow_origins': array of allowed origin URLs
     *                    - 'allow_methods': array of allowed HTTP methods
     *                    - 'allow_headers': array of allowed request headers
     *                    - 'allow_credentials': boolean for credential support
     *                    - 'max_age': integer for preflight cache duration
     * 
     * @var array{
     *   'allow_origins': array<string>,
     *   'allow_methods': array<string>,
     *   'allow_headers': array<string>,
     *   'allow_credentials': bool,
     *   'max_age': int,
     * } $data
     *
     * @return self Returns the current instance for method chaining
     * @see https://phpspa.vercel.app/security/cors
     */
    public function cors (array $data = []): self;


    /**
     * Attaches a component to the current object.
     *
     * @param Component $component The component instance to attach.
     * @return self
     */
    public function attach (Component $component): self;


    /**
     * Detaches the specified component from the current context.
     *
     * @param Component $component The component instance to be detached.
     * @return self
     */
    public function detach (Component $component): self;


    /**
     * Runs the application.
     *
     * This method is responsible for executing the main logic of the application,
     * including routing, rendering components, and managing the application lifecycle.
     *
     * @return void
     */
    public function run (): void;
}
