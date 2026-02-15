<?php

namespace App\Services;

use App\Models\Cost;

/**
 * PDF Export pro náklady
 * Používá HTML2PDF přístup pro jednoduchost
 */
class CostsPdfExporter
{
    private Cost $costModel;
    
    public function __construct()
    {
        $this->costModel = new Cost();
    }
    
    /**
     * Export měsíční analytiky do PDF
     */
    public function exportMonthly(int $userId, int $year, int $month): void
    {
        $data = $this->costModel->getMonthlyBreakdown($userId, $year, $month);
        
        $czechMonths = [
            1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
            5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
            9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
        ];
        
        $monthName = $czechMonths[$month];
        $html = $this->generateMonthlyHTML($data, $monthName, $year, $month);
        
        $this->outputPDF($html, "naklady_{$year}_{$month}.pdf");
    }
    
    /**
     * Export roční analytiky do PDF
     */
    public function exportYearly(int $userId, int $year): void
    {
        $data = $this->costModel->getYearlyOverview($userId, $year);
        $html = $this->generateYearlyHTML($data, $year);
        
        $this->outputPDF($html, "naklady_rocni_{$year}.pdf");
    }
    
    /**
     * Export kvartální analytiky do PDF
     */
    public function exportQuarterly(int $userId, int $year, int $quarter): void
    {
        $data = $this->costModel->getQuarterlyBreakdown($userId, $year, $quarter);
        $html = $this->generateQuarterlyHTML($data, $year, $quarter);
        
        $this->outputPDF($html, "naklady_Q{$quarter}_{$year}.pdf");
    }
    
