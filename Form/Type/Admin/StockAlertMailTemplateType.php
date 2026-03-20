<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StockAlertMail\Form\Type\Admin;

use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StockAlertMailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailSubject', TextType::class, [
                'label' => 'stock_alert_mail.form.mail_subject.label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 500]),
                    new Assert\Regex([
                        'pattern' => '/[\r\n]/',
                        'match' => false,
                        'message' => '件名に改行を含めることはできません。',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'stock_alert_mail.form.mail_subject.placeholder',
                ],
            ])
            ->add('mailBody', TextareaType::class, [
                'label' => 'stock_alert_mail.form.mail_body.label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => 10000]),
                ],
                'attr' => [
                    'rows' => 20,
                    'style' => 'font-family: monospace;',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StockAlertConfig::class,
        ]);
    }
}
