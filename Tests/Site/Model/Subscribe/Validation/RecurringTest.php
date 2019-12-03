<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Recurring;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorWithSubsTestCase;
use DateInterval;
use FOF30\Date\Date;
use FOF30\Utils\Ip;
use Joomla\CMS\Http\Response;

/**
 * Test the Recurring validator
 *
 * @covers \Akeeba\Subscriptions\Site\Model\Subscribe\Validation\RecurringTest
 */
class RecurringTest extends ValidatorWithSubsTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'Recurring';

		// Create the base objects
		parent::setUpBeforeClass();

		global $akeebasubsTestConfig;

		static::$container->params->setParams([
			'vendor_id'        => $akeebasubsTestConfig['vendor_id'],
			'vendor_auth_code' => $akeebasubsTestConfig['vendor_auth_code'],
		]);
		static::$container->params->save();

	}

	public function getTestData()
	{
		$jNow = new Date();

		// - 365 days ==> expires in 365 days
		$jLastYear = clone($jNow);
		$jLastYear->sub(new DateInterval('P365D'));

		// - 181 days ==> expires in 184 days
		$jLastHalfYear = clone($jNow);
		$jLastHalfYear->sub(new DateInterval('P181D'));

		// NOW ==> 365 remaining, 0 used

		// + 184 days ==> expires in 549 and change days
		$jNextHalfYear = clone($jNow);
		$jNextHalfYear->add(new DateInterval('P184D'));

		// + 365 days ==> expires in 730 days
		$jNextYear = clone($jNow);
		$jNextYear->add(new DateInterval('P365D'));

		/**
		 * This is an insane amount of tests and I mean it literally. The number of interactions between features we are
		 * allowing here is not sane and I am not even sure I have not forgotten something important. When I started
		 * writing these tests there were barely two dozen. Throughout the day I realised that this number was way too
		 * low and didn't cover a myriad of cases I can reasonably expect real-world clients to bump into.
		 *
		 * All that said and done, there are some cases that are too rare to programmatically deal with. For example,
		 * what happens if someone has bought renewals for level X for the next five years but suddenly decides to
		 * upgrade to level Y? Conversely, what happens if the client has already purchased a renewal to a bundle but
		 * now wants to downgrade? What happens when the client wants to downgrade but there's no level relation to tell
		 * the system to start the downgrade subscription after the currently active bundle subscription is over?
		 *
		 * The last case is simple: just create the damn relationship! The first two are nigh impossible to reasonably
		 * handle and the only answer that makes sense is "talk to a human and figure this shit out".
		 *
		 * Still, I have that feeling that I'm missing something. But what…?
		 */
		$testCases = [
			//<editor-fold desc="Renewal = never">
			'Non-recurring level, guest'                                                                                     => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '1',
					'coupon'  => '',
					'_upsell' => 'never',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],
			'Non-recurring level, no sub'                                                                                    => [
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id'      => '1',
					'coupon'  => '',
					'_upsell' => 'never',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],
			'Non-recurring level, with sub'                                                                                  => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 3,
						'publish_up' => $jLastHalfYear->toSql(),
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => '',
					'_upsell' => 'never',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],
			'Non-recurring level, with sub and coupon code'                                                                  => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 3,
						'publish_up' => $jLastHalfYear->toSql(),
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RECURSION',
					'_upsell' => 'never',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],
			//</editor-fold>

			//<editor-fold desc="Subscription level upgrades">
			// Purchasing bundle, always recurring, already recurringly subscribed to single product level => blocked sub
			'Purchasing bundle, always recurring, already recurringly subscribed to single product level'                    => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
						'update_url' => 'foobar',
						'cancel_url' => 'foobar',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => '',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => [
						'S1',
					],
				],
			],
			'Purchasing bundle, always recurring, already recurringly subscribed to single product level, coupon is ignored' => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
						'update_url' => 'foobar',
						'cancel_url' => 'foobar',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => 'RECURRING',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => [
						'S1',
					],
				],
			],
			// Purchasing bundle, always recurring, already subscribed to single product level => discount
			'Purchasing bundle, always recurring, already subscribed to single product level'                                => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => '',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556729',
					'initial_price'             => 37.50,
					'recurring_price'           => 11.25,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365,
					// Since it's a level upgrade user pays for 1 year, then recurring
					'blocking_subscription_ids' => null,
				],
			],
			// Purchasing bundle, always recurring, already subscribed to single product level, has coupon => coupon IGNORED
			'Purchasing bundle, always recurring, already subscribed to single product level, has coupon'                    => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => 'RECURRING',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556729',
					'initial_price'             => 37.50,
					'recurring_price'           => 11.25,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365,
					// Since it's a level upgrade user pays for 1 year, then recurring
					'blocking_subscription_ids' => null,
				],
			],

			// Purchasing bundle, renew recurring, already recurringly subscribed to single product level => blocked sub
			'Purchasing bundle, renew recurring, already recurringly subscribed to single product level'                     => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
						'update_url' => 'foobar',
						'cancel_url' => 'foobar',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => '',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => [
						'S1',
					],
				],
			],
			'Purchasing bundle, renew recurring, already recurringly subscribed to single product level, coupon is ignored'  => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
						'update_url' => 'foobar',
						'cancel_url' => 'foobar',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => 'RECURRING',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => [
						'S1',
					],
				],
			],
			'Purchasing bundle, renew recurring, already subscribed to single product level, not recurring'                  => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => '',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,
				],
			],
			'Purchasing bundle, renew recurring, already subscribed to single product level, coupon allows recurring'        => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => (clone $jNow)->sub(new DateInterval('P10D'))->toSql(),
						'enabled'    => 1,
						'state'      => 'C',
					],
				],
				'state'    => [
					'id'      => '3',
					'coupon'  => 'RECURRING',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => '556729',
					'initial_price'             => 37.50,
					'recurring_price'           => 11.25,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365,
					// Since it's a level upgrade user pays for 1 year, then recurring
					'blocking_subscription_ids' => null,
				],
			],

			/**
			 * Here is something I will not test:
			 *
			 * Two subscriptions on level 1 (current + renewal), upgrading to level 3. Level 3 is set to always
			 * recurring.
			 *
			 * The reasonable expectation is that the client pays nothing and his upgraded subscription starts
			 * immediately, then gets recurring payments after a year (or however the prorated start of the subscription
			 * is).
			 *
			 * Reality: I cannot make the subscription level relations give the correct discount and calculate the
			 * correct expiration date without screwing up the most common cases. In these rare cases I'd rather have
			 * the client contact me so I can manually upgrade their subscription. Then they can "purchase" a recurring
			 * renewal which simply activates recurring payments after their manually upgraded subscription expires,
			 * using the previous rules for "always".
			 *
			 * There are limits to my insanity!
			 */
			//</editor-fold>

			//<editor-fold desc="Subscription level downgrades">
			/**
			 * Downgrade with a subscription level relation, without a coupon.
			 *
			 * User purchased a recurring level 3 subscription 60 days ago. He canceled it, therefore it now appears as
			 * a non-recurring subscription from 60 days ago, expiring in 29 days from now.
			 *
			 * He now tries to buy a subscription on level 1. There's a subscription relation rule for downgrades which
			 * gives zero discount and is of the "after" type. The intent is that the purchased downgrade will get
			 * activated when the current Level 3 subscription expires.
			 *
			 * Therefore I need to have a recurring subscription with a trial period of 29 + 365 days and initial
			 * payment equal to level 1's full price.
			 *
			 * NOTE: Assisted downgrades require a subscription relation with mode "rules" and expiration "after".
			 */
			'Downgrade with a subscription level relation, without a coupon.'                                                => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => '',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 50.00,
					'recurring_price'           => 11.16,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],
			'Downgrade with a subscription level relation, with a coupon.'                                                   => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RECURRING',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 50.00,
					'recurring_price'           => 11.16,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],
			'Downgrade with a subscription level relation, target level has "renew" upsell, without a coupon.'               => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => '',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 50.00,
					'recurring_price'           => 11.16,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],
			'Downgrade with a subscription level relation, target level has "renew" upsell, with a coupon.'                  => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RECURRING',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 50.00,
					'recurring_price'           => 11.16,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],
			//</editor-fold>

			//<editor-fold desc="Discount coupons mixed with recurring payments">
			// Always recurring, new sub, discount coupon RENEWALDISCOUNT  => recurring sub with a discounted initial price, no tax included
			'Always recurring, new sub, discount coupon'                                                => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RENEWALDISCOUNT',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 30.00,
					'recurring_price'           => 9.00,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365,
					'blocking_subscription_ids' => null,
				],
			],
			// Always recurring, new sub, discount + recurring access coupon IAMCRAZY => recurring sub with no initial price, tax included
			'Always recurring, new sub, discount + recurring access coupon'                                                => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'IAMCRAZY',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 0.00,
					'recurring_price'           => 11.16,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,
				],
			],
			// Always recurring, downgrade with discount coupon RENEWALDISCOUNT => recurring sub with a discounted initial price and modified trial period, no tax included
			'Always recurring, downgrade with discount coupon'                                                => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RENEWALDISCOUNT',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 30.00,
					'recurring_price'           => 9.00,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],
			// Always recurring, downgrade with discount coupon + recurring access coupon IAMCRAZY => recurring sub with a discounted initial price and modified trial period, no tax included
			'Always recurring, downgrade with discount coupon + recurring access coupon'                                                => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'IAMCRAZY',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 30.00,
					'recurring_price'           => 9.00,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],

			// Renew recurring, new sub, discount coupon RENEWALDISCOUNT => not recurring
			'Renew recurring, new sub, discount coupon'                                                => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RENEWALDISCOUNT',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,
				],
			],
			// Renew recurring, new sub, discount + recurring access coupon IAMCRAZY => recurring sub with discounted initial price, tax included
			'Renew recurring, new sub, discount + recurring access coupon'                                                => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'IAMCRAZY',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 0.00,
					'recurring_price'           => 11.16,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,
				],
			],
			// Renew recurring, downgrade with discount coupon RENEWALDISCOUNT => recurring sub with a discounted initial price and modified trial period, no tax included
			'Renew recurring, downgrade with discount coupon'                                                => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'RENEWALDISCOUNT',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 30.00,
					'recurring_price'           => 9.00,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],
			// Renew recurring, downgrade with discount coupon + recurring access coupon IAMCRAZY => recurring sub with a discounted initial price and modified trial period, no tax included
			'Renew recurring, downgrade with discount coupon + recurring access coupon' => [
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'        => 3,
						'publish_up'   => (clone $jNow)->sub(new DateInterval('P60D'))->toSql(),
						'publish_down' => (clone $jNow)->add(new DateInterval('P29D'))->toSql(),
						'enabled'      => 1,
						'state'        => 'C',
					],
				],
				'state'    => [
					'id'      => '1',
					'coupon'  => 'IAMCRAZY',
					'_upsell' => 'renew',
				],
				'expected' => [
					'recurringId'               => '556090',
					'initial_price'             => 30.00,
					'recurring_price'           => 9.00,
					'recurring_frequency'       => 3,
					'recurring_type'            => 'month',
					'trial_days'                => 365 + 28,
					'blocking_subscription_ids' => null,
				],
			],

			//</editor-fold>


			//<editor-fold desc="Special subscription levels prohibiting renewals">

			// Forever subscription (FOREVER #7) => always NOT recurring
			'Forever subscription'                                                                                           => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '7',
					'coupon'  => '',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],
			// Only once level (ONLYONCE #8) => always NOT recurring
			'Only Once'                                                                                                      => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '8',
					'coupon'  => '',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],
			// Fixed expiration (FIXED #9) date subscription => always NOT recurring
			'Fixed expiration'                                                                                               => [
				'loggedIn' => 'guest',
				'subs'     => [],
				'state'    => [
					'id'      => '9',
					'coupon'  => '',
					'_upsell' => 'always',
				],
				'expected' => [
					'recurringId'               => null,
					'initial_price'             => 0.00,
					'recurring_price'           => 0.00,
					'recurring_frequency'       => 0,
					'recurring_type'            => 'day',
					'trial_days'                => 0,
					'blocking_subscription_ids' => null,

				],
			],

			//</editor-fold>

			// TODO When a recurring sub is canceled mark it as NON-recurring so it doesn't screw up calculations.

		];

		foreach ($testCases as $message => &$testCase)
		{
			$testCase = array_merge($testCase, [
				'message' => $message,
			]);
		}

		return $testCases;
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($loggedIn, $subs, $state, $expected, $message)
	{
		/**
		 * Fake the HTTP response from Paddle. This lets our tests run without making contact with the Paddle server
		 *
		 * @param array $urlParams The URL parameters Akeeba Subs sends to Paddle's pricing API
		 *
		 * @return  Response|null  A Joomla! HTTP response
		 *
		 * @since   7.0.0
		 */
		Recurring::$callbackForUnitTests = function ($urlParams) {
			$response       = new Response();
			$response->code = 200;

			if (in_array(556090, explode(',', $urlParams['product_ids'])))
			{
				$response->body = '{"success":true,"response":{"customer_country":"GR","products":[{"product_id":556090,"product_title":"TEST DataCompliance","currency":"EUR","vendor_set_prices_included_tax":false,"price":{"gross":11.16,"net":9.0,"tax":2.16},"list_price":{"gross":11.16,"net":9.0,"tax":2.16},"subscription":{"trial_days":0,"interval":"month","frequency":3,"price":{"gross":11.16,"net":9.0,"tax":2.16},"list_price":{"gross":11.16,"net":9.0,"tax":2.16}}}]}}
';

				return $response;
			}

			if (in_array(556729, explode(',', $urlParams['product_ids'])))
			{
				$response->body = '{"success":true,"response":{"customer_country":"GR","products":[{"product_id":556729,"product_title":"TEST Bundle","currency":"EUR","vendor_set_prices_included_tax":false,"price":{"gross":13.95,"net":11.25,"tax":2.70},"list_price":{"gross":13.95,"net":11.25,"tax":2.70},"subscription":{"trial_days":0,"interval":"month","frequency":3,"price":{"gross":13.95,"net":11.25,"tax":2.70},"list_price":{"gross":13.95,"net":11.25,"tax":2.70}}}]}}
';

				return $response;
			}

			return null;
		};

		/**
		 * Change the recurring subscription access type for the subscription level we will be using for our tests.
		 *
		 * If none is specified we set it to "renewal".
		 */
		$upsell = 'renewal';

		if (isset($state['_upsell']))
		{
			$upsell = $state['_upsell'] ?? 'renewal';
			unset($state['_upsell']);
		}

		/** @var Levels $level */
		$level = static::$container->factory->model('Levels')->tmpInstance();
		$level->findOrFail($state['id'] ?? 1);

		if ($level->upsell != $upsell)
		{
			// Only save stuff to the database if there's a need to, speeding up the tests.
			$level->bind([
				'upsell' => $upsell,
			])->save();
		}

		/**
		 * Fake our IP address to pretend we are in Greece. Not really used, unless we remove the
		 * Recurring::$callbackForUnitTests callback above.
		 */
		Ip::setIp('109.242.90.176');

		$this->createSubscriptions($subs);

		self::$jUser = self::$users[$loggedIn];
		self::$factory->reset();

		parent::testGetValidationResult($state, $expected, $message);
	}

	/**
	 * Perform the assertion(s) required for this test
	 *
	 * @param mixed  $expected Expected value
	 * @param mixed  $actual   Actual validator result
	 * @param string $message  Message to show on failure
	 *
	 * @return  void
	 */
	public function performAssertion($expected, $actual, $message)
	{
		if (!empty($expected['blocking_subscription_ids']))
		{
			$expected['blocking_subscription_ids'] = self::translateSubToId($expected['blocking_subscription_ids']);
		}

		parent::performAssertion($expected, $actual, $message);
	}

}
