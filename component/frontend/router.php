<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use FOF30\Container\Container;
use Joomla\CMS\Component\Router\RouterBase;
use Joomla\CMS\Factory;

defined('_JEXEC') or die();

include_once JPATH_LIBRARIES . '/fof30/include.php';

/**
 * Akeeba Subscriptions router.
 *
 * This used to be separate functions for building and parsing routes. It has been converted to a router class since it
 * is necessary for Joomla! 4. Router classes are supported since Joomla! 3.3, so no lost compatibility there.
 *
 * @since       7.0.1
 */
class AkeebasubsRouter extends RouterBase
{
	/**
	 * Build method for URLs
	 * This method is meant to transform the query parameters into a more human
	 * readable form. It is only executed when SEF mode is switched on.
	 *
	 * @param array  &$query An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @throws Exception
	 * @since   7.0.1
	 */
	public function build(&$query)
	{
		$segments = array();

		// Default view
		$default = 'Levels';

		// We need to find out if the menu item link has a view param
		if (array_key_exists('Itemid', $query))
		{
			$menu = Factory::getApplication()->getMenu()->getItem($query['Itemid']);

			if (!is_object($menu))
			{
				$menuquery = array();
			}
			else
			{
				parse_str(str_replace('index.php?', '', $menu->link), $menuquery); // remove "index.php?" and parse
			}
		}
		else
		{
			$menuquery = array();
		}

		// Add the view
		$newView = array_key_exists('view', $query) ? $query['view'] :
			(array_key_exists('view', $menuquery) ? $menuquery['view'] : $default);

		$newView = ucfirst($newView);

		if ($newView == 'Level')
		{
			$newView = 'New';
		}
		elseif ($newView == 'Message')
		{
			$newView = 'ThankYou';

			unset($query['layout']);
			unset($query['task']);
		}
		elseif (($newView == 'Userinfo') || ($newView == 'UserInfo'))
		{
			$newView = 'UserInfo';

			if (!array_key_exists('layout', $query))
			{
				unset($query['layout']);
			}
		}

		$segments[] = strtolower($newView);
		unset($query['view']);

		// Add the slug
		if ($newView != 'UserInfo')
		{
			$container = Container::getInstance('com_akeebasubs');

			if (array_key_exists('slug', $query) && ($container->inflector->isSingular($segments[0]) || ($segments[0] == 'new')))
			{
				$segments[1] = $query['slug'];
				unset($query['slug']);
			}
			elseif (array_key_exists('id', $query) && ($segments[0] == 'subscription'))
			{
				$segments[1] = $query['id'];
				unset($query['id']);
			}
		}

		// Add the subscription ID
		if (($newView == 'ThankYou') && isset($query['subid']))
		{
			$segments[] = $query['subid'];
			unset($query['subid']);
		}

		return $segments;
	}

	/**
	 * Parse method for URLs
	 * This method is meant to transform the human readable URL back into
	 * query parameters. It is only executed when SEF mode is switched on.
	 *
	 * @param array  &$segments The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @throws  Exception
	 * @since   7.0.1
	 */
	public function parse(&$segments)
	{
		// accepted views:
		$views = array('new', 'thankyou', 'cancelled', 'level', 'levels', 'message', 'subscribe', 'subscription', 'subscriptions', 'callback', 'validate', 'userinfo', 'invoices', 'invoice');

		// accepted layouts:
		$layoutsAccepted = array(
			'Invoice' => array('item')
		);

		// default view
		$default = 'levels';

		$mObject = Factory::getApplication()->getMenu()->getActive();
		$menu = is_object($mObject) ? $mObject->query : array();

		// circumvent the auto-segment decoding
		$segments = str_replace(':', '-', $segments);

		$vars = array();

		// if there's no view, but the menu item has view info, we use that
		if (count($segments))
		{
			if (!in_array(strtolower($segments[0]), $views))
			{
				$vars['view'] = array_key_exists('view', $menu) ? $menu['view'] : $default;
			}
			else
			{
				$vars['view'] = array_shift($segments);
			}

			switch ($vars['view'])
			{
				case 'New':
				case 'new':
					$vars['view'] = 'Level';
					$vars['task'] = 'read';
					break;

				case 'Invoices':
				case 'invoices':
					$vars['view'] = 'Invoices';
					$vars['layout'] = 'default';
					break;

				case 'Invoice':
				case 'invoice':
					$vars['view'] = 'Invoice';
					$vars['task'] = 'read';
					$vars['layout'] = 'item';
					break;

				case 'thankyou':
				case 'Thankyou':
				case 'ThankYou':
					$vars['view'] = 'Messages';
					$vars['task'] = 'show';
					break;

				case 'userinfo':
				case 'Userinfo':
				case 'UserInfo':
					$vars['view'] = 'UserInfo';
					$vars['task'] = 'read';
					$vars['layout'] = 'default';
					break;
			}

			array_push($segments, $vars['view']);

			if (array_key_exists('layout', $vars))
			{
				array_unshift($segments, $vars['layout']);
			}

			$layouts = array_key_exists($vars['view'], $layoutsAccepted) ? $layoutsAccepted[$vars['view']] : array();

			if (!in_array($segments[0], $layouts))
			{
				$vars['layout'] = array_key_exists('layout', $menu) ? $menu['layout'] : 'default';
			}
			else
			{
				$vars['layout'] = array_shift($segments);
			}

			// if we are in a singular view, the next item is the slug, unless we are in the userinfo view
			$container = Container::getInstance('com_akeebasubs');

			if ($container->inflector->isSingular($vars['view']) && ($vars['view'] != 'UserInfo'))
			{
				if (in_array($vars['view'], array('Subscription', 'Invoice')))
				{
					$vars['id'] = array_shift($segments);
				}
				else
				{
					$vars['slug'] = array_shift($segments);
				}
			}

			if (($vars['view'] == 'Messages') && !empty($segments))
			{
				$vars['subid'] = array_shift($segments);
			}
		}

		return $vars;
	}
}