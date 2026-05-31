<?php

namespace App\Services;

use App\Models\MatchRecord;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    public function __construct(
        private StatisticsService $statisticsService,
        private MVPCalculator $mvpCalculator,
    ) {}

    public function exportMatch(MatchRecord $match): string
    {
        $spreadsheet = new Spreadsheet();

        $this->addTeamStatsSheet($spreadsheet, $match);
        $this->addPlayerStatsSheet($spreadsheet, $match);
        $this->addMvpSheet($spreadsheet, $match);
        $this->addPlayByPlaySheet($spreadsheet, $match);

        $writer = new Xlsx($spreadsheet);
        $filename = storage_path("app/exports/match_{$match->id}.xlsx");

        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer->save($filename);

        return $filename;
    }

    public function downloadMatch(MatchRecord $match): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->addTeamStatsSheet($spreadsheet, $match);
        $this->addPlayerStatsSheet($spreadsheet, $match);
        $this->addMvpSheet($spreadsheet, $match);
        $this->addPlayByPlaySheet($spreadsheet, $match);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "match_{$match->id}_{$match->team->name}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function addTeamStatsSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Team Statistics');

        $sheet->setCellValue('A1', "Match: {$match->team->name} vs {$match->opponent_name}");
        $sheet->setCellValue('A2', "Date: {$match->created_at->format('Y-m-d H:i')}");
        $sheet->setCellValue('A3', "Final Score: {$match->team_score} - {$match->opponent_score}");

        $stats = $this->statisticsService->getTeamStats($match);

        $headers = ['Statistic', 'Value'];
        $sheet->setCellValue('A5', $headers[0]);
        $sheet->setCellValue('B5', $headers[1]);

        $row = 6;
        $data = [
            ['Total Points', $stats['points']],
            ['Field Goals', $stats['field_goals']['formatted']],
            ['Two-Point Shooting', $stats['two_point']['formatted']],
            ['Three-Point Shooting', $stats['three_point']['formatted']],
            ['Free Throws', $stats['free_throws']['formatted']],
            ['Assists', $stats['assists']],
            ['Rebounds', $stats['rebounds']],
            ['Steals', $stats['steals']],
            ['Turnovers', $stats['turnovers']],
            ['Fouls', $stats['fouls']],
        ];

        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->setCellValue("B{$row}", $item[1]);
            $row++;
        }

        $sheet->getStyle('A5:B5')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    private function addPlayerStatsSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Player Statistics');

        $headers = ['#', 'Player', 'POS', 'PTS', 'FG', '2PT', '3PT', 'FT', 'REB', 'AST', 'STL', 'TO', 'FOULS', 'EFF'];

        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $header);
        }

        $playerStats = $this->statisticsService->getAllPlayerStats($match);
        $row = 2;

        foreach ($playerStats as $stats) {
            $sheet->setCellValue("A{$row}", $stats['jersey_number'] ?? '');
            $sheet->setCellValue("B{$row}", $stats['player_name']);
            $sheet->setCellValue("C{$row}", $stats['position'] ?? '');
            $sheet->setCellValue("D{$row}", $stats['points']);
            $sheet->setCellValue("E{$row}", $stats['field_goals']['formatted']);
            $sheet->setCellValue("F{$row}", $stats['two_point']['formatted']);
            $sheet->setCellValue("G{$row}", $stats['three_point']['formatted']);
            $sheet->setCellValue("H{$row}", $stats['free_throws']['formatted']);
            $sheet->setCellValue("I{$row}", $stats['rebounds']);
            $sheet->setCellValue("J{$row}", $stats['assists']);
            $sheet->setCellValue("K{$row}", $stats['steals']);
            $sheet->setCellValue("L{$row}", $stats['turnovers']);
            $sheet->setCellValue("M{$row}", $stats['fouls']);
            $sheet->setCellValue("N{$row}", $stats['efficiency']);
            $row++;
        }

        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addMvpSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('MVP');

        $mvp = $this->mvpCalculator->calculate($match);

        if ($mvp) {
            $sheet->setCellValue('A1', 'MVP');
            $sheet->setCellValue('A2', 'Player');
            $sheet->setCellValue('B2', $mvp['player_name']);
            $sheet->setCellValue('A3', 'Team');
            $sheet->setCellValue('B3', $mvp['team_name']);
            $sheet->setCellValue('A4', 'Points');
            $sheet->setCellValue('B4', $mvp['points']);
            $sheet->setCellValue('A5', 'Efficiency');
            $sheet->setCellValue('B5', $mvp['efficiency']);

            $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
        }
    }

    private function addPlayByPlaySheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Play-by-Play');

        $headers = ['Time', 'Player', 'Action', 'Points'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $header);
        }

        $actions = $match->actions()
            ->where('is_undo', false)
            ->whereNotIn('action_type', ['substitution_out', 'substitution_in'])
            ->with('player')
            ->orderBy('id')
            ->get();

        $row = 2;
        foreach ($actions as $action) {
            $minutes = str_pad((string)intdiv($action->action_timestamp, 60), 2, '0', STR_PAD_LEFT);
            $seconds = str_pad((string)($action->action_timestamp % 60), 2, '0', STR_PAD_LEFT);
            $sheet->setCellValue("A{$row}", "{$minutes}:{$seconds}");
            $sheet->setCellValue("B{$row}", $action->player?->last_name ?? 'Unknown');
            $sheet->setCellValue("C{$row}", $this->translateAction($action->action_type));
            $sheet->setCellValue("D{$row}", $action->points);
            $row++;
        }

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function translateAction(string $actionType): string
    {
        $translations = [
            'shot_2pt_made' => '2PT Made',
            'shot_2pt_missed' => '2PT Missed',
            'shot_3pt_made' => '3PT Made',
            'shot_3pt_missed' => '3PT Missed',
            'ft_made' => 'FT Made',
            'ft_missed' => 'FT Missed',
            'rebound' => 'Rebound',
            'assist' => 'Assist',
            'steal' => 'Steal',
            'turnover' => 'Turnover',
            'foul' => 'Foul',
        ];

        return $translations[$actionType] ?? $actionType;
    }
}
