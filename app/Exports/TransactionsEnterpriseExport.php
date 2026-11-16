<?php

namespace App\Exports;

use App\Models\Currency;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionsEnterpriseExport implements WithMultipleSheets
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $transactions = $this->filteredTransactions();
        $currencies = $transactions
            ->groupBy(fn ($row) => $row->currency->code ?? 'N/A')
            ->keys()
            ->values();

        $sheets = [
            new TransactionsExecutiveSummarySheet($this->request, $transactions),
            new TransactionsLedgerSheet($this->request, $transactions),
            new TransactionsDataSheet($this->request, $transactions),
        ];

        foreach ($currencies as $code) {
            $currencyTransactions = $transactions->filter(fn ($row) => ($row->currency->code ?? 'N/A') === $code)->values();
            $sheets[] = new TransactionsCurrencyLedgerSheet($this->request, $currencyTransactions, $code);
        }

        return $sheets;
    }

    protected function filteredTransactions(): Collection
    {
        $query = Transaction::query()
            ->where('is_visible', 1)
            ->with(['account', 'currency']);

        if ($this->request->filled('account_id')) {
            $query->where('account_id', $this->request->account_id);
        }

        if ($this->request->filled('currency_id')) {
            $query->where('currency_id', $this->request->currency_id);
        }

        if ($this->request->filled('transaction_type')) {
            $query->where('transaction_type', $this->request->transaction_type);
        }

        if ($this->request->filled('from_date')) {
            $query->where('created_at', '>=', $this->request->from_date . ' 00:00:00');
        }

        if ($this->request->filled('to_date')) {
            $query->where('created_at', '<=', $this->request->to_date . ' 23:59:59');
        }

        return $query->orderBy('created_at')->get();
    }
}

class TransactionsExecutiveSummarySheet implements FromArray, WithTitle, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    protected Request $request;
    protected Collection $transactions;
    protected array $rows = [];

    public function __construct(Request $request, Collection $transactions)
    {
        $this->request = $request;
        $this->transactions = $transactions;
    }

    public function title(): string
    {
        return 'Executive Summary';
    }

    public function array(): array
    {
        $company = Setting::first();
        $companyName = $company->name ?? $company->company_name ?? 'Company';
        $address = $company->address ?? '';
        $email = $company->email ?? '';
        $from = $this->request->from_date ?: 'Start';
        $to = $this->request->to_date ?: 'End';

        $summary = $this->transactions
            ->groupBy(fn ($row) => $row->currency->code ?? 'N/A')
            ->map(function ($items, $code) {
                $credit = $items->where('transaction_type', 'credit')->sum('amount');
                $debit = $items->where('transaction_type', 'debit')->sum('amount');

                return [
                    $code,
                    $items->count(),
                    $credit,
                    $debit,
                    $credit - $debit,
                ];
            })
            ->values()
            ->toArray();

        $this->rows = [
            [$companyName, '', '', '', ''],
            [$address . ($email ? ' | ' . $email : ''), '', '', '', ''],
            ['', '', '', '', ''],
            ['TRANSACTION DETAIL REPORT', '', '', '', ''],
            ["Period: {$from} to {$to} | Basis: Accrual", '', '', '', ''],
            ['', '', '', '', ''],
            ['Currency', 'Transactions', 'Total Credit', 'Total Debit', 'Net Balance'],
            ...$summary,
            ['', '', '', '', ''],
            ['Grand Total', $this->transactions->count(), $this->transactions->where('transaction_type', 'credit')->sum('amount'), $this->transactions->where('transaction_type', 'debit')->sum('amount'), $this->transactions->where('transaction_type', 'credit')->sum('amount') - $this->transactions->where('transaction_type', 'debit')->sum('amount')],
        ];

        return $this->rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0.00',
            'D' => '#,##0.00',
            'E' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->rows);

                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A4:E4');
                $sheet->mergeCells('A5:E5');

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(4)->setRowHeight(26);

                $sheet->getStyle('A1:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A5')->getFont()->setSize(10)->getColor()->setRGB('666666');

                $sheet->getStyle('A7:E7')->applyFromArray($this->headerStyle());
                $sheet->getStyle("A7:E{$lastRow}")->applyFromArray($this->tableBorderStyle());
                $sheet->getStyle("C8:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("A{$lastRow}:E{$lastRow}")->applyFromArray($this->totalStyle());

                for ($i = 8; $i <= $lastRow; $i++) {
                    $balance = (float) $sheet->getCell("E{$i}")->getValue();
                    $sheet->getStyle("E{$i}")->getFont()->getColor()->setRGB($balance >= 0 ? '198754' : 'DC3545');
                }

                $sheet->freezePane('A8');
                $sheet->setAutoFilter("A7:E{$lastRow}");

                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(match ($col) {
                        'A' => 18,
                        'B' => 16,
                        default => 20,
                    });
                }
            },
        ];
    }

    protected function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F2A45']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    protected function tableBorderStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9DEE8']],
            ],
        ];
    }

    protected function totalStyle(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F7']],
        ];
    }
}

