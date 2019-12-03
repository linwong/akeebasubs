<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die();

// Load FOF if not already loaded
if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	throw new RuntimeException('This component requires FOF 3.0.');
}

class Com_AkeebasubsInstallerScript extends \FOF30\Utils\InstallScript
{
	/**
	 * The component's name
	 *
	 * @var   string
	 */
	protected $componentName = 'com_akeebasubs';

	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var string
	 */
	protected $componentTitle = 'Akeeba Subscriptions';

	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '7.3.0';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.9.0';

	/**
	 * Obsolete files and folders to remove from both paid and free releases. This is used when you refactor code and
	 * some files inevitably become obsolete and need to be removed.
	 *
	 * @var   array
	 */
	protected $removeFilesAllVersions = [
		'files'   => [
			'cache/com_akeebasubs.updates.php',
			'cache/com_akeebasubs.updates.ini',
			'administrator/cache/com_akeebasubs.updates.php',
			'administrator/cache/com_akeebasubs.updates.ini',

			'administrator/components/com_akeebasubs/install.akeebasubs.php',
			'administrator/components/com_akeebasubs/uninstall.akeebasubs.php',
			'administrator/components/com_akeebasubs/config.json',

			'components/com_akeebasubs/controllers/callback.php',
			'components/com_akeebasubs/controllers/config.php',
			'components/com_akeebasubs/controllers/default.php',
			'components/com_akeebasubs/controllers/juser.php',
			'components/com_akeebasubs/controllers/level.php',
			'components/com_akeebasubs/controllers/message.php',
			'components/com_akeebasubs/controllers/subscribe.php',
			'components/com_akeebasubs/controllers/subscription.php',
			'components/com_akeebasubs/controllers/taxrule.php',
			'components/com_akeebasubs/controllers/user.php',
			'components/com_akeebasubs/controllers/validate.php',
			'components/com_akeebasubs/views/level/html.php',
			'components/com_akeebasubs/views/subscribe/html.php',

			'media/com_akeebasubs/js/akeebajq.js',

			// Old fonts
			'media/com_akeebasubs/tcdpf/fonts/courier.php',
			'media/com_akeebasubs/tcdpf/fonts/courierb.php',
			'media/com_akeebasubs/tcdpf/fonts/courierbi.php',
			'media/com_akeebasubs/tcdpf/fonts/courieri.php',
			'media/com_akeebasubs/tcdpf/fonts/helvetica.php',
			'media/com_akeebasubs/tcdpf/fonts/helveticab.php',
			'media/com_akeebasubs/tcdpf/fonts/helveticabi.php',
			'media/com_akeebasubs/tcdpf/fonts/helveticai.php',
			'media/com_akeebasubs/tcdpf/fonts/symbol.php',
			'media/com_akeebasubs/tcdpf/fonts/times.php',
			'media/com_akeebasubs/tcdpf/fonts/timesbi.php',
			'media/com_akeebasubs/tcdpf/fonts/timesb.php',
			'media/com_akeebasubs/tcdpf/fonts/timesi.php',
			'media/com_akeebasubs/tcdpf/fonts/zapfdingbats.php',

			// Renamed between 5.0.0.b1 and 5.0.0 (plural to singular)
			'administrator/components/com_akeebasubs/Controller/Coupons.php',
			'administrator/components/com_akeebasubs/Controller/EmailTemplates.php',
			'administrator/components/com_akeebasubs/Controller/Invoices.php',
			'administrator/components/com_akeebasubs/Controller/Levels.php',
			'administrator/components/com_akeebasubs/Controller/Subscriptions.php',

			// Removed in 5.0.1
			'administrator/components/com_akeebasubs/Helper/ComponentParams.php',

			// Removed features no longer maintained
			'administrator/components/com_akeebasubs/View/Users/tmpl/form_customparams.php',
			'administrator/components/com_akeebasubs/Model/CustomFields.php',
			'components/com_akeebasubs/Model/CustomFields.php',

			// Replaced PHP templates with Blade
			'components/com_akeebasubs/View/Level/tmpl/default.php',
			'components/com_akeebasubs/View/Level/tmpl/default_fields.php',
			'components/com_akeebasubs/View/Level/tmpl/default_level.php',
			'components/com_akeebasubs/View/Level/tmpl/default_login.php',
			'components/com_akeebasubs/View/Level/tmpl/steps.php',

			// Obsolete CLI scripts
			'cli/akeebasubs-expiration-control.php',
			'cli/akeebasubs-expiration-notify.php',
			'cli/akeebasubs-update.php',

			// Moving to FEF
			'administrator/components/com_akeebasubs/View/eaccelerator.php',
			'administrator/components/com_akeebasubs/View/errorhandler.php',
			'administrator/components/com_akeebasubs/View/fef.php',
			'administrator/components/com_akeebasubs/View/fof.php',
			'administrator/components/com_akeebasubs/View/hhvm.php',
			'administrator/components/com_akeebasubs/View/wrongphp.php',
			'administrator/components/com_akeebasubs/View/Invoices/Form.php',
			'administrator/components/com_akeebasubs/View/CreditNotes/Form.php',

			// Akeeba Subscriptions 7 - Only support MoR integrations
			'administrator/components/com_akeebasubs/Controller/Import.php',
			'administrator/components/com_akeebasubs/Controller/Updates.php',
			'administrator/components/com_akeebasubs/Controller/User.php',

			'administrator/components/com_akeebasubs/Form/Field/APICouponLimits.php',

			'administrator/components/com_akeebasubs/Helper/EUVATInfo.php',
			'administrator/components/com_akeebasubs/Helper/Forex.php',

			'administrator/components/com_akeebasubs/Model/APICoupons.php',
			'administrator/components/com_akeebasubs/Model/CreditNoteTemplates.php',
			'administrator/components/com_akeebasubs/Model/Import.php',
			'administrator/components/com_akeebasubs/Model/InvoiceTemplates.php',
			'administrator/components/com_akeebasubs/Model/PaymentMethods.php',
			'administrator/components/com_akeebasubs/Model/States.php',
			'administrator/components/com_akeebasubs/Model/TaxHelper.php',
			'administrator/components/com_akeebasubs/Model/TaxRules.php',
			'administrator/components/com_akeebasubs/Model/Users.php',

			'administrator/components/com_akeebasubs/PluginAbstracts/AkpaymentsBase.php',

			'administrator/components/com_akeebasubs/ViewTemplates/ControlPanel/wizard.blade.php',
			'administrator/components/com_akeebasubs/ViewTemplates/ControlPanel/quickicons.blade.php',
			'administrator/components/com_akeebasubs/ViewTemplates/Subscriptions/form_customparams.blade.php',
			'administrator/components/com_akeebasubs/ViewTemplates/Common/EntryPaymentPlugins.php',

			'components/com_akeebasubs/Controller/APICoupons.php',
			'components/com_akeebasubs/Controller/TaxConfig.php',
			'components/com_akeebasubs/Controller/UserInfo.php',

			'components/com_akeebasubs/Model/APICoupons.php',
			'components/com_akeebasubs/Model/InvoiceTemplates.php',
			'components/com_akeebasubs/Model/LevelGroups.php',
			'components/com_akeebasubs/Model/PaymentMethods.php',
			'components/com_akeebasubs/Model/States.php',
			'components/com_akeebasubs/Model/Subscribe/Validation/Business.php',
			'components/com_akeebasubs/Model/Subscribe/Validation/Country.php',
			'components/com_akeebasubs/Model/Subscribe/Validation/CustomFields.php',
			'components/com_akeebasubs/Model/Subscribe/Validation/PaymentMethod.php',
			'components/com_akeebasubs/Model/Subscribe/Validation/State.php',
			'components/com_akeebasubs/Model/Subscribe/Validation/SubscriptionCustomFields.php',
			'components/com_akeebasubs/Model/TaxConfig.php',
			'components/com_akeebasubs/Model/TaxHelper.php',
			'components/com_akeebasubs/Model/TaxRules.php',
			'components/com_akeebasubs/Model/Users.php',

			'components/com_akeebasubs/View/Level/tmpl/default_donottrack.blade.php',
			'components/com_akeebasubs/View/Validate/tmpl/statelist.php',
			'components/com_akeebasubs/View/Level/tmpl/default_fields.blade.php',
			'components/com_akeebasubs/View/Level/tmpl/default_level.blade.php',
			'components/com_akeebasubs/View/Level/tmpl/default_persubscription.blade.php',
			'components/com_akeebasubs/View/Level/tmpl/default_prepayment.blade.php',
			'components/com_akeebasubs/View/Level/tmpl/default_summary.blade.php',
			'components/com_akeebasubs/View/Levels/tmpl/awesome.php',
			'components/com_akeebasubs/View/Levels/tmpl/default.php',
			'components/com_akeebasubs/View/Levels/tmpl/strappy.php',
			'components/com_akeebasubs/View/Subscriptions/tmpl/default.php',

			'components/com_akeebasubs/views/Levels/tmpl/awesome.xml',
			'components/com_akeebasubs/views/Levels/tmpl/strappy.xml',

			'media/com_akeebasubs/images/couponicons.png',
			'media/com_akeebasubs/images/dollaricons.png',
			'media/com_akeebasubs/images/export.png',
			'media/com_akeebasubs/images/sort_asc.png',
			'media/com_akeebasubs/images/sort_desc.png',
			'media/com_akeebasubs/images/subrefresh.png',
			'media/com_akeebasubs/js/autosubmit.js',

			// Replaced jqPlot with Charts.js
			'media/com_akeebasubs/js/excanvas.min.js',
			'media/com_akeebasubs/js/jqplot.barRenderer.min.js',
			'media/com_akeebasubs/js/jqplot.canvasAxisLabelRenderer.min.js',
			'media/com_akeebasubs/js/jqplot.canvasAxisTickRenderer.min.js',
			'media/com_akeebasubs/js/jqplot.canvasTextRenderer.min.js',
			'media/com_akeebasubs/js/jqplot.dateAxisRenderer.min.js',
			'media/com_akeebasubs/js/jqplot.hermite.js',
			'media/com_akeebasubs/js/jqplot.highlighter.min.js',
			'media/com_akeebasubs/js/jqplot.json2.min.js',
			'media/com_akeebasubs/js/jqplot.pieRenderer.min.js',
			'media/com_akeebasubs/js/jquery.jqplot.min.js',
		],
		'folders' => [
			'administrator/components/com_akeebasubs/commands',
			'administrator/components/com_akeebasubs/controllers',
			'administrator/components/com_akeebasubs/converter',
			'administrator/components/com_akeebasubs/databases',
			'administrator/components/com_akeebasubs/fields',
			'administrator/components/com_akeebasubs/fof',
			'administrator/components/com_akeebasubs/invoicetemplates',
			'administrator/components/com_akeebasubs/models',
			'administrator/components/com_akeebasubs/simpleforms',
			'administrator/components/com_akeebasubs/templates',
			'administrator/components/com_akeebasubs/tables',
			'administrator/components/com_akeebasubs/toolbars',
			'administrator/components/com_akeebasubs/toolbars-xxx',
			'administrator/components/com_akeebasubs/views',

			'components/com_akeebasubs/controllers',
			'components/com_akeebasubs/models',
			'components/com_akeebasubs/templates',

			// Removed features no longer maintained
			'administrator/components/com_akeebasubs/CustomField',
			'administrator/components/com_akeebasubs/View/CustomFields',

			// Moving to FEF

			'administrator/components/com_akeebasubs/View/ControlPanel/tmpl',
			'administrator/components/com_akeebasubs/View/Levels',
			'administrator/components/com_akeebasubs/View/LevelGroups',
			'administrator/components/com_akeebasubs/View/Relations',
			'administrator/components/com_akeebasubs/View/Upgrades',
			'administrator/components/com_akeebasubs/View/TaxConfig',
			'administrator/components/com_akeebasubs/View/TaxRules',
			'administrator/components/com_akeebasubs/View/States',
			'administrator/components/com_akeebasubs/View/BlockRules',
			'administrator/components/com_akeebasubs/View/Subscriptions/tmpl',
			'administrator/components/com_akeebasubs/View/Reports/tmpl',
			'administrator/components/com_akeebasubs/View/Coupons',
			'administrator/components/com_akeebasubs/View/APICoupons',
			'administrator/components/com_akeebasubs/View/MakeCoupons/tmpl',
			'administrator/components/com_akeebasubs/View/Import',
			'administrator/components/com_akeebasubs/View/Users',
			'administrator/components/com_akeebasubs/View/Invoices/tmpl',
			'administrator/components/com_akeebasubs/View/InvoiceTemplates',
			'administrator/components/com_akeebasubs/View/CreditNoteTemplates',

			// I no longer need precompiled tempaltes
			'administrator/components/com_akeebasubs/PrecompiledTemplates',
			'components/com_akeebasubs/PrecompiledTemplates',

			// Akeeba Subscriptions 7 - Only support MoR integrations
			'media/com_akeebasubs/tcpdf',

            'administrator/components/com_akeebasubs/assets',
			'administrator/components/com_akeebasubs/creditnotes',
			'administrator/components/com_akeebasubs/invoices',

			'administrator/components/com_akeebasubs/Form',

			'administrator/components/com_akeebasubs/View/Subscriptions',

			'administrator/components/com_akeebasubs/ViewTemplates/APICoupons',
			'administrator/components/com_akeebasubs/ViewTemplates/CreditNoteTemplates',
			'administrator/components/com_akeebasubs/ViewTemplates/Import',
			'administrator/components/com_akeebasubs/ViewTemplates/InvoiceTemplates',
			'administrator/components/com_akeebasubs/ViewTemplates/LevelGroups',
			'administrator/components/com_akeebasubs/ViewTemplates/States',
			'administrator/components/com_akeebasubs/ViewTemplates/TaxConfig',
			'administrator/components/com_akeebasubs/ViewTemplates/TaxRules',
			'administrator/components/com_akeebasubs/ViewTemplates/Users',

            'components/com_akeebasubs/View/APICoupons',
            'components/com_akeebasubs/View/Validate',
			'components/com_akeebasubs/View/Subscribe',
			'components/com_akeebasubs/View/Messages',

			'components/com_akeebasubs/Model/Subscribe/Validation/ValidationTrait',

			'components/com_akeebasubs/views/UserInfo',

			'media/com_akeebasubs/images/dashboard',
			'media/com_akeebasubs/images/frontend',
			'media/com_akeebasubs/invoices',

			// Akeeba Subscriptions 7 - We no longer need any Composer dependencies
            'administrator/components/com_akeebasubs/vendor',
		]
	];

