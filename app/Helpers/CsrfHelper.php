<?php

/**
 * CSRF Helper Functions
 * 
 * Global helper functions for CSRF protection in views
 */

if (!function_exists('csrf_field')) {
    /**
     * Generate HTML for CSRF token hidden field
     *
     * @return string HTML input field
     */
    function csrf_field(): string
    {
        return \Core\Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token value
     *
     * @return string The CSRF token
     */
    function csrf_token(): string
    {
        return \Core\Csrf::getToken();
    }
}

