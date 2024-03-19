<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CurrencyRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:currency-rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = getenv('CURRENCY_API_KEY');
        $response = Http::get("https://api.currencyapi.com/v3/latest?apikey=$apiKey&currencies=COP");
        $result = json_decode($response, true);
        $rate = $result['data']['COP']['value'];
        if (ExchangeRate::whereDate('created_at', now())->exists() === false)  {
            ExchangeRate::create(['rate' => round($rate, 2)]);
        }
        return Command::SUCCESS;
    }

    private function getHistiricalData() {
        ini_set('max_execution_time', 0);
        $apiKey = getenv('CURRENCY_API_KEY');
        $date = Carbon::now()->sub('1 month');
        while ($date->lt(Carbon::now())) {
            $formatedDate = $date->format('Y-m-d');
            $response = Http::get("https://api.currencyapi.com/v3/historical?apikey=$apiKey&currencies=COP&date=$formatedDate");
            $result = json_decode($response, true);
            if (isset($result['data'])) {
                $rate = $result['data']['COP']['value'];
                ExchangeRate::create([
                    'rate' => round($rate, 2),
                    'created_at' => Carbon::parse($result['meta']['last_updated_at']),
                    'updated_at' => Carbon::parse($result['meta']['last_updated_at']),
                ]);
            }
            $date->add('1 day');
        }
    }
}
