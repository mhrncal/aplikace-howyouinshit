<?php

namespace App\Models;

use App\Core\Database;

class Cost
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Všechny náklady (s podporou global vs store scope)
     */
    public function getAll(int $userId, int $page = 1, int $perPage = 20, array $filters = [], ?int $storeId = null): array
    {
        $offset = ($page - 1) * $perPage;
        
        $where = ["user_id = ?"];
        $params = [$userId];
        
        // Store filtr: zobraz globální náklady + náklady pro tento shop
        if ($storeId) {
            $where[] = "(scope = 'global' OR (scope = 'store' AND store_id = ?))";
            $params[] = $storeId;
        }
        
        if (!empty($filters['type'])) {
            $where[] = "type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['frequency'])) {
            $where[] = "frequency = ?";
            $params[] = $filters['frequency'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        if (isset($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = $filters['is_active'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $costs = $this->db->fetchAll(
            "SELECT * FROM costs 
             WHERE {$whereClause}
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM costs WHERE {$whereClause}",
            $params
        )['count'];
        
        return [
            'costs' => $costs,
            'pagination' => paginate($total, $perPage, $page)
        ];
    }

    /**
     * Najít podle ID
     */
    public function findById(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM costs WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Vytvořit náklad
     */
    public function create(array $data): int
    {
        return $this->db->insert('costs', $data);
    }

    /**
     * Aktualizovat náklad
     */
    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update('costs', $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Smazat náklad
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->delete('costs', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Toggle aktivace
     */
    public function toggleActive(int $id, int $userId): bool
    {
        $cost = $this->findById($id, $userId);
        if (!$cost) return false;
        
        return $this->db->update(
            'costs',
            ['is_active' => !$cost['is_active']],
            'id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    /**
     * Kategorie nákladů
     */
    public function getCategories(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT category FROM costs WHERE user_id = ? AND category IS NOT NULL ORDER BY category",
            [$userId]
        );
    }

    /**
     * ANALYTIKA - Celkové náklady za období
     */
    public function getTotalForPeriod(int $userId, string $startDate, string $endDate): float
    {
        $result = $this->db->fetchOne(
            "SELECT SUM(amount) as total FROM costs 
             WHERE user_id = ? 
             AND is_active = 1
             AND start_date <= ?
             AND (end_date IS NULL OR end_date >= ?)",
            [$userId, $endDate, $startDate]
        );
        
        return (float) ($result['total'] ?? 0);
    }

    /**
     * ANALYTIKA - Měsíční náklady (včetně přepočtu frekvencí)
     */
    public function getMonthlyBreakdown(int $userId, int $year, int $month): array
    {
        $firstDay = date('Y-m-01', strtotime("$year-$month-01"));
        $lastDay = date('Y-m-t', strtotime($firstDay));
        
        $costs = $this->db->fetchAll(
            "SELECT * FROM costs 
             WHERE user_id = ? 
             AND is_active = 1
             AND start_date <= ?
             AND (end_date IS NULL OR end_date >= ?)",
            [$userId, $lastDay, $firstDay]
        );
        
        $breakdown = [
            'fixed' => 0,
            'variable' => 0,
            'by_category' => [],
            'by_frequency' => [],
            'items' => []
        ];
        
        foreach ($costs as $cost) {
            // Pro jednorázové náklady: započítat pouze v měsíci start_date
            if ($cost['frequency'] === 'once') {
                $costStartMonth = (int) date('n', strtotime($cost['start_date']));
                $costStartYear = (int) date('Y', strtotime($cost['start_date']));
                
                // Pokud start_date není v tomto měsíci, přeskoč
                if ($costStartMonth !== $month || $costStartYear !== $year) {
                    continue;
                }
                
                // Započítat celou částku
                $monthlyAmount = $cost['amount'];
            } else {
                // Přepočet na měsíční částku podle frekvence S PŘESNÝMI DNY
                $monthlyAmount = $this->convertToMonthly($cost['amount'], $cost['frequency'], $year, $month);
            }
            
            // Typ
            $breakdown[$cost['type']] += $monthlyAmount;
            
            // Kategorie
            $category = $cost['category'] ?? 'Ostatní';
            if (!isset($breakdown['by_category'][$category])) {
                $breakdown['by_category'][$category] = 0;
            }
            $breakdown['by_category'][$category] += $monthlyAmount;
            
            // Frekvence
            $freq = $cost['frequency'];
            if (!isset($breakdown['by_frequency'][$freq])) {
                $breakdown['by_frequency'][$freq] = 0;
            }
            $breakdown['by_frequency'][$freq] += $monthlyAmount;
            
            // Items
            $breakdown['items'][] = array_merge($cost, [
                'monthly_amount' => $monthlyAmount
            ]);
        }
        
        $breakdown['total'] = $breakdown['fixed'] + $breakdown['variable'];
        
        // Seřadit kategorie podle částky
        arsort($breakdown['by_category']);
        
        return $breakdown;
    }

    /**
     * ANALYTIKA - Roční přehled
     */
    public function getYearlyOverview(int $userId, int $year): array
    {
        $overview = [
            'months' => [],
            'total_year' => 0,
            'avg_month' => 0,
            'fixed_total' => 0,
            'variable_total' => 0,
        ];
        
        for ($month = 1; $month <= 12; $month++) {
            $data = $this->getMonthlyBreakdown($userId, $year, $month);
            
            // České názvy měsíců
            $czechMonths = [
                1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
                5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
                9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
            ];
            
            $overview['months'][$month] = [
                'month' => $month,
                'month_name' => $czechMonths[$month],
                'total' => $data['total'],
                'fixed' => $data['fixed'],
                'variable' => $data['variable']
            ];
            $overview['total_year'] += $data['total'];
            $overview['fixed_total'] += $data['fixed'];
            $overview['variable_total'] += $data['variable'];
        }
        
        $overview['avg_month'] = $overview['total_year'] / 12;
        
        return $overview;
    }

    /**
     * Pomocná funkce - Přepočet na měsíční částku PŘESNĚ podle dní v měsíci
     */
    private function convertToMonthly(float $amount, string $frequency, int $year = null, int $month = null): float
    {
        // Pokud není zadán rok/měsíc, použij průměr
        if ($year === null || $month === null) {
            switch ($frequency) {
                case 'daily':
                    return $amount * 30.44; // průměrný měsíc (365.25/12)
                case 'weekly':
                    return $amount * 4.35; // průměrný měsíc (52.18/12)
                case 'monthly':
                    return $amount;
                case 'quarterly':
                    return $amount / 3;
                case 'yearly':
                    return $amount / 12;
                case 'once':
                    return 0;
                default:
                    return $amount;
            }
        }
        
        // Přesný výpočet podle konkrétního měsíce
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $weeksInMonth = $daysInMonth / 7;
        
        switch ($frequency) {
            case 'daily':
                return $amount * $daysInMonth; // přesný počet dní (28-31)
            case 'weekly':
                return $amount * $weeksInMonth; // přesný počet týdnů
            case 'monthly':
                return $amount;
            case 'quarterly':
                return $amount / 3;
            case 'yearly':
                return $amount / 12;
            case 'once':
                return 0;
            default:
                return $amount;
        }
    }

    /**
     * ANALYTIKA - Srovnání období
     */
    public function comparePeriods(int $userId, string $period1Start, string $period1End, string $period2Start, string $period2End): array
    {
        $period1 = $this->getTotalForPeriod($userId, $period1Start, $period1End);
        $period2 = $this->getTotalForPeriod($userId, $period2Start, $period2End);
        
        $difference = $period2 - $period1;
        $percentChange = $period1 > 0 ? ($difference / $period1) * 100 : 0;
        
        return [
            'period1' => $period1,
            'period2' => $period2,
            'difference' => $difference,
            'percent_change' => $percentChange,
            'trend' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'stable')
        ];
    }
    
    /**
     * ANALYTIKA - Týdenní náklady
     */
    public function getWeeklyBreakdown(int $userId, int $year, int $week): array
    {
        // Najít první a poslední den týdne
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $firstDay = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $lastDay = $dto->format('Y-m-d');
        
        $costs = $this->db->fetchAll(
            "SELECT * FROM costs 
             WHERE user_id = ? 
             AND is_active = 1
             AND start_date <= ?
             AND (end_date IS NULL OR end_date >= ?)",
            [$userId, $lastDay, $firstDay]
        );
        
        $breakdown = [
            'fixed' => 0,
            'variable' => 0,
            'by_category' => [],
            'total' => 0,
            'first_day' => $firstDay,
            'last_day' => $lastDay,
        ];
        
        foreach ($costs as $cost) {
            $weeklyAmount = $this->convertToWeekly($cost['amount'], $cost['frequency']);
            
            $breakdown[$cost['type']] += $weeklyAmount;
            
            $category = $cost['category'] ?? 'Ostatní';
            if (!isset($breakdown['by_category'][$category])) {
                $breakdown['by_category'][$category] = 0;
            }
            $breakdown['by_category'][$category] += $weeklyAmount;
        }
        
        $breakdown['total'] = $breakdown['fixed'] + $breakdown['variable'];
        arsort($breakdown['by_category']);
        
        return $breakdown;
    }
    
    /**
     * ANALYTIKA - Kvartální náklady  
     */
    public function getQuarterlyBreakdown(int $userId, int $year, int $quarter): array
    {
        $quarterMonths = [
            1 => [1, 3], 2 => [4, 6], 3 => [7, 9], 4 => [10, 12]
        ];
        
        [$firstMonth, $lastMonth] = $quarterMonths[$quarter];
        
        $breakdown = [
            'fixed' => 0,
            'variable' => 0,
            'by_category' => [],
            'by_month' => [],
            'total' => 0,
            'quarter' => $quarter,
            'year' => $year,
        ];
        
        $czechMonths = [
            1 => 'Leden', 2 => 'Únor', 3 => 'Březen', 4 => 'Duben',
            5 => 'Květen', 6 => 'Červen', 7 => 'Červenec', 8 => 'Srpen',
            9 => 'Září', 10 => 'Říjen', 11 => 'Listopad', 12 => 'Prosinec'
        ];
        
        for ($m = $firstMonth; $m <= $lastMonth; $m++) {
            $monthData = $this->getMonthlyBreakdown($userId, $year, $m);
            
            $breakdown['by_month'][$m] = [
                'month' => $m,
                'month_name' => $czechMonths[$m],
                'total' => $monthData['total'],
                'fixed' => $monthData['fixed'],
                'variable' => $monthData['variable'],
            ];
            
            $breakdown['fixed'] += $monthData['fixed'];
            $breakdown['variable'] += $monthData['variable'];
            
            foreach ($monthData['by_category'] as $cat => $amt) {
                if (!isset($breakdown['by_category'][$cat])) {
                    $breakdown['by_category'][$cat] = 0;
                }
                $breakdown['by_category'][$cat] += $amt;
            }
        }
        
        $breakdown['total'] = $breakdown['fixed'] + $breakdown['variable'];
        arsort($breakdown['by_category']);
        
        return $breakdown;
    }
    
    /**
     * POMOCNÁ - Převod na týdenní částku
     */
    private function convertToWeekly(float $amount, string $frequency): float
    {
        switch ($frequency) {
            case 'daily':
                return $amount * 7;
            case 'weekly':
                return $amount;
            case 'monthly':
                return $amount / 4.35;
            case 'quarterly':
                return $amount / 13;
            case 'yearly':
                return $amount / 52.18;
            case 'once':
                return 0;
            default:
                return $amount;
        }
    }
}
