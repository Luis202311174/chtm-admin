<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props
const activeTab = ref(props.tab || 'inventory')

const rooms = computed(() => props.rooms || [])
const roomTypes = computed(() => props.roomTypes || [])
const tasks = computed(() => props.tasks || [])
const templates = computed(() => props.templates || [])

// Edit modal state
const showEditModal = ref(false)
const editingRoom = ref(null)
const editForm = ref({
    room_type_id: '',
    room_number: '',
    floor: '',
    status: 'available',
    price_override: '',
})

function switchTab(tab) {
    activeTab.value = tab
    router.get('/room', { tab }, { preserveState: true, replace: true })
}

function openEditModal(room) {
    editingRoom.value = room
    editForm.value = {
        room_type_id: room.room_type_id,
        room_number: room.room_number,
        floor: room.floor || '',
        status: room.status || 'available',
        price_override: room.price_override || '',
    }
    showEditModal.value = true
}

function closeEditModal() {
    showEditModal.value = false
    editingRoom.value = null
}

function updateRoom() {
    if (!editingRoom.value) return
    
    router.put(`/room/${editingRoom.value.id}`, {
        room_type_id: editForm.value.room_type_id,
        room_number: editForm.value.room_number,
        floor: editForm.value.floor || null,
        status: editForm.value.status,
        price_override: editForm.value.price_override || null,
    }, {
        preserveState: true,
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
        onSuccess: () => closeEditModal(),
    })
}

function toggleFlag(roomId, flag) {
    router.post(`/room/${roomId}/flag`, { flag }, { preserveState: true })
}

function startCleaning(taskId) {
    router.post(`/housekeeping/${taskId}/start`, {}, { preserveState: true })
}

function completeCleaning(taskId) {
    router.post(`/housekeeping/${taskId}/complete`, {}, { preserveState: true })
}

function statusColor(status) {
    const map = {
        available: 'bg-teal-50 text-teal-700',
        occupied: 'bg-rose-50 text-rose-700',
        cleaning: 'bg-amber-50 text-amber-700',
        inspected: 'bg-blue-50 text-blue-700',
        needs_cleaning: 'bg-amber-50 text-amber-700',
        do_not_disturb: 'bg-purple-50 text-purple-700',
        maintenance: 'bg-gray-100 text-gray-600',
    }
    return map[status] || 'bg-gray-100 text-gray-600'
}

function taskStatusColor(status) {
    const map = {
        completed: 'bg-teal-50 text-teal-700',
        in_progress: 'bg-blue-50 text-blue-700',
        pending: 'bg-amber-50 text-amber-700',
    }
    return map[status] || 'bg-gray-100 text-gray-600'
}

