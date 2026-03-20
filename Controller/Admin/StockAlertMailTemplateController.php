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

namespace Plugin\StockAlertMail\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Plugin\StockAlertMail\Form\Type\Admin\StockAlertMailTemplateType;
use Plugin\StockAlertMail\Repository\StockAlertConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StockAlertMailTemplateController extends AbstractController
{
    public function __construct(
        private readonly StockAlertConfigRepository $configRepository,
    ) {
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/stock-alert/mail-template", name="stock_alert_mail_admin_mail_template", methods={"GET", "POST"})
     *
     * @Template("@StockAlertMail/admin/mail_template.twig")
     */
    public function index(Request $request): array
    {
        $config = $this->configRepository->findOneBy([]);
        if ($config === null) {
            $config = new StockAlertConfig();
            $config->setCreateDate(new \DateTime());
        }

        $form = $this->createForm(StockAlertMailTemplateType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config->setUpdateDate(new \DateTime());
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            $this->addSuccess('保存しました。', 'admin');

            return $this->redirectToRoute('stock_alert_mail_admin_mail_template');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
