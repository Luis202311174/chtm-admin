<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'

const page = usePage()
const props = page.props
const activeTab = ref(props.tab || 'bookings')
const showModal = ref(false)
const selected = ref(null)
const processing = ref(false)

const bookings = computed(() => props.bookings || [])
const rooms = computed(() => props.rooms || [])
const receipts = computed(() => props.receipts || [])
const bookingStats = computed(() => props.bookingStats || {})

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
            <div class="border-b border-gray-200 pb-4">
                <h1 class="text-2xl font-bold text-gray-900">Front Office</h1>
                <p class="text-sm text-gray-500 mt-1">Manage guest bookings, check-ins, and receipts with comprehensive reservation management tools.</p>
            </div>

            <!-- Tabs -->
            <div class="rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm inline-flex">
                <button @click="switchTab('bookings')"
                        class="px-5 py-2 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                        :class="activeTab === 'bookings' ? 'bg-teal-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'">
                    <i class="ti ti-calendar-check text-base"></i>
                    Bookings
                </button>
                <button @click="switchTab('receipts')"
                        class="px-5 py-2 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2"
                        :class="activeTab === 'receipts' ? 'bg-teal-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'">
                    <i class="ti ti-receipt text-base"></i>
                    Receipts
                </button>
            </div>

            <!-- Bookings Tab -->
            <div v-if="activeTab === 'bookings'" class="space-y-5">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-50">
                                <i class="ti ti-calendar-event text-xl text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bookings</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ bookingStats.total || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50">
                                <i class="ti ti-clock-hour-4 text-xl text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</p>
                                <p class="text-2xl font-bold text-amber-600 mt-1">{{ bookingStats.pending || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50">
                                <i class="ti ti-circle-check text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">{{ bookingStats.approved || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-[1000px] w-full text-sm text-left">
                            <thead class="border-b border-gray-200 bg-gray-50/80">
                                <tr>
                                    <th class="p-4 pl-5 font-semibold text-gray-600 w-1/4">Guest</th>
                                    <th class="p-4 font-semibold text-gray-600 w-1/6">Room</th>
                                    <th class="p-4 font-semibold text-gray-600 w-1/6">Dates</th>
                                    <th class="p-4 font-semibold text-gray-600 w-1/8">Status</th>
                                    <th class="p-4 font-semibold text-gray-600 w-1/8">Total</th>
                                    <th class="p-4 pr-5 text-center font-semibold text-gray-600 w-1/6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="b in bookings" :key="b.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="p-4 pl-5 font-semibold text-gray-900">{{ b.user?.full_name || 'Guest' }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-bold text-teal-700">
                                            #{{ b.room?.room_number || '—' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-gray-600">{{ b.start_at ? new Date(b.start_at).toLocaleDateString() : '—' }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                                              :class="b.status === 'pending' ? 'bg-amber-100 text-amber-700' : b.status === 'approved' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'">
                                            {{ b.status }}
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-gray-900">₱{{ formatAmount(b.total_amount) }}</td>
                                    <td class="p-4 pr-5 text-center">
                                        <button @click="editBooking(b)"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-700 transition-all shadow-sm hover:shadow-md cursor-pointer">
                                            <i class="ti ti-edit text-xs"></i>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="bookings.length === 0">
                                    <td colspan="6" class="p-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                                <i class="ti ti-calendar-off text-3xl text-gray-400"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500">No bookings found</p>
                                                <p class="text-xs text-gray-400 mt-1">There are no records to display in this view.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Receipts Tab -->
            <div v-if="activeTab === 'receipts'" class="space-y-5">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-[600px] w-full text-sm text-left">
                            <thead class="border-b border-gray-200 bg-gray-50/80">
                                <tr>
                                    <th class="p-4 pl-5 font-semibold text-gray-600 w-1/3">Receipt #</th>
                                    <th class="p-4 font-semibold text-gray-600 w-1/4">Amount</th>
                                    <th class="p-4 font-semibold text-gray-600 w-1/4">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="r in receipts" :key="r.id" class="hover:bg-gray-50/50 transition-colors">
                                    <td class="p-4 pl-5 font-semibold text-gray-900">#{{ r.id }}</td>
                                    <td class="p-4 font-bold text-gray-900">₱{{ formatAmount(r.amount) }}</td>
                                    <td class="p-4 text-gray-600">{{ r.created_at ? new Date(r.created_at).toLocaleDateString() : '—' }}</td>
                                </tr>
                                <tr v-if="receipts.length === 0">
                                    <td colspan="3" class="p-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                                                <i class="ti ti-receipt-off text-3xl text-gray-400"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500">No receipts found</p>
                                                <p class="text-xs text-gray-400 mt-1">No payment records are available at this time.</p>
                                            </div>
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
            <div v-if="showModal && selected" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm"
                 @click.self="closeModal">
                <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl border border-gray-100">
                    <!-- Modal Header -->
                    <div class="mb-5 flex items-start justify-between border-b border-gray-200 pb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Edit Booking #{{ selected.id }}</h3>
                            <p class="text-xs text-gray-500 mt-1">Update guest reservation details and preferences</p>
                        </div>
                        <button @click="closeModal" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            <i class="ti ti-x text-lg"></i>
                        </button>
                    </div>
                    
                    <form @submit.prevent="saveBooking" class="space-y-5">
                        <!-- Guest Information Section -->
                        <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="ti ti-user text-lg text-teal-600"></i>
                                <h4 class="font-semibold text-gray-800">Guest Information</h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">First Name</label>
                                    <input v-model="selected.user.fname" type="text" required
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Last Name</label>
                                    <input v-model="selected.user.lname" type="text" required
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Email Address</label>
                                <input v-model="selected.user.email" type="email" required
                                       class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
                            </div>
                        </div>

                        <!-- Room & Dates Section -->
                        <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="ti ti-door text-lg text-teal-600"></i>
                                <h4 class="font-semibold text-gray-800">Room & Stay Details</h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Room Assignment</label>
                                    <select v-model.number="selected.room_id" required
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none cursor-pointer transition">
                                        <option value="" disabled>Select a room</option>
                                        <option v-for="room in rooms" :key="room.id" :value="room.id">
                                            Room #{{ room.room_number }} - {{ room.room_type?.name || 'Standard' }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Total Guests</label>
                                    <input v-model.number="selected.guests" type="number" min="1" required
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Check-In Date</label>
                                    <input v-model="selected.start_at" type="date" required
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none cursor-pointer transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Check-Out Date</label>
                                    <input v-model="selected.end_at" type="date" required
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none cursor-pointer transition">
                                </div>
                            </div>
                        </div>

                        <!-- Guest Count Section -->
                        <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="ti ti-users text-lg text-teal-600"></i>
                                <h4 class="font-semibold text-gray-800">Guest Composition</h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Adults</label>
                                    <input v-model.number="selected.adults" type="number" min="0"
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Extra Beds</label>
                                    <select v-model.number="selected.extra_beds"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none cursor-pointer transition">
                                        <option :value="0">0</option>
                                        <option :value="1">1</option>
                                        <option :value="2">2</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Child Age Group</label>
                                    <input v-model="selected.child_age_group" type="text" placeholder="e.g. 5-12"
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
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
                                <h4 class="font-semibold text-gray-800">Payment Details</h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Payment Method</label>
                                    <select v-model="selected.payment_method"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none cursor-pointer transition">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="gcash">GCash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Total Amount</label>
                                    <input v-model.number="selected.total_amount" type="number" step="0.01" min="0" required
                                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition">
                                </div>
                            </div>
                        </div>

                        <!-- Special Requests Section -->
                        <div class="rounded-xl border border-gray-200 bg-gray-50/30 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="ti ti-message-2 text-lg text-teal-600"></i>
                                <h4 class="font-semibold text-gray-800">Special Requests</h4>
                            </div>
                            <textarea v-model="selected.message" rows="3" placeholder="Enter guest notes or special requests..."
                                      class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-900 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none resize-none transition"></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                            <button @click="closeModal" type="button"
                                    class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition shadow-sm cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" :disabled="processing"
                                    class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                <i v-if="!processing" class="ti ti-device-floppy"></i>
                                <i v-else class="ti ti-loader animate-spin"></i>
                                {{ processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>