<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model;


use FOF30\Container\Container;
use FOF30\Model\DataModel\Collection as DataCollection;
use FOF30\Model\Model;
use FOF30\Utils\Collection;
use Joomla\CMS\User\User;

class MySubs extends Model
{
	/**
	 * The user this model is concerned with
	 *
	 * @var   User
	 * @since 7.0.0
	 */
	private $user;

	/**
	 * All subscriptions belonging to the current user as MySubscriptions objects
	 *
	 * @var   DataCollection
	 * @since 7.0.0
	 */
	private $items;

	/**
	 * All the subscription levels the user has ever had a transaction in
	 *
	 * @var   DataCollection
	 * @since 7.0.0
	 */
	private $levels;

	/**
	 * MySubs constructor. Use the 'user' config key to pass the Joomla user object.
	 *
	 * @param Container $container Component container
	 * @param array     $config    Model configuration.
	 */
	public function __construct(Container $container, array $config = [])
	{
		parent::__construct($container, $config);

		$this->setUser(isset($config['user']) ? $config['user'] : null);
		$this->setItems(isset($config['items']) ? $config['items'] : null);

		$this->levels = $this->getLevels($this->items);
	}

	public function getDisplayData(): Collection
	{
		return $this->levels
			->sortBy(function (Levels $level) {
				return $level->ordering;
			})
			->map(function (Levels $level) {
				$isRecurring        = $this->isRecurring($level);
				$isForever          = $level->forever;
				$isFixedDate        = !empty($level->fixed_date) && ($level->fixed_date != $level->getDbo()->getNullDate());
				$latestRecurringSub = $this->getLastActiveRecurringSubscription($level);
				$relatedSub         = $this->getRelatedSubscription($level);
				$relatedType        = 'none';

				if (!is_null($relatedSub))
				{
					$relatedType = 'downgrade';

					if (in_array($relatedSub->akeebasubs_level_id, $level->upgrades->lists('akeebasubs_level_id')))
					{
						$relatedType = 'upgrade';
					}
				}

				$subsInThisLevel = (clone $this->items)
					->filter(function (Subscriptions $subscription) use ($level) {
						return $subscription->akeebasubs_level_id == $level->getId();
					})->sortByDesc(function (Subscriptions $subscription) {
						$this->container->platform->getDate($subscription->publish_down)->getTimestamp();
					}, SORT_NUMERIC);

				/** @var Subscriptions $latestSubOnThisLevel */
				$latestSubOnThisLevel = $subsInThisLevel->first();
				$levelStatus          = $this->getLevelStatus($level);

				/**
				 * We allow fixed date subscription renewals if the level's fixed expiration date is in the future of
				 * the latest subscription's expiration date. We allow a small drift of 1 day to take into account the
				 * possibility of having accidentally stripped the time component of either the subscription's
				 * expiration date or the level's fixed expiration date.
				 */
				if ($isFixedDate)
				{
					$now           = time();
					$level_expires = $this->container->platform->getDate($level->fixed_date)->getTimestamp();
					$publish_down  = $this->container->platform->getDate($latestSubOnThisLevel->publish_down)->getTimestamp();
					/**
					 * The level's expiration date must be in the future. There's no point renewing to an immediately
					 * expired subscription!
					 */
					$inTheFuture = $level_expires - $now >= (24 * 3600);
					/**
					 * Then we have to make sure that the latest subscription expires BEFORE the subscription level's
					 * expiration date. Otherwise we would allow people to subscribe again with the same expiration
					 * date which is pointless.
					 */
					$levelExpiresAfterSubscriptionExpires = ($publish_down - $level_expires) >= (24 * 3600);
					/**
					 * If either condition is not met we consider this an un-renewable fixed date sub
					 */
					$isFixedDate                          = !$inTheFuture || !$levelExpiresAfterSubscriptionExpires;
				}


				return [
					// Subscription level
					'level'        => $level,
					// Overall status (what the user perceives as their subscription status)
					'status'       => $levelStatus,
					// Latest subscription, by expiration date
					'latest'       => $latestSubOnThisLevel,
					// Recurring charges information
					'recurring'    => [
						// Has the client purchase a recurring subscription?
						'is_recurring' => $isRecurring,
						// Billing update URL
						'update_url'   => $isRecurring ? $latestRecurringSub->update_url : null,
						// Cancelation URL
						'cancel_url'   => $isRecurring ? $latestRecurringSub->cancel_url : null,
					],
					// Information about subscription upgrades and downgrades
					'related'      => [
						// Has the client purchased upgrades/downgrades? Values: none, upgrade, downgrade
						'status'      => $relatedType,
						// The (latest) upgrade / downgrade subscription purchased by the client
						'related_sub' => $relatedSub,
					],
					// Buttons to display at the bottom of the subscription level info
					'buttons'      => [
						// Let me repurchase a canceled / expired subscription?
						'purchase' => ($relatedType == 'none') && in_array($levelStatus, [
								'expired', 'canceled',
							]) && !$isRecurring && !$isForever && !$isFixedDate && ($level->enabled),
						// Let me renew this subscription?
						'renew'    => ($relatedType == 'none') && in_array($levelStatus, [
								'active', 'waiting',
							]) && !$isRecurring && !$isForever && !$isFixedDate && ($level->enabled),
					],
					// All transactions in chronological creation order
					'transactions' => $subsInThisLevel->sortByDesc(function (Subscriptions $subscription) {
						$this->container->platform->getDate($subscription->created_on)->getTimestamp();
					}),
				];
			})->sortBy(function (array $levelInfo) {
				if (!$levelInfo['level']->enabled)
				{
					return 600;
				}

				switch ($levelInfo['status'])
				{
					case 'active':
						return 100;
					case 'waiting':
						return 200;
					case 'pending':
						return 300;
					case 'new':
						return 400;
					case 'expired':
						return 500;
					case 'canceled':
						return 1000;
				}
			})->filter(function (array $levelInfo) {
				return !$levelInfo['transactions']->isEmpty();
			});
	}

