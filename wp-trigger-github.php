<?php

/**
 * @package WPTriggerGithub
 */
/*
Plugin Name: WP Trigger Github
Plugin URI: https://github.com/gglukmann/wp-trigger-github
Description: Save or update action triggers Github repository_dispatch action
Version: 1.2.3
Author: Gert Glükmann
Author URI: https://github.com/gglukmann
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text-Domain: wp-trigger-github
 */

if (!defined('ABSPATH')) {
  die;
}

class WPTriggerGithub
{
  function __construct()
  {
    add_action('admin_init', [$this, 'generalSettingsSection']);
    add_action('save_post', [$this, 'runHook'], 10, 3);
    add_action('wp_dashboard_setup', [$this, 'buildDashboardWidget']);
  }

  public function activate()
  {
    flush_rewrite_rules();
    $this->generalSettingsSection();
  }

  public function deactivate()
  {
    flush_rewrite_rules();
  }

  function runHook($post_id)
  {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

    $github_token = get_option('ga_option_token');
    $github_username = get_option('ga_option_username');
    $github_repo = get_option('ga_option_repo');

    if ($github_token && $github_username && $github_repo) {
      $url = 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/dispatches';
      $args = array(
        'method'  => 'POST',
        'body'    => json_encode(array(
          'event_type' => 'wordpress'
        )),
        'headers' => array(
          'Accept' => 'application/vnd.github.v3+json',
          'Content-Type' => 'application/json',
          'Authorization' => 'token ' . $github_token
        ),
      );

      wp_remote_post($url, $args);
    }
  }

  function generalSettingsSection()
  {
    add_settings_section(
      'ga_general_settings_section',
      'WP Trigger Github Settings',
      [$this, 'mySectionOptionsCallback'],
      'general'
    );
    add_settings_field(
      'ga_option_username',
      'Repository Owner Name',
      [$this, 'myTextboxCallback'],
      'general',
      'ga_general_settings_section',
      ['ga_option_username']

    );
    add_settings_field(
      'ga_option_repo',
      'Repository Name',
      [$this, 'myTextboxCallback'],
      'general',
      'ga_general_settings_section',
      ['ga_option_repo']
    );
    add_settings_field(
      'ga_option_token',
      'Personal Access Token',
      [$this, 'myPasswordCallback'],
      'general',
      'ga_general_settings_section',
      ['ga_option_token']
    );
    add_settings_field(
      'ga_option_workflow',
      'Actions Workflow Name',
      [$this, 'myTextboxCallback'],
      'general',
      'ga_general_settings_section',
      ['ga_option_workflow']
    );

    register_setting('general', 'ga_option_token', 'esc_attr');
    register_setting('general', 'ga_option_username', 'esc_attr');
    register_setting('general', 'ga_option_repo', 'esc_attr');
    register_setting('general', 'ga_option_workflow', 'esc_attr');
  }

  function mySectionOptionsCallback()
  {
    echo '<p>Add repository owner name, repository name and generated personal access token to trigger Actions workflow.<br />If you want to see status badge on dashboard, add workflow name.</p>';
  }

  function myTextboxCallback($args)
  {
    $option = get_option($args[0]);
    echo '<input type="text" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
  }

  function myPasswordCallback($args)
  {
    $option = get_option($args[0]);
    echo '<input type="password" id="' . $args[0] . '" name="' . $args[0] . '" value="' . $option . '" />';
  }

  /**
   * Create Dashboard Widget for Github Actions deploy status
   */
  function buildDashboardWidget()
  {
    global $wp_meta_boxes;

    wp_add_dashboard_widget('github_actions_dashboard_status', 'Deploy Status', [$this, 'buildDashboardStatus']);
  }

  function buildDashboardStatus()
  {
    $github_username = get_option('ga_option_username');
    $github_repo = get_option('ga_option_repo');
    $github_workflow = rawurlencode(get_option('ga_option_workflow'));

    $markup = '<a href="https://github.com/' . $github_username . '/' . $github_repo . '/actions" target="_blank" rel="noopener noreferrer">';
    $markup .= '<img src="https://github.com/' . $github_username . '/' . $github_repo . '/actions/workflows/' . $github_workflow . '/badge.svg" alt="Github Actions Status" />';
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
