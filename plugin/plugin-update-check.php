<?php

namespace Palasthotel\WordPress\PluginUpdateCheck;

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

if (!defined('PLUGIN_UPDATE_CHECK_GITLAB')) {
	define('PLUGIN_UPDATE_CHECK_GITLAB', 'https://gitlab.com');
}
if (!defined('PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAMESPACE')) {
	define('PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAMESPACE', '');
}
if (!defined('PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAME')) {
	define('PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAME', '');
}
if (!defined('PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_PRIVATE_TOKEN')) {
	define('PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_PRIVATE_TOKEN', '');
}
if (!defined('PLUGIN_UPDATE_CHECK_GITLAB_ASSIGNEE_USERNAME')) {
	define('PLUGIN_UPDATE_CHECK_GITLAB_ASSIGNEE_USERNAME', '');
}
if (!defined('PLUGIN_UPDATE_CHECK_GITLAB_LABELS')) {
	define('PLUGIN_UPDATE_CHECK_GITLAB_LABELS', '');
}
if(!defined('PLUGIN_UPDATE_CHECK_TICKET_DESCRIPTION_SUFFIX')) {
	define('PLUGIN_UPDATE_CHECK_TICKET_DESCRIPTION_SUFFIX',"

---

- [ ] Merge Request für Ticket erstellen
- [ ] Updates lokal einspielen
- [ ] Updates lokal testen
- [ ] Ggf. Übersetzungen aktualisieren & committen
- [ ] Updates committen & branch in `stage` mergen
- [ ] Updates auf die Stage ausrollen
- [ ] Updates auf Stage testen
- [ ] Merge Request in `main` mergen
- [ ] Updates auf Production ausrollen
- [ ] Updates auf Production testen");
}

class Plugin extends Components\Plugin {

	const TRANSIENT_IS_REPORTING = "plugin_update_check_is_reporting";

	const OPTION_HAS_GITLAB_CONNECTION_ISSUE = "plugin_update_check_gitlab_connection_issue";
	const OPTION_REPORTED_UPDATES_ID = "plugin_update_check_reported_updates_id";

	const SCHEDULE_CHECK_CONNECTION = "plugin_update_check_connection";
	const SCHEDULE_CHECK_UPDATES = "plugin_update_check_updates";
	public Gitlab $gitlab;
	public Plugins $plugins;
	public GitlabProjectConfiguration $gitlabProject;


	public function onCreate() {

		$this->plugins = new Plugins();

		if (
			empty(PLUGIN_UPDATE_CHECK_GITLAB) ||
			empty(PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAMESPACE) ||
			empty(PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAME) ||
			empty(PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_PRIVATE_TOKEN)
		) {
			// no config found so don't even try it
			return;
		}

		$this->gitlabProject = new GitlabProjectConfiguration(
			PLUGIN_UPDATE_CHECK_GITLAB,
			PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAMESPACE,
			PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_NAME,
			PLUGIN_UPDATE_CHECK_GITLAB_PROJECT_PRIVATE_TOKEN
		);

		$this->gitlab = new Gitlab();

		new AdminNotice($this);
		new PublicApi($this);
		new Schedule($this);

	}
}

Plugin::instance();




