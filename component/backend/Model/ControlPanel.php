<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Database\Installer;
use FOF30\Model\Model;
use JRegistry;
use JFactory;

class ControlPanel extends Model
{
	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return  $this
	 */
	public function checkAndFixDatabase()
	{
		$db = $this->container->platform->getDbo();

		$dbInstaller = new Installer($db, JPATH_ADMINISTRATOR . '/components/com_akeebasubs/sql/xml');
		$dbInstaller->updateSchema();

		return $this;
	}

	/**
	 * Save some magic variables we need
	 *
	 * @return  $this
	 */
	public function saveMagicVariables()
	{
		// Store the URL to this site
		$db = $this->container->platform->getDbo();
		$query = $db->getQuery(true)
			->select('params')
			->from($db->qn('#__extensions'))
			->where($db->qn('element') . '=' . $db->q('com_akeebasubs'))
			->where($db->qn('type') . '=' . $db->q('component'));
		$db->setQuery($query);
		$rawparams = $db->loadResult();

		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		$siteURL_stored = $params->get('siteurl', '');
		$siteURL_target = str_replace('/administrator', '', \JUri::base());

		if ($siteURL_target != $siteURL_stored)
		{
			$params->set('siteurl', $siteURL_target);
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->set($db->qn('params') . '=' . $db->q($params->toString()))
				->where($db->qn('element') . '=' . $db->q('com_akeebasubs'))
				->where($db->qn('type') . '=' . $db->q('component'));
			$db->setQuery($query);
			$db->execute();
		}

		return $this;
	}

	/**
	 * Removes the update sites for this extension
	 *
	 * @since  7.0.1
	 */
	public function deleteUpdateSites()
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\Updates $updatesModel */
		$updatesModel = $this->container->factory->model('Updates')->tmpInstance();
		$updatesModel->removeObsoleteUpdateSites();
		$updateSiteIds = $updatesModel->getUpdateSiteIds();

		if (!empty($updateSiteIds) && is_array($updateSiteIds))
		{
			$db                = $this->container->db;
			$obsoleteIDsQuoted = array_map([$db, 'quote'], $updateSiteIds);

			// Delete update sites
			$query = $db->getQuery(true)
				->delete('#__update_sites')
				->where($db->qn('update_site_id') . ' IN (' . implode(',', $obsoleteIDsQuoted) . ')');
			$db->setQuery($query)->execute();

			// Delete update sites to extension ID records
			$query = $db->getQuery(true)
				->delete('#__update_sites_extensions')
				->where($db->qn('update_site_id') . ' IN (' . implode(',', $obsoleteIDsQuoted) . ')');
			$db->setQuery($query)->execute();
		}

		return $this;
	}
}
