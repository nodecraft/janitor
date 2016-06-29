# Blesta: Janitor
Small Blesta plugin to automate cleaning of abandoned orders, invoices, and services.

## Installation
Download the latest release version from our [releases](https://github.com/nodecraft/janitor/releases) and then simply upload the `janitor` folder to `~/plugins` directory on your Blesta installation.

### How it Works:
Janitor creates two cron entries which can be configured both by the cron settings and by the direct plugin settings. All of the plugin's settings are based on the time the order was _created_. It may be important that you not cleanup and cancel at the same interval if you expect the orders to be marked as cancelled for any amount of time. 

Both cron tasks will __never__ cleanup any orders or services which meeting the following criteria:

- The order's invoice has any amount paid towards it.
- The order's service is active or already cancelled.
- The order's invoice is completely paid and is already closed *(database: `invoices.invoice_date_closed`)*
  

##### Cron Task: Cancel Abandoned Orders
This task is designed to strictly check for open orders which have never had any payments attached to them. First, the task will cancel the order and then next, it will void the invoice with a message as described in the language file. This cron task will also cancel any services attached to the order. This part of the cron intentionally leaves orders, invoices, and services in the database, in the possible event of this data being used by sales automation, etc.

##### Cleanup Order Database
This task is designed to completely delete all related database entries related to the order. It will remove the `orders` database entry, the `order_services` entry, and provides you the option to either leave the services as marked cancelled, or completely delete the service from the database via the plugins options. Only canceled services will be deleted if this option is set. If the service on this task is any other status than `canceled`, as set in the 'Cancel Abandoned Orders' cron task, it will ignore the service entirely.