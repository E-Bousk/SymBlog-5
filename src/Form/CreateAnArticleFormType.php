<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Image;
use App\EventSubscriber\CreateAnArticleFormSubscriber;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CreateAnArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'        => 'Titre de l\'article',
                'required'     => true
            ])
            ->add('content', TextareaType::class, [
                'label'        => 'Contenu de l\'article',
                'required'     => true
            ])
            ->add('categories', EntityType::class, [
                'label'        => 'Catégorie(s) de l\'article',
                'required'     => true,
                'class'        => Category::class,
                'choice_label' => 'name',
                'by_reference' => false, // pour hydrater la table pivot (many to many)
                'multiple'     => true,
                'expanded'     => true
            ])
            ->add('picture', FileType::class, [
                'label'        => 'Image de l\'article',
                'required'     => true,
                'mapped'       => false,
                'constraints'  => [
                    new Image([
                        'maxSize' => '1M',
                        'maxSizeMessage' => 'La taille maximale de l\'image doit être de {{ limit }} {{ suffix }}'
                    ])
                ]
            ])
            ->add('publish', SubmitType::class, [
                'label'        => 'Publier un article',
                'attr'         => [
                    'class'       => 'btn btn-success'
                ]
            ])
            ->addEventSubscriber(new CreateAnArticleFormSubscriber())
        ;            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'user_roles' => []
        ]);
    }
}