    /**
     * Generování HTML pro měsíční report
     */
    private function generateMonthlyHTML(array $data, string $monthName, int $year, int $month): string
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #1e40af; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #3b82f6; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .stat-box { 
            display: inline-block; 
            width: 48%; 
            padding: 15px; 
            margin: 5px 1%; 
            background: #eff6ff; 
            border-left: 4px solid #3b82f6;
        }
        .stat-label { font-size: 9pt; color: #6b7280; }
        .stat-value { font-size: 16pt; font-weight: bold; color: #1e40af; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Přehled nákladů - <?= $monthName ?> <?= $year ?></h1>
    
    <div style="margin: 20px 0;">
        <div class="stat-box">
            <div class="stat-label">Celkové náklady</div>
            <div class="stat-value"><?= number_format($data['total'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Fixní náklady</div>
            <div class="stat-value"><?= number_format($data['fixed'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Variabilní náklady</div>
            <div class="stat-value"><?= number_format($data['variable'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Počet dní v měsíci</div>
            <div class="stat-value"><?= $daysInMonth ?> dní</div>
        </div>
    </div>
    
    <h2>Náklady podle kategorií</h2>
    <table>
        <thead>
            <tr>
                <th>Kategorie</th>
                <th class="text-right">Částka</th>
                <th class="text-right">Podíl</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['by_category'] as $category => $amount): 
                $percentage = $data['total'] > 0 ? ($amount / $data['total']) * 100 : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($category) ?></td>
                <td class="text-right"><?= number_format($amount, 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($percentage, 1) ?> %</td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: #dbeafe;">
                <td>CELKEM</td>
                <td class="text-right"><?= number_format($data['total'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right">100 %</td>
            </tr>
        </tbody>
    </table>
    
    <h2>Náklady podle frekvence</h2>
    <table>
        <thead>
            <tr>
                <th>Frekvence</th>
                <th class="text-right">Částka (měsíčně)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $freqLabels = [
                'daily' => 'Denně',
                'weekly' => 'Týdně',
                'monthly' => 'Měsíčně',
                'quarterly' => 'Kvartálně',
                'yearly' => 'Ročně',
                'once' => 'Jednorázově'
            ];
            foreach ($data['by_frequency'] as $freq => $amount): 
            ?>
            <tr>
                <td><?= $freqLabels[$freq] ?? $freq ?></td>
                <td class="text-right"><?= number_format($amount, 0, ',', ' ') ?> Kč</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Vygenerováno: <?= date('d.m.Y H:i') ?> | E-shop Analytics Platform
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generování HTML pro roční report
     */
    private function generateYearlyHTML(array $data, int $year): string
    {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #3b82f6; color: white; padding: 8px; text-align: left; font-size: 9pt; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 9pt; }
        tr:nth-child(even) { background: #f9fafb; }
        .stat-box { 
            display: inline-block; 
            width: 23%; 
            padding: 12px; 
            margin: 5px 1%; 
            background: #eff6ff; 
            border-left: 4px solid #3b82f6;
        }
        .stat-label { font-size: 8pt; color: #6b7280; }
        .stat-value { font-size: 14pt; font-weight: bold; color: #1e40af; }
        .text-right { text-align: right; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Roční přehled nákladů <?= $year ?></h1>
    
    <div style="margin: 20px 0;">
        <div class="stat-box">
            <div class="stat-label">Celkem za rok</div>
            <div class="stat-value"><?= number_format($data['total_year'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Průměr/měsíc</div>
            <div class="stat-value"><?= number_format($data['avg_month'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Fixní celkem</div>
            <div class="stat-value"><?= number_format($data['fixed_total'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Variabilní celkem</div>
            <div class="stat-value"><?= number_format($data['variable_total'], 0, ',', ' ') ?> Kč</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Měsíc</th>
                <th class="text-right">Celkem</th>
                <th class="text-right">Fixní</th>
                <th class="text-right">Variabilní</th>
                <th class="text-right">% z roku</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['months'] as $monthData): 
                $percentage = $data['total_year'] > 0 ? ($monthData['total'] / $data['total_year']) * 100 : 0;
            ?>
            <tr>
                <td><?= $monthData['month_name'] ?></td>
                <td class="text-right"><strong><?= number_format($monthData['total'], 0, ',', ' ') ?> Kč</strong></td>
                <td class="text-right"><?= number_format($monthData['fixed'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($monthData['variable'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($percentage, 1) ?> %</td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: #dbeafe;">
                <td>CELKEM</td>
                <td class="text-right"><?= number_format($data['total_year'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($data['fixed_total'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($data['variable_total'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right">100 %</td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer">
        Vygenerováno: <?= date('d.m.Y H:i') ?> | E-shop Analytics Platform
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generování HTML pro kvartální report
     */
    private function generateQuarterlyHTML(array $data, int $year, int $quarter): string
    {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        h1 { color: #2563eb; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #1e40af; margin-top: 20px; font-size: 12pt; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #3b82f6; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .stat-box { 
            display: inline-block; 
            width: 48%; 
            padding: 12px; 
            margin: 5px 1%; 
            background: #eff6ff; 
            border-left: 4px solid #3b82f6;
        }
        .stat-label { font-size: 9pt; color: #6b7280; }
        .stat-value { font-size: 14pt; font-weight: bold; color: #1e40af; }
        .text-right { text-align: right; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 8pt; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Kvartální přehled - Q<?= $quarter ?> / <?= $year ?></h1>
    
    <div style="margin: 20px 0;">
        <div class="stat-box">
            <div class="stat-label">Celkem za kvartál</div>
            <div class="stat-value"><?= number_format($data['total'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Fixní náklady</div>
            <div class="stat-value"><?= number_format($data['fixed'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Variabilní náklady</div>
            <div class="stat-value"><?= number_format($data['variable'], 0, ',', ' ') ?> Kč</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Průměr/měsíc</div>
            <div class="stat-value"><?= number_format($data['total'] / 3, 0, ',', ' ') ?> Kč</div>
        </div>
    </div>
    
    <h2>Měsíční breakdown</h2>
    <table>
        <thead>
            <tr>
                <th>Měsíc</th>
                <th class="text-right">Celkem</th>
                <th class="text-right">Fixní</th>
                <th class="text-right">Variabilní</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['by_month'] as $monthData): ?>
            <tr>
                <td><?= $monthData['month_name'] ?></td>
                <td class="text-right"><strong><?= number_format($monthData['total'], 0, ',', ' ') ?> Kč</strong></td>
                <td class="text-right"><?= number_format($monthData['fixed'], 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($monthData['variable'], 0, ',', ' ') ?> Kč</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Kategorie za kvartál</h2>
    <table>
        <thead>
            <tr>
                <th>Kategorie</th>
                <th class="text-right">Částka</th>
                <th class="text-right">Podíl</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['by_category'] as $category => $amount): 
                $percentage = $data['total'] > 0 ? ($amount / $data['total']) * 100 : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($category) ?></td>
                <td class="text-right"><?= number_format($amount, 0, ',', ' ') ?> Kč</td>
                <td class="text-right"><?= number_format($percentage, 1) ?> %</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        Vygenerováno: <?= date('d.m.Y H:i') ?> | E-shop Analytics Platform
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Výstup PDF do prohlížeče
     */
    private function outputPDF(string $html, string $filename): void
    {
        // Nastavení hlaviček pro PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Použití wkhtmltopdf nebo DomPDF jako fallback
        // Pro jednoduchost použijeme HTML výstup který prohlížeč může vytisknout jako PDF
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo $html;
        echo '<script>window.print();</script>';
        echo '</body></html>';
    }
}
