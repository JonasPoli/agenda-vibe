<?php

namespace App\Controller\Admin;

use App\Entity\Categoria;
use App\Form\CategoriaType;
use App\Repository\CategoriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categorias')]
class CategoriaController extends AbstractController
{
    #[Route('', name: 'admin_categoria_index', methods: ['GET'])]
    public function index(CategoriaRepository $categorias): Response
    {
        return $this->render('admin/categoria/index.html.twig', [
            'linhas' => $categorias->listarComTotais(),
        ]);
    }

    #[Route('/nova', name: 'admin_categoria_nova', methods: ['GET', 'POST'])]
    public function nova(Request $request, EntityManagerInterface $em): Response
    {
        return $this->salvar($request, $em, new Categoria());
    }

    #[Route('/{id}/editar', name: 'admin_categoria_editar', methods: ['GET', 'POST'])]
    public function editar(Request $request, Categoria $categoria, EntityManagerInterface $em): Response
    {
        return $this->salvar($request, $em, $categoria);
    }

    #[Route('/{id}/alternar', name: 'admin_categoria_alternar', methods: ['POST'])]
    public function alternar(Request $request, Categoria $categoria, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('alternar'.$categoria->getId(), $request->getPayload()->getString('_token'))) {
            $categoria->setAtiva(!$categoria->isAtiva());
            $em->flush();
            $this->addFlash('success', sprintf('Categoria “%s” %s.', $categoria->getNome(), $categoria->isAtiva() ? 'ativada' : 'desativada'));
        }

        return $this->redirectToRoute('admin_categoria_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/excluir', name: 'admin_categoria_excluir', methods: ['POST'])]
    public function excluir(Request $request, Categoria $categoria, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('excluir'.$categoria->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('admin_categoria_index', [], Response::HTTP_SEE_OTHER);
        }

        $total = $categoria->getEventos()->count();
        if ($total > 0) {
            $this->addFlash('error', sprintf(
                'A categoria “%s” tem %d %s e não pode ser excluída. Mova os eventos para outra categoria ou apenas desative-a.',
                $categoria->getNome(), $total, 1 === $total ? 'evento' : 'eventos',
            ));

            return $this->redirectToRoute('admin_categoria_editar', ['id' => $categoria->getId()], Response::HTTP_SEE_OTHER);
        }

        $em->remove($categoria);
        $em->flush();
        $this->addFlash('success', sprintf('Categoria “%s” excluída.', $categoria->getNome()));

        return $this->redirectToRoute('admin_categoria_index', [], Response::HTTP_SEE_OTHER);
    }

    private function salvar(Request $request, EntityManagerInterface $em, Categoria $categoria): Response
    {
        $nova = null === $categoria->getId();
        $form = $this->createForm(CategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categoria);
            $em->flush();
            $this->addFlash('success', sprintf('Categoria “%s” %s.', $categoria->getNome(), $nova ? 'criada' : 'atualizada'));

            return $this->redirectToRoute('admin_categoria_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/categoria/form.html.twig', [
            'categoria' => $categoria,
            'form' => $form,
        ]);
    }
}
