<?php


namespace Rdlv\WordPress\Sywo\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntlTelType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $telOptions = array_merge($options, [
            'compound'     => false,
            'block_prefix' => 'intl_tel_tel',
        ]);
        $builder->add('tel', TelType::class, $telOptions);
        $builder->add('intl', HiddenType::class, [
            'mapped' => false,
        ]);
        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'error_bubbling' => false,
            ]
        );
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        $forms = iterator_to_array($forms);
        $forms['tel']->setData($viewData);
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $data = iterator_to_array($forms);
        $viewData = $data['intl']->getdata() ?: $data['tel']->getData();
    }
}