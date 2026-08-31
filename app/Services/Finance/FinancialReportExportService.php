<?php

namespace App\Services\Finance;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class FinancialReportExportService
{
    public function __construct(private readonly FinancialDashboardService $dashboard) {}

    public function export(Carbon $month, string $format): array
    {
        $data = $this->dashboard->dashboard($month, 6, 15);
        $month = $month->copy()->startOfMonth();
        $filename = 'balancete-financeiro-'.$month->format('Y-m');

        return match ($format) {
            'pdf' => [
                'content' => $this->pdf($month, $data),
                'filename' => $filename.'.pdf',
                'content_type' => 'application/pdf',
            ],
            'docx' => [
                'content' => $this->docx($month, $data),
                'filename' => $filename.'.docx',
                'content_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        };
    }

    private function pdf(Carbon $month, array $data): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($month, $data), 'UTF-8');
        $dompdf->setPaper('a4');
        $dompdf->render();

        return $dompdf->output();
    }

    private function docx(Carbon $month, array $data): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginRight' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
        ]);

        $section->addText('Balancete Financeiro', ['bold' => true, 'size' => 18]);
        $section->addText('Competência: '.$month->translatedFormat('F \d\e Y'));
        $section->addText('Gerado em: '.now()->format('d/m/Y H:i'));
        $section->addTextBreak();

        $this->addDocxTable($section, 'Resumo do mês', $this->summaryRows($data));
        $this->addDocxTable($section, 'Indicadores financeiros', $this->indicatorRows($data));
        $this->addDocxActivity($section, $data['recent_activity']);

        $path = tempnam(sys_get_temp_dir(), 'safecapital-report-');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function addDocxTable(object $section, string $title, array $rows): void
    {
        $section->addText($title, ['bold' => true, 'size' => 13]);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 90]);

        foreach ($rows as [$label, $value]) {
            $table->addRow();
            $table->addCell(7000)->addText($label);
            $table->addCell(2500)->addText($value, ['bold' => true], ['alignment' => 'right']);
        }

        $section->addTextBreak();
    }

    private function addDocxActivity(object $section, array $activities): void
    {
        $section->addText('Movimentações do mês', ['bold' => true, 'size' => 13]);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 90]);
        $table->addRow();
        foreach (['Data', 'Tipo', 'Descrição', 'Valor'] as $heading) {
            $table->addCell()->addText($heading, ['bold' => true]);
        }

        foreach ($activities as $activity) {
            $table->addRow();
            $table->addCell(1600)->addText(Carbon::parse($activity['occurred_at'])->format('d/m/Y'));
            $table->addCell(1900)->addText($this->activityLabel($activity['type']));
            $table->addCell(4700)->addText($activity['description']);
            $table->addCell(1800)->addText($this->signedMoney($activity), ['bold' => true]);
        }
    }

    private function html(Carbon $month, array $data): string
    {
        $summary = $this->htmlRows($this->summaryRows($data));
        $indicators = $this->htmlRows($this->indicatorRows($data));
        $activities = collect($data['recent_activity'])->map(function (array $activity): string {
            return '<tr><td>'.e(Carbon::parse($activity['occurred_at'])->format('d/m/Y')).'</td>'
                .'<td>'.e($this->activityLabel($activity['type'])).'</td>'
                .'<td>'.e($activity['description']).'</td>'
                .'<td class="amount">'.e($this->signedMoney($activity)).'</td></tr>';
        })->implode('');

        return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>'
            .'body{font-family:DejaVu Sans,sans-serif;color:#0f172a;font-size:10px}'
            .'h1{font-size:22px;margin:0 0 5px}h2{font-size:14px;margin:23px 0 8px}'
            .'.muted{color:#64748b}.card{background:#f8fafc;border:1px solid #e2e8f0;padding:10px}'
            .'table{width:100%;border-collapse:collapse}td,th{border:1px solid #cbd5e1;padding:7px;text-align:left}'
            .'th{background:#e2e8f0}.amount{text-align:right;font-weight:bold}'
            .'</style></head><body>'
            .'<h1>Balancete Financeiro</h1>'
            .'<p class="muted">Competência: '.e($month->translatedFormat('F \d\e Y')).'<br>Gerado em: '.e(now()->format('d/m/Y H:i')).'</p>'
            .'<h2>Resumo do mês</h2><table class="card">'.$summary.'</table>'
            .'<h2>Indicadores financeiros</h2><table>'.$indicators.'</table>'
            .'<h2>Movimentações do mês</h2><table><thead><tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Valor</th></tr></thead><tbody>'.$activities.'</tbody></table>'
            .'</body></html>';
    }

    private function summaryRows(array $data): array
    {
        $summary = $data['summary'];

        return [
            ['Faturas recebidas', $this->money($summary['invoice_income'])],
            ['Valores resgatados', $this->money($summary['recoveries_income'])],
            ['Total de entradas', $this->money($summary['total_income'])],
            ['Total de saídas', $this->money($summary['total_expenses'])],
            ['Saldo do mês', $this->money($summary['net_balance'])],
            ['Saldo a resgatar', $this->money($summary['recoverable_balance'])],
        ];
    }

    private function indicatorRows(array $data): array
    {
        return [
            ['Faturas em aberto', $data['invoices']['open']['count'].' — '.$this->money($data['invoices']['open']['amount'])],
            ['Faturas vencidas', $data['invoices']['overdue']['count'].' — '.$this->money($data['invoices']['overdue']['amount'])],
            ['Despesas pendentes', $data['expenses']['pending']['count'].' — '.$this->money($data['expenses']['pending']['amount'])],
            ['Valores pendentes de resgate', $data['recoverables']['pending']['count'].' — '.$this->money($data['recoverables']['pending']['amount'])],
        ];
    }

    private function htmlRows(array $rows): string
    {
        return collect($rows)->map(fn (array $row) => '<tr><td>'.e($row[0]).'</td><td class="amount">'.e($row[1]).'</td></tr>')->implode('');
    }

    private function money(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function signedMoney(array $activity): string
    {
        $prefix = $activity['type'] === 'EXPENSE_PAYMENT' ? '- ' : '+ ';

        return $prefix.$this->money($activity['amount']);
    }

    private function activityLabel(string $type): string
    {
        return match ($type) {
            'INVOICE_PAYMENT' => 'Fatura recebida',
            'EXPENSE_PAYMENT' => 'Saída paga',
            'RECOVERABLE_RECEIPT' => 'Valor resgatado',
        };
    }
}
