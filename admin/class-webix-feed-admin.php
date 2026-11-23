<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://webixaps.com/
 * @since      1.0.0
 *
 * @package    Webix_Feed
 * @subpackage Webix_Feed/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Webix_Feed
 * @subpackage Webix_Feed/admin
 * @author     Webix Aps <info@webixaps.com>
 */
class Webix_Feed_Admin
{
    private const WEBIX_FEED_CAMPAIGN          = "_webix_feed_campaign";
    private const WEBIX_FEED_CAMPAIGN_CATEGORY = "_webix_feed_campaign_category";
    private const WEBIX_FEED_CAMPAIGN_COUNTRY  = "_webix_campaign_country";
    private const WEBIX_FEED_CAMPAIGN_STATUS   = "_webix_feed_campaign_status";
    private const WEBIX_FEED_FILTER_STATUS     = "_webix_feed_filter_status";

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     *
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('do_meta_boxes', array($this, 'create_metaboxes'), 10, 2);
        add_action('save_post', array($this, 'save_webix_feed_metabox'), 10, 2);
        add_shortcode('webix-comparison-feed', array($this, 'doWebixComparisonFeedShortcode'));
        add_shortcode('webix-feed-by-epc', array($this, 'doWebixComparisonFeedByEpcShortcode'));
        add_shortcode('webix-feed-by-trend', array($this, 'doWebixComparisonFeedByTrendShortcode'));

