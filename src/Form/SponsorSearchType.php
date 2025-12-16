<?php

namespace App\Form;

use App\Enum\TypeSponsorEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SponsorSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de sponsor',
                'required' => false,
                'placeholder' => 'Tous les types',
                'choices' => [
                    'Entreprise'   => TypeSponsorEnum::ENTREPRISE->value,
                    'Association'  => TypeSponsorEnum::ASSOCIATION->value,
                    'Particulier'  => TypeSponsorEnum::PARTICULIER->value,
                    'ONG'          => TypeSponsorEnum::ONG->value,
                    'Institution'  => TypeSponsorEnum::INSTITUTION->value,
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('minBudget', NumberType::class, [
                'label' => 'Budget min',
                'required' => false,
                'scale' => 2,
            ])
            ->add('maxBudget', NumberType::class, [
                'label' => 'Budget max',
                'required' => false,
                'scale' => 2,
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Contrat à partir du',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Contrat jusqu\'au',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Formulaire non lié à une entité, simple tableau de critères
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}


