<?php

namespace App\Actions\Logistic\Code;

use App\Models\Logistic\Delivery;
use App\Models\Logistic\Item;
use App\Models\Logistic\Manifest;
use Carbon\Carbon;

class Code
{
    public function delivery($officeCode, $userCode, $date = null)
    {
        $date = $this->generateDate($date);
        $lastData = Delivery::whereDate('created_at', $date)->orderBy('code', 'desc')->first();

        ($lastData) ? $count = (int) str_replace('0', '', substr($lastData->code, -4)) + 1 : $count = 1;
        return '01'.$this->disorderedDate(1, $date).substr('00'.$officeCode, -3).substr('00'.$userCode, -3).substr('000'.$count, -4);
    }

    public function manifest($officeCodeFrom, $officeCodeTo, $date = null)
    {
        $date = $this->generateDate($date);
        $lastData = Manifest::whereDate('created_at', $date)->orderBy('code', 'desc')->first();

        ($lastData) ? $count = (int) str_replace('0', '', substr($lastData->code, -4)) + 1 : $count = 1;
        return '02'.$this->disorderedDate(2, $date).substr('00'.$officeCodeFrom, -3).substr('00'.$officeCodeTo, -3).substr('000'.$count, -4);
    }

    public function receipt($deliveryId, $date = null)
    {
        $date = $this->generateDate($date);
        $lastData = Item::whereDate('created_at', $date)->where('logistic_delivery_id', $deliveryId)->where('receipt', '!=', null)->orderBy('receipt', 'desc')->first();

        ($lastData) ? $count = (int) str_replace('0', '', substr($lastData->receipt, -2)) + 1 : $count = 1;
        return '03'.$this->disorderedDate(3, $date).substr('000'.$deliveryId, -4).substr('0'.$count, -2);
    }

    private function disorderedDate($type, $date = null)
    {
        $date = $this->generateDate($date);
        $dateExp = explode('-', $date);

        $dateSubstr = array_sum(str_split($dateExp[0])) + (int) $dateExp[1] + (int) $dateExp[2];
        $dateSubstr = substr('00'.$dateSubstr, -3);

        $dateSum = array_sum(str_split($dateExp[0])).array_sum(str_split($dateExp[1])).array_sum(str_split($dateExp[2]));

        return $dateSubstr.substr('000'.($dateSum + $type), -4);
    }

    private function generateDate($date = null)
    {
        return ($date === null) ? Carbon::now()->format('Y-m-d') : $date;
    }
}
