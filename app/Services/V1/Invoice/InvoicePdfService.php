<?php

namespace App\Services\V1\Invoice;

use App\Models\V1\Invoice\Invoice;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Generate PDF for invoice
     */
    public function generatePdf(Invoice $invoice): string
    {
        // Load invoice with all relationships
        $invoice->load([
            'contract.units.building',
            'contract.units.floor',
            'contract.tenant.user',
            'ownership',
            'items',
            'generatedBy',
        ]);

        // Get ownership legal name
        $companyName = $invoice->ownership->legal ?? $invoice->ownership->name ?? '';

        // Get unit information
        $units = [];
        if ($invoice->contract && $invoice->contract->units) {
            foreach ($invoice->contract->units as $unit) {
                $unitInfo = [
                    'number' => $unit->number,
                    'name' => $unit->name,
                    'building' => $unit->building->name ?? $unit->building->code ?? '',
                    'building_code' => $unit->building->code ?? '',
                    'floor' => $unit->floor ? $unit->floor->number : null,
                ];
                $units[] = $unitInfo;
            }
        }

        // Prepare data for view
        $data = [
            'invoice' => $invoice,
            'companyName' => $companyName,
            'ownership' => $invoice->ownership,
            'contract' => $invoice->contract,
            'tenant' => $invoice->contract?->tenant,
            'units' => $units,
            'items' => $invoice->items,
        ];

        // Render HTML
        $html = View::make('invoices.pdf.arabic', $data)->render();

        // Configure mPDF for Arabic and RTL
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // Font directories
        $customFontDirs = array_merge($fontDirs, [
            public_path('fonts'),
            resource_path('fonts'),
            storage_path('fonts'),
        ]);

        // Add Tajawal font if files exist, otherwise use DejaVu (built-in Arabic support)
        $tajawalRegular = public_path('fonts/Tajawal-Regular.ttf');
        $tajawalBold = public_path('fonts/Tajawal-Bold.ttf');
        
        if (file_exists($tajawalRegular) && file_exists($tajawalBold)) {
            $fontData['tajawal'] = [
                'R' => 'Tajawal-Regular.ttf',
                'B' => 'Tajawal-Bold.ttf',
                'I' => 'Tajawal-Regular.ttf',
                'BI' => 'Tajawal-Bold.ttf',
            ];
            $defaultFont = 'tajawal';
        } else {
            // Use DejaVu which has built-in Arabic support
            $defaultFont = 'dejavusans';
        }

        // Create mPDF instance
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'fontDir' => $customFontDirs,
            'fontdata' => $fontData,
            'default_font' => $defaultFont,
            'direction' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        // Set document metadata
        $mpdf->SetTitle('فاتورة - ' . $invoice->number);
        $mpdf->SetAuthor($companyName);
        $mpdf->SetSubject('فاتورة إيجار');
        $mpdf->SetKeywords('فاتورة, إيجار, ' . $invoice->number);

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF
        $filename = 'invoice_' . $invoice->number . '_' . date('Y-m-d') . '.pdf';
        $pdfPath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $mpdf->Output($pdfPath, 'F');

        return $pdfPath;
    }

    /**
     * Generate PDF and return as download response
     */
    public function downloadPdf(Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $pdfPath = $this->generatePdf($invoice);
        $filename = 'invoice_' . $invoice->number . '_' . date('Y-m-d') . '.pdf';

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate PDF and save to documents
     */
    public function generateAndSave(Invoice $invoice): string
    {
        $pdfPath = $this->generatePdf($invoice);
        
        // Move PDF to public storage
        $fileName = 'invoice_' . $invoice->number . '_' . date('Y-m-d') . '.pdf';
        $storagePath = 'documents/invoice/' . $invoice->id . '/invoice_pdf/' . $fileName;
        
        // Ensure directory exists
        $fullPath = storage_path('app/public/' . dirname($storagePath));
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        // Copy file to storage
        copy($pdfPath, storage_path('app/public/' . $storagePath));
        
        // Delete temp file
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        // Save to documents using DocumentService
        $documentService = app(\App\Services\V1\Document\DocumentService::class);
        
        // Create UploadedFile instance from the stored file
        $storedFile = new \Illuminate\Http\UploadedFile(
            storage_path('app/public/' . $storagePath),
            $fileName,
            'application/pdf',
            null,
            true
        );
        
        $document = $documentService->upload(
            entity: $invoice,
            file: $storedFile,
            type: 'invoice_pdf',
            ownershipId: $invoice->ownership_id,
            title: 'فاتورة PDF - ' . $invoice->number,
            uploadedBy: $invoice->generated_by,
            description: 'فاتورة إيجار مولد تلقائياً',
            public: false
        );

        return $document->path;
    }
}