class TransactionsLedgerSheet implements FromArray, WithTitle, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    protected Request $request;
    protected Collection $transactions;
    protected array $rows = [];

    public function __construct(Request $request, Collection $transactions)
    {
        $this->request = $request;
        $this->transactions = $transactions;
    }

    public function title(): string
    {
        return 'Full Ledger';
    }

    public function array(): array
    {
        $this->rows = [
            ['Date', 'Account Code', 'Account Name', 'Currency', 'Debit', 'Credit', 'Running Balance', 'Memo'],
        ];

        $balances = [];

        foreach ($this->transactions as $row) {
            $currency = $row->currency->code ?? 'N/A';

            if (!isset($balances[$currency])) {
                $balances[$currency] = 0;
            }

            $debit = $row->transaction_type === 'debit' ? (float) $row->amount : 0;
            $credit = $row->transaction_type === 'credit' ? (float) $row->amount : 0;

            $balances[$currency] += $credit - $debit;

            $this->rows[] = [
                optional($row->created_at)->format('Y-m-d H:i:s'),
                $row->account->code ?? '-',
                $row->account->name ?? '-',
                $currency,
                $debit ?: null,
                $credit ?: null,
                $balances[$currency],
                $row->note,
            ];
        }

        return $this->rows;
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0.00',
            'F' => '#,##0.00',
            'G' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->styleLedger($event),
        ];
    }

    protected function styleLedger(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $lastRow = count($this->rows);

        $sheet->getStyle('A1:H1')->applyFromArray($this->headerStyle());
        $sheet->getStyle("A1:H{$lastRow}")->applyFromArray($this->borderStyle());
        $sheet->getStyle("E2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setWrapText(false);

        for ($i = 2; $i <= $lastRow; $i++) {
            if ($sheet->getCell("E{$i}")->getValue()) {
                $sheet->getStyle("E{$i}")->getFont()->getColor()->setRGB('DC3545');
            }

            if ($sheet->getCell("F{$i}")->getValue()) {
                $sheet->getStyle("F{$i}")->getFont()->getColor()->setRGB('198754');
            }

            $balance = (float) $sheet->getCell("G{$i}")->getValue();
            $sheet->getStyle("G{$i}")->getFont()->getColor()->setRGB($balance >= 0 ? '198754' : 'DC3545');
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:H{$lastRow}");

        $widths = [
            'A' => 20,
            'B' => 16,
            'C' => 28,
            'D' => 12,
            'E' => 16,
            'F' => 16,
            'G' => 18,
            'H' => 55,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }

    protected function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F2A45']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
    }

    protected function borderStyle(): array
    {
        return [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E7EC']],
            ],
        ];
    }
}