function formatPrice(price) {
    if (!price) return '—'
    return '$' + Number(price).toFixed(2)
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Room Management</h1>
                <p class="text-xs text-gray-500">Manage room inventory, status, and housekeeping tasks.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-1 shadow-xs inline-flex">
                <button @click="switchTab('inventory')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition"
                        :class="activeTab === 'inventory' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Inventory</button>
                <button @click="switchTab('housekeeping')"
                        class="px-4 py-2 text-xs font-bold rounded-lg transition"
                        :class="activeTab === 'housekeeping' ? 'bg-pink-500 text-white shadow-xs' : 'text-gray-600 hover:bg-gray-50'">Housekeeping</button>
            </div>

            <!-- Inventory Tab - Table with Edit Button -->
            <div v-if="activeTab === 'inventory'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-[800px] w-full text-xs text-left">
                        <thead class="border-b border-gray-200 bg-gray-50/70 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="p-3 pl-5">Room #</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Floor</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Price</th>
                                <th class="p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in rooms" :key="r.id" class="hover:bg-gray-50/40">
                                <td class="p-3 pl-5 font-bold text-gray-900">{{ r.room_number }}</td>
                                <td class="p-3">{{ r.room_type?.name || r.room_type_name || '—' }}</td>
                                <td class="p-3 text-gray-500">{{ r.floor || '—' }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="statusColor(r.status)">{{ r.status?.replace(/_/g, ' ') }}</span>
                                </td>
                                <td class="p-3 text-gray-500">{{ formatPrice(r.price_override || r.room_type?.base_price) }}</td>
                                <td class="p-3">
                                    <button @click="openEditModal(r)" 
                                            class="px-2 py-1 text-[10px] font-bold rounded bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            title="Edit Room">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="rooms.length === 0">
                                <td colspan="6" class="p-12 text-center text-gray-400">No rooms found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Housekeeping Tab - Card View of Rooms + Tasks Table -->
            <div v-if="activeTab === 'housekeeping'" class="space-y-4">
                <!-- Room Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="r in rooms" :key="r.id" class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-gray-900">Room {{ r.room_number }}</h3>
                                <p class="text-xs text-gray-500">{{ r.room_type?.name || '—' }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="statusColor(r.status)">{{ r.status?.replace(/_/g, ' ') }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button v-if="r.status !== 'do_not_disturb'" @click="toggleFlag(r.id, 'do_not_disturb')" 
                                    class="px-3 py-1 text-xs font-bold rounded bg-purple-100 text-purple-700 hover:bg-purple-200"
                                    title="Do Not Disturb">
                                DND
                            </button>
                            <button v-if="!r.make_up_room" @click="toggleFlag(r.id, 'make_up_room')" 
                                    class="px-3 py-1 text-xs font-bold rounded bg-amber-100 text-amber-700 hover:bg-amber-200"
                                    title="Request Make Up Room">
                                Make Up
                            </button>
                            <button v-if="r.make_up_room" @click="toggleFlag(r.id, 'make_up_room')" 
                                    class="px-3 py-1 text-xs font-bold rounded bg-gray-100 text-gray-700 hover:bg-gray-200"
                                    title="Clear Make Up Room">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tasks Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="min-w-[800px] w-full text-xs text-left">
                            <thead class="border-b border-gray-200 bg-gray-50/70 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="p-3 pl-5">Task</th>
                                    <th class="p-3">Room</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Assigned To</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="t in tasks" :key="t.id" class="hover:bg-gray-50/40">
                                    <td class="p-3 pl-5 font-semibold">{{ t.note || 'Cleaning Task' }}</td>
                                    <td class="p-3">#{{ t.room?.room_number || '—' }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                              :class="taskStatusColor(t.status)">
                                            {{ t.status?.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-500">{{ t.assigned_to?.fname || t.assigned_to || '—' }}</td>
                                    <td class="p-3">
                                        <div class="flex gap-1">
                                            <button v-if="t.status === 'pending'" @click="startCleaning(t.id)" 
                                                    class="px-2 py-1 text-[10px] font-bold rounded bg-blue-100 text-blue-700 hover:bg-blue-200"
                                                    title="Start Cleaning">
                                                Start
                                            </button>
                                            <button v-if="t.status === 'in_progress'" @click="completeCleaning(t.id)" 
                                                    class="px-2 py-1 text-[10px] font-bold rounded bg-teal-100 text-teal-700 hover:bg-teal-200"
                                                    title="Complete Cleaning">
                                                Complete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tasks.length === 0">
                                    <td colspan="5" class="p-12 text-center text-gray-400">No tasks found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Room Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeEditModal">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Edit Room</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Room Number</label>
                        <input v-model="editForm.room_number" type="text" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Room Type</label>
                        <select v-model="editForm.room_type_id" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option v-for="rt in roomTypes" :key="rt.id" :value="rt.id">{{ rt.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Floor</label>
                        <input v-model="editForm.floor" type="number" min="0" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Status</label>
                        <select v-model="editForm.status" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="inspected">Inspected</option>
                            <option value="do_not_disturb">Do Not Disturb</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1">Price Override</label>
                        <input v-model="editForm.price_override" type="number" min="0" step="0.01" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Leave empty to use room type base price" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button @click="closeEditModal" class="px-4 py-2 text-xs font-bold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button @click="updateRoom" class="px-4 py-2 text-xs font-bold rounded-lg bg-pink-500 text-white hover:bg-pink-600">Save</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>