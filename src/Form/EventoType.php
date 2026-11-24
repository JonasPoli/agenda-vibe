<?php

namespace App\Form;

use App\Entity\Categoria;
use App\Entity\Evento;
use App\Repository\EventoRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventoType extends AbstractType
{
    public function __construct(
        private SluggerInterface $slugger,
        private EventoRepository $eventos,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Categorias ativas, mais a categoria atual do evento (mesmo se ficou inativa).
        $categoriaAtual = ($options['data'] ?? null)?->getCategoria()?->getId();

        $builder
            ->add('titulo', TextType::class, ['label' => 'Título'])
            ->add('categoria', EntityType::class, [
                'label' => 'Categoria',
                'class' => Categoria::class,
                'choice_label' => 'nome',
                'placeholder' => 'Escolha a categoria',
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('c')
                    ->where('c.ativa = true OR c.id = :atual')
                    ->setParameter('atual', $categoriaAtual ?? 0)
                    ->orderBy('c.nome', 'ASC'),
                // A cor vai para o template, que desenha a bolinha em cada opção.
                'choice_attr' => fn (Categoria $c) => ['data-cor' => $c->getCor()],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Endereço (slug)',
                'required' => false,
                'help' => 'Deixe em branco para gerar a partir do título.',
            ])
            ->add('resumo', TextType::class, [
                'label' => 'Resumo',
                'required' => false,
                'help' => 'Uma frase que aparece na lista de eventos do site.',
            ])
            ->add('local', TextType::class, ['label' => 'Local'])
            ->add('inicio', DateTimeType::class, [
                'label' => 'Início',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('fim', DateTimeType::class, [
                'label' => 'Término',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('descricao', TextareaType::class, [
                'label' => 'Descrição',
                'required' => false,
                'attr' => ['rows' => 8],
            ])
            ->add('ativo', CheckboxType::class, [
                'label' => 'Ativo',
                'required' => false,
                'help' => 'Só eventos ativos, de categorias ativas, aparecem no site.',
            ])
            ->add('destaque', CheckboxType::class, [
                'label' => 'Destaque na home',
                'required' => false,
                'help' => sprintf('Aparece no bloco do topo da home. No máximo %d eventos em destaque ao mesmo tempo.', Evento::MAX_DESTAQUES),
            ])
        ;

        // Gera o slug antes da validação, para o UniqueEntity enxergar o valor final.
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Evento $evento */
            $evento = $event->getData();

            if ($evento->getSlug()) {
                $evento->setSlug($this->slugger->slug($evento->getSlug())->lower()->toString());

                return;
            }

            if ($evento->getTitulo()) {
                $evento->setSlug($this->slugUnico($evento));
            }
        });
    }

    private function slugUnico(Evento $evento): string
    {
        $base = $this->slugger->slug($evento->getTitulo())->lower()->truncate(170, '', false)->toString();
        $slug = $base;

        for ($n = 2; ($outro = $this->eventos->findOneBy(['slug' => $slug])) && $outro->getId() !== $evento->getId(); ++$n) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Evento::class]);
    }
}
