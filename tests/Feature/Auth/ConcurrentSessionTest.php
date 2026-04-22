<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_login_replaces_previous_active_session(): void
    {
        $user = User::factory()->create();
        $previousSessionId = 'old-session-id-123';

        $user->forceFill([
            'active_session_id' => $previousSessionId,
        ])->save();

        DB::table(config('session.table', 'sessions'))->insert([
            'id' => $previousSessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);

        $user->refresh();

        $this->assertNotSame($previousSessionId, $user->active_session_id);
        $this->assertDatabaseMissing(config('session.table', 'sessions'), [
            'id' => $previousSessionId,
        ]);
    }
}
