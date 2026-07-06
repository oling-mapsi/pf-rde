<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\Domain\Content\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom et prénom',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 150),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10, max: 4000),
                ],
                'attr' => ['rows' => 6],
            ])
            ->add('privacyConsent', CheckboxType::class, [
                'label' => "Je consens au traitement de mes données pour l'envoi et le traitement de ma demande de contact.",
                'help' => "Finalite : permettre a Routes de Guadeloupe de recevoir, instruire et traiter votre message, puis de vous recontacter si necessaire.<br>Donnees traitees : identite, adresse e-mail, objet et contenu du message.<br>Duree de conservation : pendant le temps necessaire au traitement de la demande, puis au maximum 3 ans apres le dernier echange si un suivi administratif le justifie.<br>Droits : acces, rectification, effacement, limitation, opposition et reclamation aupres de la CNIL, dans les conditions prevues par la reglementation applicable.<br>Contact DPO : via le <a href=\"/contact\">formulaire de contact</a> en precisant \"DPO\" dans l'objet de votre demande, ainsi que via les informations figurant dans la <a href=\"/politique-confidentialite\">politique de confidentialite</a>.",
                'help_html' => true,
                'row_attr' => [
                    'class' => 'form-row--consent',
                ],
                'constraints' => [new Assert\IsTrue(message: 'Le consentement est requis.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
            'csrf_protection' => true,
        ]);
    }
}
