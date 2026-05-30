<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE users SET role = 'user' WHERE role IS NULL");
        DB::statement("ALTER TABLE users ALTER COLUMN role DROP DEFAULT");
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE jsonb USING json_build_array(role)::jsonb");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT '[\"user\"]'::jsonb");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users ALTER COLUMN role DROP DEFAULT");
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE varchar(255) USING role->>0");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
    }
};
