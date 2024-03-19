<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{__('page.custom_agent')}} {{__('page.report')}}</title>
    <link href="{{asset('assets/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    <style>
        body {
            border: solid 1px black;
            padding: 10px;
            background-color: #FFF;
        }
        .header-title {
            margin-top: 25px;
            text-align:center;
            font-size:30px;
            font-weight: 800;
            text-decoration:underline;
            clear: both;
        }
        .value {
            text-decoration: underline;
            font-weight: 600;
        }
        .table-bordered, .table-bordered td, .table-bordered th {
            border: 1px solid #2d2d2d;
        }
        .table thead th {
            border-bottom: 2px solid #2d2d2d;
            font-size: 13.6px;
        }
        #table-purchases td {
            padding-top: 8px ;
            padding-bottom: 3px ;
        }
        #table-custom_agent {
            font-size: 14px;
            font-weight: 500;
            color: black;
        }
        #table-custom_agent tbody td {
            height: 25px;
        }
        .table-payment td,
        .table-payment th {
            padding: 5px;
            border: none;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h5 class="float-right">{{__('page.date')}} : {{date('d/m/Y')}}</h5>
    <h1 class="header-title">{{__('page.custom_agent_report')}}</h1>

    @php
        $user = Auth::user();
        $purchases_array = $custom_agent->purchases()->pluck('id');
        $total_purchases = $custom_agent->purchases()->count();
        $total_amount = $custom_agent->purchases->sum('custom_taxes');
    @endphp

    <table class="w-100 mt-3" id="table-custom_agent">
        <tbody>
            <tr>
                <td>
                    <table class="w-100">
                        <tbody>
                            <tr>
                                <td>{{__('page.name')}} : </td>
                                <td class="value">{{$custom_agent->name}}</td>
                            </tr>
                            <tr>
                                <td>{{__('page.company')}} : </td>
                                <td class="value">{{$custom_agent->company}}</td>
                            </tr>
                            <tr>
                                <td>{{__('page.email')}} : </td>
                                <td class="value">{{$custom_agent->email}}</td>
                            </tr>
                            <tr>
                                <td>{{__('page.phone_number')}} : </td>
                                <td class="value">{{$custom_agent->phone_number}}</td>
                            </tr>
                            @if ($custom_agent->country)
                                <tr>
                                    <td>{{__('page.country')}} : </td>
                                    <td class="value">{{$custom_agent->country->name}}</td>
                                </tr>
                            @endif
                            <tr>
                                <td>{{__('page.city')}} : </td>
                                <td class="value">{{$custom_agent->city}}</td>
                            </tr>
                            <tr>
                                <td>{{__('page.address')}} : </td>
                                <td class="value">{{$custom_agent->address}}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td valign="bottom">
                    <table class="w-100">
                        <tbody>
                            <tr>
                                <td>{{__('page.total_amount')}} : </td>
                                <td class="value" style="font-size:20px">{{number_format($total_amount)}}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <h3 class="mt-4" style="font-size: 18px; font-weight: 500;">{{__('page.purchases')}}</h3>
    <table class="table" id="table-purchases">
        <thead>
            <tr class="bg-blue">
                <th>{{__('page.date')}}</th>
                <th>{{__('page.reference_no')}}</th>
                <th>{{__('page.grand_total')}}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $footer_grand_total = $footer_paid = 0;
                $data = $custom_agent->purchases;

            @endphp
            @foreach ($data as $item)
                @php
                    $footer_grand_total += $item->custom_taxes;
                @endphp
                <tr>
                    <td class="timestamp">{{date('d/m/Y', strtotime($item->purchased_at))}}</td>
                    <td class="reference_no">{{$item->reference_no}}</td>
                    <td class="grand_total"> {{number_format($item->custom_taxes)}} </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-right">{{__('page.total')}}</th>
                <th>{{number_format($footer_grand_total)}}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>