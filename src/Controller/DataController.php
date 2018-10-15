<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Log;



class DataController extends AbstractController 
{
    private function get_data($url) 
    {
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($handle);
        $status_code = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($status_code === 200) 
        {
            curl_close($handle);
            $result = json_decode($result, true);
            return array(
                'result' => $result,
                'status_code' => $status_code
            );
        } 
        else 
        {
            curl_close($handle);
            return array(
                'error' => $result,
                'status_code' => $status_code,
            );
        }
    }
    private function save_logs($ip, $status_code, $queryName, $foreignQuery) {
        $manager = $this->getDoctrine()->getManager();

        $log = new Log();
        $log->setUserIp($ip);
        $log->setStatusCode($status_code);
        $log->setQueryName($queryName);
        $log->setForeignQueryName($foreignQuery);
        
        $manager->persist($log);
        $manager->flush();
    }

    private function getRealIpAddr($server) {
        if (!empty($server->get('HTTP_CLIENT_IP'))) 
        {
            $ip = $server->get('HTTP_CLIENT_IP');
        }
        elseif (!empty($server->get('HTTP_X_FORWARDED_FOR')))
        {
            $ip = $server->get('HTTP_X_FORWARDED_FOR');
        } 
        else 
        {
            $ip = $server->get('REMOTE_ADDR');
        }
        return $ip;
    }

    /**
     * @Route("", name="index")
     */
    public function index(Request $request) {

        return $this->render("app/index.html.twig", array());
    }

    /**
     * @Route("fetch", name="fetch")
     */
    public function fetcher(Request $request) 
    {
        $url = 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json';
        if ($request->query->get('date')) {
            $url = $url . "&date=" . $request->query->get('date');
        }
        if ($request->query->get('currency')) {
            $url = $url . "&valcode=" . $request->query->get('currency');
        }

        $result = $this->get_data($url);
        $status_code = $result['status_code'];
        unset($result['status_code']);
        $ip = $this->getRealIpAddr($request->server);
        $queryName = $request->getRequestUri();
        $this->save_logs($ip, $status_code, $queryName, $url);

        if ($request->headers->get('accept') === "application/json") {
            $res = count($result['result']) > 1 ? $result['result'] : $result['result'][0]; 
            $response = new JsonResponse($res);
            return $response;
        }

        $url = $url . "&date=". date('Ymd', strtotime("-1 day"));
        $yesturday_data = $this->get_data($url);
        $status_code = $yesturday_data['status_code'];
        unset($yesturday_data['status_code']);
        $queryName = $request->getRequestUri();
        $this->save_logs($ip, $status_code, $queryName, $url);

        return $this->render(
            'app/fetch.html.twig',
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