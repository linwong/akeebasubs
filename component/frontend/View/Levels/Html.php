<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Levels;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Price;
use Akeeba\Subscriptions\Site\Model\Levels;

class Html extends \FOF30\View\DataView\Html
{
	/**
	 * List of subscription IDs of the current user
	 *
	 * @var  int[]
	 */
	public $subIDs = [];

	/**
	 * Should I render prices of 0 as "FREE"?
	 *
	 * @var   bool
	 */
	public $renderAsFree = false;

	/**
	 * Should I display notices about
	 *
	 * @var   bool
	 */
	public $showNotices = true;

	/**
	 * Should I localise prices?
	 *
	 * @var   bool
	 * @since 7.0.0
	 */
	public $localisePrice = true;

	/**
	 * Should I include tax in localised prices?
	 *
	 * @var   bool
	 * @since 7.0.0
	 */
	public $isTaxAllowed = true;

	/**
	 * Cache of pricing information per subscription level, required to cut down on queries in the Strappy layout.
	 *
	 * @var  object[]
	 */
	protected $pricingInformationCache = [];

	public function applyViewConfiguration()
	{
		// Transfer the parameters from the helper to the View
		$params = Price::getPricingParameters();

		$this->subIDs          = Price::getSubIDs();
		$this->renderAsFree    = $params->renderAsFree;

		$this->localisePrice    = $this->container->params->get('localisePrice', 1);
		$this->isTaxAllowed     = $this->localisePrice && $this->container->params->get('showEstimatedTax', 1);
	}

	/**
	 * Executes before rendering the page for the Browse task.
	 */
	protected function onBeforeBrowse()
	{
		$this->setLayout('default');
		$this->applyViewConfiguration();

		parent::onBeforeBrowse();
	}

	/**
	 * Returns the pricing information for a subscription level. Used by the view templates to avoid code duplication.
	 *
	 * @param   \Akeeba\Subscriptions\Site\Model\Levels  $level  The subscription level
	 *
	 * @return  object
	 */
	public function getLevelPriceInformation(Levels $level)
	{
		return Price::getLevelPriceInformation($level);
	}
}
