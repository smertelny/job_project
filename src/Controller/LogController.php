<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Log;

class LogController extends AbstractController {
    /**
     * @Route("/logs", name="log_index")
     */
    function index() {
        $logs = $this->getDoctrine()
            ->getRepository(Log::class)
            ->findAll();
        return $this->render(
            'logs/index.html.twig',
            array('logs' => $logs)
        );
    }
}