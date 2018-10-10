<?php

namespace App\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Log;

class LogController extends AbstractController {
    /**
     * @Route("/logs/{page}", name="log_index")
     */
    function index($page=1) {
        $logs = $this->getDoctrine()
            ->getRepository(Log::class)
            ->find20Logs($page);

        $paginator = new Paginator($logs);

        return $this->render(
            'logs/index.html.twig',
            array(
                'logs' => $paginator,
                'count' => intdiv(count($paginator), 20) + 1,
                'page' => $page
            )
        );
    }
}
