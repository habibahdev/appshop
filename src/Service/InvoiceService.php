<?php

namespace App\Service;

use App\Entity\Invoice;
use App\Entity\Purchase;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Génération de facture PDF pour une commande payée.
 *
 * @package App\Service
 */
class InvoiceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Environment $twig,
        private string $invoiceStoragePath
    ) {
    }

    /**
     * Génère et persiste la facture PDF d'une commande.
     *
     * Rend le template `invoice/pdf.html.twig`, le convertit en PDF via
     * Dompdf (chargement de ressources distantes désactivé), ércit le
     * fichier sur `invoice_storage_path`, et persiste l'entité {@see Invoice}.
     *
     * Appelée de façon asynchrone via {@see \App\MessageHandler\GenerateInvoiceHandler}.
     *
     * @param Purchase $purchase Commande à facturer.
     * @return Invoice La facture générée.
     */
    public function generate(Purchase $purchase): Invoice
    {
        $number = 'FAC-' . date('Y') . '-' . str_pad((string) $purchase->getId(), 6, '0', STR_PAD_LEFT);

        $html = $this->twig->render('invoice/pdf.html.twig', [
            'purchase' => $purchase,
            'number' => $number
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        $filename = $number . '.pdf';
        $fullPath = rtrim($this->invoiceStoragePath, '/') . '/' . $filename;

        if (!is_dir($this->invoiceStoragePath)) {
            mkdir($this->invoiceStoragePath, 0775, true);
        }

        file_put_contents($fullPath, $dompdf->output());

        $invoice = new Invoice();
        $invoice->setPurchase($purchase);
        $invoice->setNumber($number);
        $invoice->setFilename($filename);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }
}
