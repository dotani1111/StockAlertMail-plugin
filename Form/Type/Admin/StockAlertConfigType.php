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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StockAlertConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('threshold', IntegerType::class, [
                'label' => 'stock_alert_mail.form.threshold.label',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual(0),
                ],
                'attr' => [
                    'placeholder' => 'stock_alert_mail.form.threshold.placeholder',
                ],
                'help' => 'stock_alert_mail.form.threshold.help',
            ])
            ->add('alertEmails', TextareaType::class, [
                'label' => 'stock_alert_mail.form.alert_emails.label',
                'required' => false,
                'attr' => [
                    'placeholder' => 'stock_alert_mail.form.alert_emails.placeholder',
                    'rows' => 3,
                ],
            ])
            ->add('mailSubject', TextType::class, [
                'label' => 'stock_alert_mail.form.mail_subject.label',
                'required' => false,
                'attr' => [
                    'placeholder' => 'stock_alert_mail.form.mail_subject.placeholder',
                ],
            ])
            ->add('mailBody', TextareaType::class, [
                'label' => 'stock_alert_mail.form.mail_body.label',
                'required' => false,
                'attr' => [
                    'rows' => 15,
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
