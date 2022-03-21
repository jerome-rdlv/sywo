<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormHooks extends AbstractTypeExtension
{
    /** @var Hooks */
    private $hooks;

    public function __construct(Hooks $hooks)
    {
        $this->hooks = $hooks;
    }

    /**
     * @inheritDoc
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->hooks->do('form/build', $builder, $options);
        $this->hooks->do(sprintf('form/%s/build', $builder->getName()), $builder, $options);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->hooks->do('form/view/build', $view, $form, $options);
        $this->hooks->do(sprintf('form/%s/view/build', $form->getName()), $view, $form, $options);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $this->hooks->do('form/view/finish', $view, $form, $options);
        $this->hooks->do(sprintf('form/%s/view/finish', $form->getName()), $view, $form, $options);
    }
}