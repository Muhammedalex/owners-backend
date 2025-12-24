<?php

namespace App\Exports\V1\Ownership;

use App\Models\V1\Ownership\Building;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class UnitsTemplateExport implements WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected ?Building $building;
    protected array $floors;
    protected ?int $ownershipId;
    protected array $buildings = [];
    protected array $buildingsWithFloors = []; // المباني مع طوابقها

    public function __construct(?Building $building = null, ?int $ownershipId = null)
    {
        $this->building = $building;
        $this->ownershipId = $ownershipId;
        
        if ($building) {
            $this->floors = $building->buildingFloors()
                ->orderBy('number')
                ->get()
                ->pluck('number')
                ->toArray();
        } else {
            $this->floors = [];
        }
        
        // جلب جميع المباني مع طوابقها إذا كان ownershipId موجود ولم يكن المبنى محدد مسبقاً
        if ($ownershipId && !$building) {
            $buildingsData = Building::where('ownership_id', $ownershipId)
                ->where('active', true)
                ->with(['buildingFloors' => function($query) {
                    $query->orderBy('number');
                }])
                ->orderBy('code')
                ->orderBy('name')
                ->get();
            
            $this->buildings = $buildingsData->map(function ($building) {
                return ($building->code ? $building->code . ' - ' : '') . $building->name;
            })->toArray();
            
            // حفظ المباني مع طوابقها
            $this->buildingsWithFloors = $buildingsData->mapWithKeys(function ($building) {
                $buildingName = ($building->code ? $building->code . ' - ' : '') . $building->name;
                $floors = $building->buildingFloors->pluck('number')->toArray();
                return [$buildingName => $floors];
            })->toArray();
        }
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
                
                // If building is pre-selected, set it in first data row
                if ($this->building) {
                    $sheet->setCellValue('A2', $this->building->code . ' - ' . $this->building->name);
                    
                    // Style and lock Building column (Column A)
                    $sheet->getStyle('A2:A1000')->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E7E6E6'],
                        ],
                        'font' => [
                            'bold' => true,
                        ],
                    ]);
                    $sheet->getStyle('A2:A1000')->getProtection()
                        ->setLocked(Protection::PROTECTION_PROTECTED);
                } else {
                    // Create dropdown for Building (Column A) - only if building is NOT pre-selected
                    if (!empty($this->buildings)) {
                        // Replace commas in building names with comma+space to avoid conflicts with Excel list separator
                        $buildingList = implode(',', array_map(function($building) {
                            return str_replace(',', ', ', $building);
                        }, $this->buildings));
                        
                        $buildingValidation = $sheet->getCell('A2')->getDataValidation();
                        $buildingValidation->setType(DataValidation::TYPE_LIST);
                        $buildingValidation->setFormula1('"' . $buildingList . '"');
                        $buildingValidation->setShowDropDown(true);
                        $buildingValidation->setShowErrorMessage(true);
                        $buildingValidation->setErrorTitle('Invalid Building');
                        $buildingValidation->setError('Please select a valid building from the list.');
                        
                        // Copy validation to all rows (up to row 1000)
                        for ($row = 2; $row <= 1000; $row++) {
                            $sheet->getCell("A{$row}")->setDataValidation(clone $buildingValidation);
                        }
                        
                        // إنشاء Named Ranges للطوابق لكل مبنى
                        $spreadsheet = $sheet->getParent();
                        $startRow = 1000; // بداية منطقة البيانات المخفية
                        $currentRow = $startRow;
                        
                        // إنشاء lookup table في عمود Y (اسم المبنى) و Z (الطوابق)
                        $lookupStartRow = $currentRow;
                        $lookupRow = $lookupStartRow;
                        
                        foreach ($this->buildingsWithFloors as $buildingName => $floors) {
                            if (empty($floors)) {
                                continue;
                            }
                            
                            // تنظيف اسم المبنى لاستخدامه كـ Named Range (إزالة الأحرف الخاصة)
                            $cleanBuildingName = preg_replace('/[^A-Za-z0-9_]/', '_', $buildingName);
                            $rangeName = 'Floors_' . $cleanBuildingName;
                            $rangeName = substr($rangeName, 0, 255); // Excel limit
                            
                            // كتابة اسم المبنى في عمود Y
                            $sheet->setCellValue('Y' . $lookupRow, $buildingName);
                            
                            // كتابة الطوابق في عمود Z
                            $floorStartRow = $currentRow;
                            foreach ($floors as $index => $floor) {
                                $cell = $sheet->getCell('Z' . ($currentRow + $index));
                                $cell->setValue($floor);
                            }
                            
                            // إنشاء Named Range للطوابق
                            // استخدام اسم الورقة في الـ range
                            $range = $sheet->getTitle() . '!$Z$' . $currentRow . ':$Z$' . ($currentRow + count($floors) - 1);
                            try {
                                $namedRange = new NamedRange($rangeName, $sheet, $range);
                                $spreadsheet->addNamedRange($namedRange);
                            } catch (\Exception $e) {
                                // إذا فشل، نجرب بدون اسم الورقة
                                $range = '$Z$' . $currentRow . ':$Z$' . ($currentRow + count($floors) - 1);
                                try {
                                    $namedRange = new NamedRange($rangeName, $sheet, $range);
                                    $spreadsheet->addNamedRange($namedRange);
                                } catch (\Exception $e2) {
                                    // إذا فشل مرة أخرى، نستخدم range بسيط
                                    $range = 'Z' . $currentRow . ':Z' . ($currentRow + count($floors) - 1);
                                    $namedRange = new NamedRange($rangeName, $sheet, $range);
                                    $spreadsheet->addNamedRange($namedRange);
                                }
                            }
                            
                            $currentRow += count($floors) + 1; // مسافة بين القوائم
                            $lookupRow++;
                        }
                        
                        // إخفاء الأعمدة Y و Z (التي تحتوي على البيانات المخفية)
                        $sheet->getColumnDimension('Y')->setVisible(false);
                        $sheet->getColumnDimension('Z')->setVisible(false);
                        
                        // إنشاء dropdown للطوابق يعتمد على اختيار المبنى باستخدام INDIRECT
                        // الصيغة: INDIRECT("Floors_"&SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(A2," - ","_")," ","_"),"-","_"))
                        // ملاحظة: Excel يحتاج إلى صيغة بدون علامات اقتباس حول INDIRECT
                        // لكن PhpSpreadsheet قد لا يدعم INDIRECT بشكل صحيح في Data Validation
                        // لذلك سنستخدم طريقة بديلة: dropdown بسيط يحتوي على جميع الطوابق
                        // مع رسالة للمستخدم لاختيار الطابق الصحيح للمبنى المحدد
                        
                        // جمع جميع الطوابق من جميع المباني
                        $allFloors = [];
                        foreach ($this->buildingsWithFloors as $floors) {
                            $allFloors = array_merge($allFloors, $floors);
                        }
                        $allFloors = array_unique($allFloors);
                        sort($allFloors);
                        
                        if (!empty($allFloors)) {
                            $floorList = implode(',', $allFloors);
                            
                            $floorValidation = $sheet->getCell('B2')->getDataValidation();
                            $floorValidation->setType(DataValidation::TYPE_LIST);
                            $floorValidation->setFormula1('"' . $floorList . '"');
                            $floorValidation->setShowDropDown(true);
                            $floorValidation->setShowErrorMessage(true);
                            $floorValidation->setErrorTitle('Invalid Floor');
                            $floorValidation->setError('Please select a valid floor number. Make sure it belongs to the selected building in column A.');
                            $floorValidation->setShowInputMessage(true);
                            $floorValidation->setPromptTitle('Select Floor');
                            $floorValidation->setPrompt('Select a floor number that belongs to the building selected in column A.');
                            
                            // Copy validation to all rows (up to row 1000)
                            for ($row = 2; $row <= 1000; $row++) {
                                $sheet->getCell("B{$row}")->setDataValidation(clone $floorValidation);
                            }
                        }
                        
                        // ملاحظة: للاستخدام المستقبلي، يمكن تفعيل INDIRECT إذا كان PhpSpreadsheet يدعمه بشكل أفضل
                        /*
                        for ($row = 2; $row <= 1000; $row++) {
                            $formula = 'INDIRECT("Floors_"&SUBSTITUTE(SUBSTITUTE(SUBSTITUTE(A' . $row . '," - ","_")," ","_"),"-","_"))';
                            $floorValidation = $sheet->getCell('B' . $row)->getDataValidation();
                            $floorValidation->setType(DataValidation::TYPE_LIST);
                            $floorValidation->setFormula1($formula);
                            $floorValidation->setShowDropDown(true);
                            $floorValidation->setShowErrorMessage(true);
                            $floorValidation->setErrorTitle('Invalid Floor');
                            $floorValidation->setError('Please select a valid floor number for the selected building.');
                        }
                        */
                    }
                }
                
                // Create dropdown for Floor Number (Column B) - إذا كان المبنى محدد مسبقاً
                if (!empty($this->floors) && $this->building) {
                    $floorList = implode(',', $this->floors);
                    
                    $validation = $sheet->getCell('B2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"' . $floorList . '"');
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Invalid Floor');
                    $validation->setError('Please select a valid floor number from the list.');
                    
                    // Copy validation to all rows (up to row 1000)
                    for ($row = 2; $row <= 1000; $row++) {
                        $sheet->getCell("B{$row}")->setDataValidation(clone $validation);
                    }
                }
                
                // Create dropdown for Unit Type (Column D)
                $unitTypes = 'apartment,office,shop,warehouse,studio,villa,penthouse';
                $typeValidation = $sheet->getCell('D2')->getDataValidation();
                $typeValidation->setType(DataValidation::TYPE_LIST);
                $typeValidation->setFormula1('"' . $unitTypes . '"');
                $typeValidation->setShowDropDown(true);
                $typeValidation->setShowErrorMessage(true);
                $typeValidation->setErrorTitle('Invalid Unit Type');
                $typeValidation->setError('Please select a valid unit type from the list: apartment, office, shop, warehouse, studio, villa, penthouse');
                
                for ($row = 2; $row <= 1000; $row++) {
                    $sheet->getCell("D{$row}")->setDataValidation(clone $typeValidation);
                }
                
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

