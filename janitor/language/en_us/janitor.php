<?php
	$lang['Janitor.cron.janitor_cancel'] = "Cancel Abandoned Orders";
	$lang['Janitor.cron.janitor_cancel_description'] = "Janitor: Marks abandoned orders as cancelled. Useful for automation to send deals or automated cart reminders to customers";

	$lang['Janitor.cron.janitor_clean'] = "Cleanup Orders Database";
	$lang['Janitor.cron.janitor_clean_description'] = "Janitor: Deletes unusable data from the orders database, including completed or cancelled orders";

	$lang['Janitor.invoice.void_note'] = "Invoice was automatically voided due to non-payment of new order.";

	$lang['Janitor.index.page_title'] = 'Janitor';
	$lang['Janitor.index.boxtitle_manage'] = 'Janitor';
	$lang['Janitor.index.heading_settings'] = 'Settings';
	$lang['Janitor.index.field.pending_minutes'] = 'Time, in minutes, after order is created to set pending orders to cancelled. Set as 0 to disable.';
	$lang['Janitor.index.field.cancelled_minutes'] = 'Time, in minutes, after order is created to remove cancelled. Set this higher than the previous option to avoid both tasks from running on the same cron. Set as 0 to disable.';
	$lang['Janitor.index.field.accepted_minutes'] = 'Time, in minutes, after order is created to remove orders that have been completed. This will not remove the service, but the dead data in the ordering system. Set as 0 to disable.';
	$lang['Janitor.index.field.service_action'] = 'On \'Cleanup Order Database\' task: action to perform on services after cancelled orders are removed. NOTE: The "delete" action is non-reversible.';
	$lang['Janitor.index.field.submit'] = 'Save';

	$lang['Janitor.!error.minutes.valid'] = 'Invalid number of minutes. Please enter a number between 0 and 20160.';
	$lang['Janitor.!error.service_action.valid'] = 'Invalid service action.';
	$lang['Janitor.!success.settings_saved'] = 'Settings succesfully saved.';

?>