        add_action('admin_menu', array($this,'webix_links'));
        add_action('admin_enqueue_scripts', array ($this, 'enqueue_datatables_script'));
        add_action('admin_enqueue_scripts', array( $this, 'localize_webix_links_script'));
        add_action('wp_ajax_get_webix_posts_data', array( $this, 'get_webix_posts_data_callback'));
        add_action('wp_ajax_nopriv_get_webix_posts_data',array( $this,  'get_webix_posts_data_callback')); // For non-logged in users

    }


          

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Webix_Feed_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Webix_Feed_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/webix-feed-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Webix_Feed_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Webix_Feed_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/webix-feed-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

          // Enqueue DataTables library and custom script
    public function enqueue_datatables_script() {
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/v/dt/dt-1.13.8/datatables.min.js', array('jquery'), '1.10.24', true);
        wp_enqueue_script('webix-links-script', plugin_dir_url(__FILE__) . '/js/webix-links-script.js', array('jquery', 'datatables'), '1.0.2', true);

        // Add styles for DataTables
        wp_enqueue_style('datatables-style', 'https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css');
    }


                // Localize script to pass data to JavaScript
    public function localize_webix_links_script() {
        $args = array(
            'ajax_url' => admin_url('admin-ajax.php'),
        );
        wp_localize_script('webix-links-script', 'webix_links_script_vars', $args);
    }

    // AJAX handler to retrieve posts data
    public function get_webix_posts_data_callback() {
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'posts_per_page' => -1,
        ));

        $data = array();

        foreach ($posts as $post) {
            $data[] = array(
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_type' => $post->post_type,
                'webix_country' => get_post_meta($post->ID, '_webix_country', true),
                'webix_category' => get_post_meta($post->ID, '_webix_feed_category', true),
                'webix_subcategory' => get_post_meta($post->ID, '_webix_feed_subcategory', true),
            );
        }

        wp_send_json($data);
    }

    public function create_metaboxes($page, $context)
    {

        $screens = array('post', 'page');

        foreach ($screens as $screen) {
            add_meta_box('webix-feed', 'Webix Feed Generator', array($this, 'webix_feed_meta'), $screen, 'normal', 'high');
        }
    }

    function webix_feed_meta($post)
    {

        // Use nonce for verification
        wp_nonce_field('webix_meta_box_nonce', 'meta_webix_box_nonce');

        $post_id = $post->ID;
        $countries = $this->get_countries();
        $feed_categories = $this->get_feed_categories();
        $country_field = get_post_meta($post_id, '_webix_country', false);
        $feed_category_field = get_post_meta($post_id, '_webix_feed_category', false);
        $feed_subcategory_field = get_post_meta($post_id, '_webix_feed_subcategory', false);
        $feed_filter_status = get_post_meta($post_id, self::WEBIX_FEED_FILTER_STATUS, false);

        echo '<div class="webix-feed-meta-wrapper">';
        echo '<div class="webix-feed-header"></div>';
        echo '<div class="tab">';
        echo '<a class="tablinks active" data-tab="general-settings">General Settings</a>';
        echo '<a class="tablinks" data-tab="feed-layout">Feed Layout</a>';
        echo '<a class="tablinks" data-tab="single-product">Single Product</a>';
        echo '<a class="tablinks" data-tab="extra-shortcodes">Extra Shortcodes</a>';
        echo '</div>';

        echo '<div id="general-settings" class="tabcontent active">';
        echo '<p>To generate your comparison feed please choose your target country audience and choose the data category that you would like to display.</p>';
        echo '<p>Paste this shortcode <code> [webix-comparison-feed] </code> to content </p>';

        echo '<div class="admin-webix-feed-row">';
        echo '<div class="admin-webix-feed-label">';
        echo '<label> Country </label>';
        echo '<small> Choose your target country audience for this page. Denmark for Default</small>';
        echo '</div>';
        echo '<div class="admin-webix-feed-form-item">';
        echo '<select name="webix_country" class="find_subcategories" id="webix-country">';

        foreach ($countries as $country) {
            if (!empty($country_field) && $country_field[0] == $country->id) {
                echo '<option selected value="' . $country->id . '"> ' . $country->country_code . '-' . $country->country_name . '</option>';
            } else {
                echo '<option value="' . $country->id . '"> ' . $country->country_code . '-' . $country->country_name . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        echo '<div class="admin-webix-feed-row">';
        echo '<div class="admin-webix-feed-label">';
        echo '<label> Feed Category </label>';
        echo '</div>';
        echo '<div class="admin-webix-feed-form-item">';
        echo '<select name="webix_feed_category" class="find_subcategories" id="webix-feed-category">';
        echo '<option value=""> -- Select Feed Category -- </option>';
        foreach ($feed_categories as $feed_category) {
            if (!empty($feed_category_field) && $feed_category_field[0] == $feed_category->id) {
                echo '<option selected value="' . $feed_category->id . '"> ' . $feed_category->feed_category_name . '</option>';
            } else {
                echo '<option value="' . $feed_category->id . '"> ' . $feed_category->feed_category_name . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';

        echo '<div class="admin-webix-feed-row">';
        echo '<div class="admin-webix-feed-label">';
        echo '<label> Feed Subcategory </label>';
        echo '</div>';
        echo '<div class="admin-webix-feed-form-item" >';

        if (true||!empty($feed_subcategory_field)) {
            $feed_subcategories = $this->find_subcategories($country_field[0], $feed_category_field[0]);
            echo '<select name="webix_feed_subcategory" id="webix-feed-subcategory">';
            foreach ($feed_subcategories as $feed_subcategory) {
                if ($feed_subcategory_field[0] == $feed_subcategory->id) {
                    echo '<option selected value="' . $feed_subcategory->id . '"> ' . $feed_subcategory->feed_subcategory_name . '</option>';
                } else {
                    echo '<option value="' . $feed_subcategory->id . '"> ' . $feed_subcategory->feed_subcategory_name . '</option>';
                }
            }
            echo '</select>';
        } else {
            $feed_subcategories = $this->find_subcategories($country_field[0], $feed_category_field[0]);
            echo '<select name="webix_feed_subcategory" id="webix-feed-subcategory">';
            foreach ($feed_subcategories as $feed_subcategory) {
                
                echo '<option value="' . $feed_subcategory->id . '"> ' . $feed_subcategory->feed_subcategory_name . '</option>';
                
            }
            echo '</select>';
        }
        echo '</div>';
        echo '</div>';

        // Render checkbox if filter is shown or not.
        echo '<input type="checkbox" id="filter-show" style="display: inline-block;width: auto;" value="1" name="' . self::WEBIX_FEED_FILTER_STATUS . '"' . ($feed_filter_status[0] == 1 ? 'checked' : '') . '>';
        echo '<label for="filter-show">Show Filter</label>';

        echo '</div>';

        echo '<div id="feed-layout" class="tabcontent">';
        echo '<p>Feed Layout</p>';
        echo '</div>';

        echo $this->getSingleProductHtml($post_id, $countries, $feed_categories);
        echo $this->getExtraShortcodesHtml($post_id, $countries, $feed_categories);

        echo '</div>';
    }

    function save_webix_feed_metabox($post_id)
    {
        // Bail if we're doing an auto save
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // if our current user can't edit this post, bail
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // if our nonce isn't there, or we can't verify it, bail
        if (!isset($_POST['meta_webix_box_nonce']) || !wp_verify_nonce(
                $_POST['meta_webix_box_nonce'],
                'webix_meta_box_nonce'
            )) {
            return;
        }

        if (isset($_POST['webix_country'])) {

            $country_field = get_post_meta($post_id, '_webix_country', false);
            if ($country_field) {
                update_post_meta($post_id, '_webix_country', $_POST['webix_country']);
            } else {
                add_post_meta($post_id, '_webix_country', $_POST['webix_country'], true);
            }
        }

        if (isset($_POST['webix_feed_category'])) {

            $feed_category_field = get_post_meta($post_id, '_webix_feed_category', false);
            if ($feed_category_field) {
                update_post_meta($post_id, '_webix_feed_category', $_POST['webix_feed_category']);
            } else {
                add_post_meta($post_id, '_webix_feed_category', $_POST['webix_feed_category'], true);
            }
        }

        if (isset($_POST['webix_feed_subcategory'])) {
            $feed_subcategory_field = get_post_meta($post_id, '_webix_feed_subcategory', false);
            if ($feed_subcategory_field) {
                update_post_meta($post_id, '_webix_feed_subcategory', $_POST['webix_feed_subcategory']);
            } else {
                add_post_meta($post_id, '_webix_feed_subcategory', $_POST['webix_feed_subcategory'], true);
            }
        }

        $_POST["_webix_feed_filter_status"] = isset($_POST["_webix_feed_filter_status"]) ? $_POST["_webix_feed_filter_status"] : 0;

        foreach ($_POST as $key => $value) {
            if (in_array(
                $key,
                [
                    self::WEBIX_FEED_CAMPAIGN_COUNTRY,
                    self::WEBIX_FEED_CAMPAIGN_CATEGORY,
                    self::WEBIX_FEED_CAMPAIGN,
                    self::WEBIX_FEED_CAMPAIGN_STATUS,
                    self::WEBIX_FEED_FILTER_STATUS
                ]
            )) {
                $meta = get_post_meta($post_id, $key, false);
                if ($meta) {
                    update_post_meta($post_id, $key, $value);
                } else {
                    add_post_meta($post_id, $key, $value, true);
                }
            }
        }
    }

    /**
     * Henter feed-HTML fra ai.webixaps.com med transient-cache og stale-fallback.
     *
     * - Cache-nøglen omfatter hele URL'en (inkl. is_mobile/device_type), så
     *   desktop/mobil/tablet caches hver for sig.
     * - TTL styres af filteret 'webix_feed_cache_ttl' (default 10 min; 0 = slå cache fra).
     * - Ved API-fejl (WP_Error, ikke-200, tom body) serveres senest kendte HTML
     *   (op til 48 timer gammel) i stedet for en fejltekst, så søgemaskiner og
     *   besøgende aldrig ser rå fejlbeskeder.
     * - Al HTML (frisk, cachet og stale) sendes gennem strip_approval_rating(),
     *   så ændringen virker straks — også for allerede cachede varianter.
     */
    private function fetch_feed_html($endpoint, array $parameters)
    {
        return self::strip_approval_rating($this->fetch_feed_html_raw($endpoint, $parameters));
    }

    /**
     * Fjerner feltet "Godkendelse" fra feed-HTML'en (markedsføringslovens § 11 d,
     * gældende fra 20.11.2026: ingen fremhævning af, hvor let/hurtigt man får kredit).
     *
     * Dækker de tre varianter, Laravel leverer:
     *  1) Kort (desktop+mobil):  <li ...><p class="text-wrapper">Godkendelse: X</p></li>
     *  2) Tabel, mobil:          <div class="tm-row-inner"> label "Godkendelse" + værdi </div>
     *  3) Tabel, desktop:        kolonnen med <th>Godkendelse</th> + tilsvarende <td> i hver række
     *
     * Rammer kun den danske etiket "Godkendelse". Alt andet er urørt. Fejler et
     * regex (fx backtrack-grænse), returneres HTML'en for det trin uændret.
     */
    public static function strip_approval_rating($html)
    {
        if (!is_string($html) || false === stripos($html, 'Godkendelse')) {
            return $html;
        }

        $patterns = array(
            '~<li\b[^>]*>\s*<p\b[^>]*>\s*Godkendelse\s*:[^<]*</p>\s*</li>~iu',
            '~[ \t]*<div\b[^>]*\bclass="[^"]*\btm-row-inner\b[^"]*"[^>]*>\s*<div\b[^>]*\bclass="[^"]*\btm-label\b[^"]*"[^>]*>\s*Godkendelse\s*</div>\s*<div\b[^>]*\bclass="[^"]*\btm-value\b[^"]*"[^>]*>[^<]*</div>\s*</div>(?:\r?\n)?~iu',
        );
        foreach ($patterns as $pattern) {
            $out = preg_replace($pattern, '', $html);
            if (null !== $out) {
                $html = $out;
            }
        }

        $out = preg_replace_callback('~<table\b[^>]*>.*?</table>~is', array(__CLASS__, 'strip_approval_column'), $html);
        if (null !== $out) {
            $html = $out;
        }

        return $html;
    }

    /**
     * Fjerner "Godkendelse"-kolonnen fra én <table>. Kun rækker med præcis samme
     * antal celler som overskriftsrækken ændres (colspan-rækker o.l. røres ikke).
     */
    private static function strip_approval_column($m)
    {
        $table    = $m[0];
        $cell_re  = '~<(t[hd])\b[^>]*>.*?</\1>~is';
        $col      = -1;
        $n_header = 0;

        if (!preg_match_all('~<tr\b[^>]*>(.*?)</tr>~is', $table, $rows)) {
            return $table;
        }
        foreach ($rows[1] as $row) {
            if (preg_match_all($cell_re, $row, $cells) && in_array('th', array_map('strtolower', $cells[1]), true)) {
                foreach ($cells[0] as $i => $cell) {
                    if ('godkendelse' === strtolower(trim(html_entity_decode(strip_tags($cell), ENT_QUOTES, 'UTF-8')))) {
                        $col      = $i;
                        $n_header = count($cells[0]);
                        break 2;
                    }
                }
            }
        }
        if ($col < 0) {
            return $table;
        }

        $out = preg_replace_callback('~(<tr\b[^>]*>)(.*?)(</tr>)~is', function ($r) use ($cell_re, $col, $n_header) {
            if (!preg_match_all($cell_re, $r[2], $cells, PREG_OFFSET_CAPTURE) || count($cells[0]) !== $n_header) {
                return $r[0];
            }
            $cell = $cells[0][$col];
            return $r[1] . substr_replace($r[2], '', $cell[1], strlen($cell[0])) . $r[3];
        }, $table);

        return (null === $out) ? $table : $out;
    }

    private function fetch_feed_html_raw($endpoint, array $parameters)
    {
        $url       = $endpoint . '?' . http_build_query($parameters);
        $cache_ttl = (int) apply_filters('webix_feed_cache_ttl', 10 * MINUTE_IN_SECONDS);
        $hash      = md5($url);
        $cache_key = 'webix_feed_' . $hash;
        $stale_key = 'webix_feed_stale_' . $hash;

        if ($cache_ttl > 0) {
            $cached = get_transient($cache_key);
            if (false !== $cached && '' !== $cached) {
                return $cached;
            }
        }

        $response = wp_remote_get($url, array('timeout' => 8));

        $body = '';
        if (!is_wp_error($response) && 200 === (int) wp_remote_retrieve_response_code($response)) {
            $body = trim((string) wp_remote_retrieve_body($response));
        }

        if ('' !== $body) {
            if ($cache_ttl > 0) {
                set_transient($cache_key, $body, $cache_ttl);
                set_transient($stale_key, $body, 2 * DAY_IN_SECONDS);
            }
            return $body;
        }

        // API'et svarede ikke brugbart — servér senest kendte HTML frem for fejltekst.
        $stale = get_transient($stale_key);
        if (false !== $stale && '' !== $stale) {
            return $stale;
        }

        return '<!-- webix-feed: feed midlertidigt utilgaengeligt -->';
    }

    function doWebixComparisonFeedShortcode($atts)
    {
        global $post;

        $post_id = $post->ID;
        $is_mobile = wp_is_mobile();
        $device_type = 'desktop';
    
        // Check if the user is on mobile, and if so, determine the platform
        if ($is_mobile) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
                $device_type = 'android';
            } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false) {
                $device_type = 'iphone';
            } else {
                $device_type = 'mobile'; // fallback for other mobile devices
            }
        }
    
        $parameters = [
            "site_url"  => get_bloginfo('url'),
            "is_mobile" => $is_mobile,
            "device_type" => $device_type
        ];

        $feed_campaign_status = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_STATUS, false);
        if ($feed_campaign_status && $feed_campaign_status[0] == 'show') {
            $feed_campaign_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN, false);
            if (empty($feed_campaign_field)) {
                return "<div>Please configure your Webix Feed Generator.</div>";
            }
            $parameters["feed_campaign_id"] = $feed_campaign_field[0];
        } else {
            $feed_subcategory_field = get_post_meta($post_id, '_webix_feed_subcategory', false);
            if (empty($feed_subcategory_field)) {
                return "<div>Please configure your Webix Feed Generator.</div>";
            }
            $parameters["feed_subcategory_id"] = $feed_subcategory_field[0];
        }

        $feed_filter_status = get_post_meta($post_id, self::WEBIX_FEED_FILTER_STATUS, false);
        $feedBody = '';
        if ($feed_filter_status && $feed_filter_status[0] == 1) {
            $feed_filter_country = get_post_meta($post_id, '_webix_country', false);
            $labels = $this->get_filter_labels($feed_filter_country);
            $feedBody = $this->getFilterHtml($labels);
        }
        $feedBody .= $this->fetch_feed_html('https://ai.webixaps.com/get_campaigns', $parameters);

        return $feedBody;
    }

    function doWebixComparisonFeedByEpcShortcode($atts)
    {
        global $post;

        $post_id = $post->ID;
        $is_mobile = wp_is_mobile();
        $device_type = 'desktop';
        $country_field = get_post_meta($post_id, '_webix_country', false);
        $feed_category_field = get_post_meta($post_id, '_webix_feed_category', false);
    
        // Check if the user is on mobile, and if so, determine the platform
        if ($is_mobile) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
                $device_type = 'android';
            } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false) {
                $device_type = 'iphone';
            } else {
                $device_type = 'mobile'; // fallback for other mobile devices
            }
        }

        $atts = shortcode_atts(array(
			'limit' => '3', // Default CSV file name
			'layout' => 'default', // Default CSV file name
			'headercolor' => '#FFFFFF' // Default CSV file name
		), $atts);

    
        $parameters = [
            "site_url"  => get_bloginfo('url'),
            "is_mobile" => $is_mobile,
            "device_type" => $device_type,
            "country" => $country_field[0],
            "feed_category" => $feed_category_field[0],
            "by" => "epc",
            "limit" => $atts['limit'],
            "layout" => $atts['layout'],
            "headercolor" => $atts['headercolor']
        ];

        $feed_campaign_status = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_STATUS, false);
        if ($feed_campaign_status && $feed_campaign_status[0] == 'show') {
            $feed_campaign_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN, false);
            if (empty($feed_campaign_field)) {
                return "<div>Please configure your Webix Feed Generator.</div>";
            }
            $parameters["feed_campaign_id"] = $feed_campaign_field[0];
        } else {
            $feed_subcategory_field = get_post_meta($post_id, '_webix_feed_subcategory', false);
            if (empty($feed_subcategory_field)) {
                return "<div>Please configure your Webix Feed Generator.</div>";
            }
            $parameters["feed_subcategory_id"] = $feed_subcategory_field[0];
        }

        $feed_filter_status = get_post_meta($post_id, self::WEBIX_FEED_FILTER_STATUS, false);
        $feedBody = '';
        if ($feed_filter_status && $feed_filter_status[0] == 1) {
            $feed_filter_country = get_post_meta($post_id, '_webix_country', false);
            $labels = $this->get_filter_labels($feed_filter_country);
            $feedBody = $this->getFilterHtml($labels);
        }
        $feedBody .= $this->fetch_feed_html('https://ai.webixaps.com/get_campaigns_by', $parameters);

        return $feedBody;
    }

    function doWebixComparisonFeedByTrendShortcode($atts)
    {
        global $post;

        $post_id = $post->ID;
        $is_mobile = wp_is_mobile();
        $device_type = 'desktop';
        $country_field = get_post_meta($post_id, '_webix_country', false);
        $feed_category_field = get_post_meta($post_id, '_webix_feed_category', false);
    
        // Check if the user is on mobile, and if so, determine the platform
        if ($is_mobile) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false) {
                $device_type = 'android';
            } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false) {
                $device_type = 'iphone';
            } else {
                $device_type = 'mobile'; // fallback for other mobile devices
            }
        }

        $atts = shortcode_atts(array(
			'limit' => '3', // Default CSV file name
			'layout' => 'default', // Default CSV file name
			'headercolor' => '#FFFFFF' // Default CSV file name
		), $atts);

    
        $parameters = [
            "site_url"  => get_bloginfo('url'),
            "is_mobile" => $is_mobile,
            "device_type" => $device_type,
            "country" => $country_field[0],
            "feed_category" => $feed_category_field[0],
            "by" => "trend",
            "limit" => $atts['limit'],
            "layout" => $atts['layout'],
            "headercolor" => $atts['headercolor']
        ];

        $feed_campaign_status = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_STATUS, false);
        if ($feed_campaign_status && $feed_campaign_status[0] == 'show') {
            $feed_campaign_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN, false);
            if (empty($feed_campaign_field)) {
                return "<div>Please configure your Webix Feed Generator.</div>";
            }
            $parameters["feed_campaign_id"] = $feed_campaign_field[0];
        } else {
            $feed_subcategory_field = get_post_meta($post_id, '_webix_feed_subcategory', false);
            if (empty($feed_subcategory_field)) {
                return "<div>Please configure your Webix Feed Generator.</div>";
            }
            $parameters["feed_subcategory_id"] = $feed_subcategory_field[0];
        }

        $feed_filter_status = get_post_meta($post_id, self::WEBIX_FEED_FILTER_STATUS, false);
        $feedBody = '';
        if ($feed_filter_status && $feed_filter_status[0] == 1) {
            $feed_filter_country = get_post_meta($post_id, '_webix_country', false);
            $labels = $this->get_filter_labels($feed_filter_country);
            $feedBody = $this->getFilterHtml($labels);
        }
        $feedBody .= $this->fetch_feed_html('https://ai.webixaps.com/get_campaigns_by', $parameters);

        return $feedBody;
    }

    private function getSingleProductHtml(
        $post_id,
        $countries,
        $feed_categories
        ) {
        $feed_country_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_COUNTRY, false);
        $feed_category_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_CATEGORY, false);
        $feed_campaign_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN, false);
        $feed_campaign_status = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_STATUS, false);

        $html = '<div id="single-product" class="tabcontent">';
        $html .= '<div class="admin-webix-feed-row">';
        $html .= '<div class="admin-webix-feed-label">';
        $html .= '<label> Show single product </label>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-form-item">';
        $html .= '<input type="radio" id="campaign-status-show" style="display: inline-block;width: auto;" name="' . self::WEBIX_FEED_CAMPAIGN_STATUS . '" value="show" ' . ($feed_campaign_status[0] == 'show' ? 'checked' : '') . '>';
        $html .= '<label for="campaign-status-show" style="display: inline-block;width: auto;margin-right: 10px;">Show</label>';
        $html .= '<input type="radio" id="campaign-status-hide" style="display: inline-block;width: auto;" name="' . self::WEBIX_FEED_CAMPAIGN_STATUS . '" value="hide" ' . ($feed_campaign_status[0] != 'show' ? 'checked' : '') . '>';
        $html .= '<label for="campaign-status-hide" style="display: inline-block;width: auto;">Hide</label>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-row">';
        $html .= '<div class="admin-webix-feed-label">';
        $html .= '<label> Country </label>';
        $html .= '<small> Choose your target country audience for this page. Denmark for Default</small>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-form-item">';
        $html .= '<select name="' . self::WEBIX_FEED_CAMPAIGN_COUNTRY . '" class="find_campaigns" id="webix-feed-campaign-country">';

        foreach ($countries as $country) {
            if (!empty($feed_country_field) && $feed_country_field[0] == $country->id) {
                $html .= '<option selected value="' . $country->id . '"> ' . $country->country_code . '-' . $country->country_name . '</option>';
            } else {
                $html .= '<option value="' . $country->id . '"> ' . $country->country_code . '-' . $country->country_name . '</option>';
            }
        }
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-row">';
        $html .= '<div class="admin-webix-feed-label">';
        $html .= '<label> Feed Category </label>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-form-item">';
        $html .= '<select name="' . self::WEBIX_FEED_CAMPAIGN_CATEGORY . '" class="find_campaigns" id="webix-feed-campaign-category">';
        $html .= '<option value=""> -- Select Feed Category -- </option>';
        foreach ($feed_categories as $feed_category) {
            if (!empty($feed_category_field) && $feed_category_field[0] == $feed_category->id) {
                $html .= '<option selected value="' . $feed_category->id . '"> ' . $feed_category->feed_category_name . '</option>';
            } else {
                $html .= '<option value="' . $feed_category->id . '"> ' . $feed_category->feed_category_name . '</option>';
            }
        }
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-row">';
        $html .= '<div class="admin-webix-feed-label">';
        $html .= '<label> Feed Product </label>';
        $html .= '</div>';
        $html .= '<div class="admin-webix-feed-form-item">';

        if (!empty($feed_campaign_field)) {
            $feed_campaigns = $this->find_campaigns($feed_country_field[0], $feed_category_field[0]);
            $html .= '<select name="' . self::WEBIX_FEED_CAMPAIGN . '" id="webix-feed-campaign">';
            foreach ($feed_campaigns as $feed_campaign) {
                if ($feed_campaign_field[0] == $feed_campaign->id) {
                    $html .= '<option selected value="' . $feed_campaign->id . '"> ' . $feed_campaign->name . '</option>';
                } else {
                    $html .= '<option value="' . $feed_campaign->id . '"> ' . $feed_campaign->name . '</option>';
                }
            }
            $html .= '</select>';
        } else {
            $html .= '<select name="' . self::WEBIX_FEED_CAMPAIGN . '" id="webix-feed-campaign">';
            $html .= '</select>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function getExtraShortcodesHtml(
        $post_id,
        $countries,
        $feed_categories
        ) {
        $feed_country_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_COUNTRY, false);
        $feed_category_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_CATEGORY, false);
        $feed_campaign_field = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN, false);
        $feed_campaign_status = get_post_meta($post_id, self::WEBIX_FEED_CAMPAIGN_STATUS, false);

        $html = '<div id="extra-shortcodes" class="tabcontent">';
        $html .= '<div class="admin-webix-feed-row">';
        $html .= '<h3>[webix-feed-by-epc]</h3>';
        $html .= '<p><strong> Optional Parameters: </strong></p>';
        $html .= '<p><strong> limit: [number of items to display], 0 will display all </strong> default: 3</p>';
        $html .= '<p><strong> layout: [table, default] </strong> default: default</p>';
        $html .= '<p><strong> headercolor: [Hex Code] </strong> default: #FFFFFF</p>';
        $html .= '<p><strong> example shortcode usage: [webix-feed-by-epc limit="5" layout="table" headercolor="#FFFFFF"] </strong></p>';
        $html .= '<hr>';
        $html .= '<h3>[webix-feed-by-trend]</h3>';
        $html .= '<p><strong> Optional Parameters: </strong></p>';
        $html .= '<p><strong> limit: [number of items to display], 0 will display all </strong> default: 3</p>';
        $html .= '<p><strong> layout: [table, default] </strong> default: default</p>';
        $html .= '<p><strong> headercolor: [Hex Code] </strong> default: #FFFFFF</p>';
        $html .= '<p><strong> example shortcode usage: [webix-feed-by-trend limit="5" layout="table" headercolor="#FFFFFF"] </strong></p>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function get_countries()
    {
        $response = wp_remote_get('https://ai.webixaps.com/get_countries');
        if (is_wp_error($response)) {
            return 'There be errors, yos!';
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            return $data;
        }

        return false;
    }

    private function get_feed_categories()
    {
        $response = wp_remote_get('https://ai.webixaps.com/get_feed_categories');
        if (is_wp_error($response)) {
            return 'There be errors, yos!';
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            return $data;
        }

        return false;
    }

    private function find_subcategories($country_id, $feed_category_id)
    {
        if(empty($feed_category_id))
            $feed_category_id=1;
        $response = wp_remote_get(
            'https://ai.webixaps.com/find_subcategories?country_id=' . $country_id . '&feed_category_id=' . $feed_category_id
        );
        if (is_wp_error($response)) {
            return 'There be errors, yos!';
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            return $data;
        }

        return false;
    }

    private function find_campaigns($country_id, $feed_category_id)
    {
        $response = wp_remote_get(
            'https://ai.webixaps.com/find_campaigns?country_id=' . $country_id . '&feed_category_id=' . $feed_category_id
        );
        if (is_wp_error($response)) {
            return 'There be errors, yos!';
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            return $data;
        }

        return false;
    }

