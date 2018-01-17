<?php

class HelperRussianPostDelivery
{
    const FROM_INDEX = "344000";

    const FIXED_EXPENCES = 35;

    /**
     * Make request to russian post tariff calculator.
     * Find full reference here http://tariff.russianpost.ru/
     *
     * @param $to_index Index of delivery address (6 digits)
     * @param $weight Weight of all products in gramms
     * @param $cost Total cost of all products in cart in kopecks
     *
     * @return array
     */
    private function make_request($to_index, $weight, $cost)
    {
        $curl = curl_init();
        $request = sprintf("http://tariff.russianpost.ru/tariff/v1/calculate?json&typ=4&cat=2&weight=%d&sumoc=%d&from=%d&to=%d&isavia=0&closed=1",
            $weight, $cost, self::FROM_INDEX, $to_index);

        curl_setopt($curl, CURLOPT_URL, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($curl);

        curl_close($curl);

        if ($data === false) {
            return ['error' => 'server respond with errors'];
        }

        $data = json_decode($data, $assoc = true);

        if (isset($data['error'])) {
            return $data;
        }

        if (isset($data['paynds'])) {
            return [
                'delivery_cost' => $data['paynds'] / 100, #paynds come in kopecks
            ];
        }
    }

    public function get_calculation($to_index, $weight, $cost)
    {
        $return = $this->make_request($to_index, $weight * 1000, $cost * 100);

        $response = [
            'delivery_cost' => 0,
        ];

        if (isset($return['error'])) {
            return $return;
        }

        if (isset($return['delivery_cost'])) {
            // $response['delivery_cost'] = ceil(($return['delivery_cost'] + self::FIXED_EXPENCES) / 10) * 10;
            $response['delivery_cost'] = ceil(($return['delivery_cost']) / 10) * 10;
        }

        return $response;
    }
}
