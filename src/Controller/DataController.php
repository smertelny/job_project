<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

function get_data($url) {
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    // curl_setopt($handle, CURLOPT_HEADER, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($handle);
    // return $result;
    if (curl_getinfo($handle, CURLINFO_RESPONSE_CODE) === 200) {
        curl_close($handle);
        $result = json_decode($result, true);
        return $result;
    } else {
        curl_close($handle);
        return array('error' => $result);
    }
}

class DataController extends AbstractController {
    /**
     * @Route("", name="index")
     */
    function index(Request $request) {
        $url = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
        if ($request->query->get('date')) {
            $url = $url . "&date=" . $request->query->get('date');
        }
        // print_r(get_data($url));
        return $this->render(
            'app/index.html.twig',
            array(
                "data" => get_data($url),
                "req" => array(
                    'cur' => $request->query->get("currency")
                )
            )
        );
    }
}