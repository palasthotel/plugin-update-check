<?php

namespace Palasthotel\WordPress\PluginUpdateCheck\Source;

use Palasthotel\WordPress\PluginUpdateCheck\Model\UpdatesId;
use Palasthotel\WordPress\PluginUpdateCheck\Plugin;

class InternalStore {

	public static function isGitlabConnectionOk(): bool {
		return get_option( Plugin::OPTION_HAS_GITLAB_CONNECTION_ISSUE, "" ) === "";
	}

	public static function setGitlabConnectionOk(bool $isValid){
		if($isValid){
			delete_option(Plugin::OPTION_HAS_GITLAB_CONNECTION_ISSUE);
		} else {
			update_option(Plugin::OPTION_HAS_GITLAB_CONNECTION_ISSUE, "invalid project configuration");
		}
	}

	public static function isReporting(): bool {
		return get_transient(Plugin::TRANSIENT_IS_REPORTING) == "reporting";
	}

	public static function setIsReporting(bool $isReporting){
		if($isReporting){
			set_transient(Plugin::TRANSIENT_IS_REPORTING, "reporting", 60 * 60);
		} else {
			delete_transient(Plugin::TRANSIENT_IS_REPORTING, "reporting");
		}
	}

	public static function setReportedUpdatesId(UpdatesId $id){
        update_site_option(Plugin::OPTION_REPORTED_UPDATES_ID, $id->asString());
	}

	public static function getReportedUpdatesId(): string {
		return	get_site_option(Plugin::OPTION_REPORTED_UPDATES_ID, "");
	}


}