private function getFilterHtml($labels)
{
    // Range-slider bruges ikke længere – stopper PHP-warnings + CPU-load
    return '';
}

    private function get_filter_labels($country)
    {
        $labels = [
            // Denmark
            1 => [
                'loan_amount' => 'Lånebeløb',
                'avenue' => 'Alle',
                'your_age' => 'Din alder',
                'currency' => 'Kr',
                'age' => 'år',
            ],
            // Norway
            2 => [
                'loan_amount' => 'Lånebeløp',
                'avenue' => 'Alle',
                'your_age' => 'Din alder',
                'currency' => 'Kr',
                'age' => 'år',
            ],
            // Sweden
            3 => [
                'loan_amount' => 'Lånebelopp',
                'avenue' => 'Alla',
                'your_age' => 'Din ålder',
                'currency' => 'Kr',
                'age' => 'år',
            ],
            // Spain
            6 => [
                'loan_amount' => 'Monto del préstamo',
                'avenue' => 'Todas',
                'your_age' => 'Edad',
                'currency' => 'Euros',
                'age' => 'años',
            ],

            // Mexico
            8 => [
                'loan_amount' => 'Monto del préstamo',
                'avenue'      => 'Todas',
                'your_age'    => 'Edad',
                'currency'    => 'Pesos',
                'age'         => 'años',
                'filter'      => 'max',
            ],
            // Philippines
            17 => [
                'loan_amount' => 'Loan amount',
                'avenue'      => 'All',
                'your_age'    => 'Your age',
                'currency'    => 'PHP',
                'age'         => 'age',
                'filter'      => 'max',
            ],
            // fi
            4 => [
                'loan_amount' => 'Lainasumma',
                'avenue'      => 'Kaikki',
                'your_age'    => 'Ikäsi',
                'currency'    => 'Euros',
                'age'         => 'ikä',
                'filter'      => 'min',
            ],
            // german
            5=>[
                'loan_amount' => 'Kreditsumme',
                'avenue'      => 'Alle',
                'your_age'    => 'Ihr Alter',
                'currency'    => 'euro.',
                'age'         => 'Jahre',
                'filter'      => 'min',
            ],
            18 => [
                'loan_amount' => 'Monto del préstamo',
                'avenue'      => 'Todas',
                'your_age'    => 'Edad',
                'currency'    => 'Pesos',
                'age'         => 'años',
                'filter'      => 'max',
            ],
        ];

        if (isset($country[0], $labels[$country[0]])) {
            return $labels[$country[0]];
        }

        return $labels[1];
    }

    public function webix_links() {
        add_submenu_page(
            WEBIX_DIRECTORY . 'admin/webix-feed-admin.php', // Parent menu slug
            'Webix Links',
            'Webix Links',
            'manage_options',
            'webix-links',
            array( $this, 'render_webix_links')
        );
    }

    public function render_webix_links() {
        $countries = $this->country_list();
        $feed_categories = $this->feed_category_list();
        $feed_subcategories = $this->feed_subcategory_list();
        //print_r($countries);
        if (isset($_GET['filters'])){
             
            $post_types = array('post', 'page');
            $post_type_value = isset($_GET['post_type_val']) ? sanitize_text_field($_GET['post_type_val']) : 'All';
            $country_value = isset($_GET['webix_country']) ? sanitize_text_field($_GET['webix_country']) : 'All';
            $feed_category_value = isset($_GET['webix_category']) ? sanitize_text_field($_GET['webix_category']) : 'All';
            $feed_subcategory_value = isset($_GET['webix_subcategory']) ? sanitize_text_field($_GET['webix_subcategory']) : 'All';
       
            if ($_GET['post_type_val'] != "All"){
                $post_types = (array) $post_type_value;
           
            }

            $meta_query = array();

            if ($country_value !== 'All') {
                $meta_query[] = array(
                    'key' => '_webix_country',
                    'value' => $country_value,
                );
            }
        
            if ($feed_category_value !== 'All') {
                $meta_query[] = array(
                    'key' => '_webix_feed_category',
                    'value' => $feed_category_value,
                );
            }
        
            if ($feed_subcategory_value !== 'All') {
                $meta_query[] = array(
                    'key' => '_webix_feed_subcategory',
                    'value' => $feed_subcategory_value,
                );
            }

            if (!empty($meta_query)){
                $posts = get_posts(array(
                    'post_type' => $post_types,
                    'posts_per_page' => -1,
                    'meta_query' => $meta_query
                ));
            }else{
                $posts = get_posts(array(
                    'post_type' => $post_types,
                    'posts_per_page' => -1
                ));
            }
       
        }else{
            $posts = get_posts(array(
                'post_type' => array('post', 'page'),
                'posts_per_page' => -1,
            ));
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Webix Links</h1>
            <div class="webix-links-filter-wrapper" style="margin: 20px 0;">
               <!-- Filter Form -->
               <form id="webix-links-filter-form" action="/wp-admin/admin.php?page=webix-links" method="GET">
                    <input type="hidden" name="page" value="webix-links">
                    <label for="post-type">Post Type:</label>
                    <select id="post-type" name="post_type_val">
                        <option <?php echo (isset($_GET['post_type_val']) && $_GET['post_type_val'] == "All")?"selected":""; ?> value="All">All</option>
                        <option <?php echo (isset($_GET['post_type_val']) && $_GET['post_type_val'] == "post")?"selected":""; ?> value="post">Post</option>
                        <option <?php echo (isset($_GET['post_type_val']) && $_GET['post_type_val'] == "page")?"selected":""; ?> value="page">Page</option>
                    </select>

                    <label for="country">Country:</label>
                    <select id="country" name="webix_country">
                        <option <?php echo (isset($_GET['webix_country']) && $_GET['webix_country'] == "All")?"selected":""; ?> value="All">All</option>
                        <?php foreach ($countries as $country_id => $country){ ?>
                            <option <?php echo (isset($_GET['webix_country']) && $_GET['webix_country'] == $country_id)?"selected":""; ?> value="<?php echo $country_id; ?>"> <?php echo $country ?></option>
                        <?php } ?>
                       
                        <!-- Add more countries as needed -->
                    </select>

                    <label for="feed-category">Feed Category:</label>
                    <select id="feed-category" name="webix_category">
                        <option <?php echo (isset($_GET['webix_category']) && $_GET['webix_category'] == "All")?"selected":""; ?> value="All">All</option>
                        <?php foreach ($feed_categories as $feed_category_id => $feed_category){ ?>
                            <option <?php echo (isset($_GET['webix_category']) && $_GET['webix_category'] == $feed_category_id)?"selected":""; ?> value="<?php echo $feed_category_id; ?>"> <?php echo $feed_category ?></option>
                        <?php } ?>
                        <!-- Add more feed categories as needed -->
                    </select>

                    <label for="feed-subcategory">Feed Subcategory:</label>
                    <select id="feed-subcategory" name="webix_subcategory">
                    <option <?php echo (isset($_GET['webix_subcategory']) && $_GET['webix_subcategory'] == "All")?"selected":""; ?> value="All">All</option>
                        <?php foreach ($feed_subcategories as $feed_subcategory_id => $feed_subcategory){ ?>
                            <option <?php echo (isset($_GET['webix_subcategory']) && $_GET['webix_subcategory'] == $feed_subcategory_id)?"selected":""; ?> value="<?php echo $feed_subcategory_id; ?>"> <?php echo $feed_subcategory ?></option>
                        <?php } ?>
                        <!-- Add more feed subcategories as needed -->
                    </select>

                    <button type="submit" name="filters" value="filter-on">Apply Filters</button>
                </form>
            </div>
            <table id="webix-links-table" class="display">
                <thead>
                    <tr style="text-align: left;">
                        <th >ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Country</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $post) { ?>
                    <tr>
                        <td> <?php echo $post->ID; ?> </td>
                        <td> <a target="_blank" href="<?php echo esc_url(get_permalink($post->ID)); ?>"><?php echo $post->post_title; ?></a></td>
                        <td> <?php echo $post->post_type; ?></td>
                        <td> <?php echo $countries[get_post_meta($post->ID, '_webix_country', true)]; ?></td>
                        <td> <?php echo $feed_categories[get_post_meta($post->ID, '_webix_feed_category', true)]; ?></td>
                        <td> <?php echo $feed_subcategories[get_post_meta($post->ID, '_webix_feed_subcategory', true)]; ?></td>
                    </tr>

                <?php  } ?>
                </tbody>
            </table>
        </div>
        <?php
    }


    private function country_list(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://ai.webixaps.com/get_countries',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Cookie: XSRF-TOKEN=eyJpdiI6Ik14QVJ0WTc0VDRnVHVUdXBGY0N1aHc9PSIsInZhbHVlIjoiNW1FbFdXc2E0cUNPbjlVdndWUEpFRlV0SDRDVis5dlhUT1FvT3BKclhmL3UwSTlUTGJnV2NXUWh4NnpBUE81SFZTVThNb0x6SGVhS0FvME1sbUZYMW82YnRDaEdOdFNibkpmSWxBbmorZGhaVWFMMlEzakpvdm40WkxBcFFYVkEiLCJtYWMiOiIyZmJiYzNiMTJhY2IzN2YwNzBkN2VlMWVkOWFjMWFiMjEwNTljN2E0YmYzNDQyZmQ0ZWE5ZjdmYmQzZTI2NzQwIn0%3D; webixaps_session=eyJpdiI6ImdONjYyUk5wWFZMeFJudFlWV0VQN1E9PSIsInZhbHVlIjoiRUpyWWI5MnpuMGE2N05OYWlFMnc3NmJ3N1JsT0RyeG1wRCtqUi9LVXhzVUt5SE9KY01iNTk1aUUvQjZwcytQOWxXNXdqUnZWeG1TK05vSThOeU9zc2Y2cDZXNVZyemVtQ1oxc01kVENibU5NWWZLRWxWUkdWdU5EZkd4QUdrSzEiLCJtYWMiOiI2NjVlNDVlZGJjODExNzcwOGI1OWZhMjRjMTFhYmY0NGMzNTY1NjhmNDI3ZGRmNTY5ZjEyYWVkMWI1MGE2ZGI4In0%3D'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $decoded_response = json_decode($response);
        foreach ($decoded_response as $country){
            $countries[$country->id] = $country->country_name;
        }

        return $countries;
    }

    private function feed_category_list(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://ai.webixaps.com/get_feed_categories',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Cookie: XSRF-TOKEN=eyJpdiI6Ik14QVJ0WTc0VDRnVHVUdXBGY0N1aHc9PSIsInZhbHVlIjoiNW1FbFdXc2E0cUNPbjlVdndWUEpFRlV0SDRDVis5dlhUT1FvT3BKclhmL3UwSTlUTGJnV2NXUWh4NnpBUE81SFZTVThNb0x6SGVhS0FvME1sbUZYMW82YnRDaEdOdFNibkpmSWxBbmorZGhaVWFMMlEzakpvdm40WkxBcFFYVkEiLCJtYWMiOiIyZmJiYzNiMTJhY2IzN2YwNzBkN2VlMWVkOWFjMWFiMjEwNTljN2E0YmYzNDQyZmQ0ZWE5ZjdmYmQzZTI2NzQwIn0%3D; webixaps_session=eyJpdiI6ImdONjYyUk5wWFZMeFJudFlWV0VQN1E9PSIsInZhbHVlIjoiRUpyWWI5MnpuMGE2N05OYWlFMnc3NmJ3N1JsT0RyeG1wRCtqUi9LVXhzVUt5SE9KY01iNTk1aUUvQjZwcytQOWxXNXdqUnZWeG1TK05vSThOeU9zc2Y2cDZXNVZyemVtQ1oxc01kVENibU5NWWZLRWxWUkdWdU5EZkd4QUdrSzEiLCJtYWMiOiI2NjVlNDVlZGJjODExNzcwOGI1OWZhMjRjMTFhYmY0NGMzNTY1NjhmNDI3ZGRmNTY5ZjEyYWVkMWI1MGE2ZGI4In0%3D'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $decoded_response = json_decode($response);
        foreach ($decoded_response as $feed_category){
            $feed_categories[$feed_category->id] = $feed_category->feed_category_name;
        }

        return $feed_categories;
    }

    private function feed_subcategory_list(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://ai.webixaps.com/get_feed_subcategories',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Cookie: XSRF-TOKEN=eyJpdiI6Ik14QVJ0WTc0VDRnVHVUdXBGY0N1aHc9PSIsInZhbHVlIjoiNW1FbFdXc2E0cUNPbjlVdndWUEpFRlV0SDRDVis5dlhUT1FvT3BKclhmL3UwSTlUTGJnV2NXUWh4NnpBUE81SFZTVThNb0x6SGVhS0FvME1sbUZYMW82YnRDaEdOdFNibkpmSWxBbmorZGhaVWFMMlEzakpvdm40WkxBcFFYVkEiLCJtYWMiOiIyZmJiYzNiMTJhY2IzN2YwNzBkN2VlMWVkOWFjMWFiMjEwNTljN2E0YmYzNDQyZmQ0ZWE5ZjdmYmQzZTI2NzQwIn0%3D; webixaps_session=eyJpdiI6ImdONjYyUk5wWFZMeFJudFlWV0VQN1E9PSIsInZhbHVlIjoiRUpyWWI5MnpuMGE2N05OYWlFMnc3NmJ3N1JsT0RyeG1wRCtqUi9LVXhzVUt5SE9KY01iNTk1aUUvQjZwcytQOWxXNXdqUnZWeG1TK05vSThOeU9zc2Y2cDZXNVZyemVtQ1oxc01kVENibU5NWWZLRWxWUkdWdU5EZkd4QUdrSzEiLCJtYWMiOiI2NjVlNDVlZGJjODExNzcwOGI1OWZhMjRjMTFhYmY0NGMzNTY1NjhmNDI3ZGRmNTY5ZjEyYWVkMWI1MGE2ZGI4In0%3D'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $decoded_response = json_decode($response);
        foreach ($decoded_response as $feed_subcategory){
            $feed_subcategories[$feed_subcategory->id] = $feed_subcategory->feed_subcategory_name;
        }

        return $feed_subcategories;
    }
}