	/**
	 * The list of obsolete extra modules and plugins to uninstall on component upgrade / installation.
	 *
	 * @var array
	 */
	protected $uninstallation_queue = [
		// modules => { (folder) => { (module) }* }*
		'modules' => array(
			'admin' => [],
			'site'  => [
				'aktaxcountry',
			],
		),
		// plugins => { (folder) => { (element) }* }*
		'plugins' => [
			'akeebasubs' => [
				'acymailing',
				'ageverification',
				'agreetoeu',
				'agreetotos',
				'atscreditslegacy',
				'autocity',
				'canalyticscommerce',
				'customfields',
				'gacommerce',
				'invoices',
				'iplogger',
				'iproperty',
				'jce',
				'joomlaprofilesync',
				'kunena',
				'needslogout',
				'recaptcha',
				'reseller',
				'samplefields',
				'slavesubs',
				'sql',
				'subscriptionemailsdebug',
				'userdelete',
			],
			'akpayment'  => [
				'2checkout',
				'2conew',
				'none',
				'offline',
				'paymill',
				'paymilldss3',
				'paypal',
				'paypalpaymentspro',
				'paypalproexpress',
				'skrill',
				'stripe',
				'viva',
			],
			'system'     => [
				'as2cocollation',
				'aslogoutuser',
				'aspaypalcollation',
			],
			'user'       => [
				'aslogoutuser',
				'asresetform',
			],
		],
	];


