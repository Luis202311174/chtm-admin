<?php

namespace App\Services\Room;

use App\Models\HousekeepingTask;
use App\Models\HousekeepingTemplate;
use App\Models\HousekeepingTemplateItem;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

final class RoomService
{
    public function getRooms(): Collection
    {
        return Room::query()
            ->with(['roomType:id,name', 'roomType.amenities:id,name'])
            ->select('id', 'room_type_id', 'room_number', 'floor', 'status', 'make_up_room', 'price_override')
            ->orderBy('room_number')
            ->get();
    }

    public function getRoomTypes(): Collection
    {
        return RoomType::query()->with('amenities:id,name')
            ->select('id', 'name', 'description', 'base_price', 'capacity')
            ->orderBy('name')
            ->get();
    }

    public function getTasks(): Collection
    {
        return HousekeepingTask::query()
            ->with(['room.roomType', 'items', 'assignedTo:id,fname,lname'])
            ->select('id', 'room_id', 'template_id', 'status', 'note', 'started_at', 'completed_at', 'created_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function createRoom(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            $room = Room::query()->create([
                'room_type_id' => $data['room_type_id'],
                'room_number' => $data['room_number'],
                'floor' => $data['floor'] ?? null,
                'status' => $data['status'] ?? 'available',
            ]);

            AuditLogger::log('rooms', $room->id, 'CREATE', null, $data, Auth::user());

            return $room;
        });
    }

    public function updateRoom(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $oldData = $room->only(['room_type_id', 'room_number', 'floor', 'status', 'price_override']);
            
            $room->update([
                'room_type_id' => $data['room_type_id'] ?? $room->room_type_id,
                'room_number' => $data['room_number'] ?? $room->room_number,
                'floor' => $data['floor'] ?? $room->floor,
                'status' => $data['status'] ?? $room->status,
                'price_override' => $data['price_override'] ?? $room->price_override,
            ]);

            AuditLogger::log('rooms', $room->id, 'UPDATE', $oldData, $data, Auth::user());

            return $room->fresh(['roomType.amenities', 'images']);
        });
    }

    public function deleteRoom(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $oldData = $room->only(['id', 'room_number']);
            $room->delete();

            AuditLogger::log('rooms', $room->id, 'DELETE', $oldData, null, Auth::user());
        });
    }

    public function setRoomStatus(int $roomId, string $status): void
    {
        Room::query()->whereKey($roomId)->update(['status' => $status]);
    }

    public function setDoNotDisturb(Room $room, bool $isSet): void
    {
        $room->update([
            'status' => $isSet ? 'do_not_disturb' : 'available',
        ]);
    }

    public function requestMakeUpRoom(Room $room, ?int $roomTypeId): void
    {
        DB::transaction(function () use ($room, $roomTypeId) {
            $room->update(['make_up_room' => true, 'status' => 'cleaning']);
            
            // Create a housekeeping task
            HousekeepingTask::query()->create([
                'room_id' => $room->id,
                'status' => 'pending',
                'note' => 'Make up room requested',
            ]);
        });
    }

    public function clearMakeUpRoom(Room $room): void
    {
        $room->update(['make_up_room' => false]);
    }

    public function startCleaning(HousekeepingTask $task): void
    {
        $task->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function completeCleaning(HousekeepingTask $task): void
    {
        DB::transaction(function () use ($task) {
            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            // Clear the make_up_room flag and set room to available
            if ($task->room_id) {
                $room = Room::query()->whereKey($task->room_id)->first();
                if ($room) {
                    $room->update(['make_up_room' => false, 'status' => 'available']);
                }
            }
        });
    }

    public function updateRoomFlags(Room $room, array $flags): Room
    {
        $room->update($flags);
        return $room->fresh(['roomType']);
    }

    public function getTemplates(): Collection
    {
        return HousekeepingTemplate::query()->with('items')->get();
    }

    public function saveTemplate(string $name, ?int $roomTypeId, array $itemNames): HousekeepingTemplate
    {
        return DB::transaction(function () use ($name, $roomTypeId, $itemNames) {
            $template = HousekeepingTemplate::query()->create([
                'name' => $name,
                'room_type_id' => $roomTypeId,
            ]);

            foreach ($itemNames as $itemName) {
                if (trim($itemName) === '') {
                    continue;
                }
                HousekeepingTemplateItem::query()->create([
                    'template_id' => $template->id,
                    'item_name' => trim($itemName),
                    'default_quantity' => 1,
                ]);
            }

            return $template->load('items');
        });
    }
}