<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link https://webixaps.com/
 * @since 1.0.0
 *
 * @package Webix_Feed
 * @subpackage Webix_Feed/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package Webix_Feed
 * @subpackage Webix_Feed/public
 * @author Webix Aps <info@webixaps.com>
 */
class Webix_Feed_Public
{
  /**
   * The ID of this plugin.
   *
   * @since 1.0.0
   * @access private
   * @var string $plugin_name The ID of this plugin.
   */
  private $plugin_name;
  /**
   * The version of this plugin.
   *
   * @since 1.0.0
   * @access private
   * @var string $version The current version of this plugin.
   */
  private $version;
  /**
   * Initialize the class and set its properties.
   *
   * @param string $plugin_name The name of the plugin.
   * @param string $version The version of this plugin.
   *
   * @since 1.0.0
   */
  public function __construct($plugin_name, $version)
  {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }
  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since 1.0.0
   */
  public function enqueue_styles()
  {
    wp_enqueue_style(
      $this->plugin_name,
      plugin_dir_url(__FILE__) . 'css/webix-feed-public.css',
      array(),
      $this->version,
      'all'
    );
    wp_enqueue_style(
      $this->plugin_name.'main',
      plugin_dir_url(__FILE__) . 'css/main.css',
      array(),
      $this->version,
      'all'
    );
    // nouislider fjernet – bruges ikke længere
    /*
    wp_enqueue_style(
      'wf-filter-style',
      'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.css',
      array()
    );
    */
  }
  /**
   * Register the JavaScript for the public-facing side of the site.
   *
   * @since 1.0.0
   */
  public function enqueue_scripts()
  {
    wp_enqueue_script(
      $this->plugin_name,
      plugin_dir_url(__FILE__) . 'js/webix-feed-public.js',
      array('jquery'),
      $this->version,
      true // footer: scriptet er ikke render-kritisk og skal ikke blokere first paint
    );
    // nouislider fjernet – bruges ikke længere
    /*
    wp_enqueue_script(
      'wf-filter-script',
      'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/14.6.3/nouislider.min.js',
      array()
    );
    */
  }
}