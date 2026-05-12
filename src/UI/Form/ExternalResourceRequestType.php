<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\Domain\Access\Entity\ExternalResourceRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ExternalResourceRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Objet de la demande',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 180),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Détail du besoin',
                'attr' => ['rows' => 5],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10, max: 5000),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalResourceRequest::class,
        ]);
    }
}

