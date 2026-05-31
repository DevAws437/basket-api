<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$total = 0; $passed = 0; $failed = 0;
$results = [];

function api($method, $uri, $data = null) {
    global $kernel;
    $request = Illuminate\Http\Request::create('/api' . $uri, $method, [], [], [],
        ['CONTENT_TYPE' => 'application/json', 'Accept' => 'application/json'],
        $data ? json_encode($data) : null
    );
    $response = $kernel->handle($request);
    return [$response->getStatusCode(), json_decode($response->getContent(), true)];
}

function test($label, $method, $uri, $data = null, $expectedStatus = 200) {
    global $total, $passed, $failed, $results;
    $total++;
    [$status, $body] = api($method, $uri, $data);
    $pass = $status === $expectedStatus;
    if ($pass) $passed++; else $failed++;
    $results[] = [
        'label' => $label, 'method' => $method, 'uri' => $uri,
        'expected' => $expectedStatus, 'actual' => $status,
        'pass' => $pass, 'body' => $body,
    ];
    echo ($pass ? "  ✅" : "  ❌") . " [{$method}] {$label} -> {$status}" . ($pass ? "" : " (expected {$expectedStatus})" . ($body['error'] ?? '') . ($body['message'] ?? '')) . "\n";
    return [$status, $body];
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  🔍 اختبار شامل للنظام (Full System Test)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  1. TEAMS API\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
[$s, $teams] = test('قائمة الفرق', 'GET', '/teams');
$team1Id = $teams['data'][0]['id'] ?? 1;
test('تفاصيل الفريق', 'GET', "/teams/{$team1Id}");
test('فريق غير موجود', 'GET', '/teams/999', null, 404);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  2. MATCH CREATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
[$s, $m1] = test('إنشاء مباراة رسمية', 'POST', '/matches', [
    'type' => 'official', 'team_id' => 1, 'opponent_name' => 'Al-Ittihad', 'lineup' => [1,2,3,4,5]
]);
$m1Id = $m1['data']['id'];
$m1PeriodId = $m1['data']['periods'][0]['id'];
echo "   Match ID: {$m1Id}, Period ID: {$m1PeriodId}\n";

[$s, $m2] = test('إنشاء مباراة تدريبية', 'POST', '/matches', [
    'type' => 'training', 'team_id' => 1, 'opponent_name' => 'Al-Karamah', 'lineup' => [6,7,8,9,11]
]);
$m2Id = $m2['data']['id'];

test('بدون lineup - خطأ', 'POST', '/matches', ['type' => 'official', 'team_id' => 1, 'opponent_name' => 'x'], 422);
test('نوع غير صحيح', 'POST', '/matches', ['type' => 'invalid', 'team_id' => 1, 'opponent_name' => 'x', 'lineup' => [1,2,3,4,5]], 422);
test('فريق غير موجود', 'POST', '/matches', ['type' => 'official', 'team_id' => 999, 'opponent_name' => 'x', 'lineup' => [1,2,3,4,5]], 422);
test('لاعب غير موجود', 'POST', '/matches', ['type' => 'official', 'team_id' => 1, 'opponent_name' => 'x', 'lineup' => [1,2,3,4,999]], 422);
test('6 لاعبين', 'POST', '/matches', ['type' => 'official', 'team_id' => 1, 'opponent_name' => 'x', 'lineup' => [1,2,3,4,5,6]], 422);

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  3. MATCH MANAGEMENT\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
test('بدء المباراة', 'PATCH', "/matches/{$m1Id}/start");
[$s, $pause] = test('إيقاف مؤقت', 'PATCH', "/matches/{$m1Id}/pause");
echo "   Is paused: " . ($pause['is_paused'] ? 'true' : 'false') . "\n";
[$s, $resume] = test('استئناف', 'PATCH', "/matches/{$m1Id}/pause");
echo "   Is paused: " . ($resume['is_paused'] ? 'true' : 'false') . "\n";
test('تحديث نقاط الخصم', 'PATCH', "/matches/{$m1Id}/opponent-score", ['score' => 10]);
test('نقاط خصم سالبة - خطأ', 'PATCH', "/matches/{$m1Id}/opponent-score", ['score' => -5], 422);
test('تفاصيل المباراة', 'GET', "/matches/{$m1Id}");

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  4. GAME ACTIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$p1 = 1; $p2 = 2; $p3 = 3; $p4 = 4; $p5 = 5; $p6 = 6; $p8 = 8;

test('تسجيل 2pt ناجحة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'shot_2pt_made', 'period_id' => $m1PeriodId]);
test('تسجيل 3pt ناجحة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p2, 'action_type' => 'shot_3pt_made', 'period_id' => $m1PeriodId]);
test('تسجيل 2pt فاشلة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p3, 'action_type' => 'shot_2pt_missed', 'period_id' => $m1PeriodId]);
test('تسجيل رمية حرة ناجحة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'ft_made', 'period_id' => $m1PeriodId]);
test('تسجيل رمية حرة فاشلة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p4, 'action_type' => 'ft_missed', 'period_id' => $m1PeriodId]);
test('تسجيل متابعة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p2, 'action_type' => 'rebound', 'period_id' => $m1PeriodId]);
test('تسجيل تمريرة حاسمة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'assist', 'period_id' => $m1PeriodId]);
test('تسجيل سرقة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p3, 'action_type' => 'steal', 'period_id' => $m1PeriodId]);
test('تسجيل ضياع كرة', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p4, 'action_type' => 'turnover', 'period_id' => $m1PeriodId]);
test('تسجيل خطأ', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'foul', 'period_id' => $m1PeriodId]);
test('لاعب غير موجود في التشكيلة - خطأ', 'POST', "/matches/{$m1Id}/actions", ['player_id' => 99, 'action_type' => 'shot_2pt_made'], 422);
test('نوع حدث غير صحيح - خطأ', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'invalid_type'], 422);

[$s, $ts] = api('GET', "/matches/{$m1Id}/team-stats");
echo "   النقاط بعد الأحداث: {$ts['points']}\n";
echo "   FG: {$ts['field_goals']['formatted']}\n";
echo "   متابعات: {$ts['rebounds']}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  5. SUBSTITUTIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
test('تبديل صحيح', 'POST', "/matches/{$m1Id}/substitute", ['player_out_id' => $p3, 'player_in_id' => $p6]);
test('تبديل نفس اللاعب - خطأ', 'POST', "/matches/{$m1Id}/substitute", ['player_out_id' => $p3, 'player_in_id' => $p6], 422);
test('تبديل لاعب غير موجود بالملعب - خطأ', 'POST', "/matches/{$m1Id}/substitute", ['player_out_id' => $p6, 'player_in_id' => $p3]);

test('إضافة 4 أخطاء اضافية للاعب 1', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'foul', 'period_id' => $m1PeriodId]);
test('إضافة 4 أخطاء اضافية للاعب 1', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'foul', 'period_id' => $m1PeriodId]);
test('إضافة 4 أخطاء اضافية للاعب 1', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'foul', 'period_id' => $m1PeriodId]);
[$s, $foul5] = test('الخطأ الخامس - طرد', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p1, 'action_type' => 'foul', 'period_id' => $m1PeriodId]);
echo "   Force substitution: " . ($foul5['force_substitution'] ?? 'no') . "\n";
echo "   Fouled player: {$foul5['fouled_player_name']}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  6. UNDO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
[$s, $teamScoreBefore] = api('GET', "/matches/{$m1Id}/team-stats");
echo "   النقاط قبل التراجع: {$teamScoreBefore['points']}\n";

test('تراجع عن آخر حدث', 'POST', "/matches/{$m1Id}/undo");
[$s, $teamScoreAfter] = api('GET', "/matches/{$m1Id}/team-stats");
echo "   النقاط بعد التراجع: {$teamScoreAfter['points']}\n";

test('تراجع ثاني', 'POST', "/matches/{$m1Id}/undo");
test('تراجع ثالث', 'POST', "/matches/{$m1Id}/undo");
test('تراجع رابع', 'POST', "/matches/{$m1Id}/undo");
test('تراجع خامس', 'POST', "/matches/{$m1Id}/undo");

[$s, $teamStatsFinal] = api('GET', "/matches/{$m1Id}/team-stats");
echo "   النقاط النهائية بعد 5 تراجعات: {$teamStatsFinal['points']}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  7. STATISTICS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
test('إحصائيات الفريق', 'GET', "/matches/{$m1Id}/team-stats");
test('إحصائيات اللاعبين', 'GET', "/matches/{$m1Id}/player-stats");
test('إحصائيات لاعب محدد', 'GET', "/matches/{$m1Id}/players/{$p2}");
test('أفضل لاعب (MVP)', 'GET', "/matches/{$m1Id}/mvp");
test('تدفق المباراة', 'GET', "/matches/{$m1Id}/flow");

[$s, $mvpData] = api('GET', "/matches/{$m1Id}/mvp");
echo "   🏆 MVP: {$mvpData['player_name']} - {$mvpData['points']}pts - EFF: {$mvpData['efficiency']}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  8. END MATCH & RECORDS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
test('إنهاء المباراة', 'POST', "/matches/{$m1Id}/end");
test('السجلات', 'GET', '/records');
test('تفاصيل المباراة المسجلة', 'GET', "/records/{$m1Id}");
test('تصدير Excel', 'GET', "/records/{$m1Id}/export");

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  9. EDGE CASES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
test('حدث بعد انتهاء المباراة - خطأ', 'POST', "/matches/{$m1Id}/actions", ['player_id' => $p2, 'action_type' => 'shot_2pt_made'], 422);
test('تراجع بعد انتهاء المباراة', 'POST', "/matches/{$m1Id}/undo");
test('مباراة غير مكتملة - تفاصيل', 'GET', "/matches/{$m2Id}"); 
test('مباراة غير مكتملة - سجل', 'GET', "/records/{$m2Id}", null, 400);

[$s, $m3] = test('إنشاء مباراة للاختبار', 'POST', '/matches', [
    'type' => 'official', 'team_id' => 1, 'opponent_name' => 'Test OT', 'lineup' => [1,2,3,4,5]
]);
$m3Id = $m3['data']['id'];
test('بدء المباراة', 'PATCH', "/matches/{$m3Id}/start");

for ($i = 0; $i < 4; $i++) {
    test("إنهاء الربع " . ($i + 1), 'POST', "/matches/{$m3Id}/end-period");
}

if ($s = 200) {
    echo "   ✅ 4 أرباع انتهت\n";
    [$s, $otResult] = api('POST', "/matches/{$m3Id}/end-period");
    echo "   OT required: " . json_encode($otResult['overtime_required'] ?? false) . "\n";
    if (!empty($otResult['overtime_required'])) {
        test('إضافة شوط إضافي', 'POST', "/matches/{$m3Id}/overtime");
        test('تسجيل 3 نقاط في OT', 'POST', "/matches/{$m3Id}/actions", ['player_id' => $p1, 'action_type' => 'shot_3pt_made']);
    }
}

test('إنهاء مباراة OT', 'POST', "/matches/{$m3Id}/end");

[$s, $recordsFiltered] = test('فلترة حسب التاريخ', 'GET', '/records?date=' . date('Y-m-d'));
test('فلترة حسب الفريق', 'GET', '/records?team=Al-Wahda');
test('فلترة حسب النقاط', 'GET', '/records?score_min=5');

echo "\n\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  📊 SUMMARY / الملخص\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  المجموع: {$total}\n";
echo "  ✅ ناجح: {$passed}\n";
echo "  ❌ فاشل: {$failed}\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($failed > 0) {
    echo "  تفاصيل الإخفاقات:\n";
    foreach ($results as $r) {
        if (!$r['pass']) {
            $err = $r['body']['error'] ?? $r['body']['message'] ?? json_encode($r['body']);
            echo "  ❌ [{$r['method']}] {$r['uri']} -> {$r['actual']} (expected {$r['expected']}) - {$err}\n";
        }
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
