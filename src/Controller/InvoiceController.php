<?php

namespace App\Controller;

use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class InvoiceController extends AbstractController
{
    public function __construct(private string $invoiceStoragePath)
    {
    }

    #[Route('/facture/{number}', name: 'app_invoice')]
    public function download(
        string $number,
        InvoiceRepository $invoiceRepository
    ): BinaryFileResponse {
        $invoice = $invoiceRepository->findOneBy(['number' => $number]) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted('view', $invoice->getPurchase());

        $response = new BinaryFileResponse($this->invoiceStoragePath . '/' . $invoice->getFilename());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $invoice->getFilename());

        return $response;
    }
}
