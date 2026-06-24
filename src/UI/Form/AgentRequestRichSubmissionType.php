<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\UI\Form\Model\AgentRequestSubmissionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AgentRequestRichSubmissionType extends AbstractType
{
    /** @var array<string, list<string>> */
    private const DIRECTION_SERVICE_CHOICES = [
        'Centre Routier' => ['DTCM', 'DTAA', 'DTMG', 'DTSC', 'DGAOT', 'DGAT', 'DGAAF'],
        'Direction Territoriale' => ['DTCM', 'DTAA', 'DTMG', 'DTSC', 'DGAOT', 'DGAT', 'DGAAF'],
        'Siège' => ['DTCM', 'DTAA', 'DTMG', 'DTSC', 'DGAOT', 'DGAT', 'DGAAF'],
    ];

    /** @var list<string> */
    private const CENTER_CHOICES = ['CRAB', 'CRBM', 'CRCA', 'CRGB', 'CRPN', 'CRSA', 'CRSC', 'CRSR'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('structureType', ChoiceType::class, [
                'label' => 'Type du demandeur',
                'choices' => [
                    'Centre Routier' => 'Centre Routier',
                    'Direction Territoriale' => 'Direction Territoriale',
                    'Siège' => 'Siège',
                ],
                'attr' => [
                    'data-agent-request-submission-target' => 'structureType',
                    'data-action' => 'agent-request-submission#refresh',
                ],
            ])
            ->add('directionService', ChoiceType::class, [
                'label' => 'Direction / service',
                'choices' => $this->flattenChoices(self::DIRECTION_SERVICE_CHOICES),
            ])
            ->add('center', ChoiceType::class, [
                'label' => 'Centre',
                'required' => false,
                'placeholder' => 'Sélectionner',
                'choices' => array_combine(self::CENTER_CHOICES, self::CENTER_CHOICES),
                'row_attr' => [
                    'data-agent-request-submission-target' => 'centerField',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Courriel professionnel',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('emailConfirmation', EmailType::class, [
                'label' => 'Confirmation du courriel',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Numéro de téléphone',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 32)],
            ])
            ->add('orderReference', TextType::class, [
                'label' => "Numéro d'ordre de service / affaire",
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 120)],
            ])
            ->add('urgencyLevel', ChoiceType::class, [
                'label' => "Niveau d'urgence",
                'choices' => [
                    'Normal' => 'normal',
                    'Urgent (< 5 jours)' => 'urgent',
                    'Très urgent (< 48h)' => 'very_urgent',
                ],
                'attr' => [
                    'data-agent-request-submission-target' => 'urgencyLevel',
                    'data-action' => 'agent-request-submission#refresh',
                ],
            ])
            ->add('urgencyJustification', TextType::class, [
                'label' => "Justification de l'urgence",
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
                'row_attr' => [
                    'data-agent-request-submission-target' => 'urgencyField',
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet / intitulé',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('requestKinds', ChoiceType::class, [
                'label' => 'Type de demande',
                'choices' => [
                    'Carte' => 'map',
                    'Données' => 'data',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [new Assert\Count(min: 1, minMessage: 'Sélectionnez au moins un type de demande.')],
                'choice_attr' => static fn (): array => [
                    'data-agent-request-submission-target' => 'requestKindInput',
                    'data-action' => 'agent-request-submission#refresh',
                ],
            ])
            ->add('networkTypes', ChoiceType::class, [
                'label' => 'Type de réseau',
                'choices' => [
                    'Route Nationale' => 'Route Nationale',
                    'Route Départementale' => 'Route Départementale',
                    'Les deux' => 'Les deux',
                ],
                'expanded' => true,
                'multiple' => true,
                'constraints' => [new Assert\Count(min: 1, minMessage: 'Sélectionnez au moins un type de réseau.')],
            ])
            ->add('routeDetails', TextType::class, [
                'label' => 'Type de route (précision)',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 120)],
            ])
            ->add('geographicArea', TextType::class, [
                'label' => 'Emprise géographique',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de la demande',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 10, max: 5000)],
                'attr' => ['rows' => 7],
            ])
            ->add('additionalInformation', TextareaType::class, [
                'label' => 'Information complémentaire',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 3000)],
                'attr' => ['rows' => 4],
            ])
            ->add('hasProvidedData', CheckboxType::class, [
                'label' => "L'agent fournit déjà des données utiles",
                'required' => false,
            ])
            ->add('deliveryDestination', ChoiceType::class, [
                'label' => 'Destinataire de la production',
                'choices' => [
                    'Usage interne' => 'internal',
                    'Diffusion externe' => 'external',
                ],
            ])
            ->add('dataFormats', ChoiceType::class, [
                'label' => 'Format souhaité (données)',
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
                    'data-agent-request-submission-target' => 'dataSection',
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
                    'data-agent-request-submission-target' => 'dataSection',
                ],
            ])
            ->add('mapFormats', ChoiceType::class, [
                'label' => 'Format souhaité (carte)',
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
                    'data-agent-request-submission-target' => 'mapSection',
                ],
            ])
            ->add('mapScale', ChoiceType::class, [
                'label' => 'Échelle souhaitée',
                'required' => false,
                'placeholder' => 'Sélectionner',
                'choices' => [
                    '1/5 000' => '1/5 000',
                    '1/10 000' => '1/10 000',
                    '1/25 000' => '1/25 000',
                    '1/50 000' => '1/50 000',
                    'Libre' => 'Libre',
                ],
                'row_attr' => [
                    'data-agent-request-submission-target' => 'mapSection',
                ],
            ])
            ->add('attachment', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Pièce jointe',
                'constraints' => [
                    new Assert\File(maxSize: '20M'),
                ],
            ])
            ->add('attachmentDescription', TextType::class, [
                'label' => 'Intitulé / description de la pièce jointe',
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 180)],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data instanceof AgentRequestSubmissionData) {
                return;
            }

            if (strtolower(trim($data->email)) !== strtolower(trim($data->emailConfirmation))) {
                $form->get('emailConfirmation')->addError(new FormError('La confirmation du courriel doit être identique.'));
            }

            if ($data->structureType === 'Centre Routier' && trim((string) $data->center) === '') {
                $form->get('center')->addError(new FormError('Le centre est obligatoire pour un Centre Routier.'));
            }

            if (\in_array($data->urgencyLevel, ['urgent', 'very_urgent'], true) && trim((string) $data->urgencyJustification) === '') {
                $form->get('urgencyJustification')->addError(new FormError("La justification est obligatoire pour une demande urgente."));
            }

            if (\in_array('data', $data->requestKinds, true) && $data->dataFormats === []) {
                $form->get('dataFormats')->addError(new FormError('Sélectionnez au moins un format de données.'));
            }

            if (\in_array('map', $data->requestKinds, true) && $data->mapFormats === []) {
                $form->get('mapFormats')->addError(new FormError('Sélectionnez au moins un format de carte.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgentRequestSubmissionData::class,
        ]);
    }

    /**
     * @param array<string, list<string>> $groupedChoices
     *
     * @return array<string, string>
     */
    private function flattenChoices(array $groupedChoices): array
    {
        $flattened = [];
        foreach ($groupedChoices as $values) {
            foreach ($values as $value) {
                $flattened[$value] = $value;
            }
        }

        return $flattened;
    }
}