class TransactionsDataSheet implements FromArray, WithTitle, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    protected Request $request;
    protected Collection $transactions;
    protected array $rows = [];

    public function __construct(Request $request, Collection $transactions)
    {
        $this->request = $request;
        $this->transactions = $transactions;
    }

    public function title(): string
    {
        return 'Raw Data';
    }

    public function array(): array
    {
        $this->rows = [
            ['ID', 'Date', 'Account ID', 'Account Code', 'Account Name', 'Currency ID', 'Currency', 'Type', 'Amount', 'Debit', 'Credit', 'Memo', 'Reference', 'Module', 'Module ID', 'Created By'],
        ];

        foreach ($this->transactions as $row) {
            $this->rows[] = [
                $row->id,
                optional($row->created_at)->format('Y-m-d H:i:s'),
                $row->account_id,
                $row->account->code ?? '-',
                $row->account->name ?? '-',
                $row->currency_id,
                $row->currency->code ?? '-',
                $row->transaction_type,
                (float) $row->amount,
                $row->transaction_type === 'debit' ? (float) $row->amount : null,
                $row->transaction_type === 'credit' ? (float) $row->amount : null,
                $row->note,
                $row->reference,
                $row->table_name,
                $row->table_row_id,
                $row->created_by,
            ];
        }

        return $this->rows;
    }

    public function columnFormats(): array
    {
        return [
            'I' => '#,##0.00',
            'J' => '#,##0.00',
            'K' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->rows);

                $sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '344054']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A1:P{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E7EC']],
                    ],
                ]);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:P{$lastRow}");

                foreach (range('A', 'P') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(match ($col) {
                        'L' => 50,
                        'E' => 28,
                        default => 16,
                    });
                }
            },
        ];
    }
}

class TransactionsCurrencyLedgerSheet implements FromArray, WithTitle, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    protected Request $request;
    protected Collection $transactions;
    protected string $currencyCode;
    protected array $rows = [];

    public function __construct(Request $request, Collection $transactions, string $currencyCode)
    {
        $this->request = $request;
        $this->transactions = $transactions;
        $this->currencyCode = $currencyCode;
    }

    public function title(): string
    {
        return substr(preg_replace('/[^A-Za-z0-9 ]/', '', $this->currencyCode) . ' Ledger', 0, 31);
    }

    public function array(): array
    {
        $credit = $this->transactions->where('transaction_type', 'credit')->sum('amount');
        $debit = $this->transactions->where('transaction_type', 'debit')->sum('amount');
        $balance = $credit - $debit;

        $this->rows = [
            ["{$this->currencyCode} LEDGER", '', '', '', '', ''],
            ['Total Credit', $credit, 'Total Debit', $debit, 'Net Balance', $balance],
            ['', '', '', '', '', ''],
            ['Date', 'Account Code', 'Account Name', 'Debit', 'Credit', 'Running Balance', 'Memo'],
        ];

        $running = 0;

        foreach ($this->transactions as $row) {
            $debitAmount = $row->transaction_type === 'debit' ? (float) $row->amount : 0;
            $creditAmount = $row->transaction_type === 'credit' ? (float) $row->amount : 0;
            $running += $creditAmount - $debitAmount;

            $this->rows[] = [
                optional($row->created_at)->format('Y-m-d H:i:s'),
                $row->account->code ?? '-',
                $row->account->name ?? '-',
                $debitAmount ?: null,
                $creditAmount ?: null,
                $running,
                $row->note,
            ];
        }

        return $this->rows;
    }

    public function columnFormats(): array
    {
        return [
            'B' => '#,##0.00',
            'D' => '#,##0.00',
            'F' => '#,##0.00',
            'D' => '#,##0.00',
            'E' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => $this->styleSheet($event),
        ];
    }

    protected function styleSheet(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $lastRow = count($this->rows);

        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '2F2A45']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F7']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9DEE8']],
            ],
        ]);

        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F2A45']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E4E7EC']],
            ],
        ]);

        $sheet->getStyle("D5:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("B2:F2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        for ($i = 5; $i <= $lastRow; $i++) {
            if ($sheet->getCell("D{$i}")->getValue()) {
                $sheet->getStyle("D{$i}")->getFont()->getColor()->setRGB('DC3545');
            }

            if ($sheet->getCell("E{$i}")->getValue()) {
                $sheet->getStyle("E{$i}")->getFont()->getColor()->setRGB('198754');
            }

            $balance = (float) $sheet->getCell("F{$i}")->getValue();
            $sheet->getStyle("F{$i}")->getFont()->getColor()->setRGB($balance >= 0 ? '198754' : 'DC3545');
        }

        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:G{$lastRow}");

        $widths = [
            'A' => 20,
            'B' => 16,
            'C' => 30,
            'D' => 16,
            'E' => 16,
            'F' => 18,
            'G' => 55,
        ];

        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
    }
}
