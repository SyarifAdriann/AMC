<?php

namespace App\Services;

use InvalidArgumentException;
use PDO;

class ReportService
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function fetchReportData(string $type, string $dateFrom, string $dateTo): array
    {
        $query = "SELECT * FROM aircraft_movements WHERE movement_date BETWEEN :from AND :to";
        $params = [
            ':from' => $dateFrom,
            ':to' => $dateTo,
        ];

        switch ($type) {
            case 'charter_log':
                $query .= " AND operator_airline LIKE :airline";
                $params[':airline'] = '%Charter%';
                break;
            case 'ron_report':
                $query .= " AND is_ron = 1";
                break;
            case 'daily_log_am':
                $query .= " AND HOUR(STR_TO_DATE(on_block_time, '%H%i')) BETWEEN 0 AND 11";
                break;
            case 'daily_log_pm':
                $query .= " AND HOUR(STR_TO_DATE(on_block_time, '%H%i')) BETWEEN 12 AND 23";
                break;
            case 'monthly_summary':
                $query = "SELECT COUNT(*) AS total, DATE(movement_date) AS date
                          FROM aircraft_movements
                          WHERE movement_date BETWEEN :from AND :to
                          GROUP BY DATE(movement_date)";
                break;
            case 'logbook_narrative':
                // Use base query without additional filters.
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
        $html = "<div class='report-output'><h3>Generated Report: " . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . "</h3>";

        if ($type === 'monthly_summary') {
            $html .= "<table border='1'><tr><th>Date</th><th>Total Movements</th></tr>";
            foreach ($data as $row) {
                $html .= "<tr><td>" . htmlspecialchars($row['date'] ?? '', ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars((string) ($row['total'] ?? ''), ENT_QUOTES, 'UTF-8') . "</td></tr>";
            }
        } else {
            $html .= "<table border='1'><tr><th>ID</th><th>Registration</th><th>Type</th><th>On Block</th><th>Off Block</th><th>Stand</th><th>From</th><th>To</th><th>Arr Flight</th><th>Dep Flight</th><th>Operator</th><th>Remarks</th><th>RON</th><th>Date</th></tr>";
            foreach ($data as $row) {
                $html .= '<tr>'
                    . '<td>' . htmlspecialchars($row['id'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['registration'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['aircraft_type'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['on_block_time'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['off_block_time'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['parking_stand'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['from_location'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['to_location'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['flight_no_arr'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['flight_no_dep'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['operator_airline'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '<td>' . ((($row['is_ron'] ?? 0) ? 'Yes' : 'No')) . '</td>'
                    . '<td>' . htmlspecialchars($row['movement_date'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>'
                    . '</tr>';
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
            fputcsv($handle, ['ID', 'Registration', 'Type', 'On Block', 'Off Block', 'Stand', 'From', 'To', 'Arr Flight', 'Dep Flight', 'Operator', 'Remarks', 'RON', 'Date']);
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['id'] ?? '',
                    $row['registration'] ?? '',
                    $row['aircraft_type'] ?? '',
                    $row['on_block_time'] ?? '',
                    $row['off_block_time'] ?? '',
                    $row['parking_stand'] ?? '',
                    $row['from_location'] ?? '',
                    $row['to_location'] ?? '',
                    $row['flight_no_arr'] ?? '',
                    $row['flight_no_dep'] ?? '',
                    $row['operator_airline'] ?? '',
                    $row['remarks'] ?? '',
                    (($row['is_ron'] ?? 0) ? 'Yes' : 'No'),
                    $row['movement_date'] ?? '',
                ]);
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
        error_log('Month type: ' . gettype($month) . ', value: ' . $month);
        error_log('Year type: ' . gettype($year) . ', value: ' . $year);
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
