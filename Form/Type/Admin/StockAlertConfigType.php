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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
                'constraints' => [
                    new Assert\Callback(function ($value, ExecutionContextInterface $context) {
                        if (empty($value)) {
                            return;
                        }
                        $emails = array_filter(array_map('trim', explode(',', $value)));
                        foreach ($emails as $email) {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $context->buildViolation('「{{ value }}」は有効なメールアドレスではありません。')
                                    ->setParameter('{{ value }}', $email)
                                    ->addViolation();
                            }
                        }
                    }),
                ],
                'attr' => [
                    'placeholder' => 'stock_alert_mail.form.alert_emails.placeholder',
                    'rows' => 3,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StockAlertConfig::class,
        ]);
    }
}
