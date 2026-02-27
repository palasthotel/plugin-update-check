<?php

namespace Palasthotel\WordPress\PluginUpdateCheck;

//use Palasthotel\WordPress\PluginUpdateCheck\Components\Component;
use Palasthotel\WordPress\PluginUpdateCheck\Plugin;
use Palasthotel\WordPress\PluginUpdateCheck\Source\InternalStore;

use WP_CLI;

class Commands {

    public function onCreate(): void
    {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'update-check last-reported', [$this, 'last_reported'] );
            \WP_CLI::add_command( 'update-check is-reporting', [$this, 'is_reporting'] );
            \WP_CLI::add_command( 'update-check env', [$this, 'environment'] );

        }
    }


    public function environment($args, $assoc_args) {
        echo "Current Environment: " . Plugin::instance()->getEnvironment() . PHP_EOL;
        exit;
    }

    /**
     * show or delete last reported ID
     *
     * ## Options
     *
     * [--delete]
     *  : delete reported ID
     *
     *
     */
    public function last_reported( $args, $assoc_args ) {

        $last_issue = InternalStore::getReportedUpdatesId();
        $assoc_args = wp_parse_args(
            $assoc_args,
            array(
                'delete'   => false,
		    )
	    );

        if ( $assoc_args['delete'] === false ) {

            if ( $last_issue !== '' ) {
                echo "Last Updates ID --> \"" . $last_issue . "\"" . PHP_EOL;
                exit;
            }
            else {
                echo "No saved last updates ID exists" . PHP_EOL;
                exit;
            }

        }

        if ( $assoc_args['delete'] === true ) {
            InternalStore::removeReportedUpdatesId();
            WP_CLI::success( 'Last Updates ID deleted.' );
            exit;
        }

    }

    public function is_reporting( $args, $assoc_args ) {
        WP_CLI::line( 'Kommando 2 wird ausgeführt...' );

        WP_CLI::success( 'Kommando 2 abgeschlossen!' );
    }
}