	/**
	 * Set the active user for this class
	 *
	 * @param User|null $user
	 *
	 * @since 7.0.0
	 */
	protected function setUser(?User $user): void
	{
		if (empty($user))
		{
			$user = $this->container->platform->getUser();
		}

		$this->user = $user;
	}

	/**
	 * Set an arbitrary list of subscriptions
	 *
	 * @param DataCollection|null $collection
	 *
	 * @since 7.0.0
	 */
	protected function setItems(?DataCollection $collection)
	{
		if (is_null($collection))
		{
			$collection = $this->getSusbcriptionsForUser($this->user);
		}

		$this->items = $collection;
	}

	/**
	 * Get all subscription items for the specified user
	 *
	 * @param User $user The Joomla user object
	 *
	 * @return DataCollection
	 *
	 * @since 7.0.0
	 */
	protected function getSusbcriptionsForUser(User $user): DataCollection
	{
		if ($user->guest)
		{
			return new DataCollection();
		}

		/** @var Subscriptions $subsModel */
		$subsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

		return $subsModel
			->user_id($user->id)
			->get(true);
	}

	/**
	 * Get all subscription levels the user has ever had a transaction in.
	 *
	 * @return DataCollection
	 *
	 * @since  7.0.0
	 */
	protected function getLevels(DataCollection $items): DataCollection
	{
		/** @var Levels $levelsModel */
		$levelsModel = $this->container->factory->model('Levels')->tmpInstance();

		return $levelsModel->id(
			$items->lists('akeebasubs_level_id')
		)->get(true);
	}