	public function postflight($type, $parent)
	{
		/**
		 * Migrate country information out of #__akeebasubs_users into Joomla's user profile information
         *
		 * Why put it above the parent::postflight call? Because parent::postflight will run database schema updates
		 * which will eventually involve killing off the #__akeebasubs_users table. We need to run the migration before
         * that.
		 */
		$this->migrateCountryData();

		// Call the parent method
		parent::postflight($type, $parent);

		// Add ourselves to the list of extensions depending on Akeeba FEF
		$this->addDependency('file_fef', $this->componentName);

		// Remove the update sites for this component on installation. The update sites are now handled at the package
		// level.
		$this->removeObsoleteUpdateSites($parent);

	}

	/**
	 * Renders the post-installation message
	 */
	protected function renderPostInstallation($parent)
	{
		$this->warnAboutJSNPowerAdmin();

		?>
		<h1>Akeeba Subscriptions</h1>

		<img src="../media/com_akeebasubs/images/akeebasubs-48.png" width="48" height="48" alt="Akeeba Subscriptions"
			 align="left"/>
		<h2 style="font-size: 14pt; font-weight: bold; padding: 0; margin: 0 0 0.5em;">Welcome to Akeeba Subscriptions!</h2>
		<span>The easiest Paddle integration for Joomla!.</span>

		<?php
	}

