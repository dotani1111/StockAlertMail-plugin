<?php

namespace Plugin\StockAlertMail\Form\Type\Admin;

use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StockAlertConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('threshold', IntegerType::class, [
                'label' => '在庫アラート閾値',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual(0),
                ],
                'attr' => [
                    'placeholder' => '例: 5',
                ],
                'help' => 'この個数以下になった商品をメールで通知します。',
            ])
            ->add('alertEmails', TextareaType::class, [
                'label' => '通知先メールアドレス',
                'required' => false,
                'attr' => [
                    'placeholder' => '空欄の場合は店舗設定のメールアドレスを使用します。複数の場合はカンマ区切り。',
                    'rows' => 3,
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
