<?php

namespace Palasthotel\WordPress\PluginUpdateCheck;

use Palasthotel\WordPress\PluginUpdateCheck\Components\Component;
use Palasthotel\WordPress\PluginUpdateCheck\Model\UpdatesIdByCalenderWeek;
use Palasthotel\WordPress\PluginUpdateCheck\Model\UpdatesIdByDate;
use Palasthotel\WordPress\PluginUpdateCheck\Source\InternalStore;

class Schedule extends Component
{

    public function onCreate()
    {
        parent::onCreate();

        add_action('admin_init', [$this, 'init']);
        add_action(Plugin::SCHEDULE_CHECK_CONNECTION, [$this, 'check_connection']);
        add_action(Plugin::SCHEDULE_CHECK_UPDATES, [$this, 'check_updates']);
    }

    public function init()
    {
        if (!wp_next_scheduled(Plugin::SCHEDULE_CHECK_CONNECTION)) {
            wp_schedule_event(time(), 'hourly', Plugin::SCHEDULE_CHECK_CONNECTION);
        }
        if (!wp_next_scheduled(Plugin::SCHEDULE_CHECK_UPDATES)) {
            wp_schedule_event(time(), 'hourly', Plugin::SCHEDULE_CHECK_UPDATES);
        }
    }

    public function check_connection()
    {
        $plugin = $this->plugin;
        InternalStore::setGitlabConnectionOk(
            $plugin->gitlab->validate($plugin->gitlabProject)
        );
    }

    public function check_updates()
    {

        // no connection, abort here
        if (!InternalStore::isGitlabConnectionOk()) {
            return;
        }

        // isReporting? abort here
        if (InternalStore::isReporting()) {
            return;
        }

        $now = new \DateTime();
        // get relevant settings
        $settings = $this->plugin->settings;

        // check environment
        // abort if in wrong env
        if ($this->plugin->environment !== $settings->run_in_env)
            return;


        $frequency = $settings->ticket_frequency;
        $modifier = match ($frequency) {
            'weekly' => '+1 week',
            'biweekly' => '+2 weeks',
            'monthly' => '+1 month',
            default => '+1 week',
        };

        $dueDate_days = $settings->GitlabConfig->dueDays ?? null;

        if ( is_numeric($dueDate_days) ) {
            $dueDate_days = '+' . $dueDate_days . ' days';
        }
        else {
            $dueDate_days = '+3 days';
        }

        // check last reported issue date, determine if it's time for new issue
        $last_issue = InternalStore::getReportedUpdatesId();
        if ($last_issue !== '') {
            $next_by_frequency = new \DateTime($last_issue)->modify($modifier);
            // too early, abort
            if ($now < $next_by_frequency)
                return;
        } else { // no earlier reported issue saved
            $now = new \DateTime();
            $now = new UpdatesIdByDate($now);
            InternalStore::setReportedUpdatesId($now);
        }


        $now_copy = clone $now;
        $dueDate = $now_copy->modify($dueDate_days);

        $dayofWeek = $dueDate->format('N');
        if ($dayofWeek >= 6) {
            $dueDate->modify('next Monday');
        }



        InternalStore::setIsReporting( true );

        $list = $this->plugin->plugins->getUpdates();

        // if defined via settings, filter out/ignore plugins
        if ($settings->ignore->plugins !== null && !empty($settings->ignore->plugins)) {
            $list = array_filter($list, fn($item) => !in_array($item->slug, $settings->ignore->plugins));
        }
        $updatesCount = count($list);

        $core = $this->plugin->plugins->getCoreUpdate();
        $coreCount = count($core);

        $themeUpdates = $this->plugin->plugins->getThemeUpdates();

        // if defined via settings, filter out/ignore themes
        if ($settings->ignore->themes !== null && !empty($settings->ignore->themes)) {
            $themeUpdates = array_filter($themeUpdates, fn($item) => !in_array($item, $settings->ignore->themes));
        }
        $themeCount = count($themeUpdates);


        if ($updatesCount <= 0 && $coreCount <= 0 && $themeCount <= 0) {
            return;
        }

        $year = $dueDate->format("y");
        $week = $dueDate->format("W");

        $title = "Updates KW$week/$year";

        $description = "";
        if ($coreCount > 0) {
            $current_version = wp_get_wp_version();
            $description .= "### Wordpress Core  \n";

            foreach ($core as $update) {
                $description .= "- [ ] " . $current_version . " (" . get_locale() . ") -> **" . $update['version'] . "** (" . $update['locale'] . ")\n";
            }
        }

        if ($updatesCount > 0) {
            $description .= "### Plugins  \n";
            foreach ($list as $plugin) {
                $description .= "- [ ] **$plugin->name** $plugin->currentVersion -> $plugin->latestVersion \n";
            }
        }

        if ($themeCount > 0) {
            $description .= "### Themes \n";
            foreach ($themeUpdates as $update) {
                $description .= "- [ ] **$update** \n";
            }

        }
        $description .= $settings->GitlabConfig->TicketDescriptionSuffix;

        $userId = 0;
        if (!empty($settings->GitlabConfig->Assignee)) {
            $userId = $this->plugin->gitlab->getUserId(
                $this->plugin->gitlabProject,
                $settings->GitlabConfig->Assignee
            );
        }


        $success = $this->plugin->gitlab->createIssue(
            $this->plugin->gitlabProject,
            $title,
            $description,
            $dueDate->format("Y-m-d"),
            $userId,
            $settings->GitlabConfig->Labels,
        );

        if ($success) {
            $now = new \DateTime();
            $currentUpdatesId = new UpdatesIdByDate($now);
            InternalStore::setReportedUpdatesId($currentUpdatesId);
        } else {
            error_log("Could not create plugin update ticket");
        }

        InternalStore::setIsReporting(false);
    }

}
