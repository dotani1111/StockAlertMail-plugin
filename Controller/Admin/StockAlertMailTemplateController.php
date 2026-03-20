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
use Plugin\StockAlertMail\Service\StockAlertMailBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class StockAlertMailTemplateController extends AbstractController
{
    public function __construct(
        private readonly StockAlertConfigRepository $configRepository,
        private readonly StockAlertMailBuilder $mailBuilder,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/stock-alert/mail-template", name="stock_alert_mail_admin_mail_template", methods={"GET", "POST"})
     *
     * @Template("@StockAlertMail/admin/mail_template.twig")
     */
    public function index(Request $request): array|Response
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

            $this->addSuccess('stock_alert_mail.admin.config.save_success', 'admin');

            return $this->redirectToRoute('stock_alert_mail_admin_mail_template');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/stock-alert/mail-template/send-test", name="stock_alert_mail_admin_mail_template_send_test", methods={"POST"})
     */
    public function sendTest(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('stock_alert_mail_send_test', $request->request->get('_token'))) {
            $this->addError('admin.common.csrf_error', 'admin');

            return $this->redirectToRoute('stock_alert_mail_admin_mail_template');
        }

        $config = $this->configRepository->findOneBy([]);
        if ($config === null) {
            $this->addError('stock_alert_mail.admin.mail_template.send_test.config_not_found', 'admin');

            return $this->redirectToRoute('stock_alert_mail_admin_mail_template');
        }

        $BaseInfo = $this->mailBuilder->getBaseInfo();
        $dummyItems = $this->mailBuilder->createDummyItems();
        $toEmails = $this->mailBuilder->resolveToEmails($config);
        $subject = '[TEST] '.$this->mailBuilder->buildMailSubject($config);
        $body = $this->mailBuilder->buildMailBody($config, $dummyItems, $config->getThreshold());

        try {
            $message = (new Email())
                ->subject($subject)
                ->from(new Address($BaseInfo->getEmail01(), $BaseInfo->getShopName()))
                ->text($body);

            foreach ($toEmails as $email) {
                $message->addTo($email);
            }

            $this->mailer->send($message);

            $this->addSuccess($this->translator->trans('stock_alert_mail.admin.mail_template.send_test.success', ['%emails%' => implode(', ', $toEmails)]), 'admin');
        } catch (\Exception $e) {
            $this->addError($this->translator->trans('stock_alert_mail.admin.mail_template.send_test.failed', ['%message%' => $e->getMessage()]), 'admin');
        }

        return $this->redirectToRoute('stock_alert_mail_admin_mail_template');
    }
}
