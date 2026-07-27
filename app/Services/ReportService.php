<?php

namespace App\Services;

use InvalidArgumentException;
use PDO;

class ReportService
{
    /**
     * Movement-detail report columns, in display order: db field => header.
     * Shared by the HTML and CSV renderers so the two can never drift apart.
     */
    private const MOVEMENT_COLUMNS = [
        'id' => 'ID',
        'registration' => 'Registration',
        'aircraft_type' => 'Type',
        'on_block_time' => 'On Block',
        'off_block_time' => 'Off Block',
        'parking_stand' => 'Stand',
        'from_location' => 'From',
        'to_location' => 'To',
        'flight_no_arr' => 'Arr Flight',
        'flight_no_dep' => 'Dep Flight',
        'operator_airline' => 'Operator',
        'remarks' => 'Remarks',
        'is_ron' => 'RON',
        'movement_date' => 'Date',
    ];

    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * is_ron renders as Yes/No; every other column is a plain string.
     */
    private static function cell(array $row, string $field): string
    {
        if ($field === 'is_ron') {
            return ($row['is_ron'] ?? 0) ? 'Yes' : 'No';
        }

        return (string) ($row[$field] ?? '');
    }

    public function fetchReportData(string $type, string $dateFrom, string $dateTo): array
    {
        // Movement-detail reports share a base query; category lives on
        // aircraft_details, so the row reports LEFT JOIN it in.
        $base = "SELECT m.* FROM aircraft_movements m
                 LEFT JOIN aircraft_details d ON m.registration = d.registration
                 WHERE m.movement_date BETWEEN :from AND :to";
        $order = " ORDER BY m.movement_date, m.on_block_time";
        $params = [':from' => $dateFrom, ':to' => $dateTo];

        switch ($type) {
            case 'charter_log':
                // Charter is a category on aircraft_details, not a word in the
                // airline name. NULL category counts as charter (matches dashboards).
                $query = $base . " AND LOWER(COALESCE(d.category, 'charter')) = 'charter'" . $order;
                break;
            case 'ron_report':
                $query = $base . " AND m.is_ron = 1" . $order;
                break;
            case 'monthly_summary':
                $query = "SELECT COUNT(*) AS total, DATE(movement_date) AS date
                          FROM aircraft_movements
                          WHERE movement_date BETWEEN :from AND :to
                          GROUP BY DATE(movement_date)
                          ORDER BY date";
                break;
            case 'logbook_narrative':
                $query = $base . $order;
                break;
            default:
                throw new InvalidArgumentException("Unsupported report type: {$type}");
        }

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buildHtml(string $type, array $data): string
    {
        $labels = [
            'charter_log' => 'Charter / VVIP Flight Log',
            'ron_report' => 'Daily RON Report',
            'monthly_summary' => 'Monthly Movement Summary',
            'logbook_narrative' => 'Logbook AMC Narrative',
        ];
        $title = $labels[$type] ?? $type;

        $html = "<div class='report-output'><h3 style='font-weight:700;margin-bottom:8px;'>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . " <span style='font-weight:400;color:#6b7280;'>(" . count($data) . " rows)</span></h3>";

        if (empty($data)) {
            return $html . "<p style='color:#6b7280;padding:8px 0;'>No records found for the selected report type and date range.</p></div>";
        }

        if ($type === 'monthly_summary') {
            $html .= "<table border='1'><tr><th>Date</th><th>Total Movements</th></tr>";
            foreach ($data as $row) {
                $html .= "<tr><td>" . htmlspecialchars($row['date'] ?? '', ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars((string) ($row['total'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td></tr>";
            }
        } else {
            $html .= "<table border='1'><tr>";
            foreach (self::MOVEMENT_COLUMNS as $header) {
                $html .= '<th>' . $header . '</th>';
            }
            $html .= '</tr>';

            foreach ($data as $row) {
                $html .= '<tr>';
                foreach (array_keys(self::MOVEMENT_COLUMNS) as $field) {
                    $html .= '<td>' . htmlspecialchars(self::cell($row, $field), ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '</tr>';
            }
        }

        $html .= '</table></div>';

        return $html;
    }

    public function buildCsv(string $type, array $data): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($type === 'monthly_summary') {
            fputcsv($handle, ['Date', 'Total Movements']);
            foreach ($data as $row) {
                fputcsv($handle, [$row['date'] ?? '', $row['total'] ?? 0]);
            }
        } else {
            fputcsv($handle, array_values(self::MOVEMENT_COLUMNS));
            foreach ($data as $row) {
                $line = [];
                foreach (array_keys(self::MOVEMENT_COLUMNS) as $field) {
                    $line[] = self::cell($row, $field);
                }
                fputcsv($handle, $line);
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    public function fetchMonthlyCharterData(string $month, string $year): array
    {
        $startDate = sprintf('%s-%s-01', $year, str_pad($month, 2, '0', STR_PAD_LEFT));
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = "
            SELECT m.*, d.aircraft_type, d.operator_airline
            FROM aircraft_movements AS m
            JOIN aircraft_details AS d ON m.registration = d.registration
            WHERE d.category = 'charter'
              AND m.movement_date BETWEEN :start AND :end
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':start' => $startDate,
            ':end' => $endDate,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buildMonthlyCharterHtml(array $data, string $month, string $year): string
    {
        $timestamp = mktime(0, 0, 0, (int) $month, 1, (int) $year);
        $title = date('F Y', $timestamp);

        $html = "<h3>Monthly Charter Report: " . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h3>";
        $html .= "<table class='master-table'><tr><th>Date</th><th>Registration</th><th>Type</th><th>Origin</th><th>Destination</th><th>Operator</th><th>On Block</th><th>Off Block</th></tr>";

        foreach ($data as $row) {
            $html .= '<tr>'
                . '<td>' . htmlspecialchars($row['movement_date'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['registration'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['aircraft_type'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['from_location'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['to_location'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['operator_airline'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['on_block_time'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars($row['off_block_time'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }

        $html .= '</table>';

        return $html;
    }
}
