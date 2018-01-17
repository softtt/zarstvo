<?php

function _russianpostcalc_api_communicate($request)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "http://russianpostcalc.ru/api_beta_077.php");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($curl);

    curl_close($curl);
    if ($data === false) {
        return "10000 server error";
    }

    $js = json_decode($data, $assoc = true);
    return $js;
}

function russianpostcalc_api_calc($apikey, $password, $from_index, $to_index, $weight, $ob_cennost_rub)
{
    $request = array("apikey" => $apikey,
        "method" => "calc",
        "from_index" => $from_index,
        "to_index" => $to_index,
        "weight" => $weight,
        "ob_cennost_rub" => $ob_cennost_rub
    );

    if ($password != "") {
        $all_to_md5 = $request;
        $all_to_md5[] = $password;
        $hash = md5(implode("|", $all_to_md5));
        $request["hash"] = $hash;
    }

    $ret = _russianpostcalc_api_communicate($request);

    return $ret;
}

$ret = russianpostcalc_api_calc("8e28bc8bc8c336859047a2174609fff9", "8XcDQK5PXad2H5P", "344000", "299001", "1", "1000");

if (isset($ret['msg']['type']) && $ret['msg']['type'] == "done") {
    echo "success! codepage: UTF-8 <br/>";
    print_r($ret);
    echo "<br/>";
} else {
    echo "error! codepage: UTF-8 <br/>";
    print_r($ret);
}

