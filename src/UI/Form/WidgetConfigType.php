<?php

declare(strict_types=1);

namespace App\UI\Form;

use App\Domain\Dashboard\Enum\WidgetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', ChoiceType::class, [
            'label' => 'Type de widget',
            'choices' => [
                'Recherche de produits' => WidgetType::ProductsSearch->value,
                'Stats Nutri-Score' => WidgetType::NutriScoreStats->value,
                'Top catégorie' => WidgetType::CategoryTop->value,
                'Détail produit' => WidgetType::ProductDetail->value,
            ],
            'disabled' => true, // Type non modifiable après création
            'attr' => ['class' => 'form-select'],
        ]);

        // Ajouter les champs dynamiques selon le type
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!isset($data['type'])) {
                return;
            }

            $type = WidgetType::from($data['type']);

            match ($type) {
                WidgetType::ProductsSearch => $this->addSearchFields($form),
                WidgetType::NutriScoreStats => $this->addStatsFields($form),
                WidgetType::CategoryTop => $this->addCategoryFields($form),
                WidgetType::ProductDetail => $this->addDetailFields($form),
            };
        });
    }

    private function addSearchFields($form): void
    {
        $form
            ->add('query', TextType::class, [
                'label' => 'Recherche',
                'required' => false,
                'attr' => ['placeholder' => 'ex: coca cola'],
            ])
            ->add('limit', IntegerType::class, [
                'label' => 'Nombre de résultats',
                'data' => 10,
                'attr' => ['min' => 1, 'max' => 50],
            ]);
    }

    private function addStatsFields($form): void
    {
        $form->add('category', TextType::class, [
            'label' => 'Catégorie',
            'data' => 'snacks',
            'attr' => ['placeholder' => 'ex: snacks, beverages'],
        ]);
    }

    private function addCategoryFields($form): void
    {
        $form
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'data' => 'beverages',
            ])
            ->add('limit', IntegerType::class, [
                'label' => 'Nombre de produits',
                'data' => 5,
                'attr' => ['min' => 1, 'max' => 20],
            ]);
    }

    private function addDetailFields($form): void
    {
        $form->add('barcode', TextType::class, [
            'label' => 'Code-barres',
            'required' => false,
            'attr' => ['placeholder' => 'ex: 3017620422003'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'widget_config',
        ]);
    }
}
