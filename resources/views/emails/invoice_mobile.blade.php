<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Invoice</title>
    <link rel="stylesheet" href="invoice.css">
  </head>
  <body>

    <div class="page">

      <div class="invoice">
        <!-- HEADER -->
        <div class="header">
          <div class="header-left">
            <div class="date">
              {{ $data['data'][0]['completed_at'] ?? 'N/A' }}
            </div>
          </div>

          <div class="header-right">
            @if($logo)
                    <img src="{{ $logo }}"alt="Logo" class="logo"
                    style="display: flex; float: none; text-align: right; width: 150px;margin-top:20px;">
            @endif
          </div>
        </div>

        <div class="divider"></div>

        <!-- TOTAL AMOUNT -->
        <div class="amount">
          {{ $data['data'][0]['requested_currency_symbol'] }}
          {{ number_format($data['data'][0]['requestBill']['data']['total_amount'], 2) }}
        </div>

        <div class="invoice-no">
          {{ $data['data'][0]['request_number'] }}
        </div>

        <div class="thanks">
          {{ custom_invoice_trans('thanks_for_ordering_with_us', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}, {{ $data['data'][0]['userDetail']['data']['name'] }}
        </div>

        <div class="divider-thick"></div>

        <!-- BODY -->
        <div class="body">

          <!-- LEFT -->
          <div class="col left">
            <div class="title">{{ custom_invoice_trans('order_details', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</div>
            <div class="divider"></div>
            <div class="row icon">
              {{ custom_invoice_trans('for', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}:
              {{ $data['data']['userDetail']['name'] ??''}}
            </div>
            <div class="divider"></div>
            <div class="row icon">
              <img src="{{ public_path('invoice/Invoice_user_icon_2x.png') }}" style="width: 38px;">
              {{  $data['data'][0]['driverDetail']['data']['name']}}
            </div>
            <div class="divider"></div>
            <div class="row icon">
              <img src="{{ public_path('invoice/Invoice_dash_icon_2x.png') }}" style="width: 38px;">
       
              {{ custom_invoice_trans('total', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}
              {{ number_format($data['data'][0]['total_distance'], 2) }}
              @if ($data['data'][0]['unit'] == 'KM')
              {{ custom_invoice_trans('kms', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}
              @else
              {{ custom_invoice_trans('miles', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}
              @endif:
              {{ number_format($data['data'][0]['total_distance'], 2) }}
              @if ($data['data'][0]['unit'] == 'KM')
              <span>{{ custom_invoice_trans('km', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              @else
              <span>{{ custom_invoice_trans('miles', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              @endif
            </div>
            <div class="divider"></div>
            <div class="row icon">
              <img src="{{ public_path('invoice/Invoice_time_icon_2x.png') }}" style="width: 38px;">
              {{ custom_invoice_trans('total_mins', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}:
              {{ $data['data'][0]['total_time'] }}
              {{ custom_invoice_trans('min', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}
            </div>
            <div class="divider"></div>
            <div class="row icon">
              <img src="{{ public_path('invoice/Invoice_driver_default.png') }}" style="width: 38px;">
              {{ $data['data'][0]['vehicle_type_name'] }}
              - {{ strtoupper($data['data'][0]['driverDetail']['data']['car_number'])}}
            </div>
            <div class="divider"></div>
            <div class="route">
              <!-- Pickup -->
              <div class="route-row">
                  <span class="time">
                      {{ $data['data'][0]['trip_start_time'] }}
                  </span>

                  <span class="icon-col">
                      <span class="dot pickup"></span>
                      <span class="line"></span>
                  </span>

                  <span class="address">
                      {{ $data['data'][0]['pick_address'] }}
                  </span>
              </div>
              <!-- Drop -->
              <div class="route-row">
                  <span class="time">
                      {{ $data['data'][0]['completed_at'] }}
                  </span>

                  <span class="icon-col">
                      <span class="dot drop"></span>
                  </span>

                  <span class="address">
                      {{ $data['data'][0]['drop_address'] }}
                  </span>
              </div>
            </div>
          </div>

          <!-- RIGHT -->
          <div class="col right" style="margin-left:20px;padding-left:20px">

            <div class="title">{{ custom_invoice_trans('bill_details', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</div>
             <div class="divider"></div>
            
            <!-- base or bide fare -->
            <div class="bill">
              @if ($data['data'][0]['is_bid_ride'] === 1)
                <span>{{ custom_invoice_trans('bid_fare', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              @else
                <span>{{ custom_invoice_trans('base_fare_for', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}
                  <br/>
                  {{ number_format($data['data'][0]['requestBill']['data']['base_distance'], 2)}}  
                  @if ($data['data'][0]['unit'] == 'KM')
                  <span>{{ custom_invoice_trans('km', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
                  @else
                  <span>{{ custom_invoice_trans('miles', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
                  @endif
                </span>
              @endif
              <span style="float:right;">
                @if ($data['data'][0]['is_bid_ride'] === 1)
                {{ $data['data'][0]['requested_currency_symbol'] }}
                  {{ $data['data'][0]['accepted_ride_fare'] }}
                @else
                  {{ $data['data'][0]['requested_currency_symbol'] }} {{ $data['data'][0]['requestBill']['data']['base_price'] }}
                  
                @endif
              </span>
            </div>

            <!-- distance fare -->
            <div class="bill">
              <span>{{ custom_invoice_trans('distance_fare_for', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }} <br> 
                {{ number_format($data['data'][0]['total_distance'], 2) }}
                @if ($data['data'][0]['unit'] == 'KM')
                <span>{{ custom_invoice_trans('km', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
                @else
                <span>{{ custom_invoice_trans('miles', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
                @endif
              </span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }} {{ $data['data'][0]['requestBill']['data']['distance_price'] }}</span>
            </div>

            <!-- time price -->
            <div class="bill">
              <span>{{ custom_invoice_trans('time_fare_for', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }} <br>
                {{ $data['data'][0]['requestBill']['data']['total_time'] }}
                {{ custom_invoice_trans('min', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }} {{ $data['data'][0]['requestBill']['data']['time_price'] }}</span>
            </div>

            <!-- waiting charges -->
            <div class="bill">
              <span>{{ custom_invoice_trans('waiting_charge_for', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }} <br>
                  {{ $data['data'][0]['requestBill']['data']['calculated_waiting_time'] }}
                  {{ custom_invoice_trans('min', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }} {{ $data['data'][0]['requestBill']['data']['waiting_charge'] }}</span>
            </div>

            <!-- cancellation fee -->
            <div class="bill">
              <span>{{ custom_invoice_trans('cancellation_fee', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }} {{ $data['data'][0]['requestBill']['data']['cancellation_fee'] }}</span>
            </div>

            <!-- airport surge -->
            <div class="bill">
              @if($data['data'][0]['requestBill']['data']['airport_surge_fee'] > 0 &&  $data['data'][0]['transport_type'] === 'taxi')
              <span>{{ custom_invoice_trans('airport_surge_fee', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }}
              {{ number_format((float) $data['data'][0]['requestBill']['data']['airport_surge_fee'], 2) }}</span>
              @endif
            </div>

             <!-- convenience fee -->
            <div class="bill">
              <span>{{ custom_invoice_trans('convenience_fee', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }}
              {{ $data['data'][0]['requestBill']['data']['admin_commision'] ? $data['data'][0]['requestBill']['data']['admin_commision']: 0 }}</span>
            </div>

             <!-- Promo code -->
            <div class="bill">
              <span>{{ custom_invoice_trans('promo_discount', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;"> - {{ $data['data'][0]['requested_currency_symbol'] }}
                {{ $data['data'][0]['requestBill']['data']['promo_discount'] ? $data['data'][0]['requestBill']['data']['promo_discount']: 0 }}</span>
            </div>
             <!-- additional charges -->
              @if($data['data'][0]['requestBill']['data']['additional_charges_amount'] > 0)
              <div class="bill">
                <span>{{ custom_invoice_trans('additional_charges', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }} <br> {{$data['data'][0]['requestBill']['data']['additional_charges_reason']}}</span>
                <span style="float:right;"> {{ $data['data'][0]['requested_currency_symbol'] }}
                  {{ number_format((float) $data['data'][0]['requestBill']['data']['additional_charges_amount'], 2) }}</span>
              </div>
              @endif

               @if($data['data'][0]['requestBill']['data']['preference_price_total'] > 0)
              <div class="bill">
                <span>{{ custom_invoice_trans('preference_charges', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
                <span style="float:right;"> {{ $data['data'][0]['requested_currency_symbol'] }}
                  {{ number_format((float) $data['data'][0]['requestBill']['data']['preference_price_total'], 2) }}</span>
              </div>
              @endif

            <div class="bill highlight">
              <span>{{ custom_invoice_trans('tax', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }} ({{ get_settings('service_tax') }}%)</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }} {{ number_format($data['data'][0]['requestBill']['data']['service_tax'],2) }}</span>
            </div>

            <div class="bill total">
              <span>{{ custom_invoice_trans('total', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
              <span style="float:right;">{{ $data['data'][0]['requested_currency_symbol'] }} {{ number_format($data['data'][0]['requestBill']['data']['total_amount'],2) }}</span>
            </div>

          </div>

        </div>

        <div class="divider"></div>

        <!-- PAYMENT -->
        <div class="payment">
          <span>{{ custom_invoice_trans('paid_by', [] ,$data['data'][0]['userDetail']['data']['lang'] ?? 'en') }}</span>
          <strong>{{ ucfirst($data['data'][0]['payment_type_string']) }}</strong>
          <span class="pay-amount">
            {{ $data['data'][0]['requested_currency_symbol'] }}
            {{ number_format($data['data'][0]['requestBill']['data']['total_amount'],2) }}
          </span>
        </div>

      </div>
    </div>  


  </body>
<style>
  body {
  font-family: 'DejaVu Sans', sans-serif;
}
.body::after {
  content: "";
  display: table;
  clear: both;
}
.page {
  padding: 20px;
}

.invoice {
  width: 660px;
  margin: auto;
  background: #ffffff;
  border-radius: 4px;
  padding: 16px;
  border: 10px solid #e0e0e0;
}

.header {
  width: 100%;
}

.header-left,
.header-right {
  display: inline-block;
  width: 49%;
  vertical-align: top;
}

.logo {
  width: 150px;
}

.amount {
  text-align: center;
  font-size: 38px;
  font-weight: bold;
  margin-top: 10px;
}

.invoice-no,
.thanks {
  text-align: center;
  font-size: 14px;
  color: #707070;
}

.divider {
  height: 1px;
  background: #ececec;
  margin: 12px 0;
}

.divider-thick {
  height: 3px;
  background: #ececec;
  margin: 14px 0;
}

.body {
}
.col.left {
  width: 48%;
  float: left;
  padding-right: 15px;
  box-sizing: border-box;
}

.col.right {
  width: 48%;
  float: right;
  padding-left: 15px;
  box-sizing: border-box;
}

.title {
  font-size: 16px;
  font-weight: bold;
  padding-bottom: 8px;
  margin-bottom: 8px;
  margin-bottom: 10px;
  padding-top: 10px;
}

.row {
  font-size: 14px;
  margin: 6px 0;
}

.icon img {
  width: 18px;
  vertical-align: middle;
  margin-right: 6px;
}

.route {
      width: 100%;
      margin-top: 10px;
      font-family: 'DejaVu Sans', sans-serif;
  }

  .route-row {
      display: table;
      width: 100%;
      margin-bottom: 12px;
  }

  .time {
      display: table-cell;
      width: 90px;
      font-size: 12px;
      vertical-align: top;
      color: #000;
  }

  .icon-col {
      width: 20px;
      text-align: center;
      vertical-align: top;
      position: relative;
  }

  .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-top: 3px;
  }

  .dot.pickup {
    background-color: #28a745; /* green */
  }

  .dot.drop {
      background-color: #dc3545; /* red */
  }

  .line {
      position: absolute;
      top: 12px;
      left: 4px;
      height: 20%;
      border-left: 1px dotted #bbb;
  }

  .address {
      display: table-cell;
      padding-left: 8px;
      font-size: 12px;
      color: #000;
      line-height: 1.4;
  }

.bill {
  font-size: 14px;
  margin: 6px 0;
}

.bill span {
  display: inline-block;
  width: 48%;
}

.bill.highlight {
  background: #f3f3f3;
  padding: 6px;
}

.bill.total {
  font-weight: bold;
  font-size: 16px;
}

.payment {
  text-align: center;
  font-size: 14px;
}

.pay-amount {
  display: block;
  font-weight: bold;
  margin-top: 4px;
}
</style>
</html>