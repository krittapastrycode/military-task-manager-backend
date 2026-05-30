<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevTaskSeeder extends Seeder
{
    // ─── Royal persons ────────────────────────────────────────────────────────
    private const ROYAL_PERSONS = [
        'พระบาทสมเด็จพระเจ้าอยู่หัว',
        'สมเด็จพระนางเจ้าฯ พระบรมราชินี',
        'สมเด็จพระกนิษฐาธิราชเจ้า กรมสมเด็จพระเทพรัตนราชสุดา ฯ สยามบรมราชกุมารี',
        'สมเด็จพระบรมราชชนนีพันปีหลวง',
        'ทูลกระหม่อมหญิงอุบลรัตนราชกัญญา สิริวัฒนาพรรณวดี',
        'สมเด็จเจ้าฟ้า ฯ กรมพระศรีสวางควัฒน วรขัตติยราชนารี',
        'ผู้แทนพระองค์',
    ];

    // ─── VIP persons ─────────────────────────────────────────────────────────
    private const VIP_PERSONS = [
        'ผบ.ทบ.',
        'รอง ผบ.ทบ.',
        'เสธ.ทบ.',
        'ผบ.ทสส.',
        'นายกรัฐมนตรี',
        'รมว.กระทรวงกลาโหม',
        'ประธานองคมนตรี',
        'องคมนตรี',
        'ผช.ผบ.ทบ.(๑)',
        'ผช.ผบ.ทบ.(๒)',
    ];

    // ─── Venues ───────────────────────────────────────────────────────────────
    private const VENUES = [
        'ศาลาว่าการกลาโหม',
        'กองบัญชาการกองทัพบก',
        'สนามกีฬากองทัพบก',
        'อาคารรับรองกองทัพ',
        'ท่าอากาศยานทหาร',
        'กองพลทหารราบที่ ๑',
        'สโมสรทหารบก',
        'โรงพยาบาลพระมงกุฎเกล้า',
    ];

    // ─── Map coordinates for Bangkok area ────────────────────────────────────
    private const MAP_COORDS = [
        '13.7563,100.5018',
        '13.7650,100.5380',
        '13.7460,100.5220',
        '13.7720,100.4980',
        '13.7380,100.5640',
        '13.7890,100.5120',
        '13.7510,100.4860',
        '13.7640,100.5530',
        '13.7420,100.5310',
        '13.7810,100.5260',
    ];

    // ─── Vehicle samples ─────────────────────────────────────────────────────
    private const VEHICLES = [
        'กข-1234 สีดำ Toyota Fortuner',
        'กข-5678 สีขาว Mercedes-Benz S-Class',
        'กข-9012 สีเงิน Toyota Land Cruiser',
        'กข-3456 สีดำ BMW 7 Series',
        'กข-7890 สีน้ำเงิน Ford Ranger',
        'ขก-2345 สีแดง Chevrolet Colorado',
        'ขก-6789 สีดำ Isuzu D-Max',
        'ขก-1357 สีขาว Mitsubishi Triton',
    ];

    // ─── Convoy groups ────────────────────────────────────────────────────────
    private const CONVOYS = [
        'คณะผู้แทนพระองค์',
        'ขบวนรัฐมนตรี กห.',
        'คณะนายทหารชั้นผู้ใหญ่',
        'ขบวนเสด็จ',
        'คณะทูตานุทูต',
        'ขบวนผู้บัญชาการทหารบก',
        'คณะผู้แทนต่างประเทศ',
        'ขบวนนายกรัฐมนตรี',
    ];

    // ─── Thai month abbreviations (BE year 2568 = 2026 CE) ───────────────────
    private const THAI_MONTHS = [
        1  => 'ม.ค.',
        2  => 'ก.พ.',
        3  => 'มี.ค.',
        4  => 'เม.ย.',
        5  => 'พ.ค.',
        6  => 'มิ.ย.',
        7  => 'ก.ค.',
        8  => 'ส.ค.',
        9  => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.',
    ];

    // ─── Status distribution (indexes 0-19 for 20 tasks) ────────────────────
    // 10 success, 3 progress, 3 pending, 2 cancel, 1 on-hold, 1 reject
    private const STATUS_POOL = [
        'success', 'success', 'success', 'success', 'success',
        'success', 'success', 'success', 'success', 'success',
        'progress', 'progress', 'progress',
        'pending', 'pending', 'pending',
        'cancel', 'cancel',
        'on-hold',
        'reject',
    ];

    // ─── Task type pool (4 of each type per month, 20 total) ─────────────────
    private const TYPE_POOL = [
        'royal_security', 'royal_security', 'royal_security', 'royal_security',
        'vip_protection', 'vip_protection', 'vip_protection', 'vip_protection',
        'convoy',         'convoy',         'convoy',         'convoy',
        'traffic',        'traffic',        'traffic',        'traffic',
        'venue_security', 'venue_security', 'venue_security', 'venue_security',
    ];

    // ─── Priority pool ────────────────────────────────────────────────────────
    private const PRIORITY_POOL = [
        'urgent', 'urgent', 'urgent',
        'high',   'high',   'high',   'high',   'high',
        'medium', 'medium', 'medium', 'medium', 'medium', 'medium',
        'low',    'low',    'low',    'low',    'low',    'low',
    ];

    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // ── Fetch users and commanders ─────────────────────────────────────────
        $users = User::whereRaw("role @> '[\"user\"]'::jsonb")->get();
        $commanders = User::whereRaw("role @> '[\"commander\"]'::jsonb")->get();

        if ($users->isEmpty()) {
            $users = User::all();
        }
        if ($commanders->isEmpty()) {
            $commanders = $users;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        // ── Clear existing dev tasks ──────────────────────────────────────────
        $this->command->info('Clearing existing tasks...');
        Task::query()->delete();

        $totalCreated = 0;

        // ── Generate 20 tasks × 12 months ────────────────────────────────────
        for ($month = 1; $month <= 12; $month++) {
            $daysInMonth = Carbon::create(2026, $month, 1)->daysInMonth;

            // Shuffle pools so distribution is random per month
            $statusPool = self::STATUS_POOL;
            $typePool   = self::TYPE_POOL;
            $priorityPool = self::PRIORITY_POOL;
            shuffle($statusPool);
            shuffle($typePool);
            shuffle($priorityPool);

            for ($i = 0; $i < 20; $i++) {
                $status       = $statusPool[$i];
                $taskTypeKey  = $typePool[$i];
                $priority     = $priorityPool[$i];

                $deadlineDay  = rand(10, min(28, $daysInMonth));
                $deadlineAt   = Carbon::create(2026, $month, $deadlineDay, 8, 0, 0, 'Asia/Bangkok');

                $completedAt  = null;
                $completed    = false;
                $endAt        = null;

                if ($status === 'success') {
                    $completed   = true;
                    $completedAt = Carbon::create(2026, $month, rand(1, min(28, $daysInMonth)), rand(14, 18), 0, 0, 'Asia/Bangkok');
                    $endAt       = $completedAt->copy()->addHours(rand(1, 3));
                }

                // ── Pick random user / creator ─────────────────────────────────
                $user       = $users->random();
                $creator    = $commanders->random();

                // ── Build content per type ─────────────────────────────────────
                [$content, $title] = $this->buildContent($taskTypeKey, $month);

                // ── created_at = start of that month ──────────────────────────
                $createdAt = Carbon::create(2026, $month, 1, 0, 0, 0, 'Asia/Bangkok');

                try {
                    $task = new Task();
                    $task->title         = $title;
                    $task->description   = $this->buildDescription($taskTypeKey, $content);
                    $task->task_type_key = $taskTypeKey;
                    $task->priority      = $priority;
                    $task->status        = $status;
                    $task->deadline_at   = $deadlineAt;
                    $task->end_at        = $endAt;
                    $task->completed_at  = $completedAt;
                    $task->completed     = $completed;
                    $task->content       = $content;
                    $task->meta          = null;
                    $task->user_id       = $user->id;
                    $task->created_by    = $creator->id;
                    $task->created_at    = $createdAt;
                    $task->updated_at    = $createdAt;
                    $task->save();

                    $totalCreated++;
                } catch (\Throwable $e) {
                    $this->command->warn("Skipped task (month {$month}, index {$i}): " . $e->getMessage());
                }
            }

            $this->command->info("Month {$month}: seeded 20 tasks.");
        }

        $this->command->info("Done. Total tasks created: {$totalCreated}");
    }

    // ─── Content builders ─────────────────────────────────────────────────────

    private function randomCoord(): string
    {
        return self::MAP_COORDS[array_rand(self::MAP_COORDS)];
    }

    private function mapLink(): string
    {
        return 'https://maps.google.com/?q=' . $this->randomCoord();
    }

    private function buildContent(string $typeKey, int $month): array
    {
        $thaiMonth = self::THAI_MONTHS[$month];

        switch ($typeKey) {
            case 'royal_security': {
                $royal = self::ROYAL_PERSONS[array_rand(self::ROYAL_PERSONS)];
                $venue = self::VENUES[array_rand(self::VENUES)];
                $content = [
                    'royal_name' => $royal,
                    'map_link'   => $this->mapLink(),
                ];
                $title = "ถวายความปลอดภัย {$royal} ณ {$venue}";
                return [$content, $title];
            }

            case 'vip_protection': {
                $vip     = self::VIP_PERSONS[array_rand(self::VIP_PERSONS)];
                $vehicle = self::VEHICLES[array_rand(self::VEHICLES)];
                $content = [
                    'vip_name'     => $vip,
                    'vehicle_info' => $vehicle,
                    'map_link'     => $this->mapLink(),
                ];
                $title = "อารักขา {$vip}";
                return [$content, $title];
            }

            case 'convoy': {
                $isVehicle = (rand(0, 1) === 1);
                $convoyItem = $isVehicle
                    ? self::VEHICLES[array_rand(self::VEHICLES)]
                    : self::CONVOYS[array_rand(self::CONVOYS)];
                $content = [
                    'vehicle_or_group' => $convoyItem,
                    'map_link'         => $this->mapLink(),
                ];
                $title = "นำขบวน {$convoyItem}";
                return [$content, $title];
            }

            case 'traffic': {
                $venue   = self::VENUES[array_rand(self::VENUES)];
                $vehicle = self::VEHICLES[array_rand(self::VEHICLES)];
                $parking = $this->randomParkingAllowed();
                $direction = $this->randomTrafficDirection($venue);
                $content = [
                    'vehicle_info'      => $vehicle,
                    'venue'             => $venue,
                    'parking_allowed'   => $parking,
                    'traffic_direction' => $direction,
                ];
                $title = "จัดการจราจร {$venue}";
                return [$content, $title];
            }

            case 'venue_security': {
                $venue     = self::VENUES[array_rand(self::VENUES)];
                $startDay  = rand(1, 15);
                $endDay    = $startDay + rand(1, 3);
                $dateRange = "{$startDay}-{$endDay} {$thaiMonth} 2568";
                $startHour = rand(6, 9);
                $startTime = sprintf('%02d:00 น.', $startHour);
                $content = [
                    'date_range' => $dateRange,
                    'start_time' => $startTime,
                ];
                $title = "อารักขาสถานที่ {$venue}";
                return [$content, $title];
            }

            default: {
                return [[], 'ภารกิจพิเศษ'];
            }
        }
    }

    private function buildDescription(string $typeKey, array $content): string
    {
        switch ($typeKey) {
            case 'royal_security':
                return "ภารกิจถวายความปลอดภัยแด่{$content['royal_name']} ปฏิบัติตามคำสั่งและมาตรการรักษาความปลอดภัยสูงสุด";
            case 'vip_protection':
                return "ภารกิจอารักขาบุคคลสำคัญ {$content['vip_name']} ด้วยยานพาหนะ {$content['vehicle_info']} ตามแผนการรักษาความปลอดภัย";
            case 'convoy':
                return "ภารกิจนำและตามขบวน {$content['vehicle_or_group']} ตามเส้นทางที่กำหนด";
            case 'traffic':
                return "ภารกิจจัดการจราจรและควบคุมพื้นที่ ณ {$content['venue']} ทิศทาง: {$content['traffic_direction']}";
            case 'venue_security':
                return "ภารกิจอารักขาและรักษาความปลอดภัยสถานที่ ช่วงวันที่ {$content['date_range']} เริ่มเวลา {$content['start_time']}";
            default:
                return 'ภารกิจพิเศษตามคำสั่ง';
        }
    }

    private function randomParkingAllowed(): string
    {
        $options = [
            'นายทหารชั้นผู้ใหญ่เท่านั้น',
            'ยานพาหนะราชการ',
            'บัตรอนุญาตเท่านั้น',
            'ทุกยานพาหนะที่ได้รับอนุญาต',
            'เฉพาะขบวนเสด็จ',
            'รถรับรองและรถโดยสาร',
            'ยานพาหนะที่มีสติ๊กเกอร์อนุญาต',
        ];
        return $options[array_rand($options)];
    }

    private function randomTrafficDirection(string $venue): string
    {
        $directions = [
            "เข้า{$venue}ทางประตูหลัก ออกทางประตูด้านข้าง",
            "จัดเส้นทางเดียวผ่านหน้า{$venue} ห้ามจอดริมถนน",
            "เบี่ยงการจราจรออกจาก{$venue} เปิดเลนฉุกเฉิน",
            "ควบคุมทางแยกหน้า{$venue} สัญญาณมือ",
            "ปิดถนนด้านหน้า{$venue} เบี่ยงรถทางเลี่ยง",
            "เปิด-ปิดเลนตามขบวนเสด็จผ่าน{$venue}",
            "รักษาระยะห่างขบวนรถหน้า{$venue} ห้ามแซง",
        ];
        return $directions[array_rand($directions)];
    }
}
