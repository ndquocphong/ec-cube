<?php

namespace Eccube\Form\Type\Shopping;

use Eccube\Entity\Order;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class OrderType extends AbstractType
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var DeliveryRepository
     */
    protected $deliveryRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * OrderType constructor.
     * @param OrderRepository $orderRepository
     * @param DeliveryRepository $deliveryRepository
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        DeliveryRepository $deliveryRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->deliveryRepository = $deliveryRepository;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'message',
                TextareaType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Length(['min' => 0, 'max' => 3000]),
                    ],
                ]
            )
            ->add(
                'use_point',
                NumberType::class,
                [
                    'required' => false,
                    'label' => '利用ポイント',
                    'constraints' => array(
                        new Regex(array(
                            'pattern' => "/^\d+$/u",
                            'message' => 'form.type.numeric.invalid'
                        )),
                    ),
                ]
            )
            ->add(
                'Shippings',
                CollectionType::class,
                [
                    'entry_type' => ShippingType::class,
                    'by_reference' => false
                ]
            )->add(
                'mode',
                HiddenType::class,
                [
                    'mapped' => false,
                ]
            )->add(
                'param',
                HiddenType::class,
                [
                    'mapped' => false,
                ]
            );

        // POSTされないデータをエンティティにセットする.
        // TODO Calculatorで行うのが適切.
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var Order $Order */
                $Order = $event->getData();
                // XXX 非会員購入の際, use_point が null で submit される？
                if ($Order->getUsePoint() === null) {
                    $Order->setUsePoint(0);
                }
                $Payment = $Order->getPayment();
                $Order->setPaymentMethod($Payment ? $Payment->getMethod() : null);
                // TODO CalculateChargeStrategy でセットする
                // $Order->setCharge($Payment ? $Payment->getCharge() : null);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Eccube\Entity\Order',
            ]
        );
    }

    public function getBlockPrefix()
    {
        return '_shopping_order';
    }
}
