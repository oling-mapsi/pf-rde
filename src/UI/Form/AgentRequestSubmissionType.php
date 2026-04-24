<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\Domain\Agent\Entity\AgentRequest;
use App\Domain\Agent\Entity\AgentRequestType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AgentRequestSubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('requestType', EntityType::class, [
                'class' => AgentRequestType::class,
                'choice_label' => 'label',
                'label' => 'Type de demande',
                'placeholder' => 'Choisir...',
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 180),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10, max: 5000)],
                'attr' => ['rows' => 6],
            ])
            ->add('attachment', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Piece jointe (optionnel)',
                'constraints' => [
                    new Assert\File(maxSize: '20M'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgentRequest::class,
        ]);
    }
}
