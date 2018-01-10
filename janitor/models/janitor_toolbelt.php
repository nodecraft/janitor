<?php
/**
 * Janitor cleanup cron plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.janitor
 * @license MIT https://opensource.org/licenses/MIT
 * @link https://github.com/nodecraft/janitor
 */

class JanitorToolbelt extends JanitorModel {
	public function __construct() {
		parent::__construct();
		Language::loadLang("janitor", null, PLUGINDIR . "janitor" . DS . "language" . DS);
		Loader::loadComponents($this, array("Record", "SettingsCollection"));
		Loader::loadModels($this, array('Janitor.JanitorSettings'));
	}

	/**
	 * CRON method to cancel any services with cancelled orders, 
	 *
	 * @return array
	 */
	public function cancel() {
		$results = array();
		foreach($this->staleOrders('cancel') as $order) {
			$results[$order->id] = $this->cancelOrder($order);
		}

		return $results;
	}

	/**
	 * CRON method to remove orders and services which require cleanup
	 *
	 * @return array
	 */
	public function clean() {
		$results = array();
		foreach($this->staleOrders('clean') as $order) {
			$results[$order->id] = $this->cleanOrder($order);
		}
		return $results;
	}

	/**
	 * Performs lookup and handles either cancel or clean method on order. Ignores time limits
	 *
	 * @param object $order Object returned from the order table
	 * @return array
	 */
	public function handleOrder($order_number, $method='cancel') {
		$order = $this->Record->select()->from("orders")->where("order_number", "=", $order_number)->fetch();
		$method_name = $method . 'Order';
		if($order && method_exists($this, $method_name)){
			return $this->{$method_name}($order);
		}
		return false;
	}


	/**
	 * Perform cancel order function to cancel order, invoice, and service
	 *
	 * @param object $order Object returned from the order table
	 * @return array
	 */
	private function cancelOrder($order) {
		$results = array();

		// Update order status
		$results['order'] = $this->Record->where("id", "=", $order->id)->
			set("status", "canceled")->update("orders");

		// update services status, where applicable
		$results['services'] = $this->Record->
			innerJoin("order_services", "order_services.service_id", "=", "services.id", false)->
			where("order_services.order_id", "=", $order->id)->
			where("services.status", "in", array("pending", "in_review"))->
			set("services.status", "canceled")->
			update("services");

		// update invoices
		if (!isset($this->Invoices)) {
			Loader::loadModels($this, array("Invoices"));
		}
		$results['invoice'] = $this->Invoices->edit($order->invoice_id, array(
			'note_public' => Language::_("Janitor.invoice.void_note", true),
			'status' => "void"
		));
		return $results;
	}

	/**
	 * Perform clean order function to delete cancelled invoices and remove order data
	 *
	 * @param object $order Object returned from the order table
	 * @return array
	 */
	private function cleanOrder($order) {
		$results = array();
		if ($order->settings['service_action'] === 'delete') {
			$services = $this->Record->select(array('services.id'))->from("order_services")->
				innerJoin("services", "services.id", "=", "order_services.service_id", false)->
				where("order_services.order_id", "=", $order->id)->
				where("services.status", "=", "canceled")->
				fetchAll();

			$results['services'] = array();
			foreach($services as $service) {
				$results['services'][$service->id] = $this->Record->from("services")->where("id", "=", $service->id)->delete();
			}
		}
		$results['order_services'] = $this->Record->from("order_services")->where("order_id", "=", $order->id)->delete();
		$results['order'] = $this->Record->from("orders")->where("id", "=", $order->id)->delete();
		return $results;
	}

	/**
	 * Fetch stale orders to be marked for clean or cancellation
	 *
	 * @param string $type The type of stale orders to return. If not specified, return all
	 * @return array
	 */
	private function staleOrders($type=null) {
		$orders = $this->Record->select(array(
			"orders.*",
			"order_forms.company_id" => "company_id",
			"invoices.total" => 'invoice_total',
			"invoices.paid" => 'invoice_paid',
			"invoices.status" => "invoice_status",
			"invoices.date_closed" => "invoice_date_closed"
		))->from("orders")->
			where("orders.status", "!=", "fraud", true)->
			innerJoin("invoices", "invoices.id", "=", "orders.invoice_id", false)->
			innerJoin("order_forms", "order_forms.id", "=", "orders.order_form_id", false)->
			fetchAll();

		$settings = array();

		$results = array(
			"cancel" => array(),
			"clean" => array(),
			"ignored" => array()
		);

		foreach($orders as $order) {
			if (!isset($settings[$order->company_id])) {
				$settings[$order->company_id] = $this->JanitorSettings->getSettings($order->company_id);
			}
			$order->settings = $settings[$order->company_id];
			if ($order->invoice_date_closed !== null && $order->invoice_paid >= $order->invoice_total) {
				$order->paid = true;
			} else
			{
				$order->paid = false;
			}
			$job = array();
			if ($order->status === 'canceled') {
				$job['setting'] = 'cancelled_minutes';
				$job['queue'] = 'clean';
			}else
			{
				if ($order->paid) {
					$job['setting'] = 'accepted_minutes';
					$job['queue'] = 'clean';
				}else
				{
					if ($order->invoice_paid > 0) {
						$job['queue'] = 'ignored';
					}else
					{
						$job['setting'] = 'pending_minutes';
						$job['queue'] = 'cancel';
					}
				}
			}
			$order->date_added = strtotime($order->date_added);
			if (isset($job['setting'])) {
				$job['time'] = strtotime('-' . abs($settings[$order->company_id][$job['setting']]) . " minutes");
			}
			if ($job['queue'] == 'ignored' || $settings[$order->company_id][$job['setting']] !== "0" && $order->date_added < $job['time']) {
				$results[$job['queue']][] = $order;
			}
			else {
				$results['ignored'][] = $order;
			}
		};
		if (!$type) {
			return $results;
		}
		return $results[$type];
	}

}

?>