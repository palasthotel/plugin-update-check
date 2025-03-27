<?php

namespace Palasthotel\WordPress\PluginUpdateCheck\Source;

use Palasthotel\WordPress\PluginUpdateCheck\Model\PluginVersion;
use WP_Error;

class Plugins {

	/**
	 * @return PluginVersion[]
	 */
	public function getVersionList(): array {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		}
		$plugins=get_plugins();
		$list = [];
		foreach($plugins as $slug=>$plugin) {
			$real_slug=explode('/',$slug);
			if ( count($real_slug)>1 ) {
                		$real_slug=$real_slug[count($real_slug)-2];
            		}

			$pluginVersion = new PluginVersion();
			$pluginVersion->slug = $real_slug;
			$pluginVersion->name = $plugin['Name'];
			$pluginVersion->currentVersion = $plugin['Version'];

			$call_result = plugins_api('plugin_information',['slug'=>$real_slug,'fields'=>['version'=>true]]);
			if($call_result instanceof WP_Error) {
				error_log("Cannot find any plugin information for $slug");
			} else {
				$pluginVersion->latestVersion = $call_result->version;
			}
			$list[] = $pluginVersion;
		}

		return $list;
	}

	/**
	 * @return PluginVersion[]
	 */
	public function getUpdates(){
		$list = $this->getVersionList();
		return array_values(array_filter($list, function($plugin){
			return $plugin->needsUpdate();
		}));
	}
}
