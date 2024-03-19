<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sale Report</title>

    <link href="{{asset('assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    <style>
        body {
            color: #584747;
            border: solid 1px black;
            padding: 10px;
            background-color: #FFF;
            /* background: url('{{asset("images/bg_pdf.jpg")}}') no-repeat; */
            /* background-size: 100% 100%; */
        }
        .table td, .table th {
            padding: .4rem;
        }
        .main {
        }
        .title {
            margin-top: 30px;
            text-align:center;
            font-size:30px;
            font-weight: 700;
            text-decoration:underline;
        }
        .value {
            font-size: 14px;
            font-weight: 500;
            text-decoration: underline;
        }
        .field {
            font-size: 12px;
        }
        td.value {
            /* line-height: 1; */
        }
        .table-bordered, .table-bordered td, .table-bordered th {
            border: 1px solid #2d2d2d;
        }
        .table thead th {
            border-bottom: 2px solid #2d2d2d;
        }
        #table-customer {
            font-size: 14px;
            font-weight: 600;
        }
        #table-item {
            font-size: 14px;
            color: #584747;
        }
        .footer {
            position: absolute;
            bottom: 10px;;
        }
        .footer tr td {
            font-size: 11px;
            color: #584747;
            text-align: center;
            line-height: 1;
        }

    </style>
</head>
<body>
    <div class="main">
        <h2 class="text-center font-weight-bold mt-5">{{__('page.sale_report')}}</h2>
        <table class="w-100 mt-5" id="table-customer">
            <tbody>
                <tr>
                    <td class="w-50" valign="top">
                        <h5 class="mb-0 text-uppercase">{{__('page.sale')}}</h5>
                        <p class="my-0 text-center" style="font-size:24px">{{$sale->reference_no}}</p>

                    </td>
                    <td class="w-50 pt-3 text-right" rowspan="2" valign="top">
                        @if($sale->customer)
                            <table class="w-100">
                                <tr><td class="value">{{$sale->customer->name}}</td></tr>
                                <tr><td class="value">{{$sale->customer->company}}</td></tr>
                                <tr><td class="value">{{$sale->customer->email}}</td></tr>
                                <tr><td class="value">{{$sale->customer->phone_number}}</td></tr>
                                <tr><td class="value">{{$sale->customer->country->name ?? ''}}</td></tr>
                                <tr><td class="value">{{$sale->customer->city}}</td></tr>
                                <tr><td class="value">{{$sale->customer->address}}</td></tr>
                            </table>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="pt-1">
                        <table class="w-100">
                            <tr>
                                <td class="field">{{__('page.date')}} : </td>
                                <td class="value">{{date('d/m/Y', strtotime($sale->sold_at))}}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="table">
            <thead>
                <tr>
                    <th>{{__('page.vehicle')}}</th>
                    <th>{{__('page.price')}}(USD)</th>
                    <th>{{__('page.price')}}(COP)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{$sale->product->reference}}({{ $sale->product->engine_number }} / {{ $sale->product->vin }})</td>
                    <td>
                        {{number_format($sale->total_price)}}
                    </td>
                    <td>
                        {{number_format($sale->total_price_cop)}}
                    </td>
                </tr>
            </tbody>
        </table>
        <h4 class="mt-e" style="font-weight: 600;">{{__('page.payment_list')}}</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>{{__('page.date')}}</th>
                    <th>{{__('page.reference_no')}}</th>
                    <th>{{__('page.amount')}}</th>
                    <th>{{__('page.note')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->payments as $item)
                    <tr>
                        <td>{{date('d/m/Y', strtotime($item->paid_at))}}</td>
                        <td>{{$item->reference_no}}</td>
                        <td>
                            {{number_format($item->amount)}} USD
                            /
                            {{number_format($item->amount_cop)}} COP
                        </td>
                        <td>
                            <span class="tx-info note">{{$item->note}}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" style="text-align:right">{{__('page.paid')}}</th>
                    <th>
                        {{number_format($sale->paid_amount)}} USD
                        /
                        {{number_format($sale->paid_amount_cop)}} COP
                    </th>
                </tr>
                <tr>
                    <th colspan="3" style="text-align:right">{{__('page.balance')}}</th>
                    <th>
                        {{number_format($sale->total_price - $sale->paid_amount)}} USD
                        /
                        {{number_format($sale->total_price_cop - $sale->paid_amount_cop)}} COP
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>