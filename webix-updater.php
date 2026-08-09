<?php
class WP_GitHub_Updater_For_Webixfeed {
    public function __construct()
    {
        add_action( 'http_request_args', array($this,'http_request_args_filter'), 10, 2);
        add_filter( 'pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update'));
        add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 3 );
        //add_filter( 'upgrader_source_selection', array($this, 'upgrader_webix_source_selection_filter'), 1, 3);

    }
  /**
     * Update plugin from github
     */
    function check_for_plugin_update($transient) {
        
        $plugin_slug = plugin_basename(PLUGIN_DIRECTORY);
       
        $plugin_data = get_plugin_data(PLUGIN_DIRECTORY);
      
        $github_url = GITHUB_URL;

        $request_args = array( 'timeout' => 10 );
        if ( defined( 'GITHUB_TOKEN' ) && GITHUB_TOKEN !== '' ) {
            $request_args['headers'] = array(
                'Authorization' => 'token ' . GITHUB_TOKEN,
            );
        }
        $request = wp_remote_get($github_url, $request_args);
        if (is_wp_error($request)) {
            return $transient;
        }

        /*
         * HÆRDNING (3.1.37): GitHub returnerer kun et ARRAY af releases ved succes.
         * Ved fejl (404 Not Found ved privat repo uden token, 401 Bad credentials,
         * rate limit m.m.) returneres et JSON-OBJEKT — og isset($obj[0]) er en
         * fatal error i PHP 8 ("Cannot use object of type stdClass as array").
         * Derfor: tjek HTTP-status OG at svaret er et non-tomt array af objekter,
         * før der røres ved $releases[0].
         */
        if (200 !== (int) wp_remote_retrieve_response_code($request)) {
            return $transient;
        }
        $releases = json_decode(wp_remote_retrieve_body($request));
        if (!is_array($releases)
            || empty($releases)
            || !is_object($releases[0])
            || empty($releases[0]->tag_name)) {
            return $transient;
        }
        $request = $releases;
        if (version_compare($plugin_data['Version'], $request[0]->tag_name, '<')) {
            if (!isset($transient) || !is_object($transient)) {
                $transient = (object) array();
            }
            if (!isset($transient->response) || !is_array($transient->response)) {
                $transient->response = array();
            }

            $transient->response[$plugin_slug] = (object) array(
                'slug' => PLUGIN_DIRECTORY,
                'plugin' => PLUGIN_SLUG,
                'new_version' => $request[0]->tag_name,
                'url' => $request[0]->html_url,
                'package' => $request[0]->zipball_url,
            );
            // NB: zipball'en downloades IKKE her længere — WordPress henter selv
            // pakken ved opdatering, og http_request_args_filter tilføjer auth-headeren.
        }

        // Save the update transient for 24 hours
        set_transient('my_plugin_update_transient', time(), 24 * 60 * 60);
        return $transient;
    }

    function http_request_args_filter($args, $url) {
        if (strpos($url, 'https://api.github.com/') !== false
            && defined('GITHUB_TOKEN') && GITHUB_TOKEN !== '') {
            $args['headers']['Authorization'] = 'token ' . GITHUB_TOKEN;
        }

        return $args;
    }

    public function upgrader_post_install( $true, $hook_extra, $result ) {

        global $wp_filesystem;
        
        // Move & Activate
        $proper_destination = WP_PLUGIN_DIR.'/'.PROPER_FOLDER_NAME;
        $wp_filesystem->move( $result['destination'], $proper_destination );
        $result['destination'] = $proper_destination;
        $activate = activate_plugin(plugin_basename(PLUGIN_DIRECTORY));
        
        // Output the update message
        $fail  = __( 'The plugin has been updated, but could not be reactivated. Please reactivate it manually.', 'github_plugin_updater' );
        $success = __( 'Plugin reactivated successfully.', 'github_plugin_updater' );
        echo is_wp_error( $activate ) ? $fail : $success;
        return $result;
        
        }

    function upgrader_webix_source_selection_filter($source, $remote_source, $upgrader) {
        // Check if the plugin being installed is ours
        if (!$this->is_plugin_update($upgrader->skin->plugin_info)) {
            return $source;
        }
        if (!is_object($GLOBALS['wp_filesystem'])) {
            return $source;
        }

        // Do not modify the directory name
        return $source;
    }

    function is_plugin_update($plugin_info) {
        if (!isset($plugin_info['destination']))
            return false;
        // Get the directory name of the plugin being installed
        $directory = dirname($plugin_info['destination']);

        // Check if it matches the directory of our own plugin
        return $directory === dirname(PLUGIN_DIRECTORY);
    }
}
