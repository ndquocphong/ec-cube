<?php

namespace Eccube\Tests\Web\Admin\Customer;

use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;

/**
 * Class CustomerEditControllerTest
 * @package Eccube\Tests\Web\Admin\Customer
 */
class CustomerEditControllerTest extends AbstractAdminWebTestCase
{

    /**
     * Customer
     */
    protected $Customer;

    /** @var  CustomerRepository */
    protected $customerRepository;

    /** @var  OrderStatusRepository */
    protected $orderStatusRepository;

    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer();
        $this->customerRepository = $this->container->get(CustomerRepository::class);
        $this->orderStatusRepository = $this->container->get(OrderStatusRepository::class);
    }

    /**
     * createFormData
     * @return array
     */
    protected function createFormData()
    {
        $faker = $this->getFaker();
        $tel = explode('-', $faker->phoneNumber);

        $email = $faker->safeEmail;
        $password = $faker->lexify('????????');
        $birth = $faker->dateTimeBetween;

        $form = array(
            'name' => array('name01' => $faker->lastName, 'name02' => $faker->firstName),
            'kana' => array('kana01' => $faker->lastKanaName, 'kana02' => $faker->firstKanaName),
            'company_name' => $faker->company,
            'zip' => array('zip01' => $faker->postcode1(), 'zip02' => $faker->postcode2()),
            'address' => array('pref' => '5', 'addr01' => $faker->city, 'addr02' => $faker->streetAddress),
            'tel' => array('tel01' => $tel[0], 'tel02' => $tel[1], 'tel03' => $tel[2]),
            'fax' => array('fax01' => $tel[0], 'fax02' => $tel[1], 'fax03' => $tel[2]),
            'email' => $email, 'password' => array('first' => $password, 'second' => $password),
            'birth' => array('year' => $birth->format('Y'), 'month' => $birth->format('n'), 'day' => $birth->format('j')),
            'sex' => 1,
            'job' => 1,
            'status' => 1,
            'point' => 0,
            '_token' => 'dummy',
        );

        return $form;
    }

    /**
     * testIndex
     */
    public function testIndex()
    {
        $this->client->request(
            'GET',
            $this->generateUrl('admin_customer_edit', array('id' => $this->Customer->getId()))
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testIndex
     */
    public function testIndexBackButton()
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('admin_customer_edit', array('id' => $this->Customer->getId()))
        );

        $this->expected = '検索画面に戻る';
        $this->actual = $crawler->filter('#detail_box__footer')->text();
        $this->assertContains($this->expected, $this->actual);
    }

    /**
     * testIndexWithPost
     */
    public function testIndexWithPost()
    {
        $form = $this->createFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('admin_customer_edit', array('id' => $this->Customer->getId())),
            array('admin_customer' => $form)
        );
        $this->assertTrue($this->client->getResponse()->isRedirect(
            $this->generateUrl(
                'admin_customer_edit',
                array('id' => $this->Customer->getId())
            )
        ));
        $EditedCustomer = $this->customerRepository->find($this->Customer->getId());

        $this->expected = $form['email'];
        $this->actual = $EditedCustomer->getEmail();
        $this->verify();
    }

    /**
     * testNew
     */
    public function testNew()
    {
        $this->client->request(
            'GET',
            $this->generateUrl('admin_customer_new')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * testNewWithPost
     */
    public function testNewWithPost()
    {
        $form = $this->createFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('admin_customer_new'),
            array('admin_customer' => $form)
        );

        $NewCustomer = $this->customerRepository->findOneBy(array('email' => $form['email']));
        $this->assertNotNull($NewCustomer);
        $this->assertTrue($form['email'] == $NewCustomer->getEmail());
    }

    /**
     * testShowOrder
     */
    public function testShowOrder()
    {
        $id = $this->Customer->getId();

        //add Order pendding status for this customer
        $Order = $this->createOrder($this->Customer);
        $OrderStatus = $this->orderStatusRepository->find($this->eccubeConfig['order_pre_end']);
        $Order->setOrderStatus($OrderStatus);
        $this->Customer->addOrder($Order);
        $this->entityManager->persist($this->Customer);
        $this->entityManager->flush();

        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('admin_customer_edit', array('id' => $id))
        );

        $orderListing = $crawler->filter('#history_box__body')->text();
        $this->assertRegexp('/'.$Order->getId().'/', $orderListing);
    }

    public function testNotShowProcessingOrder()
    {
//        $this->markTestSkipped('Problem with Doctrine');
        $id = $this->Customer->getId();

        //add Order pendding status for this customer
        $Order = $this->createOrder($this->Customer);
        $OrderStatus = $this->orderStatusRepository->find($this->eccubeConfig['order_processing']);
        $Order->setOrderStatus($OrderStatus);
        $this->Customer->addOrder($Order);
        $this->entityManager->persist($Order);
        $this->entityManager->persist($this->Customer);
        $this->entityManager->flush();
        unset($this->Customer);

        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('admin_customer_edit', array('id' => $id))
        );

        $orderListing = $crawler->filter('#history_box')->text();
        $this->assertContains('データはありません', $orderListing);
    }

}
