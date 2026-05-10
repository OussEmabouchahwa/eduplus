<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '+212 6XX XXX XXX'],
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => ['placeholder' => 'Parlez un peu de vous...', 'rows' => 3],
            ])
            ->add('profilePhotoFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('specialization', TextType::class, [
                'label' => 'Spécialisation',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Fullstack Developer, UX Designer...'],
            ])
            ->add('experience', TextType::class, [
                'label' => 'Expérience (années)',
                'required' => false,
                'attr' => ['placeholder' => 'Nombre d\'années d\'expérience'],
            ])
            ->add('major', TextType::class, [
                'label' => 'Filière / Études',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Informatique, Design Graphique...'],
            ])
            ->add('githubUrl', TextType::class, [
                'label' => 'Lien GitHub',
                'required' => false,
                'attr' => ['placeholder' => 'https://github.com/votre-compte'],
            ])
            ->add('linkedinUrl', TextType::class, [
                'label' => 'Lien LinkedIn',
                'required' => false,
                'attr' => ['placeholder' => 'https://linkedin.com/in/votre-profil'],
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
