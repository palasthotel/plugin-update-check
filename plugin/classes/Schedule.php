<?php

namespace Palasthotel\WordPress\PluginUpdateCheck;

use Palasthotel\WordPress\PluginUpdateCheck\Components\Component;
use Palasthotel\WordPress\PluginUpdateCheck\Model\UpdatesIdByCalenderWeek;
use Palasthotel\WordPress\PluginUpdateCheck\Source\InternalStore;

class Schedule extends Component {

	public function onCreate() {
		parent::onCreate();

		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( Plugin::SCHEDULE_CHECK_CONNECTION, [ $this, 'check_connection' ] );
		add_action( Plugin::SCHEDULE_CHECK_UPDATES, [ $this, 'check_updates' ] );
	}

	public function init() {
		if ( ! wp_next_scheduled( Plugin::SCHEDULE_CHECK_CONNECTION ) ) {
			wp_schedule_event( time(), 'hourly', Plugin::SCHEDULE_CHECK_CONNECTION );
		}
		if ( ! wp_next_scheduled( Plugin::SCHEDULE_CHECK_UPDATES ) ) {
			wp_schedule_event( time(), 'twicedaily', Plugin::SCHEDULE_CHECK_UPDATES );
		}
	}

	public function check_connection() {
		$plugin = $this->plugin;
		InternalStore::setGitlabConnectionOk(
			$plugin->gitlab->validate( $plugin->gitlabProject )
		);
	}

	public function check_updates() {

		if ( ! InternalStore::isGitlabConnectionOk() ) {
			return;
		}

		$now  = new \DateTime();
		$dueDate = $now->modify( 'Friday this week' );
		if ( $dueDate->getTimestamp() - $now->getTimestamp() < HOUR_IN_SECONDS * 36 ) {
			$dueDate = $now->modify( 'Friday next week' );
		}

		$currentUpdatesId = new UpdatesIdByCalenderWeek($dueDate);
		if ( InternalStore::getReportedUpdatesId() == $currentUpdatesId->asString() ) {
			return;
		}

		if ( InternalStore::isReporting() ) {
			return;
		}
		InternalStore::setIsReporting( true );

		$list         = $this->plugin->plugins->getUpdates();
		$updatesCount = count( $list );

        $core = $this->plugin->plugins->getCoreUpdate();
        $coreCount = count( $core );

        $themeUpdates = $this->plugin->plugins->getThemeUpdates();
        $themeCount = count( $themeUpdates );



		if ( $updatesCount <= 0 && $coreCount <= 0 && $themeCount <= 0 ) {
			return;
		}

		$year = $dueDate->format( "y" );
		$week = $dueDate->format( "W" );

		$title = "Updates KW$week/$year";

		$description = "";
        if ( $coreCount > 0 ) {
            $current_version = wp_get_wp_version();
            $description .= "### Wordpress Core  \n";

            foreach ( $core as $update ) {
                $description .= "- [ ] " . $current_version . " (" . get_locale() . ") -> **" . $update['version'] . "** (" . $update['locale'] . ")\n";
            }
        }

        if ( $updatesCount > 0 ){
            $description .= "### Plugins  \n";
            foreach ($list as $plugin) {
                $description .= "- [ ] **$plugin->name** $plugin->currentVersion -> $plugin->latestVersion \n";
            }
        }

        if ( $themeCount > 0 ) {
            $description .= "### Themes \n";
            foreach ( $themeUpdates as $update ) {
                $description .= "- [ ] **$update** \n";
            }

        }
		$description .= PLUGIN_UPDATE_CHECK_TICKET_DESCRIPTION_SUFFIX;
  
		$userId = 0;
		if(!empty(PLUGIN_UPDATE_CHECK_GITLAB_ASSIGNEE_USERNAME)){
			$userId = $this->plugin->gitlab->getUserId(
				$this->plugin->gitlabProject,
				PLUGIN_UPDATE_CHECK_GITLAB_ASSIGNEE_USERNAME
			);
		}


		$success = $this->plugin->gitlab->createIssue(
			$this->plugin->gitlabProject,
			$title,
			$description,
			$dueDate->format( "Y-m-d" ),
			$userId,
			PLUGIN_UPDATE_CHECK_GITLAB_LABELS
		);

		if($success) {
			InternalStore::setReportedUpdatesId( $currentUpdatesId );
		} else {
			error_log("Could not create plugin update ticket");
		}

		InternalStore::setIsReporting( false );
	}

}
