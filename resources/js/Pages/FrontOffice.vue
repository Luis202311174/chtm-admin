<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const activeTab = ref(page.props.tab || 'bookings')
let pollInterval = null
const showModal = ref(false)
const selected = ref(null)
const processing = ref(false)

const bookings = computed(() => page.props.bookings || [])
const rooms = computed(() => page.props.rooms || [])
const receipts = computed(() => page.props.receipts || [])
const bookingStats = computed(() => page.props.bookingStats || {})

onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ preserveState: true, preserveScroll: true, only: ['bookings', 'rooms', 'bookingStats', 'tab'] })
    }, 15000)
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
})

function switchTab(tab) {
    activeTab.value = tab
    router.get('/frontoffice', { tab }, { preserveState: true, replace: true })
}

function editBooking(booking) {
    // Create a copy and format dates for input
    const formattedBooking = { ...booking }
    if (formattedBooking.start_at) {
        formattedBooking.start_at = formatDateForInput(formattedBooking.start_at)
    }
    if (formattedBooking.end_at) {
        formattedBooking.end_at = formatDateForInput(formattedBooking.end_at)
    }
    // Ensure user object exists for form binding
    if (!formattedBooking.user) {
        formattedBooking.user = { fname: '', lname: '', email: '' }
    }
    selected.value = formattedBooking
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    selected.value = null
}

function formatAmount(amount) {
    if (!amount) return '0.00'
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 })
}

