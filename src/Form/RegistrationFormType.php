<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['placeholder' => 'Jean', 'autocomplete' => 'given-name'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre prénom.'),
                    new Length(max: 50),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['placeholder' => 'Dupont', 'autocomplete' => 'family-name'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre nom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr'  => ['placeholder' => 'lecteur42', 'autocomplete' => 'username'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir un pseudo.'),
                    new Length(min: 3, max: 30,
                        minMessage: 'Le pseudo doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères.',
                    ),
                    new Regex(
                        pattern: '/^[a-zA-Z0-9_\-]+$/',
                        message: 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr'  => ['placeholder' => 'jean@exemple.com', 'autocomplete' => 'email'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label'  => 'Mot de passe',
                'attr'   => ['autocomplete' => 'new-password', 'placeholder' => 'Au moins 6 caractères'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe.'),
                    new Length(
                        min: 6,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label'  => "J'accepte les conditions d'utilisation",
                'constraints' => [
                    new IsTrue(message: "Vous devez accepter les conditions d'utilisation."),
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
