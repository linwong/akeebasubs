<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Site\Model\Subscriptions;

defined('_JEXEC') or die();

/**
 * @var \Akeeba\Subscriptions\Site\View\Subscriptions\Html $this
 * @var array                                              $levelInfo
 */

$imageSize = $this->container->params->get('summaryimages', 1);
$currencyPosition = $this->container->params->get('currencypos', 'before');
$currencySymbol = $this->container->params->get('currencysymbol', '€');


/** @var \Akeeba\Subscriptions\Site\Model\Levels $level */
$level = $levelInfo['level'];
/** @var Subscriptions $lastSub */
$lastSub = $levelInfo['latest'];
/** @var Subscriptions|null $relatedSub */
$relatedSub = $levelInfo['related']['related_sub'];
/** @var \FOF30\Model\DataModel\Collection $allSubs */
$allSubs = $levelInfo['transactions'];

if ($allSubs->isEmpty())
{
    return;
}

$lastFortniteTimestamp = (new \FOF30\Date\Date())->sub(new DateInterval('P2W'))->getTimestamp();

/** @var Subscriptions|null $unpaidSub */
$unpaidSub = (clone $allSubs)->filter(function (Subscriptions $sub) {
	return $sub->status == 'new';
})->filter(function (Subscriptions $sub) use ($lastFortniteTimestamp) {
	try {
		return (new FOF30\Date\Date($sub->created_on))->getTimestamp() >= $lastFortniteTimestamp;
    } catch (Exception $e) {
		return false;
    }
})->first();

/** @var Subscriptions|null $pendingSub */
$pendingSub = (clone $allSubs)->filter(function (Subscriptions $sub) {
	return $sub->status == 'pending';
})->filter(function (Subscriptions $sub) use ($lastFortniteTimestamp) {
	try {
		return (new FOF30\Date\Date($sub->created_on))->getTimestamp() >= $lastFortniteTimestamp;
	} catch (Exception $e) {
		return false;
	}
})->first();

$statusToColor = function(string $status): string {
	switch ($status)
	{
		case 'new':
			return 'red';
			break;

		case 'pending':
			return 'orange';
			break;

		default:
		case 'active':
		    return 'green';
			break;

		case 'waiting':
			return 'teal';
			break;

		case 'expired':
			return 'info';
			break;

		case 'canceled':
			return 'grey';
			break;
	}
};

$formatCurrency = function(float $price) use ($currencyPosition, $currencySymbol): string {
	$ret = ($currencyPosition == 'before') ? $currencySymbol : '';
	$ret .= sprintf('%0.2f', $price);
	$ret .= ($currencyPosition == 'after') ? $currencySymbol : '';

	return $ret;
};

?>

