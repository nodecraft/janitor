<?php

/**
 * Janitor cleanup cron plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.janitor
 * @license MIT https://opensource.org/licenses/MIT
 * @link https://github.com/nodecraft/janitor
 */

class JanitorPlugin extends Plugin {
	/**
	 * Construct
	 */
	public function __construct() {
		// load plugin config
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");

		// Load components required by this plugin
		Language::loadLang("janitor", null, dirname(__FILE__) . DS . "language" . DS);
	}

	/**
	 * Add cron tasks to Blesta
	 *
	 * @param int $plugin_id The ID of the plugin being installed
	 */
	public function install($plugin_id) {
		Loader::loadModels($this, array('Janitor.JanitorSettings'));
		$this->JanitorSettings->setSettings(null, array(
			'pending_minutes' => 360,
			'cancelled_minutes' => 1440,
			'accepted_minutes' => 60,
			'service_action' => 'cancel'
		));
		return $this->setupCron();

	}

	/**
	 * Remove cron tasks from Blesta
	 *
	 * @param int $plugin_id The ID of the plugin being uninstalled
	 * @param boolean $last_instance True if $plugin_id is the last instance across all companies for this plugin, false otherwise
	 */
	public function uninstall($plugin_id, $last_instance) {
		return $this->removeCron();
	}

	/**
	 * Execute the cron tasks
	 *
	 * @param string $key The cron task to execute
	 */
	public function cron($key) {
		Loader::loadModels($this, array("Janitor.JanitorToolbelt"));
		switch ($key) {
			case "janitor_cancel":
				$this->JanitorToolbelt->cancel();
			break;
			case "janitor_clean":
				$this->JanitorToolbelt->clean();
			break;
		}
	}

	/**
	 * Helper function to list parts of cron tasks for input/removal
	 *
	 */
	private function getCron() {
		return array(
			array(
				'task' => array(
					'key' => 'janitor_cancel',
					'plugin_dir' => 'janitor',
					'name' => Language::_("Janitor.cron.janitor_cancel", true),
					'description' => Language::_("Janitor.cron.janitor_cancel_description", true),
					'type' => 'interval'
				),
				'runner' => array(
					'interval' => 360, // default to 6 hours
					'enabled' => 1
				)
			),
			array(
				'task' => array(
					'key' => 'janitor_clean',
					'plugin_dir' => 'janitor',
					'name' => Language::_("Janitor.cron.janitor_clean", true),
					'description' => Language::_("Janitor.cron.janitor_clean_description", true),
				),
				'runner' => array(
					'interval' => 720, // default to 12 hours
					'enabled' => 1
				)
			)
		);
	}

	/**
	 * Helper function which inserts cron tasks into CronTasks
	 *
	 */
	private function setupCron() {
		Loader::loadModels($this, array("CronTasks"));
		foreach($this->getCron() as $cron) {
			$task_id = false;
			$get_task_id = $this->CronTasks->getByKey($cron['task']['key'], $cron['task']['plugin_dir']);
			if ($get_task_id) {
				$task_id = $get_task_id->id;
			}
			if (!$task_id) {
				$task_id = $this->CronTasks->add($cron['task']);
			}
			if ($task_id) {
				$add = $this->CronTasks->addTaskRun($task_id, $cron['runner']);
			}
		}
	}

	/**
	 * Helper function which removes cron tasks from CronTasks
	 *
	 */
	private function removeCron() {
		Loader::loadModels($this, array("CronTasks"));
		$plugin_dir = 'janitor';
		foreach($this->getCron() as $cron) {
			// Remove the cron tasks
			$cron_task = $this->CronTasks->getByKey($cron['task']['key'], $plugin_dir);
			if ($cron_task){
				$this->CronTasks->delete($cron_task->id, $plugin_dir);
			}
			// Remove individual cron task runs
			$cron_task_run = $this->CronTasks->getTaskRunByKey($cron['task']['key'], $plugin_dir);
			if ($cron_task_run) {
				$this->CronTasks->deleteTaskRun($cron_task_run->task_run_id);
			}
		}
	}

}
?>