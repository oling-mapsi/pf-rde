<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\UI\Form\Model\ExternalUserRegistrationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ExternalUserRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('organizationName', TextType::class, [
                'label' => 'Société',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 180),
                ],
            ])
            ->add('companySiret', TextType::class, [
                'label' => 'SIRET',
                'help' => '14 chiffres. Vérifié automatiquement dans la base SIRENE.',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(pattern: '/^\d{14}$/', message: 'Le SIRET doit contenir exactement 14 chiffres.'),
                ],
            ])
            ->add('websiteUrl', TextType::class, [
                'label' => 'Site web (optionnel)',
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 512),
                    new Assert\Url(protocols: ['http', 'https'], requireTld: false),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('postalAddress', TextareaType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('accountRequestReason', TextareaType::class, [
                'label' => 'Pourquoi souhaitez-vous créer ce compte ?',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 20, max: 2000),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 12, max: 4096),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalUserRegistrationData::class,
            'csrf_protection' => true,
        ]);
    }
}
