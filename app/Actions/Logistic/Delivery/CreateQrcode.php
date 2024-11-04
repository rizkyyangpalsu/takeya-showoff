<?php

namespace App\Actions\Logistic\Delivery;

use App\Models\Logistic\Item;
use Illuminate\Support\Facades\DB;
use App\Actions\Logistic\Code\Code;

class CreateQrcode
{
    /**
     * @throws \Exception
     */
    public function generate($delivery, $date = null)
    {
        $items = Item::where('logistic_delivery_id', $delivery->id)->with('price', 'delivery.originOffice', 'delivery.destinationOffice')->get();

        if ($items) {
            DB::beginTransaction();

            try {
                foreach ($items as $key => $item) {
                    if ($item->receipt == null) {
                        $code = new Code();
                        $item->receipt = $code->receipt($delivery->id, $date);
                        $item->save();
                    }
                }

                $delivery->is_printed_receipt = true;
                $delivery->save();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        }

        return $items;
    }
}
