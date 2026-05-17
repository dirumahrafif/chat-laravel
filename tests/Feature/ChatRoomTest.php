<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\ChatUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatRoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_can_create_room()
    {
        $response = $this->post('/room', [
            'name' => 'Test User'
        ]);

        $room = Room::first();
        $this->assertNotNull($room);
        $response->assertRedirect('/room/' . $room->code);
        $this->assertEquals('Test User', session('chat_user_name'));
    }

    public function test_can_join_room()
    {
        $room = Room::factory()->create(['code' => 'ABCDEFGH']);

        $response = $this->post("/room/{$room->code}/join", [
            'name' => 'John Doe'
        ]);

        $response->assertJson(['success' => true]);
        $this->assertEquals('John Doe', session('chat_user_name'));
        $this->assertDatabaseHas('chat_users', [
            'room_id' => $room->id,
            'name' => 'John Doe'
        ]);
    }

    public function test_cannot_join_expired_room()
    {
        $room = Room::factory()->create([
            'code' => 'EXPIRED1',
            'expired_at' => now()->subHour()
        ]);

        $response = $this->get("/room/{$room->code}");
        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Room has expired.');

        $response = $this->post("/room/{$room->code}/join", [
            'name' => 'John Doe'
        ]);
        $response->assertStatus(410);
    }

    public function test_can_send_message()
    {
        $room = Room::factory()->create(['code' => 'CHAT1234']);
        $user = ChatUser::factory()->create(['room_id' => $room->id, 'name' => 'Sender']);

        session(['chat_user_id' => $user->id, 'chat_user_name' => 'Sender']);

        $response = $this->post("/room/{$room->code}/messages", [
            'message' => 'Hello World'
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => 'Hello World'
        ]);
    }

    public function test_typing_status()
    {
        $room = Room::factory()->create(['code' => 'TYPING12']);
        $user1 = ChatUser::factory()->create(['room_id' => $room->id, 'name' => 'User 1']);
        $user2 = ChatUser::factory()->create(['room_id' => $room->id, 'name' => 'User 2']);

        // User 2 is typing
        session(['chat_user_id' => $user2->id]);
        $this->post("/room/{$room->code}/typing");

        // User 1 checks status
        session(['chat_user_id' => $user1->id]);
        $response = $this->get("/room/{$room->code}/typing/status");

        $response->assertJson(['typing' => ['User 2']]);
    }
}
