<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>
<div class="akeeba-panel--info">
	<strong>
		Akeeba Subscriptions {{{ AKEEBASUBS_VERSION }}}
	</strong>

	<br/>
	<span style="font-size: x-small">
		Copyright &copy;2010&ndash;{{{ $this->getContainer()->platform->getDate(AKEEBASUBS_DATE)->format('Y') }}}
		Nicholas K. Dionysopoulos / AkeebaBackup.com
	</span>
	<br/>

	<span style="font-size: x-small">
		Akeeba Subscriptions is Free software released under the
		<a href="www.gnu.org/licenses/gpl.html">GNU General Public License,</a>
		version 3 of the license or &ndash;at your option&ndash; any later version
		published by the Free Software Foundation.
	</span>	
</div>
