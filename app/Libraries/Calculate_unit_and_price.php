<?php
namespace App\Libraries;


class Calculate_unit_and_price {

    public function calculate_purchase_price($qty_ton,$qty_kg,$price){
        $kg = $this->convert_total_kg($qty_ton,$qty_kg);
        $kg_price = $this->ton_price_to_kg_price($price);
        return $kg * $kg_price;

    }
    public function calculate_selling_price($qty_ton,$qty_kg,$salePrice){
        $kg = $this->convert_total_kg($qty_ton,$qty_kg);
        $kg_price = $this->ton_price_to_kg_price($salePrice);
        return $kg * $kg_price;
    }
    public function convert_total_kg($qty_ton,$qty_kg){
        $ton_to_kg = $qty_ton * 1000;
        return $ton_to_kg + $qty_kg;
    }

    public function ton_price_to_kg_price($price){
        return $price / 1000;
    }

    public function convert_total_kg_to_ton($qty){
        $totalTon = floor($qty / 1000);
        $totalKg = $qty % 1000;
        return array('ton' => $totalTon, 'kg'=> $totalKg);
    }

    public function par_ton_price_by_par_kg_price($kgPrice){
        return $kgPrice*1000;
    }



}