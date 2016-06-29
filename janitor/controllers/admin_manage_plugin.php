<?php
/**
 * Janitor cleanup cron plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.janitor
 * @license MIT https://opensource.org/licenses/MIT
 * @link https://github.com/nodecraft/janitor
 */
class AdminManagePlugin extends AppController{
    /**
     * Performs necessary initialization
     */
    private function init() {
        // Require login
        $this->parent->requireLogin();

        $this->uses(array('Janitor.JanitorSettings'));

        Language::loadLang("janitor", null, PLUGINDIR . "janitor" . DS . "language" . DS);

        $this->parent->structure->set(
            'page_title',
            Language::_(
                'janitor.'
                . Loader::fromCamelCase($this->action ? $this->action : 'index')
                . '.page_title',
                true
            )
        );

        // Set the view to render for all actions under this controller
        $this->view->setView(null, 'janitor.default');
    }

    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index() {
        $this->init();

        $vars = (object) $this->JanitorSettings->getSettings($this->parent->company_id);

        if (!empty($this->post)) {
            $this->JanitorSettings->setSettings(
                $this->parent->company_id,
                $this->post
            );

            if (($error = $this->JanitorSettings->errors())) {
                $this->parent->setMessage('error', $error);
            } else {
                $this->parent->setMessage(
                    'message',
                    Language::_('Janitor.!success.settings_saved', true)
                );
            }

            $vars = (object) $this->post;
        }
        $service_actions = array(
            array(
                'name' => 'delete',
                'value' => 'delete',
            ),
            array(
                'name' => 'cancel',
                'value' => 'cancel'
            )
        );
        // Set the view to render
        return $this->partial(
            'admin_manage_plugin',
            compact('vars', 'service_actions')
        );
    }
}
