<?php

namespace App\Controller\Admin;

use App\Entity\Evento;
use App\Form\EventoType;
use App\Repository\CategoriaRepository;
use App\Repository\EventoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/eventos')]
class EventoController extends AbstractController
{
    #[Route('', name: 'admin_evento_index', methods: ['GET'])]
    public function index(Request $request, EventoRepository $eventos, CategoriaRepository $categorias): Response
    {
        $busca = trim((string) $request->query->get('q'));
        $categoriaId = $request->query->getInt('categoria') ?: null;
        $situacao = \in_array($request->query->get('situacao'), ['ativos', 'inativos', 'destaques'], true)
            ? $request->query->get('situacao')
            : null;

        return $this->render('admin/evento/index.html.twig', [
            'eventos' => $eventos->buscarAdmin($busca ?: null, $categoriaId, $situacao),
            'categorias' => $categorias->findBy([], ['nome' => 'ASC']),
            'busca' => $busca,
            'categoriaId' => $categoriaId,
            'situacao' => $situacao,
            'filtrando' => $busca || $categoriaId || $situacao,
            'destaquesVigentes' => \count($eventos->destaquesVigentes()),
        ]);
    }

    #[Route('/{id}/alternar', name: 'admin_evento_alternar', methods: ['POST'])]
    public function alternar(Request $request, Evento $evento, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('alternar'.$evento->getId(), $request->getPayload()->getString('_token'))) {
            $evento->setAtivo(!$evento->isAtivo());
            $em->flush();
            $this->addFlash('success', sprintf('Evento “%s” %s.', $evento->getTitulo(), $evento->isAtivo() ? 'ativado' : 'desativado'));
        }

        return $this->voltarParaLista($request);
    }

    #[Route('/{id}/destacar', name: 'admin_evento_destacar', methods: ['POST'])]
    public function destacar(Request $request, Evento $evento, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($this->isCsrfTokenValid('destacar'.$evento->getId(), $request->getPayload()->getString('_token'))) {
            $evento->setDestaque(!$evento->isDestaque());
            $erros = $validator->validate($evento);

            if (\count($erros) > 0) {
                $this->addFlash('error', $erros[0]->getMessage());
            } else {
                $em->flush();
                $this->addFlash('success', sprintf('Evento “%s” %s.', $evento->getTitulo(), $evento->isDestaque() ? 'agora está em destaque' : 'saiu do destaque'));
            }
        }

        return $this->voltarParaLista($request);
    }

    /** Volta para a lista mantendo os filtros que estavam aplicados. */
    private function voltarParaLista(Request $request): Response
    {
        $voltar = $request->getPayload()->getString('voltar');

        return str_starts_with($voltar, '/admin/eventos')
            ? $this->redirect($voltar, Response::HTTP_SEE_OTHER)
            : $this->redirectToRoute('admin_evento_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/novo', name: 'admin_evento_novo', methods: ['GET', 'POST'])]
    public function novo(Request $request, EntityManagerInterface $em): Response
    {
        $evento = new Evento();
        $form = $this->createForm(EventoType::class, $evento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($evento);
            $em->flush();
            $this->addFlash('success', sprintf('Evento “%s” criado.', $evento->getTitulo()));

            return $this->redirectToRoute('admin_evento_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evento/form.html.twig', [
            'evento' => $evento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/editar', name: 'admin_evento_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Evento $evento, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventoType::class, $evento);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', sprintf('Evento “%s” atualizado.', $evento->getTitulo()));

            return $this->redirectToRoute('admin_evento_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evento/form.html.twig', [
            'evento' => $evento,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/excluir', name: 'admin_evento_excluir', methods: ['POST'])]
    public function excluir(Request $request, Evento $evento, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('excluir'.$evento->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($evento);
            $em->flush();
            $this->addFlash('success', sprintf('Evento “%s” excluído.', $evento->getTitulo()));
        }

        return $this->redirectToRoute('admin_evento_index', [], Response::HTTP_SEE_OTHER);
    }
}
