<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Levels;
use FOF30\Container\Container;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\String\StringHelper;

class plgContentAsprice extends CMSPlugin
{
	/**
	 * List of subscription level titles to subscription level IDs
	 *
	 * @var   array
	 */
	protected static $levels = null;

	/**
	 * List of UPPERCASE slugs to subscription level IDs
	 *
	 * @var   string[]
	 */
	protected static $upperSlugs = null;

	/**
	 * List of levelID to an array ['product_id' => 1234, 'plan_id' => 5678] where
	 * -- product_id  is the one-off Paddle product's ID
	 * -- plan_id     is the recurring Paddle subscription plan's ID
	 * If either is not set it will contain NULL instead of an integer.
	 *
	 * @var   array
	 * @since 7.0.0
	 */
	protected static $levelIdToProductIds = null;

	/**
	 * Maps subscription level IDs to pricing information
	 *
	 * @var   array
	 */
	protected static $prices = null;

	/** @var bool Should I localise prices? Mirrors localisePrice component option setting. */
	protected static $localisePrices;

	/**
	 * Should this plugin be allowed to run? True if FOF can be loaded and the Akeeba Subscriptions component is enabled
	 *
	 * @var  bool
	 */
	private $enabled = true;

	/**
	 * Plugin constructor
	 *
	 * @param   object  &$subject  Used by the parent constructor
	 * @param   array   $config    Used by the parent constructor
	 *
	 * @return  void
	 */
	public function __construct(&$subject, $config = array())
	{
		// Make sure FOF is loaded
		if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
		{
			$this->enabled = false;
		}

		// Do not run if Akeeba Subscriptions is not enabled
		if (!ComponentHelper::isEnabled('com_akeebasubs'))
		{
			$this->enabled = false;
		}

		$this->loadLanguage('plg_content_asprice');

		parent::__construct($subject, $config);
	}

	/**
	 * Initializes all the level information. It's not part of the constructor to allow for deferred initialization
	 * right before we first need it.
	 *
	 * @return  void
	 *
	 * @since   7.0.0
	 */
	private static function initializeLevelInformation(): void
	{
		$container = Container::getInstance('com_akeebasubs');

		/** @var Levels $levelsModel */
		$levelsModel = $container
			->factory
			->model('Levels')
			->tmpInstance();

		self::$levels               = [];
		self::$upperSlugs           = [];
		self::$levelIdToProductIds  = [];
		$productIDs                 = [];

		$list = $levelsModel->get(true);

		if (!count($list))
		{
			return;
		}

		/** @var Levels $level */
		foreach ($list as $level)
		{
			$thisTitle                                  = strtoupper($level->title);
			self::$levels[$thisTitle]                   = $level->akeebasubs_level_id;
			self::$upperSlugs[strtoupper($level->slug)] = $level->akeebasubs_level_id;

			$productRecord = [
				'product_id' => null,
				'plan_id'    => null,
			];

			if ($level->paddle_product_id)
			{
				$productIDs[]                = $level->paddle_product_id;
				$productRecord['product_id'] = $level->paddle_product_id;
			}

			if ($level->paddle_plan_id)
			{
				$productIDs[]             = $level->paddle_plan_id;
				$productRecord['plan_id'] = $level->paddle_plan_id;
			}

			self::$levelIdToProductIds[$level->akeebasubs_level_id] = $productRecord;
		}
	}

