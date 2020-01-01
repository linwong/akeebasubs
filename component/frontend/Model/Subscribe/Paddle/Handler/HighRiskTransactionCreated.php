<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Paddle\Handler;

use Akeeba\Subscriptions\Site\Model\Subscribe\HandlerTraits\StackCallback;
use Akeeba\Subscriptions\Site\Model\Subscribe\SubscriptionCallbackHandlerInterface;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;

/**
 * Handle a notification of a high risk transaction
 *
 * @see         https://paddle.com/docs/reference-using-webhooks/#high_risk_transaction_created
 *
 * @since       7.0.0
 */
class HighRiskTransactionCreated implements SubscriptionCallbackHandlerInterface
{
	use StackCallback;

	/**
	 * The component's container
	 *
	 * @var   Container
	 * @since 7.0.0
	 */
	private $container;

	/**
	 * Constructor
	 *
	 * @param Container $container The component container
	 *
	 * @since  7.0.0
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Handle a webhook callback from the payment service provider about a specific subscription
	 *
	 * @param   Subscriptions  $subscription  The subscription the webhook refers to
	 * @param   array          $requestData   The request data minus component, option, view, task
	 *
	 * @return  string|null  Text to include in the callback response page
	 *
	 * @throws  \RuntimeException  In case an error occurs. The exception code will be used as the HTTP status.
	 *
	 * @since  7.0.0
	 */
	public function handleCallback(Subscriptions $subscription, array $requestData): ?string
	{
		// Sanity check
		if ($requestData['status'] != 'pending')
		{
			return null;
		}

		// Create a message
		$eventTime = $requestData['event_time'];
		$caseId    = $requestData['case_id'];
		$riskScore = (float) $requestData['risk_score'];
		$message   = sprintf("Transaction flagged as high risk on %s. Case ID %s, risk score %0.2f",
			$eventTime, $caseId, $riskScore);

		// Set the transaction to Pending status
		$updates = [
			'state' => 'P',
			'notes' => $subscription->notes . "\n" . $message,
		];

		// Stack this callback's information to the subscription record
		$updates = array_merge($updates, $this->getStackCallbackUpdate($subscription, $requestData));

		// Add high risk transaction case parameters
		$updates['params'] = array_merge($updates['params'], [
			'risk_case_id'            => $requestData['case_id'],
			'risk_case_created'       => $requestData['created_at'],
			'risk_score'              => $requestData['risk_score'],
			'paddle_customer_user_id' => $requestData['customer_user_id'],
		]);

		$subscription->save($updates);

		// Done. No output to be sent (returns a 200 OK with an empty body)
		return null;
	}
}