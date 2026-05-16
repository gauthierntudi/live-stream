<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('donor_phone', 32)->nullable()->after('donor_email');
            $table->string('maxicash_transaction_id', 120)->nullable()->after('maxicash_reference')->index();
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['donor_phone', 'maxicash_transaction_id']);
        });
    }
};
