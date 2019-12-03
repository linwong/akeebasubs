<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Utils\FEFHelper\BrowseView;
use FOF30\Utils\SelectOptions;

defined('_JEXEC') or die();

/**
 * @var \FOF30\View\DataView\Form $this
 * @var  Subscriptions            $row
 * @var  Subscriptions            $model
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

$paymentMethodOptions = [
	'apple-pay'     => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_APPLE'),
	'card'          => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_CARD'),
	'free'          => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_FREE'),
	'paypal'        => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_PAYPAL'),
	'wire-transfer' => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_WIRE'),
	'unknown'       => JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_UNKNOWN'),
];

?>
@extends('admin:com_akeebasubs/Common/browse')

@section('browse-filters')
    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('akeebasubs_subscription_id', 'akeebasubs_subscription_id', 'JGLOBAL_NUM')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        {{ BrowseView::modelFilter('akeebasubs_level_id', 'title', 'Levels', 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')  }}
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('search', 'search', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('paystate', $stateOptions, 'COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('paykey', 'paykey', 'COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR_KEY')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('payment_method', $paymentMethodOptions, 'COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_FIELDTITLE')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @selectfilter('filter_discountmode', $discountOptions, 'COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT')
    </div>

    <div class="akeeba-filter-element akeeba-form-group">
        @searchfilter('filter_discountcode', 'filter_discountcode', 'COM_AKEEBASUBS_SUBSCRIPTION_DISCOUNTCODE')
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

    <div class="akeeba-filter-element akeeba-form-group">
        {{ BrowseView::publishedFilter('enabled', 'JENABLED') }}
    </div>
@stop

@section('browse-table-header')
    {{-- ### HEADER ROW ### --}}
    <tr>
        <th width="20px">
            @jhtml('FEFHelper.browse.checkall')
        </th>
        <th width="20px">
            @sortgrid('akeebasubs_subscription_id', 'JGLOBAL_NUM')
        </th>
        <th>
            @sortgrid('akeebasubs_level_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')
        </th>
        <th>
            @sortgrid('user_id', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')
        </th>
        <th>
            @sortgrid('state')
        </th>
        <th width="60">
            @sortgrid('discount')
        </th>
        <th width="8%">
            @sortgrid('gross_amount')
        </th>
        <th width="14%">
            @sortgrid('publish_up')
        </th>
        <th width="10%">
            @sortgrid('created_on')
        </th>
        <th width="30">
            @sortgrid('enabled', 'JENABLED')
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

        if ($row->getFieldValue('state') == 'N')
        {
            $trClass = 'new';
        }

        if ($row->getFieldValue('state') == 'X')
        {
            $trClass = 'canceled';
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
                @jhtml('FEFHelper.browse.published', $row->enabled, $i, '', false)
            </td>
        </tr>
    @endforeach
@stop

{{-- This used to display the Run Integrations interface --}}
@section('browse-page-bottom')
    @js('media://com_akeebasubs/js/blockui.js')

    <div id="refreshMessage" style="display:none">
        <h3>
            @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_TITLE')
        </h3>
        <p>
            <img id="asriSpinner" src="@media('media://com_akeebasubs/images/throbber.gif')" align="center"/>
        </p>
        <p>
            <span id="asriPercent">0</span>
            @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_PROGRESS')
        </p>
    </div>

    <script type="text/javascript">
		var akeebasubs_token = "@token()";
    </script>
@stop