	/**
	 * Process the {asprice LEVELTITLE} markup. Used with preg_replace_callback.
	 *
	 * @param   array  $match  A match to the {asprice} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processPrice($match): string
	{
		// Fetch a list of subscription levels if we haven't done so already
		if (is_null(self::$levels))
		{
			self::initializeLevelInformation();
		}

		$levelId = self::getId($match[1]);

		if ($levelId <= 0)
		{
			return '';
		}

		$pricingInfo = self::getPrice($levelId);

		$js = $pricingInfo['js']['oneoff'];

		if (!empty($js))
		{
			$js        = "\nwindow.jQuery(document).ready(function($) {" . $js . "});\n";
			$container = Container::getInstance('com_akeebasubs');
			$container->template->addJSInline($js);
		}

		return $pricingInfo['oneoff'];
	}

	/**
	 * Process the {aspricerecurring LEVELTITLE} markup. Used with preg_replace_callback.
	 *
	 * @param   array  $match  A match to the {asprice} plugin tag
	 *
	 * @return  string  The processed result
	 */
	private static function processPriceRecurring($match): string
	{
		// Fetch a list of subscription levels if we haven't done so already
		if (is_null(self::$levels))
		{
			self::initializeLevelInformation();
		}

		$levelId = self::getId($match[1]);

		if ($levelId <= 0)
		{
			return '';
		}

		$pricingInfo = self::getPrice($levelId);

		$js = $pricingInfo['js']['recurring'];

		if (!empty($js))
		{
			$js        = "\nwindow.jQuery(document).ready(function($) {" . $js . "});\n";
			$container = Container::getInstance('com_akeebasubs');
			$container->template->addJSInline($js);
		}

		return $pricingInfo['recurring'];
	}

	/**
	 * Gets the numeric level ID given a level title, slug or level ID in $title.
	 *
	 * @param   string  $title  The subscription level title, slug or ID
	 *
	 * @return  int  The subscription level ID or slug, or -1 if not found
	 */
	private static function getId(string $title): int
	{
		// Don't process invalid titles
		if (empty($title))
		{
			return -1;
		}

		$title = strtoupper($title);

		if (array_key_exists($title, self::$levels))
		{
			return self::$levels[$title];
		}

		if (array_key_exists($title, self::$upperSlugs))
		{
			return self::$upperSlugs[$title];
		}

		if (!is_numeric($title))
		{
			return -1;
		}

		$id    = (int) $title;

		if (in_array($id, self::$levels))
		{
			return $id;
		}

		// No match!
		return -1;
	}

	/**
	 * Get the formatted net price in the default currency from the subscription level information.
	 *
	 * @param   int  $levelId  The level ID
	 *
	 * @return  string  The formatted price
	 *
	 * @since   7.0.0
	 */
	private static function getPriceFromLevel(int $levelId): string
	{
		$container = Container::getInstance('com_akeebasubs', [], 'site');

		/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
		$level = $container->factory->model('Levels');
		$level->load($levelId);

		$price = '';

		if (($level->price < 0.01) && $container->params->get('renderasfree', 0))
		{
			return Text::_('COM_AKEEBASUBS_LEVEL_LBL_FREE');
		}

		if ($container->params->get('currencypos', 'before') == 'before')
		{
			$price .= $container->params->get('currencysymbol', '€');
		}

		$price .= sprintf('%1.02F', $level->price);

		if ($container->params->get('currencypos', 'before') == 'after')
		{
			$price .= $container->params->get('currencysymbol', '€');
		}

		return $price;
	}

