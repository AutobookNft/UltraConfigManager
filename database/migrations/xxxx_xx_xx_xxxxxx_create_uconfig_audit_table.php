use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUConfigAuditTable extends Migration
{
    public function up()
    {
        Schema::create('uconfig_audit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('uconfig_id');
            $table->string('action'); // 'created', 'updated', 'deleted'
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // Chiavi esterne
            $table->foreign('uconfig_id')->references('id')->on('uconfig')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('uconfig_audit');
    }
} 