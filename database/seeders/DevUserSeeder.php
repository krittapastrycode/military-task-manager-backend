<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('user1234');

        $ranks = ['ร.ต.', 'ร.ท.', 'ร.อ.', 'พ.ต.', 'พ.ท.', 'ส.อ.', 'จ.ส.อ.'];
        $units = ['กองพันที่ 1', 'กองพันที่ 2', 'กองร้อยที่ 1', 'กองร้อยที่ 2', 'หน่วยบัญชาการ'];
        $positions = ['ผู้บังคับหมู่', 'ผู้ช่วยผู้บังคับหมวด', 'เสมียน', 'พลขับ', 'นายทหารฝ่ายส่งกำลัง'];

        // 50 unique Thai military names (first + last)
        $thaiNames = [
            'สมชาย มะลิวัลย์',
            'วิชัย ทองดี',
            'อนุชา สมบูรณ์',
            'ธนากร แสงทอง',
            'ณัฐพล รุ่งเรือง',
            'ประสิทธิ์ ดำรงค์',
            'สุรศักดิ์ พงษ์ไพร',
            'ชาญชัย บุญมี',
            'พิทักษ์ ศรีวิชัย',
            'กิตติพงษ์ นามสกุล',
            'ศักดิ์ดา โพธิ์ทอง',
            'สุเทพ วงศ์สมบัติ',
            'มนัส เพชรรัตน์',
            'บุญชัย สายสุวรรณ',
            'วรวุฒิ ใจดี',
            'อภิชาติ ตันติกุล',
            'ทศพร แก้วมณี',
            'นิพนธ์ หาญกล้า',
            'ปรีชา สุขสวัสดิ์',
            'รักชาติ พงษ์ประภา',
            'สมศักดิ์ ชัยมงคล',
            'ดนัย ราชบุรี',
            'เอกชัย บุญสมบัติ',
            'ไพรัช ทวีสุข',
            'กฤษณะ พรหมสุวรรณ',
            'วิทยา สุขประเสริฐ',
            'ณัฐวุฒิ อ่อนน้อม',
            'สราวุธ ดีมาก',
            'ชนินทร์ พลายงาม',
            'อุดม ศรีสมบูรณ์',
            'ธีรพงษ์ มงคลชัย',
            'ยุทธนา รักสงบ',
            'สุรพล แป้นเพชร',
            'วันชัย ทวีผล',
            'จตุรงค์ สุขใจ',
            'กิตติ พลังรักษ์',
            'นฤนาท บุญศิริ',
            'วีระ ชวนชม',
            'พงษ์ศักดิ์ ท้าวบุญมี',
            'อนันต์ ลือชา',
            'สุพจน์ กิจบำรุง',
            'ชัยณรงค์ บำเพ็ญบุญ',
            'ไพโรจน์ หวังดี',
            'เกรียงศักดิ์ ศรีธรรม',
            'ภานุพงศ์ เพิ่มผล',
            'สุทัศน์ ประเสริฐผล',
            'อิทธิพล รักษาราษฎร์',
            'ณัฐพงษ์ แสงสว่าง',
            'สมพงษ์ บุญเลิศ',
            'ปิยะ สายรัตน์',
        ];

        // 5 unique Thai military names for commanders
        $commanderNames = [
            'วิโรจน์ ศรีสุวรรณ',
            'ประยุทธ์ แสนดี',
            'ชัยวัฒน์ พรหมมา',
            'สุชาติ ยิ่งยศ',
            'ธงชัย มีชัย',
        ];

        // Seed 50 regular users
        for ($i = 1; $i <= 50; $i++) {
            $name = $thaiNames[$i - 1];
            $rank = $ranks[($i - 1) % count($ranks)];
            $unit = $units[($i - 1) % count($units)];
            $position = $positions[($i - 1) % count($positions)];

            User::updateOrCreate(
                ['email' => "user{$i}@gmail.com"],
                [
                    'name'           => $name,
                    'password'       => $password,
                    'rank'           => $rank,
                    'unit'           => $unit,
                    'position'       => $position,
                    'role'           => ['user'],
                    'is_active'      => true,
                    'email_verified' => true,
                ]
            );
        }

        // Seed 5 commanders
        for ($i = 1; $i <= 5; $i++) {
            $name = $commanderNames[$i - 1];
            $rank = $ranks[($i - 1) % count($ranks)];
            $unit = $units[($i - 1) % count($units)];
            $position = $positions[($i - 1) % count($positions)];

            User::updateOrCreate(
                ['email' => "commander{$i}@gmail.com"],
                [
                    'name'           => $name,
                    'password'       => $password,
                    'rank'           => $rank,
                    'unit'           => $unit,
                    'position'       => $position,
                    'role'           => ['commander'],
                    'is_active'      => true,
                    'email_verified' => true,
                ]
            );
        }
    }
}