function formatDateTime(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatDateForInput(d) {
    if (!d) return ''
    const date = new Date(d)
    return date.toISOString().split('T')[0]
}

function saveBooking() {
    if (!selected.value) return
    
    processing.value = true
    router.put(route('frontoffice.update', { booking: selected.value.id }), {
        guest_fname: selected.value.user?.fname || '',
        guest_lname: selected.value.user?.lname || '',
        guest_email: selected.value.user?.email || '',
        start_at: selected.value.start_at,
        end_at: selected.value.end_at,
        room_id: selected.value.room_id,
        guests: selected.value.guests,
        extra_beds: selected.value.extra_beds || 0,
        has_child: selected.value.has_child || false,
        child_age_group: selected.value.child_age_group || '',
        has_pwd: selected.value.has_pwd || false,
        has_senior: selected.value.has_senior || false,
    }, {
        preserveState: true,
        onFinish: () => {
            processing.value = false
            closeModal()
        },
    })
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="text-heading-1">Front Office</h1>
                <p class="text-body text-gray-500 mt-1">Manage guest bookings, check-ins, and receipts with comprehensive reservation management tools.</p>
            </div>

            <!-- Tabs -->
            <div class="tab-group">
                <button @click="switchTab('bookings')"
                        class="tab-button"
                        :class="activeTab === 'bookings' ? 'tab-button-active' : 'tab-button-inactive'">
                    <i class="ti ti-calendar-check text-sm"></i>
                    Bookings
                </button>
                <button
                    type="button"
                    disabled
                    class="tab-button tab-button-inactive cursor-not-allowed opacity-60"
                    title="Receipts feature coming soon">
                    <i class="ti ti-receipt text-sm"></i>
                    Receipts <span class="text-xs font-medium">(Soon)</span>
                </button>
            </div>

            <!-- Bookings Tab -->
            <div v-if="activeTab === 'bookings'" class="space-y-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="stats-card">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50">
                                <i class="ti ti-calendar-event text-xl text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-label">Total Bookings</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ bookingStats.total || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="stats-card">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50">
                                <i class="ti ti-clock-hour-4 text-xl text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-label">Pending</p>
                                <p class="text-2xl font-bold text-amber-600 mt-1">{{ bookingStats.pending || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="stats-card">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50">
                                <i class="ti ti-circle-check text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-label">Approved</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">{{ bookingStats.approved || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="table-container">
                    <div class="overflow-x-auto">
                        <table class="min-w-[1000px] w-full data-table">
                            <thead>
                                <tr>
                                    <th class="w-2/5">Guest</th>
                                    <th class="w-1/5">Room</th>
                                    <th class="w-1/5">Dates</th>
                                    <th class="w-1/6">Status</th>
                                    <th class="w-1/6">Total</th>
                                    <th class="w-1/6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="b in bookings" :key="b.id" class="transition-colors hover:bg-gray-50/50">
                                    <td class="font-semibold text-gray-900">{{ b.user?.full_name || 'Guest' }}</td>
                                    <td>
                                        <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">
                                            #{{ b.room?.room_number || '—' }}
                                        </span>
                                    </td>
                                    <td class="text-caption font-medium">{{ b.start_at ? new Date(b.start_at).toLocaleDateString() : '—' }}</td>
                                    <td>
                                        <span class="badge" :class="b.status === 'pending' ? 'badge-warning' : b.status === 'approved' ? 'badge-info' : 'badge-neutral'">
                                            {{ b.status }}
                                        </span>
                                    </td>
                                    <td class="font-bold text-gray-900">₱{{ formatAmount(b.total_amount) }}</td>
                                    <td class="text-center">
                                        <button @click="editBooking(b)"
                                                class="btn btn-primary">
                                            <i class="ti ti-edit text-xs"></i>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="bookings.length === 0">
                                    <td colspan="6" class="p-12">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="ti ti-calendar-off text-3xl text-gray-300"></i>
                                            </div>
                                            <p class="text-body font-medium text-gray-500">No bookings found</p>
                                            <p class="text-caption mt-1">There are no records to display in this view.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Receipts Tab -->
            <div v-if="activeTab === 'receipts'" class="space-y-6">
                <div class="table-container">
                    <div class="overflow-x-auto">
                        <table class="min-w-[600px] w-full data-table">
                            <thead>
                                <tr>
                                    <th class="w-1/3">Receipt #</th>
                                    <th class="w-1/3">Amount</th>
                                    <th class="w-1/3">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in receipts" :key="r.id" class="transition-colors hover:bg-gray-50/50">
                                    <td class="font-semibold text-gray-900">#{{ r.id }}</td>
                                    <td class="font-bold text-gray-900">₱{{ formatAmount(r.amount) }}</td>
                                    <td class="text-caption font-medium">{{ r.created_at ? new Date(r.created_at).toLocaleDateString() : '—' }}</td>
                                </tr>
                                <tr v-if="receipts.length === 0">
                                    <td colspan="3" class="p-12">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="ti ti-receipt-off text-3xl text-gray-300"></i>
                                            </div>
                                            <p class="text-body font-medium text-gray-500">No receipts found</p>
                                            <p class="text-caption mt-1">No payment records are available at this time.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Teleport to="body">
            <div v-if="showModal && selected" class="modal-backdrop" @click.self="closeModal">
                <div class="modal-container" @click.self="closeModal">
                    <div class="modal-panel w-full max-w-3xl">
                        <!-- Modal Header -->
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <div>
                                <h3 class="text-heading-2">Edit Booking #{{ selected.id }}</h3>
                                <p class="text-caption mt-1">Update guest reservation details and preferences</p>
                            </div>
                            <button @click="closeModal" class="btn-icon">
                                <i class="ti ti-x text-lg text-gray-500"></i>
                            </button>
                        </div>
                        
                        <form @submit.prevent="saveBooking" class="p-6 space-y-5">
                            <!-- Guest Information Section -->
                            <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <i class="ti ti-user text-lg text-teal-600"></i>
                                    <h4 class="text-heading-3">Guest Information</h4>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">First Name</label>
                                        <input v-model="selected.user.fname" type="text" required
                                               class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">Last Name</label>
                                        <input v-model="selected.user.lname" type="text" required
                                               class="form-input" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="form-label">Email Address</label>
                                    <input v-model="selected.user.email" type="email" required
                                           class="form-input" />
                                </div>
                            </div>

                            <!-- Room & Dates Section -->
                            <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <i class="ti ti-door text-lg text-teal-600"></i>
                                    <h4 class="text-heading-3">Room & Stay Details</h4>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Room Assignment</label>
                                        <select v-model.number="selected.room_id" required
                                                class="form-input">
                                            <option value="" disabled>Select a room</option>
                                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                                Room #{{ room.room_number }} - {{ room.room_type?.name || 'Standard' }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Total Guests</label>
                                        <input v-model.number="selected.guests" type="number" min="1" required
                                               class="form-input" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="form-label">Check-In Date</label>
                                        <input v-model="selected.start_at" type="date" required
                                               class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">Check-Out Date</label>
                                        <input v-model="selected.end_at" type="date" required
                                               class="form-input" />
                                    </div>
                                </div>
                            </div>

                            <!-- Guest Count Section -->
                            <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <i class="ti ti-users text-lg text-teal-600"></i>
                                    <h4 class="text-heading-3">Guest Composition</h4>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="form-label">Adults</label>
                                        <input v-model.number="selected.guests" type="number" min="1" required
                                               class="form-input" />
                                    </div>
                                    <div>
                                        <label class="form-label">Extra Beds</label>
                                        <select v-model.number="selected.extra_beds"
                                                class="form-input">
                                            <option :value="0">0</option>
                                            <option :value="1">1</option>
                                            <option :value="2">2</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Child Age Group</label>
                                        <input v-model="selected.child_age_group" type="text" placeholder="e.g. 5-12"
                                               class="form-input" />
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-4 mt-4">
                                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600 cursor-pointer">
                                        <input v-model="selected.has_child" type="checkbox"
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 w-4 h-4">
                                        Has Child
                                    </label>
                                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600 cursor-pointer">
                                        <input v-model="selected.has_pwd" type="checkbox"
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 w-4 h-4">
                                        PWD
                                    </label>
                                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600 cursor-pointer">
                                        <input v-model="selected.has_senior" type="checkbox"
                                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 w-4 h-4">
                                        Senior
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Section -->
                            <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <i class="ti ti-credit-card text-lg text-teal-600"></i>
                                    <h4 class="text-heading-3">Payment Details</h4>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Payment Method</label>
                                        <select v-model="selected.payment_method"
                                                class="form-input">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="gcash">GCash</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Total Amount</label>
                                        <input v-model.number="selected.total_amount" type="number" step="0.01" min="0" required
                                               class="form-input" />
                                    </div>
                                </div>
                            </div>

                            <!-- Special Requests Section -->
                            <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <i class="ti ti-message-2 text-lg text-teal-600"></i>
                                    <h4 class="text-heading-3">Special Requests</h4>
                                </div>
                                <textarea v-model="selected.message" rows="3" placeholder="Enter guest notes or special requests..."
                                        class="form-input resize-none"></textarea>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                                <button @click="closeModal" type="button"
                                        class="btn btn-secondary">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="processing"
                                        class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i v-if="!processing" class="ti ti-device-floppy text-xs"></i>
                                    <i v-else class="ti ti-loader animate-spin text-xs"></i>
                                    {{ processing ? 'Saving...' : 'Save Changes' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>