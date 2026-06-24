<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\Domain\Access\Entity\ExternalResourceRequest;
use App\UI\Form\Model\ResourceRequestSubmissionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ExternalResourceRequestSubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isPublicContext = $options['submission_context'] === 'public';
        $showRequesterType = $options['show_requester_type'];

        if ($showRequesterType) {
            $builder->add('requesterType', ChoiceType::class, [
                'label' => 'Type de demandeur',
                'choices' => [
                    'Usager' => ExternalResourceRequest::REQUESTER_TYPE_USAGER,
                    'Professionnel' => ExternalResourceRequest::REQUESTER_TYPE_PROFESSIONAL,
                ],
                'expanded' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'data-request-submission-target' => 'requesterType',
                    'data-action' => 'request-submission#refresh',
                ],
            ]);
        }

        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Courriel',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ]);

        if ($isPublicContext) {
            $builder->add('emailConfirmation', EmailType::class, [
                'label' => 'Confirmation du courriel',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ]);
        }

        $builder
            ->add('phoneNumber', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 32),
                ],
                'row_attr' => [
                    'data-request-submission-target' => 'professionalField',
                ],
            ])
            ->add('organizationName', TextType::class, [
                'label' => "Nom de l'organisme",
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 180),
                ],
                'row_attr' => [
                    'data-request-submission-target' => 'professionalField',
                ],
            ])
            ->add('companySiret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => false,
                'row_attr' => [
                    'data-request-submission-target' => 'professionalField',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 120),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet / intitulé de la demande',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 180),
                ],
            ])
            ->add('requestKinds', ChoiceType::class, [
                'label' => 'Type de demande',
                'choices' => [
                    'Carte' => ExternalResourceRequest::REQUEST_KIND_MAP,
                    'Données' => ExternalResourceRequest::REQUEST_KIND_DATA,
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\Count(min: 1, minMessage: 'Sélectionnez au moins un type de demande.'),
                ],
                'choice_attr' => static fn (): array => [
                    'data-action' => 'request-submission#refresh',
                    'data-request-submission-target' => 'requestKindInput',
                ],
            ])
            ->add('networkTypes', ChoiceType::class, [
                'label' => 'Type de réseau',
                'choices' => [
                    'Route Nationale' => 'Route Nationale',
                    'Route Départementale' => 'Route Départementale',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [
                    new Assert\Count(min: 1, minMessage: 'Sélectionnez au moins un type de réseau.'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Description de la demande',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10, max: 5000),
                ],
                'attr' => ['rows' => 7],
            ])
            ->add('additionalInformation', TextareaType::class, [
                'label' => 'Information complémentaire',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 3000),
                ],
                'attr' => ['rows' => 4],
            ])
            ->add('dataFormats', ChoiceType::class, [
                'label' => 'Format souhaité pour les données',
                'choices' => [
                    'CSV' => 'CSV',
                    'SHP' => 'SHP',
                    'JSON' => 'JSON',
                    'GeoJSON' => 'GeoJSON',
                    'KML' => 'KML',
                    'Autre' => 'Autre',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'row_attr' => [
                    'data-request-submission-target' => 'dataSection',
                ],
            ])
            ->add('projectionSystem', ChoiceType::class, [
                'label' => 'Système de projection',
                'required' => false,
                'placeholder' => 'Sélectionner',
                'choices' => [
                    'RGAF09' => 'RGAF09',
                    'WGS84' => 'WGS84',
                    'RGF93' => 'RGF93',
                    'Autre' => 'Autre',
                ],
                'row_attr' => [
                    'data-request-submission-target' => 'dataSection',
                ],
            ])
            ->add('mapFormats', ChoiceType::class, [
                'label' => 'Format souhaité pour la carte',
                'choices' => [
                    'A4' => 'A4',
                    'A3' => 'A3',
                    'A0' => 'A0',
                    'PDF numérique' => 'PDF numérique',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'row_attr' => [
                    'data-request-submission-target' => 'mapSection',
                ],
            ])
            ->add('mapScale', ChoiceType::class, [
                'label' => 'Échelle souhaitée',
                'required' => false,
                'placeholder' => 'Sélectionner',
                'choices' => [
                    '1/25 000' => '1/25 000',
                    '1/50 000' => '1/50 000',
                    '1/100 000' => '1/100 000',
                    'Libre' => 'Libre',
                ],
                'row_attr' => [
                    'data-request-submission-target' => 'mapSection',
                ],
            ])
            ->add('privacyConsent', CheckboxType::class, [
                'label' => 'Je confirme avoir pris connaissance de la politique de confidentialité et du traitement de mes données pour instruire cette demande.',
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($isPublicContext): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data instanceof ResourceRequestSubmissionData) {
                return;
            }

            $isProfessional = $data->requesterType === ExternalResourceRequest::REQUESTER_TYPE_PROFESSIONAL;
            $wantsData = \in_array(ExternalResourceRequest::REQUEST_KIND_DATA, $data->requestKinds, true);
            $wantsMap = \in_array(ExternalResourceRequest::REQUEST_KIND_MAP, $data->requestKinds, true);

            if ($isPublicContext && strtolower(trim($data->email)) !== strtolower(trim($data->emailConfirmation))) {
                $form->get('emailConfirmation')->addError(new FormError('La confirmation du courriel doit être identique.'));
            }

            if ($isProfessional) {
                foreach (['phoneNumber', 'organizationName', 'companySiret'] as $fieldName) {
                    $value = trim((string) $data->{$fieldName});
                    if ($value === '') {
                        $form->get($fieldName)->addError(new FormError('Ce champ est obligatoire pour un professionnel.'));
                    }
                }
            }

            $normalizedSiret = preg_replace('/\D+/', '', (string) $data->companySiret);
            if ($normalizedSiret !== '' && !preg_match('/^\d{14}$/', $normalizedSiret)) {
                $form->get('companySiret')->addError(new FormError('Le SIRET doit contenir exactement 14 chiffres.'));
            }

            if ($wantsData && $data->dataFormats === []) {
                $form->get('dataFormats')->addError(new FormError('Sélectionnez au moins un format de données.'));
            }

            if ($wantsMap && $data->mapFormats === []) {
                $form->get('mapFormats')->addError(new FormError('Sélectionnez au moins un format de carte.'));
            }

            if (!$data->privacyConsent) {
                $form->get('privacyConsent')->addError(new FormError('Le consentement est requis pour transmettre la demande.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResourceRequestSubmissionData::class,
            'csrf_protection' => true,
            'submission_context' => 'public',
            'show_requester_type' => true,
        ]);

        $resolver->setAllowedValues('submission_context', ['public', 'professional']);
        $resolver->setAllowedTypes('show_requester_type', 'bool');
    }
}
