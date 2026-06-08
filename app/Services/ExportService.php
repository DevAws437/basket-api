<?php

namespace App\Services;

use App\Models\MatchRecord;
use App\Models\PlayerAction;
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

    public function downloadMatch(MatchRecord $match): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->addMatchInfoSheet($spreadsheet, $match);
        $this->addTeamStatsSheet($spreadsheet, $match);
        $this->addPlayerStatsSheet($spreadsheet, $match);
        $this->addPeriodDetailsSheet($spreadsheet, $match);
        $this->addMvpSheet($spreadsheet, $match);
        $this->addPlayByPlaySheet($spreadsheet, $match);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "match_{$match->id}_{$match->team->name}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function addMatchInfoSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('معلومات المباراة');

        $row = 1;
        $bold = ['font' => ['bold' => true, 'size' => 14]];
        $label = ['font' => ['bold' => true]];
        $border = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet->setCellValue("A{$row}", 'معلومات المباراة');
        $sheet->getStyle("A{$row}")->applyFromArray($bold);
        $row += 2;

        $info = [
            ['النوع', $match->type === 'official' ? 'رسمية' : 'تدريبية'],
            ['الحالة', $match->status === 'completed' ? 'منتهية' : ($match->status === 'in_progress' ? 'جارية' : 'ملغاة')],
            ['الفريق', $match->team->name],
            ['الخصم', $match->opponent_name],
            ['النتيجة', "{$match->team_score} - {$match->opponent_score}"],
            ['تاريخ الإنشاء', $match->created_at->format('Y-m-d H:i:s')],
            ['آخر تحديث', $match->updated_at->format('Y-m-d H:i:s')],
            ['الربع الحالي', $match->current_period ?? '—'],
            ['إيقاف مؤقت', $match->is_paused ? 'نعم' : 'لا'],
        ];

        if ($match->status === 'completed') {
            $result = $match->team_score > $match->opponent_score ? 'فوز' : 'خسارة';
            $info[] = ['النتيجة النهائية', $result];
        }

        foreach ($info as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->getStyle("A{$row}")->applyFromArray($label);
            $sheet->setCellValue("B{$row}", $item[1]);
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($border);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function addTeamStatsSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('إحصائيات الفريق');

        $stats = $this->statisticsService->getTeamStats($match);

        $sheet->setCellValue('A1', "{$match->team->name} vs {$match->opponent_name}");
        $sheet->setCellValue('A2', "{$match->team_score} - {$match->opponent_score}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['الإحصائية', 'القيمة', 'المحاولات', 'النسبة'];
        $row = 4;
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}{$row}", $h);
        }
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);

        $data = [
            ['النقاط', $stats['points'], '', ''],
            ['نقاط الخصم', $stats['opponent_score'], '', ''],
            ['رميات ثنائية', $stats['two_point']['formatted'], $stats['two_point']['attempted'], $stats['two_point']['percentage'] . '%'],
            ['رميات ثلاثية', $stats['three_point']['formatted'], $stats['three_point']['attempted'], $stats['three_point']['percentage'] . '%'],
            ['رميات حرة', $stats['free_throws']['formatted'], $stats['free_throws']['attempted'], $stats['free_throws']['percentage'] . '%'],
            ['إجمالي التسديد', $stats['field_goals']['formatted'], $stats['field_goals']['attempted'], $stats['field_goals']['percentage'] . '%'],
            ['متابعات', $stats['rebounds'], '', ''],
            ['تمريرات حاسمة', $stats['assists'], '', ''],
            ['سرقات', $stats['steals'], '', ''],
            ['أخطاء', $stats['turnovers'], '', ''],
            ['أخطاء شخصية', $stats['fouls'], '', ''],
        ];

        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item[0]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->setCellValue("B{$row}", $item[1]);
            $sheet->setCellValue("C{$row}", $item[2]);
            $sheet->setCellValue("D{$row}", $item[3]);
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function addPlayerStatsSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('إحصائيات اللاعبين');

        $headers = ['#', 'اللاعب', 'المركز', 'نقاط', '2PT', '3PT', 'FT', 'FG%', 'REB', 'AST', 'STL', 'TO', 'أخطاء', 'EFF'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        $playerStats = $this->statisticsService->getAllPlayerStats($match);
        $row = 2;

        foreach ($playerStats as $stats) {
            $fgPct = $stats['field_goals']['attempted'] > 0
                ? round(($stats['field_goals']['made'] / $stats['field_goals']['attempted']) * 100, 1) . '%'
                : '—';
            $sheet->setCellValue("A{$row}", $stats['jersey_number'] ?? '');
            $sheet->setCellValue("B{$row}", $stats['player_name']);
            $sheet->setCellValue("C{$row}", $stats['position'] ?? '');
            $sheet->setCellValue("D{$row}", $stats['points']);
            $sheet->setCellValue("E{$row}", $stats['two_point']['formatted']);
            $sheet->setCellValue("F{$row}", $stats['three_point']['formatted']);
            $sheet->setCellValue("G{$row}", $stats['free_throws']['formatted']);
            $sheet->setCellValue("H{$row}", $fgPct);
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
        $sheet->getStyle('A:N')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function addPeriodDetailsSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('تفاصيل الأشواط');

        $headers = ['الربع', 'النوع', 'المدة', 'بدء', 'انتهاء', 'نقاط الفريق', 'نقاط الخصم'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $periods = $match->periods()->orderBy('period_number')->get();
        $row = 2;
        $teamTotal = 0;
        $oppTotal = 0;

        foreach ($periods as $period) {
            $actions = PlayerAction::where('match_id', $match->id)
                ->where('period_id', $period->id)
                ->where('is_undo', false)
                ->get();

            $periodTeamPts = $actions->whereIn('action_type', ['shot_2pt_made', 'shot_3pt_made', 'ft_made'])->sum('points');
            $periodOppPts = $this->getOpponentPointsForPeriod($match, $period);
            $teamTotal += $periodTeamPts;
            $oppTotal += $periodOppPts;

            $typeMap = ['quarter' => 'ربع', 'ot' => 'وقت إضافي'];
            $sheet->setCellValue("A{$row}", $period->period_number);
            $sheet->setCellValue("B{$row}", $typeMap[$period->type] ?? $period->type);
            $sheet->setCellValue("C{$row}", $period->duration ? sprintf('%d:%02d', intdiv($period->duration, 60), $period->duration % 60) : '—');
            $sheet->setCellValue("D{$row}", $period->started_at ? $period->started_at->format('H:i:s') : '—');
            $sheet->setCellValue("E{$row}", $period->ended_at ? $period->ended_at->format('H:i:s') : '—');
            $sheet->setCellValue("F{$row}", $periodTeamPts);
            $sheet->setCellValue("G{$row}", $periodOppPts);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'المجموع');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->setCellValue("F{$row}", $teamTotal);
        $sheet->setCellValue("G{$row}", $oppTotal);
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function addMvpSheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('أفضل لاعب');

        $mvp = $this->mvpCalculator->calculate($match);

        if ($mvp) {
            $sheet->mergeCells('A1:B1');
            $sheet->setCellValue('A1', '🏅 أفضل لاعب');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

            $labels = ['اللاعب', 'الفريق', 'الرقم', 'المركز', 'النقاط', 'الكفاءة'];
            $keys = ['player_name', 'team_name', 'jersey_number', 'position', 'points', 'efficiency'];
            $row = 3;
            foreach ($labels as $i => $label) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->setCellValue("B{$row}", $mvp[$keys[$i]] ?? '—');
                $row++;
            }

            $row++;
            $sheet->setCellValue("A{$row}", 'جميع الإحصائيات');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            $statLabels = ['رميات ثنائية', 'رميات ثلاثية', 'رميات حرة', 'متابعات', 'تمريرات حاسمة', 'سرقات', 'أخطاء'];
            $statKeys = ['two_point', 'three_point', 'free_throws', 'rebounds', 'assists', 'steals', 'turnovers'];
            if (isset($mvp['stats'])) {
                foreach ($statLabels as $i => $label) {
                    $val = $mvp['stats'][$statKeys[$i]] ?? null;
                    $formatted = is_array($val) ? ($val['formatted'] ?? '—') : ($val ?? '—');
                    $sheet->setCellValue("A{$row}", $label);
                    $sheet->setCellValue("B{$row}", $formatted);
                    $row++;
                }
            }

            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getStyle('A:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
    }

    private function addPlayByPlaySheet(Spreadsheet $spreadsheet, MatchRecord $match): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('سير المباراة');

        $headers = ['الوقت', '#', 'اللاعب', 'الحركة', 'نقاط', 'الربع', 'نتيجة الفريق', 'نتيجة الخصم'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $actions = $match->actions()
            ->where('is_undo', false)
            ->with('player', 'period')
            ->orderBy('id')
            ->get();

        $runningTeam = 0;
        $runningOpp = 0;
        $row = 2;

        foreach ($actions as $action) {
            $minutes = str_pad((string)intdiv($action->action_timestamp, 60), 2, '0', STR_PAD_LEFT);
            $seconds = str_pad((string)($action->action_timestamp % 60), 2, '0', STR_PAD_LEFT);

            if (in_array($action->action_type, ['shot_2pt_made', 'shot_3pt_made', 'ft_made'])) {
                $runningTeam += $action->points;
            }
            if (in_array($action->action_type, ['shot_2pt_made', 'shot_3pt_made', 'ft_made'])) {
                $runningOpp += 0;
            }

            $sheet->setCellValue("A{$row}", "{$minutes}:{$seconds}");
            $sheet->setCellValue("B{$row}", $action->player?->jersey_number ?? '');
            $sheet->setCellValue("C{$row}", $this->fullName($action->player));
            $sheet->setCellValue("D{$row}", $this->translateAction($action->action_type));
            $sheet->setCellValue("E{$row}", $action->points ?: '—');
            $sheet->setCellValue("F{$row}", $action->period?->period_number ?? '');
            $sheet->setCellValue("G{$row}", $runningTeam);
            $sheet->setCellValue("H{$row}", $runningOpp);
            $row++;
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function getOpponentPointsForPeriod(MatchRecord $match, $period): int
    {
        return 0;
    }

    private function fullName($player): string
    {
        if (!$player) return '—';
        $name = $player->first_name ?? '';
        if (!empty($player->last_name)) $name .= ' ' . $player->last_name;
        return trim($name) ?: '—';
    }

    private function translateAction(string $actionType): string
    {
        $translations = [
            'shot_2pt_made' => '2 نقطة ✓',
            'shot_2pt_missed' => '2 نقطة ✗',
            'shot_3pt_made' => '3 نقاط ✓',
            'shot_3pt_missed' => '3 نقاط ✗',
            'ft_made' => 'رمية حرة ✓',
            'ft_missed' => 'رمية حرة ✗',
            'rebound' => 'متابعة',
            'assist' => 'تمريرة حاسمة',
            'steal' => 'سرقة',
            'turnover' => 'خطأ',
            'foul' => 'خطأ شخصي',
            'substitution_out' => 'تبديل (خروج)',
            'substitution_in' => 'تبديل (دخول)',
        ];

        return $translations[$actionType] ?? $actionType;
    }
}
