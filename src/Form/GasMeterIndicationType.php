<?php

namespace App\Form;

use App\Entity\GasMeter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class GasMeterIndicationType extends AbstractType
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setAction($this->router->generate('app_gas_meter_form'))
            ->add('indication', NumberType::class, [
                'required'    => true,
                'attr'        => ['class' => 'form-control'],
                'help'        => sprintf('Poprzedni odczyt: %s m³', $options['lastIndication']),
                'constraints' => [new GreaterThanOrEqual($options['lastIndication'], message: "Podany odczyt nie może być mniejszy niz poprzedni")],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'     => GasMeter::class,
            'lastIndication' => 0,
        ]);
    }
}
