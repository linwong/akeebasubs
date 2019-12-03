<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Model\MissingInvoices;use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Utils\FEFHelper\BrowseView;
use FOF30\Utils\SelectOptions;

defined('_JEXEC') or die();

/**
 * @var \FOF30\View\DataView\Form $this
 * @var  MissingInvoices          $row
 * @var  MissingInvoices          $model
 */

$model = $this->getModel();

$stateOptions = [
	'N' => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_N'),
	'P' => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_P'),
	'C' => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_C'),
	'X' => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_X'),
];

$discountOptions = [
	'none'    => JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_NONE'),
	'coupon'  => JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_COUPON'),
	'upgrade' => JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_UPGRADE'),
];

$returnurl = urlencode(base64_encode(\JUri::getInstance()->toString()));
?>
@extends('admin:com_akeebasubs/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('akeebasubs_subscription_id', 'akeebasubs_subscription_id', 'JGLOBAL_NUM')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @jhtml('calendar', $model->publish_up, 'publish_up', 'publish_up', '%Y-%m-%d', ['placeholder' => JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')])
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @jhtml('calendar', $model->publish_down, 'publish_down', 'publish_down', '%Y-%m-%d', ['placeholder' => JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')])
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @jhtml('calendar', $model->created_on, 'created_on', 'created_on', '%Y-%m-%d', ['placeholder' => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON')])
    </div>

@stop

@section('browse-table-header')
    {{-- ### HEADER ROW ### --}}
    <tr>
        <th width="20px">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th width="60px">
            @sortgrid('akeebasubs_subscription_id', 'JGLOBAL_NUM')
        </th>
        <th width="15%">
            @sortgrid('akeebasubs_level_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')
        </th>
        <th width="10%">
            @sortgrid('user_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
        </th>
        <th width="30">
            @sortgrid('state', 'COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')
        </th>
        <th width="60">
            @sortgrid('discount', 'COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT')
        </th>
        <th width="8%">
            @sortgrid('gross_amount', 'COM_AKEEBASUBS_SUBSCRIPTIONS_AMOUNT')
        </th>
        <th width="14%">
            @sortgrid('publish_up', 'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')
        </th>
        <th width="10%">
            @sortgrid('created_on', 'COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON')
        </th>
        <th>
            @lang('COM_AKEEBASUBS_INVOICES_LBL_ACTIONS')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
    {{-- Table body shown when records are present. --}}
	<?php $i = 0; ?>
    @foreach($this->items as $row)
		<?php
		$trClass = $row->enabled ? '' : 'expired';

		if (!$row->enabled && ($row->getFieldValue('state') == 'C'))
		{
			$jExpires = new DateTime($row->publish_down);

			if ($jExpires->getTimestamp() > time())
			{
				$trClass = 'pending-renewal';
			}
		}
		?>
        <tr class="{{ $trClass }}">
            <td>
                @jhtml('FEFHelper.browse.id', ++$i, $row->getId())
            </td>
            <td>
                <a href="@route(\FOF30\Utils\FEFHelper\BrowseView::parseFieldTags('index.php?option=com_akeebasubs&view=Subscriptions&task=edit&id=[ITEM:ID]', $row))">
                    {{{ $row->getId() }}}
                </a>
            </td>
            <td>
                <a href="@route(\FOF30\Utils\FEFHelper\BrowseView::parseFieldTags('index.php?option=com_akeebasubs&view=Levels&task=edit&id=[ITEM:AKEEBASUBS_LEVEL_ID]', $row))">
                    {{{  BrowseView::modelOptionName($row->akeebasubs_level_id, 'Levels') }}}
                </a>
            </td>
            <td>
                @include('admin:com_akeebasubs/Common/ShowUser', ['item' => $row, 'field' => 'user_id', 'linkURL' => 'index.php?option=com_users&task=user.edit&id=[ITEM:USER_ID]'])
            </td>
            <td>
                @include('admin:com_akeebasubs/Common/ShowPaymentStatus', ['item' => $row, 'field' => 'state'])
            </td>
            <td>
                @include('admin:com_akeebasubs/Common/ShowSubscriptionDiscount', ['item' => $row])
            </td>
            <td>
                @include('admin:com_akeebasubs/Common/ShowPriceBreakdown', ['item' => $row])
            </td>
            <td>
                <div class="akeebasubs-susbcription-publishup">
                    {{ Akeeba\Subscriptions\Admin\Helper\Format::date($row->publish_up) }}
                </div>
                <div class="akeebasubs-susbcription-publishdown">
                    {{ Akeeba\Subscriptions\Admin\Helper\Format::date($row->publish_down) }}
                </div>

            </td>
            <td>
                {{ Akeeba\Subscriptions\Admin\Helper\Format::date($row->created_on) }}
            </td>
            <td>
                <a href="@route("index.php?option=com_akeebasubs&view=Invoices&task=generateForSubscription&id={$row->akeebasubs_subscription_id}&returnurl=$returnurl")"
                   class="akeeba-btn--green" title="@lang('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE')">
                    <span class="akion-refresh"></span>
                    @lang('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE')
                </a>
            </td>
        </tr>
    @endforeach
@stop