	protected function renderPostUninstallation($parent)
	{
		?>
		<h2 style="font-size: 14pt; font-weight: bold; padding: 0; margin: 0 0 0.5em;">&nbsp;Akeeba Subscriptions Uninstallation</h2>
		<p>We are sorry that you decided to uninstall Akeeba Subscriptions.</p>

		<?php
	}


	/**
	 * The PowerAdmin extension makes menu items disappear. People assume it's our fault. JSN PowerAdmin authors don't
	 * own up to their software's issue. I have no choice but to warn our users about the faulty third party software.
	 */
	private function warnAboutJSNPowerAdmin()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('component'))
					->where($db->qn('element') . ' = ' . $db->q('com_poweradmin'))
					->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$hasPowerAdmin = $db->setQuery($query)->loadResult();

		if (!$hasPowerAdmin)
		{
			return;
		}

		$query = $db->getQuery(true)
					->select('manifest_cache')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('component'))
					->where($db->qn('element') . ' = ' . $db->q('com_poweradmin'))
					->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$paramsJson = $db->setQuery($query)->loadResult();
		$jsnPAManifest = new JRegistry();
		$jsnPAManifest->loadString($paramsJson, 'JSON');
		$version = $jsnPAManifest->get('version', '0.0.0');

		if (version_compare($version, '2.1.2', 'ge'))
		{
			return;
		}

		echo <<< HTML
