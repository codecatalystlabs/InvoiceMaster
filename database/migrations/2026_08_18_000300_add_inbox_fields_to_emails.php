<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->string('in_reply_to')->nullable()->after('message_id');
            $table->text('cc_email')->nullable()->after('to_email');
            $table->text('bcc_email')->nullable()->after('cc_email');
            $table->longText('body_text')->nullable()->after('body_html');
            $table->boolean('has_attachment')->default(false)->after('body_text');
            $table->string('attachment_name')->nullable()->after('has_attachment');
            $table->timestamp('received_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('received_at');
        });

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->string('filename');
            $table->string('filepath', 500);
            $table->unsignedInteger('filesize')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn([
                'in_reply_to', 'cc_email', 'bcc_email', 'body_text',
                'has_attachment', 'attachment_name', 'received_at', 'read_at',
            ]);
        });
    }
};
