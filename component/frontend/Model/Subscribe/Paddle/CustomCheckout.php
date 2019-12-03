<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Paddle;

use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use FOF30\Container\Container;
use FOF30\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use RuntimeException;

/**
 * Implements Paddle's custom checkout logic
 *
 * @see
 *
 * @since   7.0.0
 */
class CustomCheckout
{
	/**
	 * Component's container
	 *
	 * @var   Container
	 * @since 7.0.0
	 */
	private $container;

	/**
	 * CustomCheckout constructor.
	 *
	 * @param   Container  $container  The component container
	 *
	 * @since   7.0.0
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Get the custom checkout URL
	 *
	 * @param   Subscriptions  $sub
	 *
	 * @return  string
	 *
	 * @since   7.0.0
	 *
	 * @throws  RuntimeException
	 */
	public function getCheckoutUrl(Subscriptions $sub): string
	{
		$user = Factory::getUser($sub->user_id);
		/** @var Levels $level */
		$level = $this->container->factory->model('Levels')->tmpInstance();
		$level->findOrFail($sub->akeebasubs_level_id);

		if (empty($level->paddle_product_id))
		{
			throw new RuntimeException(sprintf('There is no Paddle product associated with %s', $level->title));
		}

		// If it's a recurring subscription I will need to extract some values from the subscription parameters
		$plan_id             = isset($sub->params['recurring_plan_id']) ? $sub->params['recurring_plan_id'] : null;
		$trial_days          = isset($sub->params['override_trial_days']) ? $sub->params['override_trial_days'] : 0.00;
		$initial_price       = isset($sub->params['override_initial_price']) ? $sub->params['override_initial_price'] : null;
		$purchasingRecurring = !is_null($plan_id);

		// The product ID is either a product (one-off purchase) or a subscription plan (recurring). Get the correct one.
		$product_id = $purchasingRecurring ? $plan_id : $level->paddle_product_id;

		// Get the checkout URL expiration date
		$jExpires     = new Date();
		$recoveryDays = $this->container->params->get('payment_recovery_lifetime', 7);
		$period       = new \DateInterval('P' . ($recoveryDays + 1) . 'D');
		$jExpires->add($period);

		$fields = [
			'vendor_id'         => $this->container->params->get('vendor_id'),
			'vendor_auth_code'  => $this->container->params->get('vendor_auth_code'),
			'product_id'        => $product_id,
			'prices'            => [
				$this->container->params->get('currency') . ':' . sprintf('%0.2f', $sub->net_amount),
			],
			'discountable'      => 0,
			'quantity_variable' => 0,
			'quantity'          => 1,
			'marketing_consent' => 0,
			'customer_email'    => $user->email,
			'passthrough'       => $sub->getId(),
			'expires'           => $jExpires->format('Y-m-d'),
		];

		// Recurring subscriptions need some more work on our part
		if ($purchasingRecurring)
		{
			unset($fields['discountable']);

			/**
			 * If the initial price is zero (or not overridden), we have to unset the custom initial pricing field. This
			 * is due to Paddle's web UI always setting the initial price to 0.00 when you edit a subscription plan,
			 * i.e. leaving the value empty always results in a zero initial payment.
			 */
			if (is_null($initial_price) || ($initial_price < 0.01))
			{
				unset($fields['prices']);
			}
			/**
			 * When we have a non-zero initial price we need to send it to Paddle explicitly.
			 */
			else
			{
				$fields['prices'] = [
					$this->container->params->get('currency') . ':' . sprintf('%0.2f', $initial_price),
				];
			}

			/**
			 * If we have a trial_days override we need to pass that along to Paddle.
			 */
			if (!is_null($trial_days) && ($trial_days > 0))
			{
				$fields['trial_days'] = $trial_days;
			}
		}
		else
		{
			// Do not override the price and Paddle coupons on full price subscriptions
			if ($sub->discount_amount < 0.01)
			{
				unset($fields['prices']);
				unset($fields['discountable']);
			}
		}

		// Add country from the user's profile
		$country = $this->getCountry($user);

		/**
		 * We only send the country if it does NOT fall into one of the following categories:
		 * - Empty value -- very old record with no country attached
		 * - 'XX'        -- invalid country, possibly from the obsolete import users feature
		 * - 'AF'        -- Afghanistan. Most likely that's an idiot who couldn't get arsed to enter their real country
		 *                  and selected the first item on the list, resulting in an invalid record (and having them
		 *                  essentially break the law because they are tax evading!).
		 */
		if (!empty($country) && ($country != 'XX') && ($country != 'AF'))
		{
			$fields['customer_country'] = $country;
		}

		// Here we make the request to the Paddle API
		$url = 'https://vendors.paddle.com/api/2.0/product/generate_pay_link';
		$ch  = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		$response = curl_exec($ch);

		$curlErrNo  = curl_errno($ch);
		$curlErrMsg = curl_error($ch);

		if ($curlErrNo)
		{
			throw new RuntimeException(sprintf('cURL error %u: %s', $curlErrNo, $curlErrMsg));
		}

		// And handle the response...
		$data = json_decode($response);

		if (empty($data) || !is_object($data) || !isset($data->success))
		{
			throw new RuntimeException('JSON decoding error');
		}

		if ($data->success)
		{
			return $data->response->url;
		}

		throw new RuntimeException('Paddle error: ' . $data->error->message);
	}

	private function getCountry(User $user): ?string
	{
		$db = $this->container->db;
		$query = $db->getQuery(true)
			->select([
				$db->qn('profile_value')
			])->from($db->qn('#__user_profiles'))
			->where($db->qn('user_id') . ' = ' . $db->q($user->id))
			->where($db->qn('profile_key') . ' = ' . $db->q('akeebasubs.country'));

		try
		{
			return $db->setQuery($query)->loadResult();
		}
		catch (\Exception $e)
		{
			return null;
		}
	}
}