<div class="well" style="margin: 2em 0;">
<h1 style="font-size: 32pt; line-height: 120%; color: red; margin-bottom: 1em">WARNING: Menu items for {$this->componentName} might not be displayed on your site.</h1>
<p style="font-size: 18pt; line-height: 150%; margin-bottom: 1.5em">
	We have detected that you are using JSN PowerAdmin on your site. This software ignores Joomla! standards and
	<b>hides</b> the Component menu items to {$this->componentName} in the administrator backend of your site. Unfortunately we
	can't provide support for third party software. Please contact the developers of JSN PowerAdmin for support
	regarding this issue.
</p>
<p style="font-size: 18pt; line-height: 120%; color: green;">
	Tip: You can disable JSN PowerAdmin to see the menu items to {$this->componentName}.
</p>
</div>

HTML;

	}

	/**
	 * Removes obsolete update sites created for the component (we are now using an update site for the package, not the
	 * component).
	 *
	 * @param   JInstallerAdapterComponent  $parent  The parent installer
	 */
	private function removeObsoleteUpdateSites($parent)
	{
		$db = $parent->getParent()->getDBO();

		$query = $db->getQuery(true)
		            ->select($db->qn('extension_id'))
		            ->from($db->qn('#__extensions'))
		            ->where($db->qn('type') . ' = ' . $db->q('component'))
		            ->where($db->qn('name') . ' = ' . $db->q($this->componentName));
		$db->setQuery($query);
		$extensionId = $db->loadResult();

		if (!$extensionId)
		{
			return;
		}

		$query = $db->getQuery(true)
		            ->select($db->qn('update_site_id'))
		            ->from($db->qn('#__update_sites_extensions'))
		            ->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		$ids = $db->loadColumn(0);

		if (!is_array($ids) && empty($ids))
		{
			return;
		}

		foreach ($ids as $id)
		{
			$query = $db->getQuery(true)
			            ->delete($db->qn('#__update_sites'))
			            ->where($db->qn('update_site_id') . ' = ' . $db->q($id));
			$db->setQuery($query);

			try
			{
				$db->execute();
			}
			catch (\Exception $e)
			{
				// Do not fail in this case
			}
		}
	}

	/**
	 * Migrate country information from the #__akeebasubs_users table into Joomla's profile fields.
     *
     * @return  void
     *
     * @since   7.0.0
	 */
	private function migrateCountryData()
    {
	    /**
	     * In case you're wondering why use a hardcoded query instead of going through JDatabaseQuery: I am doing an
	     * INSERT from SELECT query which is superbly efficient and does not use any PHP memory. The alternative
	     * involves loading N records to PHP memory and executing N to 2N queries to see if the data already exists and
	     * decide whether to do an INSERT. Try that with 70,000 records and tell me how that worked for you ;)
	     */
        $db = JFactory::getDbo();
        $sql = <<< SQL
INSERT INTO
    `#__user_profiles`
SELECT
       `user_id`,
       "akeebasubs.country" AS `profile_key`,
       `country` AS `profile_value`,
       0 as `ordering`
FROM
     `#__akeebasubs_users`
WHERE
      `user_id` NOT IN (
              SELECT
                     `user_id`
              FROM
                     `#__user_profiles`
              WHERE
                    `profile_key` = 'akeebasubs.country'
              AND NOT
                  (profile_value = "" OR profile_value = "XX")
       )
SQL;
	    /**
	     * Why the try-catch with no action? Well, country information is nice to have, not necessary to have. Moreover,
	     * in the not-so-distant future we're going to kill the #__akeebasubs_users table which means this query will
	     * fail.
	     */
        try
        {
	        $db->setQuery($sql)->execute();
        }
        catch (Exception $e)
        {
            // No sweat.
        }
    }
}
