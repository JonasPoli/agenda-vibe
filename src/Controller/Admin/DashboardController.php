<?php

namespace App\Controller\Admin;

use App\Repository\EventoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EventoRepository $eventos): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'total' => $eventos->count([]),
            'proximos' => $eventos->contarProximos(),
            'inativos' => $eventos->contarInativos(),
            'agenda' => $eventos->proximosVisiveis(5),
            'destaques' => $eventos->destaquesVigentes(),
        ]);
    }
}
