<?php

namespace App\Exports\V1\Ownership;

use App\Models\V1\Ownership\Unit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UnitsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected int $ownershipId;
    protected array $filters;

    public function __construct(int $ownershipId, array $filters = [])
    {
        $this->ownershipId = $ownershipId;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Unit::with(['building', 'floor'])
            ->where('ownership_id', $this->ownershipId);

        // Apply filters
        if (isset($this->filters['building_id'])) {
            $query->where('building_id', $this->filters['building_id']);
        }
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (isset($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }
        if (isset($this->filters['active'])) {
            $query->where('active', $this->filters['active']);
        }

        return $query->orderBy('building_id')->orderBy('number')->get();
    }

    public function headings(): array
    {
        return [
            'Building Code/Name',
            'Floor Number',
            'Unit Number',
            'Unit Type',
            'Unit Name',
            'Area (m²)',
            'Price Monthly',
            'Price Quarterly',
            'Price Yearly',
        ];
    }

    public function map($unit): array
    {
        return [
            $unit->building->code ?? $unit->building->name,
            $unit->floor ? $unit->floor->number : null,
            $unit->number,
            ucfirst($unit->type), // Capitalize first letter
            $unit->name,
            $unit->area, // Keep as number for proper formatting
            $unit->price_monthly, // Keep as number for proper formatting
            $unit->price_quarterly, // Keep as number for proper formatting
            $unit->price_yearly, // Keep as number for proper formatting
        ];
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                // Style header row (Row 1)
                $headerRange = 'A1:' . $highestColumn . '1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 12,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'], // Nice blue color
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                    ],
                ]);
                
                // Set header row height
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                // Freeze header row
                $sheet->freezePane('A2');
                
                // Style data rows
                $dataRange = 'A2:' . $highestColumn . $highestRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Alternate row colors for better readability
                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle("A{$row}:" . $highestColumn . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8F9FA'],
                            ],
                        ]);
                    }
                }
                
                // Format numeric columns
                // Area column (F)
                $sheet->getStyle('F2:F' . $highestRow)->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                
                // Price columns (G, H, I)
                $sheet->getStyle('G2:I' . $highestRow)->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                
                // Center align specific columns
                $centerColumns = ['B', 'C', 'D', 'F']; // Floor, Unit Number, Unit Type, Area
                foreach ($centerColumns as $col) {
                    $sheet->getStyle($col . '2:' . $col . $highestRow)->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                }
                
                // Right align price columns
                $sheet->getStyle('G2:I' . $highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
                
                // Auto-fit columns (with max width limit)
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                    $maxWidth = $sheet->getColumnDimension($col)->getWidth();
                    if ($maxWidth > 30) {
                        $sheet->getColumnDimension($col)->setWidth(30);
                    }
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styles are now handled in registerEvents for better control
        return [];
    }

    public function columnWidths(): array
    {
        // Column widths are now auto-sized in registerEvents
        return [
            'A' => 28, // Building Code/Name
            'B' => 16, // Floor Number
            'C' => 16, // Unit Number
            'D' => 20, // Unit Type
            'E' => 28, // Unit Name
            'F' => 16, // Area (m²)
            'G' => 20, // Price Monthly
            'H' => 20, // Price Quarterly
            'I' => 20, // Price Yearly
        ];
    }
}

