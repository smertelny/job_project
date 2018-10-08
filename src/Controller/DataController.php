<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Log;

function get_data($url) {
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($handle);
    $status_code = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    if ($status_code === 200) {
        curl_close($handle);
        $result = json_decode($result, true);
        return array(
            'result' => $result,
            'status_code' => $status_code
        );
    } else {
        curl_close($handle);
        return array(
            'error' => $result,
            'status_code' => $status_code,
        );
    }
}



class DataController extends AbstractController {
    function save_logs($ip, $status_code) {
        $manager = $this->getDoctrine()->getManager();

        $log = new Log();
        $log->setUserIp($ip);
        $log->setStatusCode($status_code);
        
        $manager->persist($log);
        $manager->flush();
    }

    function getRealIpAddr($server) {
        if (!empty($server->get('HTTP_CLIENT_IP'))) {
            $ip = $server->get('HTTP_CLIENT_IP');
        } elseif (!empty($server->get('HTTP_X_FORWARDED_FOR'))) {
            $ip = $server->get('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $server->get('REMOTE_ADDR');
        }
        return $ip;
    }

    /**
     * @Route("", name="index")
     */
    function index(Request $request) {
        $url = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
        if ($request->query->get('date')) {
            $url = $url . "&date=" . $request->query->get('date');
        }

        $result = get_data($url);
        $status_code = $result['status_code'];
        unset($result['status_code']);
        $ip = $this->getRealIpAddr($request->server);
        $this->save_logs($ip, $status_code);

        $yesturday_data = get_data($url . "&date=". date('Ymd', strtotime("-1 day")));
        $status_code = $yesturday_data['status_code'];
        unset($yesturday_data['status_code']);
        $this->save_logs($ip, $status_code);


        return $this->render(
            'app/index.html.twig',
            array(
                "data" => $result,
                "yesturday_data" => $yesturday_data,
                "req" => array(
                    'cur' => $request->query->get("currency")
                )
            )
        );
    }
}