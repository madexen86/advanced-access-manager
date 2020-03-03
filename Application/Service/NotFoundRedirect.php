<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * 404 redirect service
 *
 * @since 6.4.0 Refactored to use 404 object instead of AAM config
 * @since 6.0.0 Initial implementation of the service
 *
 * @package AAM
 * @version 6.4.0
 */
class AAM_Service_NotFoundRedirect
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.404-redirect.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_404Redirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('404 Redirect', AAM_KEY),
                    'description' => __('Manage frontend 404 (Not Found) redirect for any group of users or individual user.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 40);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize the service hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function initializeHooks()
    {
        add_action('wp', array($this, 'wp'));
    }

    /**
     * Main frontend access control hook
     *
     * @return void
     *
     * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/64
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @global WP_Post $post
     * @version 6.4.0
     */
    public function wp()
    {
        global $wp_query;

        if ($wp_query->is_404) { // Handle 404 redirect
            $options = AAM::getUser()->getObject(
                AAM_Core_Object_NotFoundRedirect::OBJECT_TYPE
            )->getOption();

            if (isset($options['404.redirect.type'])) {
                $type = $options['404.redirect.type'];
            } else {
                $type = 'default';
            }

            if ($type !== 'default') {
                AAM_Core_Redirect::execute(
                    $type,
                    array($type =>  $options["404.redirect.{$type}"])
                );
            }
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_NotFoundRedirect::bootstrap();
}