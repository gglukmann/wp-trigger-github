<?php

/**
 * @package WPTriggerGithub
 */
/*
Plugin Name: WP Trigger Github
Plugin URI: https://github.com/gglukmann/wp-trigger-github
Description: Save action triggers Github repository_dispatch action
Version: 1.0.0
Author: Gert Glükmann
Author URI: https://github.com/gglukmann
License: GPLv3
Text-Domain: wp-trigger-github
 */

if (!defined('ABSPATH')) {
  die;
}

class WPTriggerGithub
{
  function __construct()
  {
    add_action('admin_init', array($this, 'my_general_section'));
    add_action('save_post', array($this, 'publish_static_hook'), 10, 3);
  }

  public function activate()
  {
    flush_rewrite_rules();
    $this->my_general_section();
  }

  public function deactivate()
  {
    flush_rewrite_rules();
  }

  function publish_static_hook($id)
  {
    $github_token = get_option('option_token');
    $github_username = get_option('option_username');
    $github_repo = get_option('option_repo');

    if ($github_token && $github_username && $github_repo) {
      $url = 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/dispatches';
      $args = array(
        'method'  => 'POST',
        'body'    => json_encode(array(
          'event_type' => 'dispatch'
        )),
        'headers' => array(
          'Accept' => 'application/vnd.github.everest-preview+json',
          'Content-Type' => 'application/json',
          'Authorization' => 'token ' . $github_token
        ),
      );

      wp_remote_post($url, $args);
    }
  }

  function my_general_section()
  {
    add_settings_section(
      'my_settings_section',
      'Github Settings',
      array($this, 'my_section_options_callback'),
      'general'
    );
    add_settings_field(
      'option_username',
      'Repository Owner Name',
      array($this, 'my_textbox_callback'),
      'general',
      'my_settings_section',
      array(
        'option_username'
      )
    );
    add_settings_field(
      'option_repo',
      'Repository Name',
      array($this, 'my_textbox_callback'),
      'general',
      'my_settings_section',
      array(
        'option_repo'
      )
    );
    add_settings_field(
      'option_token',
      'Personal Access Token',
      array($this, 'my_password_callback'),
      'general',
      'my_settings_section',
      array(
        'option_token'
      )
    );

    register_setting('general', 'option_token', 'esc_attr');
    register_setting('general', 'option_username', 'esc_attr');
    register_setting('general', 'option_repo', 'esc_attr');
  }

  function my_section_options_callback()
  {
    echo '<p>Settings for Github</p>';
  }

  function my_textbox_callback($args)
  {
    $option = get_option($args[0]);
    echo '<input type="text" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
  }

  function my_password_callback($args)
  {
    $option = get_option($args[0]);
    echo '<input type="password" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
  }
}


if (class_exists('WPTriggerGithub')) {
  $WPTriggerGithub = new WPTriggerGithub();
}

// activation
register_activation_hook(__FILE__, array($WPTriggerGithub, 'activate'));

// deactivate
register_deactivation_hook(__FILE__, array($WPTriggerGithub, 'deactivate'));