	/**
	 * Get the subscription status for an entire level, in the sense that this is how the user perceives it.
	 *
	 * @param Levels $level
	 *
	 * @return string
	 *
	 * @since 7.0.0
	 */
	protected function getLevelStatus(Levels $level): string
	{
		$allStatuses = (clone $this->items)
			->filter(function (Subscriptions $sub) use ($level) {
				return $sub->akeebasubs_level_id == $level->getId();
			})
			->lists('status', 'akeebasubs_subscription_id');

		/**
		 * At least one transaction is active, therefore the subscription is active.
		 *
		 * The active subscription may be a canceled recurring which has not reached its expiration date. We need to
		 * still report it as 'active'. There is a further area below the subscription about the recurring status.
		 */
		if (in_array('active', $allStatuses))
		{
			return 'active';
		}

		/**
		 * There are no active transactions BUT there's a paid one which will start counting in the future. This is
		 * an upgrade or a downgrade to an existing subscription.
		 */
		if (in_array('waiting', $allStatuses))
		{
			return 'waiting';
		}

		/**
		 * No active or waiting transactions BUT there's one marked as pending (payment being processed). Pending
		 * payments block renewals / upgrades.
		 */
		if (in_array('pending', $allStatuses))
		{
			return 'pending';
		}

		/**
		 * There are no active, waiting or pending transactions BUT there's one that's not yet paid.
		 *
		 * Note that the Subscriptions model automatically removes unpaid transactions over a configurable amount of
		 * time old (default: 7 days). Moreover, the Subscribe model will reuse / replace New transactions if the client
		 * retries subscribing. Therefore it's guaranteed my list is not forever "spammed" with unpaid transactions.
		 */
		if (in_array('new', $allStatuses))
		{
			return 'new';
		}

		/**
		 * There are no active, waiting, pending or new transactions BUT there is at least one "expired" (and possible
		 * one or more "canceled"). As a result this is an expired subscription.
		 *
		 * Why not say it's canceled if the latest transaction is canceled? Maybe the user had a subscription, they
		 * tried to purchase a renewal and they either changed their mind or the transaction got canceled / refunded.
		 * In this case their subscription can reasonably be called "expired" since the reason they don't have service
		 * is that they failed to purchase a renewal (for whatever reason).
		 */
		if (in_array('expired', $allStatuses))
		{
			return 'expired';
		}

		/**
		 * There are no other transaction types except "canceled". So, it's a canceled subscription.
		 */
		return 'canceled';
	}

	/**
	 * Is this subscription level set up to automatically renew (recurring)?
	 *
	 * @param Levels $level
	 *
	 * @return bool
	 *
	 * @since 7.0.0
	 */
	protected function isRecurring(Levels $level): bool
	{
		/**
		 * Why not used $level->allowRecurring to filter out levels which don't support recurring purchases?
		 *
		 * Because the level may have used to support recurring purchases but the site owner decided to temporarily or
		 * permanently disable the recurring purchases. In this case the already purchased recurring subscriptions will
		 * continue to charge the client automatically and we still need to manage them.
		 */
		$allRecurringSubs = (clone $this->items)
			->filter(function (Subscriptions $sub) use ($level) {
				return $sub->recurring && ($sub->akeebasubs_level_id == $level->getId());
			});

		if ($allRecurringSubs->count() < 1)
		{
			return false;
		}

		return $allRecurringSubs->reduce(function ($carry, Subscriptions $sub) {
			if ($sub->enabled)
			{
				return true;
			}

			return $carry;
		}, false);
	}

	/**
	 * Get the earliest waiting subscription which is either an upgrade or a downgrade to the specified subscription
	 * level. Returns null if none is applicable.
	 *
	 * @param Levels $level
	 *
	 * @return Subscriptions|null
	 *
	 * @since 7.0.0
	 */
	protected function getRelatedSubscription(Levels $level): ?Subscriptions
	{
		$relatedLevels = $level->downgrades->lists('akeebasubs_level_id');

		$relatedSubs = empty($relatedLevels) ? (new DataCollection()) : (clone $this->items)
			->filter(function (Subscriptions $subscription) use ($relatedLevels) {
				return in_array($subscription->akeebasubs_level_id, $relatedLevels) && ($subscription->status == 'waiting');
			});

		if ($relatedSubs->count() < 1)
		{
			$relatedLevels = $level->upgrades->lists('akeebasubs_level_id');

			$relatedSubs = empty($relatedLevels) ? (new DataCollection()) : (clone $this->items)
				->filter(function (Subscriptions $subscription) use ($relatedLevels) {
					return in_array($subscription->akeebasubs_level_id, $relatedLevels) && ($subscription->status == 'waiting');
				});
		}

		return $relatedSubs->sortByDesc(function (Subscriptions $sub) {
			$this->container->platform->getDate($sub->publish_down)->getTimestamp();
		}, SORT_NUMERIC)->first();
	}

	/**
	 * Get the latest, active recurring subscription on a given subscription level
	 *
	 * @param Levels $level
	 *
	 * @return ?Subscriptions
	 *
	 * @since  7.0.0
	 */
	protected function getLastActiveRecurringSubscription(Levels $level): ?Subscriptions
	{
		return (clone $this->items)
			->filter(function (Subscriptions $sub) use ($level) {
				return $sub->recurring && ($sub->akeebasubs_level_id == $level->getId()) && $sub->enabled;
			})
			->sortByDesc(function (Subscriptions $subscription) {
				$this->container->platform->getDate($subscription->publish_down)->getTimestamp();
			}, SORT_NUMERIC)
			->first();
	}

}