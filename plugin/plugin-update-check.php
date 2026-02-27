<?php

namespace Palasthotel\WordPress\PluginUpdateCheck;

use Palasthotel\WordPress\PluginUpdateCheck\Commands;
use Palasthotel\WordPress\PluginUpdateCheck\Model\GitlabProjectConfiguration;
use Palasthotel\WordPress\PluginUpdateCheck\Source\Gitlab;
use Palasthotel\WordPress\PluginUpdateCheck\Source\Plugins;

/**
 * Plugin Name: Plugin Update Check
 * Description: Provides a page to check for the up-to-date-state of all plugins
 * Version: 1.0
 * Requires at least: 5.0
 * Tested up to: 6.5.5
 * Author: Palasthotel <enno.welbers@palasthotel.de, edward.bock@palasthotel.de>
 * Author URI: https://palasthotel.de
 * Text Domain: plugin-update-check
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @copyright Copyright (c) 2024, Palasthotel
 */

require_once __DIR__ . "/vendor/autoload.php";

class Plugin extends Components\Plugin {

	const TRANSIENT_IS_REPORTING = "plugin_update_check_is_reporting";

	const OPTION_HAS_GITLAB_CONNECTION_ISSUE = "plugin_update_check_gitlab_connection_issue";
	const OPTION_REPORTED_UPDATES_ID = "plugin_update_check_reported_updates_id";

	const SCHEDULE_CHECK_CONNECTION = "plugin_update_check_connection";
	const SCHEDULE_CHECK_UPDATES = "plugin_update_check_updates";
	public Gitlab $gitlab;
	public Plugins $plugins;
    public $environment;
    public $settings;
	public GitlabProjectConfiguration $gitlabProject;


	public function onCreate() {

		$this->plugins = new Plugins();
        $this->environment = $this->getEnvironment();
        $this->settings = null;
        if ( file_exists(dirname(ABSPATH) . '/Butlerfile') )
            $this->settings = json_decode(file_get_contents(dirname(ABSPATH) . '/Butlerfile'))->update_check;

        // no settings, no work. I quit
        if ( $this->settings === null ) {
            return;
        }


        $GitlabConfig = $this->settings->GitlabConfig;
        if (
            empty($GitlabConfig->url) ||
            empty($GitlabConfig->ProjectNamespace) ||
            empty($GitlabConfig->ProjectName) ||
            empty($GitlabConfig->PrivateToken)
        ){
            return;
        }


		$this->gitlabProject = new GitlabProjectConfiguration(
			$GitlabConfig->url,
			$GitlabConfig->ProjectNamespace,
			$GitlabConfig->ProjectName,
			$GitlabConfig->PrivateToken
		);

		$this->gitlab = new Gitlab();

		new AdminNotice($this);
		new PublicApi($this);
		new Schedule($this);
        new Commands($this)->onCreate();

	}
    public function getEnvironment() {
        if (stristr(dirname(ABSPATH), '/Users/')) {
            return 'butler';
        }
        if ( file_exists(dirname(ABSPATH) . '/config/site-config.json') ){
            $config = json_decode(file_get_contents(dirname(ABSPATH) . '/config/site-config.json'));
            return $config->environment ?? false;
        }
    }
}

Plugin::instance();




