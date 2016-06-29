<?php

/**
 * Janitor cleanup cron plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.janitor
 * @license MIT https://opensource.org/licenses/MIT
 * @link https://github.com/nodecraft/janitor
 */

class JanitorController extends AppController {

	public function preAction() {
		parent::preAction();

		// Override default view directory
		$this->view->view = "default";
		$this->structure->view = "default";
	}

}

?>