<div class="akeeba-panel--{{ $level->enabled ? $statusToColor($levelInfo['status']) : 'grey' }} akeebasubs-subscription-levels-subscriptions-details">
    {{-- Header for the Level --}}
    <header class="akeeba-block-header mysubs-level-header">
        {{-- LEVEL IMAGE AND TITLE --}}
        <h3>
            @if ($imageSize && !empty($level->image))
                <img src="{{ \Joomla\CMS\Uri\Uri::base() }}{{ $level->image }}"
                     class="akeebasubs-subscription-level-image hasTooltip"
                     width="{{ (int)$imageSize }}px"
                     title="{{{ $level->title }}}" />
            @endif

            {{{ $level->title }}}
        </h3>

        {{-- SUBSCRIPTION STATUS AND EXPIRATION --}}
        <div class="akeebasubs-subscription-info">
            <span class="hasTooltip"
                  title="@lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $levelInfo['status'] . '_HELP')"
            >
                <strong>
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $levelInfo['status'])
                </strong>
            </span>
            <span>
                @if (in_array($levelInfo['status'], ['canceled', 'expired']))
                    @sprintf(
                    'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISHDATES_EXPIRED',
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($lastSub->publish_down)
                    )
                @elseif ($levelInfo['status'] == 'waiting')
                    @sprintf(
                    'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISHDATES_RENEWAL',
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($lastSub->publish_up)
                    )
                @elseif ($levelInfo['status'] == 'active')
                    @sprintf(
                    'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISHDATES_ACTIVE',
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($lastSub->publish_down)
                    )
                @endif
            </span>
        </div>
    </header>

    {{-- Main Contents for the Level --}}

    {{-- LEVEL DESCRIPTION --}}
    <div
            class="akeebasubs-subscription-description akeebasubs-subscriptions-description-level"
            id="akeebasubs-subscriptions-description-level-{{{ $level->getId() }}}">
        {{ $level->description }}
    </div>

    {{-- RENEWAL / UPGRADE / DOWNGRADE INFO --}}
    <div>
        {{-- SUBSCRIPTION LEVEL IS UNPUBLISHED --}}
        @if (!$level->enabled)
            <span class="akeeba-label--red">
                @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL_UNPUBLISHED')
            </span>
        {{-- SUBSCRIPTION HAS BEEN DOWNGRADED --}}
        @elseif ($levelInfo['related']['status'] == 'downgrade')
            <p class="akeeba-block--info">
                @sprintf(
                    'COM_AKEEBASUBS_SUBSCRIPTIONS_ALREADYDOWNGRADED',
                    $relatedSub->getId(),
                    $relatedSub->level->title,
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($relatedSub->publish_up),
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($relatedSub->publish_down)
                )
            </p>
        {{--  SUBSCRIPTION HAS BEEN UPGRADED --}}
        @elseif ($levelInfo['related']['status'] == 'upgrade')
            <p class="akeeba-block--info">
                @sprintf(
                    'COM_AKEEBASUBS_SUBSCRIPTIONS_ALREADYUPGRADED',
                    $relatedSub->getId(),
                    $relatedSub->level->title,
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($relatedSub->publish_up),
                    \Akeeba\Subscriptions\Admin\Helper\Format::date($relatedSub->publish_down)
                )
            </p>
        {{-- PENDING PAYMENT --}}
        @elseif (!is_null($pendingSub))
            <div class="akeeba-block--warning">
                <h5>
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_PENDING_PAYMENT_CONTAINED')
                </h5>
                <a href="@route('index.php?option=com_akeebasubs&view=Message&subid=' . $pendingSub->getId())"
                   class="akeeba-btn--grey">
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_BTN_MORE_INFO')
                </a>
            </div>
        {{-- NOT YET PAID --}}
        @elseif (!is_null($unpaidSub))
            <div class="akeeba-block--warning">
                <h5>
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_UNPAID_CONTAINED')
                </h5>
                <a href="@route('index.php?option=com_akeebasubs&view=Message&subid=' . $unpaidSub->getId())"
                   class="akeeba-btn--grey">
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_BTN_MORE_INFO')
                </a>
            </div>
        {{-- CAN BE UPGRADED --}}
        @elseif ($levelInfo['buttons']['renew'] || $levelInfo['buttons']['purchase'])
            <div class="akeebasubs-subscription-level-renew-container">
                <a class="akeeba-btn--primary"
                  href="@route('index.php?option=com_akeebasubs&view=Level&slug=' . $level->slug)">
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_' . ($levelInfo['buttons']['renew'] ? 'RENEW' : 'REPURCHASE'))
                </a>
            </div>
        @endif


        {{-- RECURRING SUBSCRIPTION MANAGEMENT --}}
        @if ($levelInfo['recurring']['is_recurring'])
        <div class="akeeba-block--success">
            <p>
                @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_RECURRING_INFO')
            </p>
            <p>
                <button class="akeeba-btn--teal"
                        onclick="Paddle.Checkout.open({override: '{{ $levelInfo['recurring']['update_url'] }}'});">
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_BTN_UPDATE')
                </button>
                <button class="akeeba-btn--red--small"
                        onclick="Paddle.Checkout.open({override: '{{ $levelInfo['recurring']['cancel_url'] }}'});">
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_CANCEL_RECURRING')
                </button>
            </p>
        </div>
        @endif

        {{-- BILLING HISTORY --}}
        <h4 class="akeebasubs-subscriptions-billing-history-head">
            <span>
                @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_HEAD_BILLINGHISTORY')
            </span>
            <button
                    class="akeeba-btn--dark--mini"
                    onclick="akeebasubs_toggle_div('akeebasubs_my_subscriptions_level_{{ $level->slug }}')"
            >
                @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_HISTORY_SHOW_HIDE')
            </button>
        </h4>
        <div id="akeebasubs_my_subscriptions_level_{{ $level->slug }}">
        @foreach ($allSubs as $sub)
        <?php /** @var Subscriptions $sub */ ?>
            {{-- TRANSACTION DISPLAY --}}
            <div class="akeeba-panel--info akeebasubs-subscription-container">
                {{-- TRANSACTION HEADER --}}
                <header class="akeebasubs-subscription-header akeeba-block-header">
                    {{-- TRANSACTION HEADER :: ID --}}
                    <span class="akeebasubs-subscription-id">
                        #{{ $sub->getId() }}
                    </span>

                    <span class="akeebasubs-subscription-purchase-date">
                        @if ((int)substr($sub->created_on, 0, 4) < 1)
                            @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_INVALIDCREATIONDATE')
                        @else
                            {{ \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->created_on) }}
                        @endif
                    </span>

                    {{-- TRANSACTION HEADER :: STATUS --}}
                    @if ($sub->status == 'canceled')
                        <span class="akeeba-label--{{ $statusToColor($sub->status) }} hasTooltip akeebasubs-subscription-status pull-right"
                              title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_DETAILED_CANCELLATION_REASON_' . $sub->cancellation_reason)"
                        >
                            @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $sub->status)
                        </span>
                    @else
                        <span class="akeeba-label--{{ $statusToColor($sub->status) }} hasTooltip pull-right"
                              title="@lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $sub->status . '_HELP')"
                        >
                            @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $sub->status)
                        </span>
                    @endif
                </header>
                {{-- EXPIRATION INFORMATION --}}
                <div>
                    {{-- Unpaid or pending --}}
                    @if (in_array($sub->status, ['new', 'pending']))
                        <p>
                            @lang(($sub->status == 'new') ? 'COM_AKEEBASUBS_SUBSCRIPTIONS_UNPAID' : 'COM_AKEEBASUBS_SUBSCRIPTIONS_PENDING_PAYMENT')
                        </p>
                        <a href="@route('index.php?option=com_akeebasubs&view=Message&subid=' . $sub->getId())"
                           class="akeeba-btn--infp">
                            @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_BTN_MORE_INFO')
                        </a>
                    {{-- Canceled --}}
                    @elseif ($sub->status == 'canceled')
                        <p>
                            @lang('COM_AKEEBASUBS_SUBSCRIPTION_DETAILED_CANCELLATION_REASON_' . $sub->cancellation_reason)
                        </p>
                    {{-- Expired --}}
                    @elseif ($sub->status == 'expired')
                        <p>
                            @sprintf(
                            'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISHDATES_EXPIRED_ALL',
                            \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->publish_up),
                            \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->publish_down)
                            )
                        </p>
                    {{-- Renewal / upgrade / downgrade --}}
                    @elseif ($sub->status == 'waiting')
                        <p>
                            @sprintf(
                            'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISHDATES_RENEWAL_ALL',
                            \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->publish_up),
                            \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->publish_down)
                            )
                        </p>
                    {{-- Active --}}
                    @elseif ($sub->status == 'active')
                        <p>
                            @sprintf(
                            'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISHDATES_ACTIVE_ALL',
                            \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->publish_up),
                            \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->publish_down)
                            )
                        </p>
                    @endif
                </div>
                @if (!in_array($sub->status, ['new', 'pending']))
                <div>
                    @if ($sub->gross_amount > 0.01)
                    {{-- PAYMENT METHOD & TRANSACTION ID --}}
                    <p class="akeebasubs-subscription-purchase-method">
                        @if ($sub->processor == 'paddle')
                            @if ($sub->payment_method == 'unknown')
                                <span class="akpayment-icon-unknown hasTooltip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_UNKNOWN')"></span>
                            @elseif ($sub->payment_method == 'apple-pay')
                                <span class="akpayment-icon-apple hasTooltip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_APPLE')"></span>
                            @elseif ($sub->payment_method == 'card')
                                <span class="akion-card hasTooltip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_CARD')"></span>
                            @elseif ($sub->payment_method == 'free')
                                <span class="akion-beer hasTooltip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_FREE')"></span>
                            @elseif ($sub->payment_method == 'paypal')
                                <span class="akpayment-icon-paypal hasTooltip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_PAYPAL')"></span>
                            @elseif ($sub->payment_method == 'wire-transfer')
                                <span class="akpayment-icon-bank hasTooltip" title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_PAYMENT_TYPE_WIRE')"></span>
                            @endif
                        @endif

                        {{{ ucfirst($sub->processor) }}}

                        @sprintf('COM_AKEEBASUBS_SUBSCRIPTIONS_TRANSACTION_ID', $sub->processor_key)
                    </p>

                    {{-- RECEIPT / INVOICE --}}
                    @if (!empty($sub->akeebasubs_invoice_id) || !empty($sub->receipt_url))
                        <p>
                            @if (!empty($sub->receipt_url))
                                <a class="akeeba-btn--grey--small"
                                   href="{{ $sub->receipt_url }}"
                                   target="_blank">
                                    <span class="akion-document-text"></span>
                                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_BTN_RECEIPT')
                                </a>
                            @endif

                            @if (!empty($sub->akeebasubs_invoice_id))
                                <a class="akeeba-btn--small--grey--small"
                                   href="@route('index.php?option=com_akeebasubs&view=Invoice&task=read&id=' . $sub->getId() . '&tmpl=component')"
                                   target="_blank"
                                >
                                    <span class="akion-document-text"></span>
                                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_INVOICE')
                                </a>
                            @endif
                        </p>
                    @endif

                    {{-- PRICE ANALYSIS --}}
                    <table class="akeeba-table--leftbold--compact--striped">
                        <tbody>
                        @if ($sub->prediscount_amount > 0.009)
                        <tr>
                            <td>@lang('COM_AKEEBASUBS_LEVEL_SUM_ORIGINALLY')</td>
                            <td>{{ $formatCurrency($sub->prediscount_amount) }}</td>
                        </tr>
                        @endif
                        @if ($sub->discount_amount > 0.009)
                        <tr>
                            <td>@lang('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')</td>
                            <td>{{ $formatCurrency(-$sub->discount_amount) }}</td>
                        </tr>
                        @endif
                        @if (abs($sub->net_amount - $sub->gross_amount) > 0.009)
                        <tr>
                            <td>@lang('COM_AKEEBASUBS_LEVEL_SUM_NET')</td>
                            <td>{{ $formatCurrency($sub->net_amount) }}</td>
                        </tr>
                        @endif
                        @if ($sub->tax_amount > 0.009)
                        <tr>
                            <td>@lang('COM_AKEEBASUBS_LEVEL_SUM_TAX_CONCRETE')</td>
                            <td>{{ $formatCurrency($sub->tax_amount) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>@lang('COM_AKEEBASUBS_SUBSCRIPTION_AMOUNT_PAID')</td>
                            <td style="font-weight: bold;">{{ $formatCurrency($sub->gross_amount) }}</td>
                        </tr>
                        </tbody>
                    </table>
                    @else
                        <strong>
                            @lang('COM_AKEEBASUBS_SUBSCRIPTION_FREE')
                        </strong>
                    @endif
                </div>
                @endif
            </div>
        <!--
        <li>
            {{-- Subscription ID --}}
            #{{ $sub->getId() }}
            {{-- Status --}}
            @if ($sub->status == 'canceled')
                <span class="akeeba-label--{{ $statusToColor($sub->status) }} hasTooltip"
                      title="@lang('COM_AKEEBASUBS_SUBSCRIPTION_DETAILED_CANCELLATION_REASON_' . $sub->cancellation_reason)"
                >
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $sub->status)
                </span>
            @else
                <span class="akeeba-label--{{ $statusToColor($sub->status) }} hasTooltip"
                      title="@lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $sub->status . '_HELP')"
                >
                    @lang('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $sub->status)
                </span>
            @endif
            {{-- Created on --}}
            {{ \Akeeba\Subscriptions\Admin\Helper\Format::date($sub->created_on) }}
            {{ $sub->processor}}
            {{ $sub->processor_key}}
        </li>
        -->
        @endforeach
        </div>

    </div>
</div>
