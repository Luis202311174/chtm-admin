<?php

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Services\Room\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function __construct(private readonly RoomService $rooms) {}

    public function index(Request $request): Response
    {
        $tab = $request->string('tab', 'inventory')->toString();
        if (!in_array($tab, ['inventory', 'housekeeping'], true)) {
            $tab = 'inventory';
        }

        $roomsData = [];
        $roomTypesData = [];
        $tasksData = [];
        $templatesData = [];

        if ($tab === 'inventory') {
            $roomsData = $this->rooms->getRooms()->toArray();
            $roomTypesData = $this->rooms->getRoomTypes()->toArray();
        } elseif ($tab === 'housekeeping') {
            $roomsData = $this->rooms->getRooms()->toArray();
            $tasksData = $this->rooms->getTasks()->toArray();
            $templatesData = $this->rooms->getTemplates()->toArray();
        }

        return Inertia::render('Room', [
            'title' => 'Room Management',
            'tab' => $tab,
            'rooms' => $roomsData,
            'roomTypes' => $roomTypesData,
            'tasks' => $tasksData,
            'templates' => $templatesData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:32', 'unique:rooms,room_number'],
            'floor' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string'],
        ]);
        $this->rooms->createRoom($data);
        return back()->with('status', 'Room created.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:32', 'unique:rooms,room_number,' . $room->id],
            'floor' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ]);
        $this->rooms->updateRoom($room, $data);
        return back()->with('status', 'Room updated.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->rooms->deleteRoom($room);
        return back()->with('status', 'Room deleted.');
    }

    public function toggleFlag(Request $request, Room $room): RedirectResponse
    {
        $flag = $request->string('flag')->toString();
        match ($flag) {
            'do_not_disturb' => $this->rooms->setDoNotDisturb($room, $room->status !== 'do_not_disturb'),
            'make_up_room' => $room->make_up_room
                ? $this->rooms->clearMakeUpRoom($room)
                : $this->rooms->requestMakeUpRoom($room, (int) $room->room_type_id),
            default => null,
        };
        return back();
    }

    public function startCleaning(HousekeepingTask $task): RedirectResponse
    {
        $this->rooms->startCleaning($task);
        return back()->with('status', 'Cleaning started.');
    }

    public function completeCleaning(HousekeepingTask $task): RedirectResponse
    {
        $this->rooms->completeCleaning($task);
        return back()->with('status', 'Cleaning completed.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'string'],
        ]);
        $cleanItems = collect($data['items'])
            ->map(fn($item) => trim((string) $item))
            ->filter(fn($item) => $item !== '')
            ->all();
        $this->rooms->saveTemplate($data['name'], $data['room_type_id'] ?? null, $cleanItems);
        return back()->with('status', 'Checklist template saved.');
    }
}