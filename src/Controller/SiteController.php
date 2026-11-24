<?php

namespace App\Controller;

use App\Repository\EventoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteController extends AbstractController
{
    #[Route('/', name: 'site_inicio')]
    public function inicio(EventoRepository $eventos): Response
    {
        $proximos = $eventos->proximosVisiveis();

        // Agrupa por mês ("2026-12") para a lista da agenda.
        $porMes = [];
        foreach ($proximos as $evento) {
            $porMes[$evento->getInicio()->format('Y-m')][] = $evento;
        }

        return $this->render('site/inicio.html.twig', [
            'proximo' => $proximos[0] ?? null,
            'destaques' => $eventos->destaquesVisiveis(),
            'porMes' => $porMes,
        ]);
    }

    #[Route('/evento/{slug}', name: 'site_evento')]
    public function evento(string $slug, EventoRepository $eventos): Response
    {
        $evento = $eventos->visivelPorSlug($slug);

        // Evento inexistente, inativo ou de categoria inativa: página amigável com status 404.
        if (!$evento) {
            return $this->render('site/evento_nao_encontrado.html.twig', [
                'sugestoes' => $eventos->proximosVisiveis(3),
            ], new Response(status: Response::HTTP_NOT_FOUND));
        }

        return $this->render('site/evento.html.twig', [
            'evento' => $evento,
            'outros' => array_filter(
                $eventos->proximosVisiveis(4),
                fn ($e) => $e->getId() !== $evento->getId(),
            ),
        ]);
    }
}