	/**
	 * Get pricing information for a level. Each time you call this you will get different results because the HTML IDs
	 * generated are meant to be unique.
	 *
	 * @param   int  $levelId  The subscription level ID
	 *
	 * @return  array  Pricing information
	 *
	 * @since   7.0.0
	 */
	private static function getPrice(int $levelId): array
	{
		static $loadedJs = false;

		// Initialization
		$ret = [
			'oneoff'    => self::getPriceFromLevel($levelId),
			'recurring' => '',
			'js'        => [
				'oneoff'    => null,
				'recurring' => null,
			],
		];

		// Get the Paddle product information
		$productIDInfo = self::$levelIdToProductIds[$levelId];
		$product_id    = $productIDInfo['product_id'];
		$plan_id       = $productIDInfo['plan_id'];

		// Load the Paddle JS if necessary
		if (((!empty($product_id) && self::$localisePrices) || !empty($plan_id)) && !$loadedJs)
		{
			// Make sure jQuery is actually loaded
			HTMLHelper::_('jquery.framework');

			$loadedJs  = true;
			$container = Container::getInstance('com_akeebasubs');
			$vendor    = $container->params->get('vendor_id');
			$setupJS   = <<< JS
window.jQuery('document').ready(function(){
	Paddle.Setup({
		vendor: $vendor
	});
});

JS;
			$container->template->addJS('https://cdn.paddle.com/paddle/paddle.js', false, false, $container->mediaVersion);
			$container->template->addJS('media://com_akeebasubs/js/signup.js', false, false, $container->mediaVersion);
			$container->template->addJSInline($setupJS);

			$langStrings = [
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_ONE_DAY',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_DAY',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_ONE_WEEK',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_WEEK',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_ONE_MONTH',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_MONTH',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_ONE_YEAR',
				'COM_AKEEBASUBS_LEVEL_LBL_OPTIN_RECURRING_PERIOD_YEAR',
			];
			array_walk($langStrings, [\Joomla\CMS\Language\Text::class, 'script']);
		}

		// Add localised one-off pricing
		if (!is_null($product_id) && self::$localisePrices)
		{
			$htmlId              = 'akeebasubs-plg-price-' . $product_id . '-' . self::uuid_v4();
			$ret['js']['oneoff'] = <<< JS
akeebasubsLocalisePrice('$product_id', false, null, null, '$htmlId', null, null);

JS;
			$ret['oneoff']       = <<< HTML
<span id="$htmlId">{$ret['oneoff']}</span>
HTML;
		}

		// Add localised recurring pricing
		if (!is_null($plan_id))
		{
			$priceContainerId       = 'akeebasubs-plg-price-' . $plan_id . '-' . self::uuid_v4();
			$frequencyContainerId   = 'akeebasubs-plg-price-frequency-' . $plan_id . '-' . self::uuid_v4();
			$ret['js']['recurring'] = <<< JS
akeebasubsLocaliseRecurringPriceOnly('$plan_id', false, '$priceContainerId', '$frequencyContainerId');

JS;
			$loading                = Text::_('PLG_CONTENT_ASPRICE_LBL_LOADING');
			$every                  = Text::_('PLG_CONTENT_ASPRICE_LBL_EVERY');
			$ret['recurring']       = <<< HTML
<span id="$priceContainerId">$loading</span> $every <span id="$frequencyContainerId">$loading</span> 
HTML;

		}

		return $ret;
	}

	/**
	 * Handles the content preparation event fired by Joomla!
	 *
	 * @param   mixed     $context     Unused in this plugin.
	 * @param   stdClass  $article     An object containing the article being processed.
	 * @param   mixed     $params      Unused in this plugin.
	 * @param   int       $limitstart  Unused in this plugin.
	 *
	 * @return  bool
	 */
	public function onContentPrepare($context, &$article, &$params, $limitstart = 0): bool
	{
		if (!$this->enabled)
		{
			return true;
		}

		if (is_null(self::$localisePrices))
		{
			$container            = Container::getInstance('com_akeebasubs');
			self::$localisePrices = $container->params->get('localisePrice', 1) == 1;
		}

		$accceptableActions = [
			'asprice',
		];

		$mustProcess = false;

		foreach ($accceptableActions as $action)
		{
			// Check whether the plugin should process or not
			if (StringHelper::strpos($article->text, $action) !== false)
			{
				$mustProcess = true;

				break;
			}
		}

		if (!$mustProcess)
		{
			return true;
		}

		// {asprice MYLEVEL} ==> 10.00€
		$regex         = "#{asprice (.*?)}#s";
		$article->text = preg_replace_callback($regex, ['self', 'processPrice'], $article->text);

		// {aspricerecurring MYLEVEL} ==> 10.00€ for 365 days, then 0.56€ / 3 months
		$regex = "#{aspricerecurring (.*?)}#s";
		$article->text = preg_replace_callback($regex, array('self', 'processPriceRecurring'), $article->text);

		return true;
	}

	/**
	 * Generate a UUID v4
	 *
	 * @return  string
	 *
	 * @since   7.0.0
	 */
	private static function uuid_v4(): string
	{
		$data    = Crypt::genRandomBytes(16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

}
