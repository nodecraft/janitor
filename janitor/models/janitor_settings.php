<?php
/**
 * Janitor cleanup cron plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.janitor
 * @license MIT https://opensource.org/licenses/MIT
 * @link https://github.com/nodecraft/janitor
 */

class JanitorSettings extends AppModel {
	public function __construct(){
		parent::__construct();

		if (!isset($this->SettingsCollection)) {
			Loader::loadComponents($this, array('SettingsCollection'));
		}

		Language::loadLang("janitor", null, PLUGINDIR . "janitor" . DS . "language" . DS);
	}

	/**
	 * Fetches settings
	 *
	 * @param int $company_id
	 * @return array
	 */
	public function getSettings($company_id=null) {
		if($company_id === null){
			$company_id = Configure::get("Blesta.company_id");
		}
		$supported = $this->supportedSettings();
		$company_settings = $this->SettingsCollection->fetchSettings(null, $company_id);
		$settings = array();
		foreach ($company_settings as $setting => $value) {
			if (($index = array_search($setting, $supported)) !== false) {
				$settings[$index] = $value;
			}
		}
		return $settings;
	}

	/**
	 * Set settings
	 *
	 * @param int $company_id
	 * @param array $settings Key/value pairs
	 */
	public function setSettings($company_id=null, array $settings) {
		if($company_id === null){
			$company_id = Configure::get("Blesta.company_id");
		}
		if (!isset($this->Companies)) {
			Loader::loadModels($this, array('Companies'));
		}

		$valid_settings = array();
		foreach ($this->supportedSettings() as $key => $name) {
			if (array_key_exists($key, $settings)) {
				$valid_settings[$name] = $settings[$key];
			}
		}

		$this->Input->setRules($this->getRules($valid_settings));
		if ($this->Input->validates($valid_settings)) {
			$this->Companies->setSettings($company_id, $valid_settings);
		}
	}

	/**
	 * Fetch supported settings
	 *
	 * @return array
	 */
	public function supportedSettings() {
		return array(
			'pending_minutes' => 'janitor.pending_minutes',
			'cancelled_minutes' => 'janitor.cancelled_minutes',
			'accepted_minutes' => 'janitor.accepted_minutes',
			'service_action' => 'janitor.service_action'
		);
	}

	/**
	 * Input validate rules
	 *
	 * @param array $vars
	 * @return array
	 */
	private function getRules($vars) {
		return array(
			'janitor.pending_minutes' => array(
				'valid' => array(
					'pre_format' => array(array($this, "formatNumber")),
					'rule' => array("between", "0", "20160", true),
					'message' => $this->_('Janitor.!error.minutes.valid')
				)
			),
			'janitor.cancelled_minutes' => array(
				'valid' => array(
					'pre_format' => array(array($this, "formatNumber")),
					'rule' => array("between", "0", "20160", true),
					'message' => $this->_('Janitor.!error.minutes.valid')
				)
			),
			'janitor.accepted_minutes' => array(
				'valid' => array(
					'pre_format' => array(array($this, "formatNumber")),
					'rule' => array("between", "0", "20160", true),
					'message' => $this->_('Janitor.!error.minutes.valid')
				)
			),
			'janitor.service_action' => array(
				'valid' => array(
					'rule' => array("in_array", array("delete", "cancel")),
					'message' => $this->_('Janitor.!error.service_action.valid')
				)
			),
		);
	}
	/**
	 * Format the provided number, stripping any non numeric characters
	 *
	 * @param string $number
	 * @return string
	 */
	public function formatNumber($number) {
		return preg_replace("/[^0-9]*/", "", $number);
	}
}
