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
        available: 'badge-success',
        occupied: 'badge-danger',
        cleaning: 'badge-warning',
        inspected: 'badge-info',
        needs_cleaning: 'badge-warning',
        do_not_disturb: 'badge-neutral',
        maintenance: 'badge-neutral',
    }
    return map[status] || 'badge-neutral'
}

function taskStatusColor(status) {
    const map = {
        completed: 'badge-success',
        in_progress: 'badge-info',
        pending: 'badge-warning',
    }
    return map[status] || 'badge-neutral'
}

function formatPrice(price) {
    if (!price) return '—'
    return '₱' + Number(price).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="page-header">
                <h1 class="text-heading-1">Room Management</h1>
                <p class="text-body text-gray-500">Manage room inventory, status, and housekeeping tasks.</p>
            </div>

            <!-- Tabs -->
            <div class="tab-group">
                <button @click="switchTab('inventory')"
                        class="tab-button"
                        :class="activeTab === 'inventory' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-bed text-sm"></i>
                    Inventory
                </button>
                <button @click="switchTab('housekeeping')"
                        class="tab-button"
                        :class="activeTab === 'housekeeping' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-spray text-sm"></i>
                    Housekeeping
                </button>
            </div>

            <!-- Inventory Tab - Table with Edit Button -->
            <div v-if="activeTab === 'inventory'" class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-[800px] w-full data-table">
                        <thead>
                            <tr>
                                <th class="w-1/5">Room #</th>
                                <th class="w-1/4">Type</th>
                                <th class="w-1/6">Floor</th>
                                <th class="w-1/6">Status</th>
                                <th class="w-1/6">Price</th>
                                <th class="w-1/6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in rooms" :key="r.id" class="transition-colors hover:bg-gray-50/50">
                                <td class="font-semibold text-gray-900">#{{ r.room_number }}</td>
                                <td class="text-gray-600">{{ r.room_type?.name || r.room_type_name || '—' }}</td>
                                <td class="text-gray-500">{{ r.floor || '—' }}</td>
                                <td>
                                    <span class="badge" :class="statusColor(r.status)">
                                        {{ r.status?.replace(/_/g, ' ') }}
                                    </span>
                                </td>
                                <td class="text-gray-600 font-medium">{{ formatPrice(r.price_override || r.room_type?.base_price) }}</td>
                                <td class="text-center">
                                    <button @click="openEditModal(r)" 
                                            class="btn btn-secondary"
                                            title="Edit Room">
                                        <i class="ti ti-edit text-xs"></i>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="rooms.length === 0">
                                <td colspan="6" class="p-12">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="ti ti-building-off text-3xl text-gray-300"></i>
                                        </div>
                                        <p class="text-body font-medium text-gray-500">No rooms found</p>
                                        <p class="text-caption mt-1">No room inventory records available</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Housekeeping Tab - Card View of Rooms + Tasks Table -->
            <div v-if="activeTab === 'housekeeping'" class="space-y-6">
                <!-- Room Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="r in rooms" :key="r.id" class="card">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-heading-3">Room {{ r.room_number }}</h3>
                                    <p class="text-caption">{{ r.room_type?.name || '—' }}</p>
                                </div>
                                <span class="badge" :class="statusColor(r.status)">
                                    {{ r.status?.replace(/_/g, ' ') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button v-if="r.status !== 'do_not_disturb'" @click="toggleFlag(r.id, 'do_not_disturb')" 
                                        class="btn btn-secondary text-xs px-3 py-1.5"
                                        title="Set Do Not Disturb">
                                    <i class="ti ti-bell-off text-xs"></i>
                                    DND
                                </button>
                                <button v-if="r.status === 'do_not_disturb'" @click="toggleFlag(r.id, 'do_not_disturb')" 
                                        class="btn btn-ghost text-xs px-3 py-1.5"
                                        title="Clear Do Not Disturb">
                                    <i class="ti ti-bell text-xs"></i>
                                    Clear DND
                                </button>
                                <button v-if="!r.make_up_room" @click="toggleFlag(r.id, 'make_up_room')" 
                                        class="btn btn-secondary text-xs px-3 py-1.5"
                                        title="Request Make Up Room">
                                    <i class="ti ti-spray text-xs"></i>
                                    Make Up
                                </button>
                                <button v-if="r.make_up_room" @click="toggleFlag(r.id, 'make_up_room')" 
                                        class="btn btn-ghost text-xs px-3 py-1.5"
                                        title="Clear Make Up Room Request">
                                    <i class="ti ti-bell-x text-xs"></i>
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks Table -->
                <div class="table-container">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="text-heading-3">Housekeeping Tasks</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[800px] w-full data-table">
                            <thead>
                                <tr>
                                    <th class="w-2/5">Task</th>
                                    <th class="w-1/5">Room</th>
                                    <th class="w-1/5">Status</th>
                                    <th class="w-1/5">Assigned To</th>
                                    <th class="w-1/5 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="t in tasks" :key="t.id" class="transition-colors hover:bg-gray-50/50">
                                    <td class="font-medium">{{ t.note || 'Cleaning Task' }}</td>
                                    <td>#{{ t.room?.room_number || '—' }}</td>
                                    <td>
                                        <span class="badge" :class="taskStatusColor(t.status)">
                                            {{ t.status?.replace(/_/g, ' ') }}
                                        </span>
                                    </td>
                                    <td class="text-gray-500">{{ t.assigned_to?.fname || t.assigned_to || '—' }}</td>
                                    <td class="text-center">
                                        <div class="flex gap-2 justify-center">
                                            <button v-if="t.status === 'pending'" @click="startCleaning(t.id)" 
                                                    class="btn btn-secondary text-xs px-3 py-1.5">
                                                <i class="ti ti-player-play text-xs"></i>
                                                Start
                                            </button>
                                            <button v-if="t.status === 'in_progress'" @click="completeCleaning(t.id)" 
                                                    class="btn btn-primary text-xs px-3 py-1.5">
                                                <i class="ti ti-check text-xs"></i>
                                                Complete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="tasks.length === 0">
                                    <td colspan="5" class="p-12">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="ti ti-spray-off text-3xl text-gray-300"></i>
                                            </div>
                                            <p class="text-body font-medium text-gray-500">No tasks found</p>
                                            <p class="text-caption mt-1">No housekeeping tasks scheduled</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Room Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="modal-backdrop" @click.self="closeEditModal">
                <div class="modal-container" @click.self="closeEditModal">
                    <div class="modal-panel w-full max-w-md">
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <div>
                                <h2 class="text-heading-2">Edit Room</h2>
                                <p class="text-caption mt-1">Update room details and configuration</p>
                            </div>
                            <button @click="closeEditModal" class="btn-icon">
                                <i class="ti ti-x text-lg text-gray-500"></i>
                            </button>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="form-label">Room Number</label>
                                <input v-model="editForm.room_number" type="text" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Room Type</label>
                                <select v-model="editForm.room_type_id" class="form-input">
                                    <option v-for="rt in roomTypes" :key="rt.id" :value="rt.id">{{ rt.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Floor</label>
                                <input v-model="editForm.floor" type="number" min="0" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select v-model="editForm.status" class="form-input">
                                    <option value="available">Available</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="inspected">Inspected</option>
                                    <option value="do_not_disturb">Do Not Disturb</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Price Override</label>
                                <input v-model="editForm.price_override" type="number" min="0" step="0.01" 
                                       class="form-input" placeholder="Leave empty to use room type base price" />
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 p-6 border-t border-gray-100">
                            <button @click="closeEditModal" class="btn btn-secondary">
                                Cancel
                            </button>
                            <button @click="updateRoom" class="btn btn-primary">
                                <i class="ti ti-device-floppy text-xs"></i>
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>