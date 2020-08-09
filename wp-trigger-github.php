<?php

/**
 * @package WPTriggerGithub
 */
/*
Plugin Name: WP Trigger Github
Plugin URI: https://github.com/gglukmann/wp-trigger-github
Description: Save or update action triggers Github repository_dispatch action
Version: 1.2.1
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
    add_action('admin_init', array($this, 'general_settings_section'));
    add_action('save_post', array($this, 'run_hook'), 10, 3);
    add_action('wp_dashboard_setup', array($this, 'build_dashboard_widget'));
  }

  public function activate()
  {
    flush_rewrite_rules();
    $this->general_settings_section();
  }

  public function deactivate()
  {
    flush_rewrite_rules();
  }

  function run_hook($post_id)
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!(wp_is_post_revision($post_id) || wp_is_post_autosave($post_id))) return;

    $github_token = get_option('option_token');
    $github_username = get_option('option_username');
    $github_repo = get_option('option_repo');

    if ($github_token && $github_username && $github_repo) {
      $url = 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/dispatches';
      $args = array(
        'method'  => 'POST',
        'body'    => json_encode(array(
          'event_type' => 'wordpress'
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

  function general_settings_section()
  {
    add_settings_section(
      'general_settings_section',
      'WP Trigger Github Settings',
      array($this, 'my_section_options_callback'),
      'general'
    );
    add_settings_field(
      'option_username',
      'Repository Owner Name',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_username'
      )
    );
    add_settings_field(
      'option_repo',
      'Repository Name',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_repo'
      )
    );
    add_settings_field(
      'option_token',
      'Personal Access Token',
      array($this, 'my_password_callback'),
      'general',
      'general_settings_section',
      array(
        'option_token'
      )
    );
    add_settings_field(
      'option_workflow',
      'Actions Workflow Name',
      array($this, 'my_textbox_callback'),
      'general',
      'general_settings_section',
      array(
        'option_workflow'
      )
    );

    register_setting('general', 'option_token', 'esc_attr');
    register_setting('general', 'option_username', 'esc_attr');
    register_setting('general', 'option_repo', 'esc_attr');
    register_setting('general', 'option_workflow', 'esc_attr');
  }

  function my_section_options_callback()
  {
    echo '<p>Add repository owner name, repository name and generated personal access token to trigger Actions workflow.<br />If you want to see status badge on dashboard, add workflow name.</p>';
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

  /**
   * Create Dashboard Widget for Github Actions deploy status
   */
  function build_dashboard_widget()
  {
    global $wp_meta_boxes;

    wp_add_dashboard_widget('github_actions_dashboard_status', 'Deploy Status', array($this, 'build_dashboard_status'));
  }

  function build_dashboard_status()
  {
    $github_username = get_option('option_username');
    $github_repo = get_option('option_repo');
    $github_workflow = rawurlencode(get_option('option_workflow'));

    $markup = '<a href="https://github.com/' . $github_username . '/' . $github_repo . '/actions" target="_blank" rel="noopener noreferrer">';
    $markup .= '<img src="https://github.com/' . $github_username . '/' . $github_repo . '/workflows/' . $github_workflow . '/badge.svg" alt="Github Actions Status" />';
    $markup .= '</a>';

    echo $markup;
  }
}


if (class_exists('WPTriggerGithub')) {
  $WPTriggerGithub = new WPTriggerGithub();
}

// activation
register_activation_hook(__FILE__, array($WPTriggerGithub, 'activate'));

// deactivate
register_deactivation_hook(__FILE__, array($WPTriggerGithub, 'deactivate'));
