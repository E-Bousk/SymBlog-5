<?php

namespace App\Form;

use App\Entity\User;
use App\Form\FormExtension\HoneyPotType;
use App\Form\FormExtension\RepeatedPasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class RegistrationFormType extends HoneyPotType
{
    /**
     * Build a form with HTML attributes and Validator constraints
     * 
     * @param FormBuilderInterface<callable> $builder 
     * @param array<mixed> $options 
     * @return void 
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Appel du formulaire de « HoneyPotType »
        parent::buildForm($builder, $options);

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('password', RepeatedPasswordType::class)
            ->add('agreeTerms', CheckboxType::class, [
                'label' => "J'accepte les conditions d'utilisation de ce site.",
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue([
                        'message' => "Vous devez accepter les conditions d'utilisation de ce site pour vous inscrire.",
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
